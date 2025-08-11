<?php
/**
 * Plugin Name:       Fund Manager Pro
 * Description:       Manage non-profit members, payments, reports, and bulk SMS notifications.
 * Version:           1.0.0
 * Author:            Your Name
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fund-manager-pro
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants
if ( ! defined( 'FMP_VERSION' ) ) {
	define( 'FMP_VERSION', '1.0.0' );
}
if ( ! defined( 'FMP_PLUGIN_FILE' ) ) {
	define( 'FMP_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'FMP_PLUGIN_DIR' ) ) {
	define( 'FMP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'FMP_PLUGIN_URL' ) ) {
	define( 'FMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Includes
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-activator.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-deactivator.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-sms.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-cron.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-admin.php';
require_once FMP_PLUGIN_DIR . 'includes/class-fmp-frontend.php';

// Activation/Deactivation Hooks
register_activation_hook( __FILE__, [ 'FMP_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'FMP_Deactivator', 'deactivate' ] );

// Plugin init
function fmp_plugins_loaded() {
	load_plugin_textdomain( 'fund-manager-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'fmp_plugins_loaded' );

function fmp_init() {
	// Initialize core components
	FMP_Cron::get_instance();
	FMP_Admin::get_instance();
	FMP_Frontend::get_instance();
}
add_action( 'init', 'fmp_init' );

// Enqueue assets for admin
function fmp_admin_assets( $hook ) {
	// Load on our pages only when possible
	if ( strpos( $hook, 'fund-manager-pro' ) === false && strpos( $hook, 'fmp' ) === false ) {
		return;
	}
	wp_enqueue_style( 'fmp-admin', FMP_PLUGIN_URL . 'assets/css/admin.css', [], FMP_VERSION );
	wp_enqueue_script( 'fmp-admin', FMP_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], FMP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'fmp_admin_assets' );

// Enqueue assets for reports
function fmp_reports_assets( $hook ) {
	if ( strpos( $hook, 'fund-manager-pro' ) === false && strpos( $hook, 'fmp' ) === false ) {
		return;
	}
	wp_register_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '4.4.1', true );
	wp_enqueue_script( 'fmp-reports', FMP_PLUGIN_URL . 'assets/js/reports.js', [ 'chartjs' ], FMP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'fmp_reports_assets' );

// Dashboard widget
function fmp_register_dashboard_widgets() {
	if ( current_user_can( 'manage_options' ) || current_user_can( 'fmp_manage_funds' ) ) {
		wp_add_dashboard_widget( 'fmp_dashboard_widget', __( 'Fund Manager Pro Overview', 'fund-manager-pro' ), [ 'FMP_Admin', 'render_dashboard_widget' ] );
	}
}
add_action( 'wp_dashboard_setup', 'fmp_register_dashboard_widgets' );