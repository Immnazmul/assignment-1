<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Activator {
	public static function activate() {
		self::create_table();
		self::add_roles_caps();
		self::add_default_options();
		self::schedule_cron();
	}

	private static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = $wpdb->prefix . 'fund_transactions';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			fund_name VARCHAR(191) NOT NULL,
			trx_date DATE NOT NULL,
			trx_type VARCHAR(10) NOT NULL, -- in|out
			amount DECIMAL(12,2) NOT NULL,
			purpose TEXT NULL,
			payment_method VARCHAR(100) NULL,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY fund_name (fund_name),
			KEY trx_date (trx_date),
			KEY trx_type (trx_type)
		) $charset_collate;";
		dbDelta( $sql );
	}

	private static function add_roles_caps() {
		add_role( 'dfcm_accountant', __( 'Accountant', 'dfcm' ), [
			'read' => true,
			'dfcm_manage' => true,
		] );
		$admin = get_role( 'administrator' );
		if ( $admin && ! $admin->has_cap( 'dfcm_manage' ) ) {
			$admin->add_cap( 'dfcm_manage' );
		}
	}

	private static function add_default_options() {
		add_option( 'dfcm_funds', [ 'Madrasa Fund', 'Mosque Expense Fund', 'Iftar Fund', 'Land & Construction Fund' ] );
		add_option( 'dfcm_front_password', '' );
	}

	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'dfcm_monthly_summary' ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'monthly', 'dfcm_monthly_summary' );
		}
	}
}

// Add custom monthly schedule if missing
add_filter( 'cron_schedules', function( $schedules ){
	if ( ! isset( $schedules['monthly'] ) ) {
		$schedules['monthly'] = [ 'interval' => 30 * DAY_IN_SECONDS, 'display' => __( 'Once Monthly', 'dfcm' ) ];
	}
	return $schedules;
} );