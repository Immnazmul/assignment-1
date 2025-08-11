<?php
/**
 * Plugin Name: Madrasa Fund Management
 * Description: Multi-fund cashbook manager with transactions, per-fund opening balance, import/export, reports, and a React-powered fund manager.
 * Version: 2.0.0
 * Requires PHP: 8.0
 * Author: Your Name
 * Text Domain: mfm
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constants
if ( ! defined( 'MFM_VERSION' ) ) define( 'MFM_VERSION', '2.0.0' );
if ( ! defined( 'MFM_PLUGIN_FILE' ) ) define( 'MFM_PLUGIN_FILE', __FILE__ );
if ( ! defined( 'MFM_PLUGIN_DIR' ) ) define( 'MFM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'MFM_PLUGIN_URL' ) ) define( 'MFM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Includes
require_once MFM_PLUGIN_DIR . 'includes/class-mfm-activator.php';
require_once MFM_PLUGIN_DIR . 'includes/class-mfm-admin.php';
require_once MFM_PLUGIN_DIR . 'includes/class-mfm-frontend.php';
require_once MFM_PLUGIN_DIR . 'includes/class-mfm-export.php';

register_activation_hook( __FILE__, [ 'MFM_Activator', 'activate' ] );
register_uninstall_hook( __FILE__, [ 'MFM_Activator', 'uninstall' ] );

add_action( 'plugins_loaded', function(){
	load_plugin_textdomain( 'mfm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

add_action( 'init', function(){
	MFM_Admin::get_instance();
	MFM_Frontend::get_instance();
} );

// Admin assets
add_action( 'admin_enqueue_scripts', function( $hook ){
	if ( strpos( $hook, 'mfm' ) === false ) return;
	wp_enqueue_style( 'mfm-admin', MFM_PLUGIN_URL . 'assets/css/admin.css', [], MFM_VERSION );
	wp_enqueue_script( 'jquery' );
	// Enqueue wp-element (React) for funds UI
	wp_enqueue_script( 'wp-element' );
	wp_enqueue_script( 'mfm-funds', MFM_PLUGIN_URL . 'assets/js/funds.js', [ 'wp-element', 'jquery' ], MFM_VERSION, true );
	wp_localize_script( 'mfm-funds', 'mfmFunds', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'mfm_funds' ),
	] );
} );

// Frontend assets
add_action( 'wp_enqueue_scripts', function(){
	wp_register_style( 'mfm-frontend', MFM_PLUGIN_URL . 'assets/css/frontend.css', [], MFM_VERSION );
	wp_register_script( 'jquery' );
} );