<?php
/**
 * Plugin Name: WooCommerce Utils
 * Description: Provides helpful utilities for WooCommerce.
 * Version: 1.0.0
 * Author: Example Author
 * Text Domain: woocommerce-utils
 * Domain Path: /languages
 * GitHub Plugin URI: https://github.com/example/woocommerce-utils
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
if ( ! defined( 'WC_UTILS_VERSION' ) ) {
    define( 'WC_UTILS_VERSION', '1.0.0' );
}
if ( ! defined( 'WC_UTILS_PATH' ) ) {
    define( 'WC_UTILS_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WC_UTILS_URL' ) ) {
    define( 'WC_UTILS_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WC_UTILS_REPO_RAW' ) ) {
    define( 'WC_UTILS_REPO_RAW', 'https://raw.githubusercontent.com/example/woocommerce-utils/main/' );
}
if ( ! defined( 'WC_UTILS_REPO_ZIP' ) ) {
    define( 'WC_UTILS_REPO_ZIP', 'https://github.com/example/woocommerce-utils/archive/refs/heads/main.zip' );
}

/**
 * Initialize the plugin.
 */
function wc_utils_init() {
    // Ensure WooCommerce is active.
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wc_utils_missing_wc_notice' );
        return;
    }

    // Include required files.
    require_once WC_UTILS_PATH . 'includes/class-wc-utils-admin.php';
    require_once WC_UTILS_PATH . 'includes/class-wc-utils-features.php';
    require_once WC_UTILS_PATH . 'includes/class-wc-utils-updater.php';

    // Initialize classes.
    new WC_Utils_Admin();
    new WC_Utils_Features();
    new WC_Utils_Updater( WC_UTILS_REPO_RAW, WC_UTILS_REPO_ZIP, plugin_basename( __FILE__ ), WC_UTILS_VERSION );
}
add_action( 'plugins_loaded', 'wc_utils_init' );

/**
 * Display an admin notice if WooCommerce is not active.
 */
function wc_utils_missing_wc_notice() {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce Utils requires WooCommerce to be active.', 'woocommerce-utils' ) . '</p></div>';
}

?>
