<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle admin menu and settings.
 */
class WC_Utils_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    /**
     * Register plugin menu.
     */
    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'WooCommerce Utils', 'woocommerce-utils' ),
            __( 'WC Utils', 'woocommerce-utils' ),
            'manage_woocommerce',
            'wc-utils',
            array( $this, 'render_page' )
        );
    }

    /**
     * Render settings page.
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WooCommerce Utils', 'woocommerce-utils' ); ?></h1>
            <p><?php esc_html_e( 'This plugin adds helpful utilities for WooCommerce.', 'woocommerce-utils' ); ?></p>
        </div>
        <?php
    }
}
