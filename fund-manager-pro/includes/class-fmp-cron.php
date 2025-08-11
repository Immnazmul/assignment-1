<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FMP_Cron {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'fmp_daily_cron', [ $this, 'maybe_send_unpaid_sms' ] );
	}

	public function maybe_send_unpaid_sms() {
		$day = absint( get_option( 'fmp_cron_day', 5 ) );
		if ( $day < 1 || $day > 28 ) {
			$day = 5; // safe default
		}

		$current_day = (int) current_time( 'j' );
		if ( $current_day !== $day ) {
			return;
		}

		global $wpdb;
		$members_table  = $wpdb->prefix . 'fmp_members';
		$payments_table = $wpdb->prefix . 'fmp_payments';

		$year  = (int) current_time( 'Y' );
		$month = (int) current_time( 'n' );

		// Members with unpaid dues up to previous month
		$sql = "SELECT m.id, m.name, m.phone, m.whatsapp, m.monthly_amount
			FROM $members_table m
			WHERE m.active = 1";
		$members = $wpdb->get_results( $sql );
		if ( empty( $members ) ) {
			return;
		}

		$template = get_option( 'fmp_message_template', '' );
		$to_send_numbers = [];
		$messages        = [];

		foreach ( $members as $member ) {
			$unpaid_count = $this->get_unpaid_month_count( (int) $member->id, $year, $month );
			if ( $unpaid_count > 0 ) {
				$amount_due = number_format( (float) $member->monthly_amount * $unpaid_count, 2, '.', '' );
				$month_label = date_i18n( 'F Y', current_time( 'timestamp' ) );
				$message = FMP_SMS::render_template( $template, [
					'name'     => sanitize_text_field( $member->name ),
					'month'    => $month_label,
					'amount'   => $amount_due,
					'whatsapp' => sanitize_text_field( $member->whatsapp ),
				] );
				$to_send_numbers[] = $member->phone;
				$messages[ $member->phone ] = $message;
			}
		}

		// BulkSMSBD supports one message for all numbers; but messages can vary per member.
		// To keep consistent template output, we will send same message structure; if per-member messages differ, send individually.
		$grouped = [];
		foreach ( $messages as $number => $msg ) {
			$grouped[ $msg ][] = $number;
		}

		foreach ( $grouped as $msg => $numbers ) {
			FMP_SMS::send_bulk( $numbers, $msg );
		}
	}

	private function get_unpaid_month_count( int $member_id, int $year, int $month ): int {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'fmp_payments';

		// Count months this year up to last month
		$target_month = max( 1, $month - 1 );
		$total_months = $target_month; // 1..(month-1)
		if ( $total_months <= 0 ) {
			return 0;
		}

		$paid_count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $payments_table WHERE member_id = %d AND year = %d AND month BETWEEN 1 AND %d AND paid = 1",
			$member_id, $year, $target_month
		) );

		$unpaid = max( 0, $total_months - $paid_count );
		return $unpaid;
	}
}