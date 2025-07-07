<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Simple GitHub based plugin updater.
 */
class WC_Utils_Updater {

    /**
     * GitHub repository raw URL.
     *
     * @var string
     */
    private $raw_url;

    /**
     * GitHub repository zip URL.
     *
     * @var string
     */
    private $zip_url;

    /**
     * Plugin file basename.
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Current version.
     *
     * @var string
     */
    private $version;

    /**
     * Constructor.
     */
    public function __construct( $repo_raw_url, $repo_zip_url, $plugin_basename, $version ) {
        $this->raw_url        = trailingslashit( $repo_raw_url );
        $this->zip_url        = $repo_zip_url;
        $this->plugin_basename = $plugin_basename;
        $this->version        = $version;

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
    }

    /**
     * Check GitHub for a newer version.
     *
     * @param object $transient Update transient.
     * @return object
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = wp_remote_get( $this->raw_url . 'woocommerce-utils.php' );

        if ( is_wp_error( $remote ) ) {
            return $transient;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $transient;
        }

        $body = wp_remote_retrieve_body( $remote );

        if ( ! preg_match( '/^\s*\*\s*Version:\s*(.*)$/mi', $body, $matches ) ) {
            return $transient;
        }

        $remote_version = trim( $matches[1] );

        if ( version_compare( $this->version, $remote_version, '>=' ) ) {
            return $transient;
        }

        $plugin             = new stdClass();
        $plugin->slug       = dirname( $this->plugin_basename );
        $plugin->new_version = $remote_version;
        $plugin->url        = $this->raw_url;
        $plugin->package    = $this->zip_url;

        $transient->response[ $this->plugin_basename ] = $plugin;

        return $transient;
    }

    /**
     * Provide plugin information for the update screen.
     *
     * @param false|object|array $result The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Installation API.
     * @param object             $args   Plugin API arguments.
     * @return object|false
     */
    public function plugins_api( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( empty( $args->slug ) || dirname( $this->plugin_basename ) !== $args->slug ) {
            return $result;
        }

        $remote = wp_remote_get( $this->raw_url . 'readme.txt' );

        if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $result;
        }

        $readme = wp_remote_retrieve_body( $remote );

        $info = new stdClass();
        $info->name    = 'WooCommerce Utils';
        $info->slug    = dirname( $this->plugin_basename );
        $info->version = $this->version;
        $info->sections = array(
            'description' => $readme,
        );
        $info->download_link = $this->zip_url;

        return $info;
    }
}

