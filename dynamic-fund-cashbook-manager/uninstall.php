<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

delete_option( 'dfcm_funds' );
delete_option( 'dfcm_front_password' );

global $wpdb; $table = $wpdb->prefix . 'fund_transactions';
$wpdb->query( "DROP TABLE IF EXISTS $table" );