<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Simple GitHub based plugin updater.
 */
class WC_Utils_Updater {

    /**
     * GitHub repository slug (e.g. "owner/repo").
     *
     * @var string
     */
    private $repo;

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
    public function __construct( $repo_slug, $plugin_basename, $version ) {
        $this->repo            = $repo_slug;
        $this->plugin_basename = $plugin_basename;
        $this->version         = $version;

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

        $api_url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repo );
        $remote  = wp_remote_get( $api_url, array( 'headers' => array( 'Accept' => 'application/vnd.github.v3+json' ) ) );

        if ( is_wp_error( $remote ) ) {
            return $transient;
        }

        if ( 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $transient;
        }

        $release = json_decode( wp_remote_retrieve_body( $remote ), true );
        if ( empty( $release['tag_name'] ) || empty( $release['zipball_url'] ) ) {
            return $transient;
        }

        $remote_tag     = ltrim( $release['tag_name'], 'v' );
        $remote_version = $remote_tag;

        if ( version_compare( $this->version, $remote_version, '>=' ) ) {
            return $transient;
        }

        $plugin             = new stdClass();
        $plugin->slug       = dirname( $this->plugin_basename );
        $plugin->new_version = $remote_version;
        $plugin->url        = $release['html_url'];
        $plugin->package    = $release['zipball_url'];

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

        $api_url = sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repo );
        $release  = wp_remote_get( $api_url, array( 'headers' => array( 'Accept' => 'application/vnd.github.v3+json' ) ) );

        if ( is_wp_error( $release ) || 200 !== wp_remote_retrieve_response_code( $release ) ) {
            return $result;
        }

        $release_data = json_decode( wp_remote_retrieve_body( $release ), true );
        if ( empty( $release_data['tag_name'] ) || empty( $release_data['zipball_url'] ) ) {
            return $result;
        }

        $tag    = $release_data['tag_name'];
        $zip    = $release_data['zipball_url'];

        $remote  = wp_remote_get( sprintf( 'https://raw.githubusercontent.com/%s/%s/readme.txt', $this->repo, $tag ) );

        if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) ) {
            return $result;
        }

        $readme = wp_remote_retrieve_body( $remote );

        $info = new stdClass();
        $info->name    = 'WooCommerce Utils';
        $info->slug    = dirname( $this->plugin_basename );
        $info->version = ltrim( $tag, 'v' );
        $info->sections = array(
            'description' => $readme,
        );
        $info->download_link = $zip;

        return $info;
    }
}

