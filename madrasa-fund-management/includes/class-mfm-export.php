<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class MFM_Export {
	public static function get_funds(): array {
		global $wpdb; $table = $wpdb->prefix . 'mfm_funds';
		return (array) $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );
	}

	public static function get_fund_summary( int $fund_id, string $from = '', string $to = '', string $type = '' ): array {
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions'; $funds = $wpdb->prefix . 'mfm_funds';
		$opening = (float) $wpdb->get_var( $wpdb->prepare( "SELECT opening_balance FROM $funds WHERE id=%d", $fund_id ) );
		$where = ' WHERE fund_id = %d'; $params = [ $fund_id ];
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		if ( $type && in_array( $type, [ 'in', 'out' ], true ) ) { $where .= ' AND trx_type = %s'; $params[] = $type; }
		$sql = "SELECT SUM(CASE WHEN trx_type='in' THEN amount ELSE 0 END) AS total_in,
			SUM(CASE WHEN trx_type='out' THEN amount ELSE 0 END) AS total_out FROM $tx $where";
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $params ) );
		$in = $row && $row->total_in ? (float) $row->total_in : 0.0;
		$out = $row && $row->total_out ? (float) $row->total_out : 0.0;
		return [ 'opening' => $opening, 'in' => $in, 'out' => $out, 'balance' => $opening + $in - $out ];
	}

	public static function get_transactions( int $fund_id, string $from = '', string $to = '', string $type = '' ): array {
		global $wpdb; $tx = $wpdb->prefix . 'mfm_transactions';
		$where = ' WHERE fund_id = %d'; $params = [ $fund_id ];
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		if ( $type && in_array( $type, [ 'in', 'out' ], true ) ) { $where .= ' AND trx_type = %s'; $params[] = $type; }
		$sql = "SELECT * FROM $tx $where ORDER BY trx_date DESC, trx_time DESC, id DESC";
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}
}