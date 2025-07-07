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
        add_action( 'add_meta_boxes', array( $this, 'add_order_meta_box' ) );
        add_action( 'admin_post_wc_utils_add_meta', array( $this, 'handle_add_meta' ) );
        add_action( 'admin_post_wc_utils_delete_meta', array( $this, 'handle_delete_meta' ) );
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

    /**
     * Add a meta box showing all order meta values.
     */
    public function add_order_meta_box() {
        add_meta_box(
            'wc-utils-order-meta',
            __( 'Order Meta Fields', 'woocommerce-utils' ),
            array( $this, 'render_order_meta_box' ),
            'shop_order',
            'normal',
            'default'
        );
    }

    /**
     * Render the order meta box.
     *
     * @param WP_Post $post Order post object.
     */
    public function render_order_meta_box( $post ) {
        $order = wc_get_order( $post->ID );
        if ( ! $order ) {
            return;
        }

        echo '<table class="widefat striped"><tbody>';
        foreach ( $order->get_meta_data() as $meta ) {
            $key   = esc_html( $meta->key );
            $value = maybe_serialize( $meta->value );
            echo '<tr><th style="width:200px">' . $key . '</th><td>' . esc_html( $value );
            if ( 0 !== strpos( $meta->key, '_' ) ) {
                $delete_url = wp_nonce_url(
                    add_query_arg(
                        array(
                            'action'   => 'wc_utils_delete_meta',
                            'order_id' => $order->get_id(),
                            'meta_key' => rawurlencode( $meta->key ),
                        ),
                        admin_url( 'admin-post.php' )
                    ),
                    'wc_utils_delete_meta_' . $order->get_id()
                );
                echo ' <a href="' . esc_url( $delete_url ) . '" class="button-link delete-meta">' . esc_html__( 'Delete', 'woocommerce-utils' ) . '</a>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h4>' . esc_html__( 'Add Meta', 'woocommerce-utils' ) . '</h4>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'wc_utils_add_meta_' . $order->get_id() );
        echo '<input type="hidden" name="action" value="wc_utils_add_meta" />';
        echo '<input type="hidden" name="order_id" value="' . esc_attr( $order->get_id() ) . '" />';
        echo '<p><label>' . esc_html__( 'Meta Key', 'woocommerce-utils' ) . '<br />';
        echo '<input type="text" name="meta_key" /></label></p>';
        echo '<p><label>' . esc_html__( 'Meta Value', 'woocommerce-utils' ) . '<br />';
        echo '<textarea name="meta_value" rows="2" cols="40"></textarea></label></p>';
        echo '<p><input type="submit" class="button" value="' . esc_attr__( 'Add Meta', 'woocommerce-utils' ) . '" /></p>';
        echo '</form>';
    }

    /**
     * Handle adding custom meta data.
     */
    public function handle_add_meta() {
        if ( empty( $_POST['order_id'] ) || ! isset( $_POST['meta_key'], $_POST['meta_value'] ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }

        $order_id = absint( $_POST['order_id'] );
        if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'woocommerce-utils' ) );
        }

        check_admin_referer( 'wc_utils_add_meta_' . $order_id );

        $key   = sanitize_text_field( wp_unslash( $_POST['meta_key'] ) );
        $value = wp_unslash( $_POST['meta_value'] );

        if ( '' !== $key && 0 !== strpos( $key, '_' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $order->add_meta_data( $key, $value, false );
                $order->save();
            }
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }

    /**
     * Handle deletion of custom meta data.
     */
    public function handle_delete_meta() {
        if ( empty( $_GET['order_id'] ) || empty( $_GET['meta_key'] ) ) {
            wp_safe_redirect( wp_get_referer() );
            exit;
        }

        $order_id = absint( $_GET['order_id'] );
        if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'woocommerce-utils' ) );
        }

        check_admin_referer( 'wc_utils_delete_meta_' . $order_id );

        $meta_key = sanitize_text_field( wp_unslash( $_GET['meta_key'] ) );

        if ( 0 !== strpos( $meta_key, '_' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $order->delete_meta_data( $meta_key );
                $order->save();
            }
        }

        wp_safe_redirect( wp_get_referer() );
        exit;
    }
}
