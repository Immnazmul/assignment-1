<?php
/**
 * Plugin Name: Dynamic Fund Cashbook Manager
 * Description: Manage multiple fund categories, transactions, CSV/PDF export, and password-protected frontend reports with DataTables.
 * Version: 1.0.0
 * Author: Your Name
 * Requires PHP: 8.0
 * Text Domain: dfcm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
if ( ! defined( 'DFCM_VERSION' ) ) {
	define( 'DFCM_VERSION', '1.0.0' );
}
if ( ! defined( 'DFCM_PLUGIN_FILE' ) ) {
	define( 'DFCM_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'DFCM_PLUGIN_DIR' ) ) {
	define( 'DFCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'DFCM_PLUGIN_URL' ) ) {
	define( 'DFCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Includes
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-activator.php';
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-deactivator.php';
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-admin.php';
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-frontend.php';
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-cron.php';
require_once DFCM_PLUGIN_DIR . 'includes/class-dfcm-export.php';

register_activation_hook( __FILE__, [ 'DFCM_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'DFCM_Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function(){
	load_plugin_textdomain( 'dfcm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'init', function(){
	DFCM_Admin::get_instance();
	DFCM_Frontend::get_instance();
	DFCM_Cron::get_instance();
} );

// Admin assets (Bootstrap + DataTables via CDN)
add_action( 'admin_enqueue_scripts', function( $hook ){
	if ( strpos( $hook, 'dfcm' ) === false && strpos( $hook, 'fund-cashbook' ) === false ) { return; }
	wp_enqueue_style( 'dfcm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3' );
	wp_enqueue_style( 'dfcm-datatables', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css', [], '1.13.8' );
	wp_enqueue_style( 'dfcm-admin', DFCM_PLUGIN_URL . 'assets/css/admin.css', [], DFCM_VERSION );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'dfcm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.3.3', true );
	wp_enqueue_script( 'dfcm-datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.8', true );
	wp_enqueue_script( 'dfcm-admin', DFCM_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery','dfcm-datatables' ], DFCM_VERSION, true );
} );

// Frontend assets
add_action( 'wp_enqueue_scripts', function(){
	wp_register_style( 'dfcm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3' );
	wp_register_style( 'dfcm-datatables', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css', [], '1.13.8' );
	wp_register_style( 'dfcm-frontend', DFCM_PLUGIN_URL . 'assets/css/frontend.css', [], DFCM_VERSION );
	wp_register_script( 'dfcm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.3.3', true );
	wp_register_script( 'dfcm-datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.8', true );
	wp_register_script( 'dfcm-frontend', DFCM_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery','dfcm-datatables' ], DFCM_VERSION, true );
} );