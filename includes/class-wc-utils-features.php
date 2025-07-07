<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implement various WooCommerce utilities.
 */
class WC_Utils_Features {

    public function __construct() {
        add_filter( 'wc_order_statuses', array( $this, 'register_order_status' ) );
        add_action( 'init', array( $this, 'add_order_status' ) );
    }

    /**
     * Register new order status.
     */
    public function add_order_status() {
        register_post_status( 'wc-shipped', array(
            'label'                     => 'Shipped',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'woocommerce-utils' )
        ) );
    }

    /**
     * Add to list of WC order statuses.
     */
    public function register_order_status( $order_statuses ) {
        $new_order_statuses = array();

        foreach ( $order_statuses as $key => $status ) {
            $new_order_statuses[ $key ] = $status;
            if ( 'wc-processing' === $key ) {
                $new_order_statuses['wc-shipped'] = _x( 'Shipped', 'WooCommerce order status', 'woocommerce-utils' );
            }
        }

        return $new_order_statuses;
    }
}
