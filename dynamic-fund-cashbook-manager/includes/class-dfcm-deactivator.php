<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DFCM_Deactivator {
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'dfcm_monthly_summary' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'dfcm_monthly_summary' );
		}
	}
}