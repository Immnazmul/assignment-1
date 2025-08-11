<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Export {
	public static function get_funds(): array {
		$funds = get_option( 'dfcm_funds', [] );
		return is_array( $funds ) ? $funds : [];
	}

	public static function get_fund_summary( string $fund = '', string $from = '', string $to = '' ): array {
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$where = ' WHERE 1=1'; $params = [];
		if ( $fund ) { $where .= ' AND fund_name = %s'; $params[] = $fund; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		$sql = "SELECT SUM(CASE WHEN trx_type='in' THEN amount ELSE 0 END) as total_in,
			SUM(CASE WHEN trx_type='out' THEN amount ELSE 0 END) as total_out FROM $table $where";
		$q = $params ? $wpdb->prepare( $sql, $params ) : $sql;
		$row = $wpdb->get_row( $q );
		$total_in = $row ? (float) $row->total_in : 0.0;
		$total_out = $row ? (float) $row->total_out : 0.0;
		return [ 'in' => $total_in, 'out' => $total_out, 'balance' => $total_in - $total_out ];
	}

	public static function get_transactions( string $fund = '', string $from = '', string $to = '', string $type = '' ): array {
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$where = ' WHERE 1=1'; $params = [];
		if ( $fund ) { $where .= ' AND fund_name = %s'; $params[] = $fund; }
		if ( $from ) { $where .= ' AND trx_date >= %s'; $params[] = $from; }
		if ( $to )   { $where .= ' AND trx_date <= %s'; $params[] = $to; }
		if ( $type && in_array( $type, [ 'in', 'out' ], true ) ) { $where .= ' AND trx_type = %s'; $params[] = $type; }
		$sql = "SELECT * FROM $table $where ORDER BY trx_date DESC, id DESC";
		$q = $params ? $wpdb->prepare( $sql, $params ) : $sql;
		return (array) $wpdb->get_results( $q );
	}
}