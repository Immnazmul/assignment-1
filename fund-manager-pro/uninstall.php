<?php
/**
 * Uninstall script for Fund Manager Pro
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Cap check: only admins should uninstall via WP core, but double-check
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

// Delete options
delete_option( 'fmp_api_key' );
delete_option( 'fmp_sender_id' );
delete_option( 'fmp_cron_day' );
delete_option( 'fmp_message_template' );
delete_option( 'fmp_msg_due_template' );
delete_option( 'fmp_msg_paid_template' );

// Drop tables
global $wpdb;
$members_table  = $wpdb->prefix . 'fmp_members';
$payments_table = $wpdb->prefix . 'fmp_payments';
$wpdb->query( "DROP TABLE IF EXISTS $payments_table" );
$wpdb->query( "DROP TABLE IF EXISTS $members_table" );