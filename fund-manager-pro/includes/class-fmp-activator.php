<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FMP_Activator {
	public static function activate() {
		self::create_tables();
		self::add_roles_and_caps();
		self::add_default_options();
		self::schedule_cron();
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$members_table   = $wpdb->prefix . 'fmp_members';
		$payments_table  = $wpdb->prefix . 'fmp_payments';

		$members_sql = "CREATE TABLE $members_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NULL,
			name VARCHAR(191) NOT NULL,
			phone VARCHAR(50) NOT NULL,
			whatsapp VARCHAR(50) NULL,
			monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY phone (phone)
		) $charset_collate;";

		$payments_sql = "CREATE TABLE $payments_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			member_id BIGINT(20) UNSIGNED NOT NULL,
			year SMALLINT(4) NOT NULL,
			month TINYINT(2) NOT NULL,
			amount DECIMAL(10,2) NOT NULL DEFAULT 0,
			paid TINYINT(1) NOT NULL DEFAULT 0,
			paid_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY member_month (member_id, year, month),
			KEY member_id (member_id)
		) $charset_collate;";

		dbDelta( $members_sql );
		dbDelta( $payments_sql );
	}

	private static function add_roles_and_caps() {
		// Accountant role
		add_role( 'fmp_accountant', __( 'Accountant', 'fund-manager-pro' ), [
			'read'              => true,
			'fmp_manage_funds' => true,
		] );

		// Member role can view own history
		add_role( 'fmp_member', __( 'Member', 'fund-manager-pro' ), [
			'read'            => true,
			'fmp_view_own'    => true,
		] );

		// Ensure admin has capability too
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( 'fmp_manage_funds' ) ) {
			$admin->add_cap( 'fmp_manage_funds' );
		}
	}

	private static function add_default_options() {
		add_option( 'fmp_api_key', '' );
		add_option( 'fmp_sender_id', '' );
		add_option( 'fmp_cron_day', 5 );
		add_option( 'fmp_message_template', 'Hello {name}, you have unpaid dues for {month}. Amount: {amount}. WhatsApp: {whatsapp}.' );
		add_option( 'fmp_msg_due_template', 'Dear {name}, your {month} dues amounting to {amount} are pending. Please pay at your earliest convenience.' );
		add_option( 'fmp_msg_paid_template', 'Thank you {name}! We have received your payment for {month}. Amount: {amount}.' );
	}

	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'fmp_daily_cron' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'fmp_daily_cron' );
		}
	}
}