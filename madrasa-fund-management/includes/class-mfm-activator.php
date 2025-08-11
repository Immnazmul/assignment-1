<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MFM_Activator {
	public static function activate() {
		self::create_tables();
		self::add_roles();
	}

	public static function uninstall() {
		// Keep data by default; remove only transient options if any later
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$funds = $wpdb->prefix . 'mfm_funds';
		$tx = $wpdb->prefix . 'mfm_transactions';

		$sql_funds = "CREATE TABLE $funds (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			opening_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY name (name)
		) $charset;";

		$sql_tx = "CREATE TABLE $tx (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			fund_id BIGINT(20) UNSIGNED NOT NULL,
			trx_date DATE NOT NULL,
			trx_time TIME NOT NULL,
			trx_type VARCHAR(10) NOT NULL,
			amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			description TEXT NULL,
			entry_by VARCHAR(191) NULL,
			mode VARCHAR(100) NULL,
			balance DECIMAL(12,2) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY fund_id (fund_id),
			KEY trx_date (trx_date),
			KEY trx_type (trx_type),
			KEY mode (mode)
		) $charset;";

		dbDelta( $sql_funds );
		dbDelta( $sql_tx );
	}

	private static function add_roles() {
		add_role( 'mfm_accountant', __( 'Accountant', 'mfm' ), [
			'read' => true,
			'mfm_manage' => true,
		] );
	}
}