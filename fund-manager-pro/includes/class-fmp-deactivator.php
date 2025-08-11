<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FMP_Deactivator {
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'fmp_daily_cron' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'fmp_daily_cron' );
		}
	}
}