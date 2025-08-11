<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MFM_Admin {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) self::$instance = new self();
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_post_mfm_save_trx', [ $this, 'handle_save_trx' ] );
		add_action( 'admin_post_mfm_delete_trx', [ $this, 'handle_delete_trx' ] );
		add_action( 'admin_post_mfm_import_step1', [ $this, 'handle_import_step1' ] );
		add_action( 'admin_post_mfm_import_do', [ $this, 'handle_import_do' ] );
		add_action( 'admin_post_mfm_export', [ $this, 'handle_export' ] );

		add_action( 'wp_ajax_mfm_list_funds', [ $this, 'ajax_list_funds' ] );
		add_action( 'wp_ajax_mfm_create_fund', [ $this, 'ajax_create_fund' ] );
		add_action( 'wp_ajax_mfm_update_fund', [ $this, 'ajax_update_fund' ] );
		add_action( 'wp_ajax_mfm_delete_fund', [ $this, 'ajax_delete_fund' ] );
	}

	private function can_manage(): bool { return current_user_can( 'mfm_manage' ) || current_user_can( 'manage_options' ); }
	private function can_crud(): bool { return current_user_can( 'mfm_manage' ); }

	public function register_menus() {
		$cap_view = 'read';
		add_menu_page( __( 'Madrasa Fund', 'mfm' ), __( 'Madrasa Fund', 'mfm' ), $cap_view, 'mfm-funds', [ $this, 'page_funds' ], 'dashicons-money-alt', 56 );
		add_submenu_page( 'mfm-funds', __( 'Funds', 'mfm' ), __( 'Funds', 'mfm' ), $cap_view, 'mfm-funds', [ $this, 'page_funds' ] );
		add_submenu_page( 'mfm-funds', __( 'Transactions', 'mfm' ), __( 'Transactions', 'mfm' ), $cap_view, 'mfm-transactions', [ $this, 'page_transactions' ] );
		add_submenu_page( 'mfm-funds', __( 'Import', 'mfm' ), __( 'Import', 'mfm' ), $cap_view, 'mfm-import', [ $this, 'page_import' ] );
	}

	public function page_funds() {
		if ( ! $this->can_manage() ) wp_die( __( 'No permission', 'mfm' ) );
		include MFM_PLUGIN_DIR . 'templates/admin-funds.php';
	}

	public function page_transactions() {
		include MFM_PLUGIN_DIR . 'templates/admin-transactions.php';
	}

	public function page_import() {
		include MFM_PLUGIN_DIR . 'templates/admin-import.php';
	}

	// CRUD Handlers
	public function handle_save_trx() {
		if ( ! $this->can_crud() ) wp_die( __( 'No permission', 'mfm' ) );
		check_admin_referer( 'mfm_save_trx' );
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
		$fund_id = isset( $_POST['fund_id'] ) ? absint( $_POST['fund_id'] ) : 0;
		$trx_date = sanitize_text_field( wp_unslash( $_POST['trx_date'] ?? '' ) );
		$trx_time = sanitize_text_field( wp_unslash( $_POST['trx_time'] ?? '00:00:00' ) ); if ( strlen( $trx_time ) === 5 ) $trx_time .= ':00';
		$trx_type = sanitize_text_field( wp_unslash( $_POST['trx_type'] ?? 'in' ) ); $trx_type = in_array( $trx_type, [ 'in', 'out' ], true ) ? $trx_type : 'in';
		$amount   = (float) ( $_POST['amount'] ?? 0 );
		$description = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
		$entry_by = sanitize_text_field( wp_unslash( $_POST['entry_by'] ?? '' ) );
		$mode = sanitize_text_field( wp_unslash( $_POST['mode'] ?? '' ) );
		$now = current_time( 'mysql' );
		$wpdb->insert( $tx, [
			'fund_id' => $fund_id,
			'trx_date' => $trx_date,
			'trx_time' => $trx_time,
			'trx_type' => $trx_type,
			'amount' => $amount,
			'description' => $description,
			'entry_by' => $entry_by,
			'mode' => $mode,
			'balance' => 0,
			'created_at' => $now,
			'updated_at' => $now,
		], [ '%d','%s','%s','%s','%f','%s','%s','%s','%f','%s','%s' ] );
		$this->recalc_balances_for_fund( $fund_id );
		wp_redirect( add_query_arg( [ 'page' => 'mfm-transactions', 'fund_id' => $fund_id, 'mfm_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete_trx() {
		if ( ! $this->can_crud() ) wp_die( __( 'No permission', 'mfm' ) );
		check_admin_referer( 'mfm_delete_trx' );
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
		$id = absint( $_POST['id'] ?? 0 );
		$fund_id = absint( $_POST['fund_id'] ?? 0 );
		if ( $id ) $wpdb->delete( $tx, [ 'id' => $id ], [ '%d' ] );
		$this->recalc_balances_for_fund( $fund_id );
		wp_redirect( add_query_arg( [ 'page' => 'mfm-transactions', 'fund_id' => $fund_id, 'mfm_notice' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	private function recalc_balances_for_fund( int $fund_id ) {
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions'; $funds = $wpdb->prefix . 'mfm_funds';
		$opening = (float) $wpdb->get_var( $wpdb->prepare( "SELECT opening_balance FROM $funds WHERE id=%d", $fund_id ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, trx_type, amount FROM $tx WHERE fund_id=%d ORDER BY trx_date ASC, trx_time ASC, id ASC", $fund_id ) );
		$balance = $opening;
		foreach ( $rows as $r ) {
			$balance += ( $r->trx_type === 'in' ? (float) $r->amount : - (float) $r->amount );
			$wpdb->update( $tx, [ 'balance' => $balance ], [ 'id' => $r->id ], [ '%f' ], [ '%d' ] );
		}
	}

	// Import
	public function handle_import_step1() {
		if ( ! $this->can_crud() ) wp_die( __( 'No permission', 'mfm' ) );
		check_admin_referer( 'mfm_import_step1' );
		if ( empty( $_FILES['mfm_csv']['tmp_name'] ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'mfm-import', 'mfm_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}
		$tmp = sanitize_text_field( wp_unslash( $_FILES['mfm_csv']['tmp_name'] ) );
		$fh = fopen( $tmp, 'r' );
		if ( ! $fh ) {
			wp_redirect( add_query_arg( [ 'page' => 'mfm-import', 'mfm_notice' => 'error' ], admin_url( 'admin.php' ) ) );
			exit;
		}
		$header = fgetcsv( $fh ); fclose( $fh );
		$upload = wp_upload_bits( 'mfm_import_' . time() . '.csv', null, file_get_contents( $tmp ) );
		if ( ! empty( $upload['error'] ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'mfm-import', 'mfm_notice' => 'upload_error' ], admin_url( 'admin.php' ) ) );
			exit;
		}
		$funds = MFM_Export::get_funds();
		include MFM_PLUGIN_DIR . 'templates/admin-import-map.php';
		exit;
	}

	public function handle_import_do() {
		if ( ! $this->can_crud() ) wp_die( __( 'No permission', 'mfm' ) );
		check_admin_referer( 'mfm_import_do' );
		$file = sanitize_text_field( wp_unslash( $_POST['file'] ?? '' ) );
		$map  = isset( $_POST['map'] ) && is_array( $_POST['map'] ) ? array_map( 'absint', $_POST['map'] ) : [];
		$fund_mapping = isset( $_POST['fund_mapping'] ) ? sanitize_text_field( wp_unslash( $_POST['fund_mapping'] ) ) : 'use_selected'; // use_selected | from_column
		$selected_fund = absint( $_POST['selected_fund'] ?? 0 );
		if ( ! $file || empty( $map ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'mfm-import', 'mfm_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}
		$fh = fopen( $file, 'r' ); $header = fgetcsv( $fh );
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions'; $funds = $wpdb->prefix . 'mfm_funds';
		$now = current_time( 'mysql' );
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			$fund_id = $selected_fund;
			if ( $fund_mapping === 'from_column' && isset( $map['fund'] ) ) {
				$fund_name = sanitize_text_field( (string) ( $row[ $map['fund'] ] ?? '' ) );
				if ( $fund_name !== '' ) {
					$found_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $funds WHERE name=%s", $fund_name ) );
					if ( ! $found_id ) {
						$wpdb->insert( $funds, [ 'name' => $fund_name, 'opening_balance' => 0, 'created_at' => $now, 'updated_at' => $now ], [ '%s','%f','%s','%s' ] );
						$found_id = (int) $wpdb->insert_id;
					}
					$fund_id = $found_id;
				}
			}
			$trx_date = trim( (string) ( $row[ $map['trx_date'] ] ?? '' ) );
			$trx_time = trim( (string) ( $row[ $map['trx_time'] ] ?? '00:00:00' ) ); if ( strlen( $trx_time ) === 5 ) $trx_time .= ':00';
			$trx_type = strtolower( trim( (string) ( $row[ $map['trx_type'] ] ?? 'in' ) ) ); $trx_type = in_array( $trx_type, [ 'in', 'out' ], true ) ? $trx_type : 'in';
			$amount = (float) ( $row[ $map['amount'] ] ?? 0 );
			$description = (string) ( $row[ $map['description'] ] ?? '' );
			$entry_by = (string) ( $row[ $map['entry_by'] ] ?? '' );
			$mode = (string) ( $row[ $map['mode'] ] ?? '' );
			$wpdb->insert( $tx, [
				'fund_id' => $fund_id,
				'trx_date' => $trx_date,
				'trx_time' => $trx_time,
				'trx_type' => $trx_type,
				'amount' => $amount,
				'description' => wp_kses_post( $description ),
				'entry_by' => sanitize_text_field( $entry_by ),
				'mode' => sanitize_text_field( $mode ),
				'balance' => 0,
				'created_at' => $now,
				'updated_at' => $now,
			], [ '%d','%s','%s','%s','%f','%s','%s','%s','%f','%s','%s' ] );
		}
		fclose( $fh );
		// Recalc per fund encountered
		$fund_ids = $wpdb->get_col( "SELECT DISTINCT fund_id FROM $tx" );
		foreach ( $fund_ids as $fid ) { $this->recalc_balances_for_fund( (int) $fid ); }
		wp_redirect( add_query_arg( [ 'page' => 'mfm-transactions', 'mfm_notice' => 'imported' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_export() {
		if ( ! current_user_can( 'read' ) ) wp_die( __( 'No permission', 'mfm' ) );
		check_admin_referer( 'mfm_export' );
		$fund_id = absint( $_POST['fund_id'] ?? 0 );
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		$from = sanitize_text_field( wp_unslash( $_POST['from'] ?? '' ) );
		$to   = sanitize_text_field( wp_unslash( $_POST['to'] ?? '' ) );
		$format = sanitize_text_field( wp_unslash( $_POST['format'] ?? 'csv' ) );
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
		$params = [ $fund_id ];
		$where = ' WHERE fund_id = %d';
		if ( $type && in_array( $type, [ 'in', 'out' ], true ) ) { $where .= ' AND trx_type = %s'; $params[] = $type; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		$sql = "SELECT trx_date, trx_time, trx_type, amount, description, entry_by, mode, balance FROM $tx $where ORDER BY trx_date ASC, trx_time ASC, id ASC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( $format === 'xls' ) {
			header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=mfm_report_' . date( 'Ymd_His' ) . '.xls' );
			echo "<table border='1'>";
			echo "<tr><th>Date</th><th>Time</th><th>Type</th><th>Amount</th><th>Description</th><th>Entry By</th><th>Mode</th><th>Balance</th></tr>";
			foreach ( $rows as $r ) { echo '<tr>'; foreach ( $r as $v ) echo '<td>' . esc_html( $v ) . '</td>'; echo '</tr>'; }
			echo '</table>';
			exit;
		}
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mfm_report_' . date( 'Ymd_His' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'Date','Time','Type','Amount','Description','Entry By','Mode','Balance' ] );
		foreach ( $rows as $r ) fputcsv( $out, $r );
		fclose( $out );
		exit;
	}

	// AJAX funds
	public function ajax_list_funds() {
		check_ajax_referer( 'mfm_funds', 'nonce' );
		if ( ! $this->can_manage() ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );
		global $wpdb; $funds = $wpdb->prefix . 'mfm_funds';
		$list = $wpdb->get_results( "SELECT * FROM $funds ORDER BY name ASC" );
		wp_send_json_success( [ 'funds' => $list ] );
	}
	public function ajax_create_fund() {
		check_ajax_referer( 'mfm_funds', 'nonce' );
		if ( ! $this->can_manage() ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$opening = (float) ( $_POST['opening'] ?? 0 );
		if ( $name === '' ) wp_send_json_error( [ 'message' => 'Name required' ], 400 );
		global $wpdb; $funds = $wpdb->prefix . 'mfm_funds'; $now = current_time( 'mysql' );
		$wpdb->insert( $funds, [ 'name' => $name, 'opening_balance' => $opening, 'created_at' => $now, 'updated_at' => $now ], [ '%s','%f','%s','%s' ] );
		wp_send_json_success();
	}
	public function ajax_update_fund() {
		check_ajax_referer( 'mfm_funds', 'nonce' );
		if ( ! $this->can_manage() ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );
		$id = absint( $_POST['id'] ?? 0 );
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$opening = (float) ( $_POST['opening'] ?? 0 );
		global $wpdb; $funds = $wpdb->prefix . 'mfm_funds';
		$wpdb->update( $funds, [ 'name' => $name, 'opening_balance' => $opening, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ], [ '%s','%f','%s' ], [ '%d' ] );
		// Recalc fund balances when opening changes
		$this->recalc_balances_for_fund( $id );
		wp_send_json_success();
	}
	public function ajax_delete_fund() {
		check_ajax_referer( 'mfm_funds', 'nonce' );
		if ( ! $this->can_manage() ) wp_send_json_error( [ 'message' => 'No permission' ], 403 );
		$id = absint( $_POST['id'] ?? 0 );
		global $wpdb; $funds = $wpdb->prefix . 'mfm_funds'; $tx = $wpdb->prefix . 'mfm_transactions';
		$wpdb->delete( $tx, [ 'fund_id' => $id ], [ '%d' ] );
		$wpdb->delete( $funds, [ 'id' => $id ], [ '%d' ] );
		wp_send_json_success();
	}
}