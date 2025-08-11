<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Cron {
	private static $instance = null;
	public static function get_instance(): self {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}
	private function __construct() {
		add_action( 'dfcm_monthly_summary', [ $this, 'generate_monthly_summary' ] );
	}

	public function generate_monthly_summary() {
		global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
		$year = (int) current_time( 'Y' );
		$month = (int) current_time( 'n' );
		$first = sprintf( '%04d-%02d-01', $year, $month );
		$last  = date( 'Y-m-t', strtotime( $first ) );
		$sql = "SELECT fund_name,
			SUM(CASE WHEN trx_type='in' THEN amount ELSE 0 END) as total_in,
			SUM(CASE WHEN trx_type='out' THEN amount ELSE 0 END) as total_out
			FROM $table WHERE trx_date BETWEEN %s AND %s GROUP BY fund_name";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $first, $last ) );
		$summary = [ 'period' => $first . ' to ' . $last, 'funds' => [] ];
		foreach ( $rows as $r ) {
			$summary['funds'][ $r->fund_name ] = [ 'in' => (float) $r->total_in, 'out' => (float) $r->total_out, 'balance' => (float) $r->total_in - (float) $r->total_out ];
		}
		update_option( 'dfcm_summary_' . date( 'Y_m' ), $summary, false );
	}
}