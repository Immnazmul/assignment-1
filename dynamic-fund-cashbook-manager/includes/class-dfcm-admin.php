<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Admin {
	private static $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		add_action( 'admin_post_dfcm_save_trx', [ $this, 'handle_save_trx' ] );
		add_action( 'admin_post_dfcm_delete_trx', [ $this, 'handle_delete_trx' ] );
		add_action( 'admin_post_dfcm_import_csv', [ $this, 'handle_import_csv' ] );
		add_action( 'admin_post_dfcm_export_csv', [ $this, 'handle_export_csv' ] );
		add_action( 'admin_post_dfcm_export_pdf', [ $this, 'handle_export_pdf' ] );
	}

	private function ensure_manage_cap() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'dfcm_manage' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'dfcm' ) );
		}
	}

	public function register_menus() {
		$cap = current_user_can( 'manage_options' ) ? 'manage_options' : 'dfcm_manage';
		if ( ! current_user_can( $cap ) ) { return; }

		add_menu_page( __( 'Fund Cashbook', 'dfcm' ), __( 'Fund Cashbook', 'dfcm' ), $cap, 'dfcm-transactions', [ $this, 'render_transactions_page' ], 'dashicons-analytics', 58 );
		add_submenu_page( 'dfcm-transactions', __( 'Transactions', 'dfcm' ), __( 'Transactions', 'dfcm' ), $cap, 'dfcm-transactions', [ $this, 'render_transactions_page' ] );
		add_submenu_page( 'dfcm-transactions', __( 'Import/Export', 'dfcm' ), __( 'Import/Export', 'dfcm' ), $cap, 'dfcm-import-export', [ $this, 'render_import_export_page' ] );
		add_submenu_page( 'dfcm-transactions', __( 'Reports', 'dfcm' ), __( 'Reports', 'dfcm' ), $cap, 'dfcm-reports', [ $this, 'render_reports_page' ] );
		add_submenu_page( 'dfcm-transactions', __( 'Settings', 'dfcm' ), __( 'Settings', 'dfcm' ), $cap, 'dfcm-settings', [ $this, 'render_settings_page' ] );
	}

	public function register_settings() {
		register_setting( 'dfcm_settings_group', 'dfcm_funds', [ 'type' => 'array', 'sanitize_callback' => [ $this, 'sanitize_funds' ] ] );
		register_setting( 'dfcm_settings_group', 'dfcm_front_password', [ 'sanitize_callback' => 'sanitize_text_field' ] );
	}

	public function sanitize_funds( $value ) {
		if ( ! is_array( $value ) ) { return []; }
		$clean = [];
		foreach ( $value as $v ) {
			$v = sanitize_text_field( $v );
			if ( $v !== '' ) { $clean[] = $v; }
		}
		return array_values( array_unique( $clean ) );
	}

	public function render_transactions_page() {
		$this->ensure_manage_cap();
		include DFCM_PLUGIN_DIR . 'templates/admin-transactions.php';
	}
	public function render_import_export_page() {
		$this->ensure_manage_cap();
		include DFCM_PLUGIN_DIR . 'templates/admin-import-export.php';
	}
	public function render_reports_page() {
		$this->ensure_manage_cap();
		include DFCM_PLUGIN_DIR . 'templates/admin-reports.php';
	}
	public function render_settings_page() {
		$this->ensure_manage_cap();
		include DFCM_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	public function handle_save_trx() {
		$this->ensure_manage_cap();
		check_admin_referer( 'dfcm_save_trx' );
		global $wpdb;
		$table = $wpdb->prefix . 'fund_transactions';
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$fund = isset( $_POST['fund_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fund_name'] ) ) : '';
		$trx_date = isset( $_POST['trx_date'] ) ? sanitize_text_field( wp_unslash( $_POST['trx_date'] ) ) : '';
		$trx_type = isset( $_POST['trx_type'] ) ? sanitize_text_field( wp_unslash( $_POST['trx_type'] ) ) : '';
		$amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
		$purpose = isset( $_POST['purpose'] ) ? wp_kses_post( wp_unslash( $_POST['purpose'] ) ) : '';
		$method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : '';
		$notes = isset( $_POST['notes'] ) ? wp_kses_post( wp_unslash( $_POST['notes'] ) ) : '';

		$now = current_time( 'mysql' );
		$data = [
			'fund_name' => $fund,
			'trx_date' => $trx_date,
			'trx_type' => $trx_type === 'out' ? 'out' : 'in',
			'amount' => $amount,
			'purpose' => $purpose,
			'payment_method' => $method,
			'notes' => $notes,
			'updated_at' => $now,
		];
		$format = [ '%s','%s','%s','%f','%s','%s','%s','%s' ];

		if ( $id ) {
			$wpdb->update( $table, $data, [ 'id' => $id ], $format, [ '%d' ] );
		} else {
			$data['created_at'] = $now;
			$format[] = '%s';
			$wpdb->insert( $table, $data, $format );
		}

		wp_redirect( add_query_arg( [ 'page' => 'dfcm-transactions', 'dfcm_notice' => 'saved' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete_trx() {
		$this->ensure_manage_cap();
		check_admin_referer( 'dfcm_delete_trx' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id ) {
			global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
			$wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		}
		wp_redirect( add_query_arg( [ 'page' => 'dfcm-transactions', 'dfcm_notice' => 'deleted' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_import_csv() {
		$this->ensure_manage_cap();
		check_admin_referer( 'dfcm_import_csv' );
		if ( empty( $_FILES['dfcm_csv']['tmp_name'] ) ) {
			wp_redirect( add_query_arg( [ 'page' => 'dfcm-import-export', 'dfcm_notice' => 'missing' ], admin_url( 'admin.php' ) ) );
			exit;
		}
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$now = current_time( 'mysql' );
		$fh = fopen( sanitize_text_field( wp_unslash( $_FILES['dfcm_csv']['tmp_name'] ) ), 'r' );
		if ( ! $fh ) { wp_redirect( add_query_arg( [ 'page' => 'dfcm-import-export', 'dfcm_notice' => 'error' ], admin_url( 'admin.php' ) ) ); exit; }
		$header = fgetcsv( $fh );
		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			list( $date, $type, $amount, $fund, $purpose, $method, $notes ) = array_pad( $row, 7, '' );
			$wpdb->insert( $table, [
				'fund_name' => sanitize_text_field( $fund ),
				'trx_date' => sanitize_text_field( $date ),
				'trx_type' => $type === 'out' ? 'out' : 'in',
				'amount' => floatval( $amount ),
				'purpose' => wp_kses_post( $purpose ),
				'payment_method' => sanitize_text_field( $method ),
				'notes' => wp_kses_post( $notes ),
				'created_at' => $now,
				'updated_at' => $now,
			], [ '%s','%s','%s','%f','%s','%s','%s','%s','%s' ] );
		}
		fclose( $fh );
		wp_redirect( add_query_arg( [ 'page' => 'dfcm-import-export', 'dfcm_notice' => 'imported' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_export_csv() {
		$this->ensure_manage_cap();
		check_admin_referer( 'dfcm_export_csv' );
		$fund = isset( $_POST['fund_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fund_name'] ) ) : '';
		$from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
		$to   = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		$filename = 'dfcm_report_' . sanitize_file_name( $fund ? $fund : 'all' ) . '_' . date( 'Ymd_His' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, [ 'Date', 'Type', 'Amount', 'Fund', 'Purpose', 'Method', 'Notes' ] );
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$where = ' WHERE 1=1'; $params = [];
		if ( $fund ) { $where .= ' AND fund_name = %s'; $params[] = $fund; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		$sql = "SELECT trx_date, trx_type, amount, fund_name, purpose, payment_method, notes FROM $table $where ORDER BY trx_date ASC";
		$q = $params ? $wpdb->prepare( $sql, $params ) : $sql;
		$rows = $wpdb->get_results( $q, ARRAY_A );
		foreach ( $rows as $r ) { fputcsv( $output, $r ); }
		exit;
	}

	public function handle_export_pdf() {
		$this->ensure_manage_cap();
		check_admin_referer( 'dfcm_export_pdf' );
		$fund = isset( $_POST['fund_name'] ) ? sanitize_text_field( wp_unslash( $_POST['fund_name'] ) ) : '';
		$from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
		$to   = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		// Fetch data
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$where = ' WHERE 1=1'; $params = [];
		if ( $fund ) { $where .= ' AND fund_name = %s'; $params[] = $fund; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		$sql = "SELECT trx_date, trx_type, amount, fund_name, purpose, payment_method FROM $table $where ORDER BY trx_date ASC";
		$q = $params ? $wpdb->prepare( $sql, $params ) : $sql;
		$rows = $wpdb->get_results( $q );

		require_once DFCM_PLUGIN_DIR . 'lib/fpdf.php';
		$pdf = new FPDF();
		$pdf->AddPage();
		$pdf->SetFont('Arial','B',14);
		$pdf->Cell(0,10, 'Fund Report: ' . ( $fund ? $fund : 'All' ), 0, 1);
		$pdf->SetFont('Arial','',10);
		$pdf->Cell(0,8, 'Date Range: ' . ( $from ? $from : '-' ) . ' to ' . ( $to ? $to : '-' ), 0, 1);
		$pdf->Ln(2);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(28,8,'Date',1); $pdf->Cell(20,8,'Type',1); $pdf->Cell(28,8,'Amount',1); $pdf->Cell(40,8,'Fund',1); $pdf->Cell(74,8,'Purpose',1); $pdf->Ln();
		$pdf->SetFont('Arial','',9);
		$total_in = 0; $total_out = 0;
		foreach ( $rows as $r ) {
			$pdf->Cell(28,7, $r->trx_date,1);
			$pdf->Cell(20,7, strtoupper($r->trx_type),1);
			$pdf->Cell(28,7, number_format( (float) $r->amount, 2 ),1);
			$pdf->Cell(40,7, $r->fund_name,1);
			$pdf->Cell(74,7, mb_substr( wp_strip_all_tags( (string) $r->purpose ), 0, 60 ),1);
			$pdf->Ln();
			if ( $r->trx_type === 'in' ) { $total_in += (float) $r->amount; } else { $total_out += (float) $r->amount; }
		}
		$pdf->Ln(2);
		$pdf->SetFont('Arial','B',10);
		$pdf->Cell(40,8, 'Total In: ' . number_format( $total_in, 2 ), 0, 1);
		$pdf->Cell(40,8, 'Total Out: ' . number_format( $total_out, 2 ), 0, 1);
		$pdf->Cell(40,8, 'Balance: ' . number_format( $total_in - $total_out, 2 ), 0, 1);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="dfcm_report_' . date('Ymd_His') . '.pdf"');
		$pdf->Output('I');
		exit;
	}
}