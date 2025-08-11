<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class FMP_Admin {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_post_fmp_save_member', [ $this, 'handle_save_member' ] );
		add_action( 'admin_post_fmp_delete_member', [ $this, 'handle_delete_member' ] );
		add_action( 'admin_post_fmp_save_payments', [ $this, 'handle_save_payments' ] );
		add_action( 'admin_post_fmp_export_csv', [ $this, 'handle_export_csv' ] );
		add_action( 'admin_post_fmp_import_csv', [ $this, 'handle_import_csv' ] );
		add_action( 'admin_post_fmp_send_sms', [ $this, 'handle_send_sms' ] );

		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_menus() {
		$capability_admin  = 'manage_options';
		$capability_manage = 'fmp_manage_funds';

		$cap = current_user_can( $capability_admin ) ? $capability_admin : $capability_manage;
		if ( ! current_user_can( $cap ) ) {
			return;
		}

		$hook = add_menu_page(
			__( 'Fund Manager Pro', 'fund-manager-pro' ),
			__( 'Fund Manager Pro', 'fund-manager-pro' ),
			$cap,
			'fund-manager-pro',
			[ $this, 'render_members_page' ],
			'dashicons-money-alt',
			56
		);

		add_submenu_page( 'fund-manager-pro', __( 'Members', 'fund-manager-pro' ), __( 'Members', 'fund-manager-pro' ), $cap, 'fund-manager-pro', [ $this, 'render_members_page' ] );
		add_submenu_page( 'fund-manager-pro', __( 'Payments', 'fund-manager-pro' ), __( 'Payments', 'fund-manager-pro' ), $cap, 'fmp-payments', [ $this, 'render_payments_page' ] );
		add_submenu_page( 'fund-manager-pro', __( 'Reports', 'fund-manager-pro' ), __( 'Reports', 'fund-manager-pro' ), $cap, 'fmp-reports', [ $this, 'render_reports_page' ] );
		add_submenu_page( 'fund-manager-pro', __( 'Bulk SMS', 'fund-manager-pro' ), __( 'Bulk SMS', 'fund-manager-pro' ), $cap, 'fmp-sms', [ $this, 'render_sms_page' ] );
		add_submenu_page( 'fund-manager-pro', __( 'Import/Export', 'fund-manager-pro' ), __( 'Import/Export', 'fund-manager-pro' ), $cap, 'fmp-import-export', [ $this, 'render_import_export_page' ] );
		add_submenu_page( 'fund-manager-pro', __( 'Settings', 'fund-manager-pro' ), __( 'Settings', 'fund-manager-pro' ), $cap, 'fmp-settings', [ $this, 'render_settings_page' ] );
	}

	public static function render_dashboard_widget() {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'fmp_payments';
		$members_table  = $wpdb->prefix . 'fmp_members';
		$year  = (int) current_time( 'Y' );
		$month = (int) current_time( 'n' );

		$total_collected = (float) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(amount),0) FROM $payments_table WHERE paid = 1 AND year = %d AND month = %d",
			$year, $month
		) );

		$active_members = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $members_table WHERE active = 1" );

		// pending dues up to current month for active members
		$members = $wpdb->get_results( "SELECT id, monthly_amount FROM $members_table WHERE active = 1" );
		$pending_total = 0.0;
		foreach ( $members as $m ) {
			$paid_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $payments_table WHERE member_id = %d AND year = %d AND month BETWEEN 1 AND %d AND paid = 1",
				$m->id, $year, $month
			) );
			$due_count = max( 0, $month - $paid_count );
			$pending_total += ( (float) $m->monthly_amount * $due_count );
		}

		echo '<ul class="fmp-dashboard">';
		echo '<li><strong>' . esc_html__( 'Collected This Month:', 'fund-manager-pro' ) . '</strong> ' . esc_html( number_format( $total_collected, 2 ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Total Pending Dues:', 'fund-manager-pro' ) . '</strong> ' . esc_html( number_format( $pending_total, 2 ) ) . '</li>';
		echo '<li><strong>' . esc_html__( 'Active Members:', 'fund-manager-pro' ) . '</strong> ' . esc_html( $active_members ) . '</li>';
		echo '</ul>';
	}

	public function render_members_page() {
		$this->ensure_manage_capability();
		include FMP_PLUGIN_DIR . 'templates/admin-members.php';
	}

	public function render_payments_page() {
		$this->ensure_manage_capability();
		include FMP_PLUGIN_DIR . 'templates/admin-payments.php';
	}

	public function render_reports_page() {
		$this->ensure_manage_capability();
		global $wpdb;
		$payments = $this->get_monthly_summary();
		$yearly   = $this->get_yearly_summary();
		include FMP_PLUGIN_DIR . 'templates/admin-reports.php';
	}

	public function render_sms_page() {
		$this->ensure_manage_capability();
		include FMP_PLUGIN_DIR . 'templates/admin-sms.php';
	}

	public function render_import_export_page() {
		$this->ensure_manage_capability();
		include FMP_PLUGIN_DIR . 'templates/admin-import-export.php';
	}

	public function render_settings_page() {
		$this->ensure_manage_capability();
		include FMP_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	private function ensure_manage_capability() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'fmp_manage_funds' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'fund-manager-pro' ) );
		}
	}

	public function register_settings() {
		register_setting( 'fmp_settings_group', 'fmp_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'fmp_settings_group', 'fmp_sender_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'fmp_settings_group', 'fmp_cron_day', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'fmp_settings_group', 'fmp_message_template', [ 'sanitize_callback' => 'wp_kses_post' ] );
		register_setting( 'fmp_settings_group', 'fmp_msg_paid_template', [ 'sanitize_callback' => 'wp_kses_post' ] );
	}

	// Handlers
	public function handle_save_member() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_save_member' );

		global $wpdb;
		$members_table = $wpdb->prefix . 'fmp_members';
		$payments_table = $wpdb->prefix . 'fmp_payments';

		$member_id = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$whatsapp  = isset( $_POST['whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp'] ) ) : '';
		$amount    = isset( $_POST['monthly_amount'] ) ? floatval( $_POST['monthly_amount'] ) : 0;
		$active    = isset( $_POST['active'] ) ? 1 : 0;
		$user_id   = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : null;
		$months    = isset( $_POST['months_paid'] ) && is_array( $_POST['months_paid'] ) ? array_map( 'absint', $_POST['months_paid'] ) : [];
		$year      = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) current_time( 'Y' );

		if ( empty( $name ) || empty( $phone ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'fund-manager-pro', 'fmp_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$now = current_time( 'mysql' );

		if ( $member_id > 0 ) {
			$wpdb->update( $members_table, [
				'name'           => $name,
				'phone'          => $phone,
				'whatsapp'       => $whatsapp,
				'monthly_amount' => $amount,
				'active'         => $active,
				'user_id'        => $user_id,
				'updated_at'     => $now,
			], [ 'id' => $member_id ], [ '%s','%s','%s','%f','%d','%d','%s' ], [ '%d' ] );
		} else {
			$wpdb->insert( $members_table, [
				'name'           => $name,
				'phone'          => $phone,
				'whatsapp'       => $whatsapp,
				'monthly_amount' => $amount,
				'active'         => $active,
				'user_id'        => $user_id,
				'created_at'     => $now,
				'updated_at'     => $now,
			], [ '%s','%s','%s','%f','%d','%d','%s','%s' ] );
			$member_id = (int) $wpdb->insert_id;
		}

		// Handle initial months paid for selected year
		foreach ( range( 1, 12 ) as $m ) {
			$paid    = in_array( $m, $months, true ) ? 1 : 0;
			$exists  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $payments_table WHERE member_id=%d AND year=%d AND month=%d", $member_id, $year, $m ) );
			$data    = [
				'member_id' => $member_id,
				'year'      => $year,
				'month'     => $m,
				'amount'    => $amount,
				'paid'      => $paid,
				'paid_at'   => $paid ? $now : null,
				'updated_at'=> $now,
			];
			$format  = [ '%d','%d','%d','%f','%d','%s','%s' ];
			if ( $exists ) {
				$wpdb->update( $payments_table, $data, [ 'member_id' => $member_id, 'year' => $year, 'month' => $m ], $format, [ '%d','%d','%d' ] );
			} else {
				$data['created_at'] = $now;
				$format[] = '%s';
				$wpdb->insert( $payments_table, $data, $format );
			}
		}

		wp_redirect( add_query_arg( [ 'page' => 'fund-manager-pro', 'fmp_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete_member() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_delete_member' );
		$member_id = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
		if ( $member_id ) {
			global $wpdb;
			$members_table = $wpdb->prefix . 'fmp_members';
			$payments_table = $wpdb->prefix . 'fmp_payments';
			$wpdb->delete( $payments_table, [ 'member_id' => $member_id ], [ '%d' ] );
			$wpdb->delete( $members_table, [ 'id' => $member_id ], [ '%d' ] );
		}
		wp_redirect( add_query_arg( [ 'page' => 'fund-manager-pro', 'fmp_notice' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_save_payments() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_save_payments' );

		global $wpdb;
		$payments_table = $wpdb->prefix . 'fmp_payments';
		$members_table  = $wpdb->prefix . 'fmp_members';

		$member_id = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
		$year      = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) current_time( 'Y' );
		$months    = isset( $_POST['months'] ) && is_array( $_POST['months'] ) ? array_map( 'absint', $_POST['months'] ) : [];
		$amount    = isset( $_POST['monthly_amount'] ) ? floatval( $_POST['monthly_amount'] ) : 0;
		$now       = current_time( 'mysql' );

		if ( $member_id <= 0 ) {
			wp_redirect( add_query_arg( [ 'page' => 'fmp-payments', 'fmp_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$member = $wpdb->get_row( $wpdb->prepare( "SELECT name, phone, whatsapp FROM $members_table WHERE id=%d", $member_id ) );
		$paid_template = (string) get_option( 'fmp_msg_paid_template', '' );

		foreach ( range( 1, 12 ) as $m ) {
			$paid  = in_array( $m, $months, true ) ? 1 : 0;
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT paid FROM $payments_table WHERE member_id=%d AND year=%d AND month=%d", $member_id, $year, $m ) );
			$was_paid = $exists ? (int) $exists : 0;
			$data  = [
				'member_id' => $member_id,
				'year'      => $year,
				'month'     => $m,
				'amount'    => $amount,
				'paid'      => $paid,
				'paid_at'   => $paid ? $now : null,
				'updated_at'=> $now,
			];
			$format = [ '%d','%d','%d','%f','%d','%s','%s' ];
			if ( $exists !== null ) {
				$wpdb->update( $payments_table, $data, [ 'member_id' => $member_id, 'year' => $year, 'month' => $m ], $format, [ '%d','%d','%d' ] );
			} else {
				$data['created_at'] = $now;
				$format[] = '%s';
				$wpdb->insert( $payments_table, $data, $format );
			}

			if ( $paid && ! $was_paid && $member ) {
				$label = date_i18n( 'F Y', mktime( 0, 0, 0, $m, 1, $year ) );
				$msg = FMP_SMS::render_template( $paid_template, [
					'name' => $member->name,
					'month' => $label,
					'amount' => number_format( (float) $amount, 2, '.', '' ),
					'whatsapp' => $member->whatsapp,
				] );
				FMP_SMS::send_bulk( [ $member->phone ], $msg );
			}
		}

		wp_redirect( add_query_arg( [ 'page' => 'fmp-payments', 'member_id' => $member_id, 'year' => $year, 'fmp_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_export_csv() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_export_csv' );
		$year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) current_time( 'Y' );

		$filename = 'fmp_members_' . $year . '_' . date( 'Ymd_His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ 'name', 'phone', 'whatsapp', 'monthly_amount', 'months_paid' ] );

		global $wpdb;
		$members_table  = $wpdb->prefix . 'fmp_members';
		$payments_table = $wpdb->prefix . 'fmp_payments';

		$members = $wpdb->get_results( "SELECT id, name, phone, whatsapp, monthly_amount FROM $members_table WHERE active = 1" );
		foreach ( $members as $m ) {
			$paid_months = $wpdb->get_col( $wpdb->prepare( "SELECT month FROM $payments_table WHERE member_id=%d AND year=%d AND paid=1 ORDER BY month ASC", $m->id, $year ) );
			$paid_str    = implode( '|', array_map( 'intval', $paid_months ) );
			fputcsv( $output, [ $m->name, $m->phone, $m->whatsapp, $m->monthly_amount, $paid_str ] );
		}
		exit;
	}

	public function handle_import_csv() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_import_csv' );
		if ( empty( $_FILES['fmp_csv']['tmp_name'] ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'fmp-import-export', 'fmp_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		$year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : (int) current_time( 'Y' );
		$file = fopen( sanitize_text_field( wp_unslash( $_FILES['fmp_csv']['tmp_name'] ) ), 'r' );
		if ( ! $file ) {
			wp_redirect( add_query_arg( [ 'page' => 'fmp-import-export', 'fmp_notice' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$members_table  = $wpdb->prefix . 'fmp_members';
		$payments_table = $wpdb->prefix . 'fmp_payments';
		$now = current_time( 'mysql' );

		$header = fgetcsv( $file ); // skip header
		while ( ( $row = fgetcsv( $file ) ) !== false ) {
			list( $name, $phone, $whatsapp, $monthly_amount, $months_paid ) = $row;
			$name = sanitize_text_field( $name );
			$phone = sanitize_text_field( $phone );
			$whatsapp = sanitize_text_field( $whatsapp );
			$monthly_amount = floatval( $monthly_amount );
			$paid_months = array_filter( array_map( 'absint', explode( '|', (string) $months_paid ) ) );

			$existing_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $members_table WHERE phone=%s", $phone ) );
			if ( $existing_id ) {
				$wpdb->update( $members_table, [
					'name' => $name,
					'whatsapp' => $whatsapp,
					'monthly_amount' => $monthly_amount,
					'updated_at' => $now,
				], [ 'id' => $existing_id ], [ '%s','%s','%f','%s' ], [ '%d' ] );
				$member_id = $existing_id;
			} else {
				$wpdb->insert( $members_table, [
					'name' => $name,
					'phone' => $phone,
					'whatsapp' => $whatsapp,
					'monthly_amount' => $monthly_amount,
					'active' => 1,
					'created_at' => $now,
					'updated_at' => $now,
				], [ '%s','%s','%s','%f','%d','%s','%s' ] );
				$member_id = (int) $wpdb->insert_id;
			}

			foreach ( range( 1, 12 ) as $m ) {
				$paid = in_array( $m, $paid_months, true ) ? 1 : 0;
				$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $payments_table WHERE member_id=%d AND year=%d AND month=%d", $member_id, $year, $m ) );
				$data = [
					'member_id' => $member_id,
					'year' => $year,
					'month' => $m,
					'amount' => $monthly_amount,
					'paid' => $paid,
					'paid_at' => $paid ? $now : null,
					'updated_at' => $now,
				];
				$format = [ '%d','%d','%d','%f','%d','%s','%s' ];
				if ( $exists ) {
					$wpdb->update( $payments_table, $data, [ 'member_id' => $member_id, 'year' => $year, 'month' => $m ], $format, [ '%d','%d','%d' ] );
				} else {
					$data['created_at'] = $now;
					$format[] = '%s';
					$wpdb->insert( $payments_table, $data, $format );
				}
			}
		}
		fclose( $file );

		wp_redirect( add_query_arg( [ 'page' => 'fmp-import-export', 'fmp_notice' => 'imported' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_send_sms() {
		$this->ensure_manage_capability();
		check_admin_referer( 'fmp_send_sms' );

		$message_template = isset( $_POST['message'] ) ? sanitize_text_field( wp_unslash( $_POST['message'] ) ) : '';
		$member_ids       = isset( $_POST['member_ids'] ) && is_array( $_POST['member_ids'] ) ? array_map( 'absint', $_POST['member_ids'] ) : [];
		if ( empty( $member_ids ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'fmp-sms', 'fmp_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}

		global $wpdb;
		$members_table = $wpdb->prefix . 'fmp_members';
		$placeholders  = implode( ',', array_fill( 0, count( $member_ids ), '%d' ) );
		$query         = $wpdb->prepare( "SELECT id, name, phone, whatsapp, monthly_amount FROM $members_table WHERE id IN ($placeholders)", $member_ids );
		$members       = $wpdb->get_results( $query );
		$year          = (int) current_time( 'Y' );
		$month         = (int) current_time( 'n' );

		$grouped = [];
		foreach ( $members as $m ) {
			$label = date_i18n( 'F Y', current_time( 'timestamp' ) );
			$msg   = FMP_SMS::render_template( $message_template, [
				'name'     => $m->name,
				'month'    => $label,
				'amount'   => number_format( (float) $m->monthly_amount, 2, '.', '' ),
				'whatsapp' => $m->whatsapp,
			] );
			$grouped[ $msg ][] = $m->phone;
		}

		foreach ( $grouped as $msg => $numbers ) {
			FMP_SMS::send_bulk( $numbers, $msg );
		}

		wp_redirect( add_query_arg( [ 'page' => 'fmp-sms', 'fmp_notice' => 'sent' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private function get_monthly_summary(): array {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'fmp_payments';
		$year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : (int) current_time( 'Y' );
		$data = [];
		for ( $m = 1; $m <= 12; $m++ ) {
			$collected = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $payments_table WHERE paid=1 AND year=%d AND month=%d", $year, $m ) );
			$data[] = $collected;
		}
		return [ 'year' => $year, 'collected' => $data ];
	}

	private function get_yearly_summary(): array {
		global $wpdb;
		$payments_table = $wpdb->prefix . 'fmp_payments';
		$current_year = (int) current_time( 'Y' );
		$start_year   = $current_year - 4;
		$data = [];
		for ( $y = $start_year; $y <= $current_year; $y++ ) {
			$total = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $payments_table WHERE paid=1 AND year=%d", $y ) );
			$data[] = [ 'year' => $y, 'total' => $total ];
		}
		return $data;
	}
}