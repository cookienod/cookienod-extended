<?php
/**
 * GitHub Updater for CookieNod Plugin
 *
 * Handles updates from GitHub instead of WordPress.org
 */

if (!defined('ABSPATH')) {
    exit;
}

class CookieNod_Updater {

    private $github_repo = 'cookienod/cookienod-extended';
    private $plugin_file;
    private $plugin_version;
    private $plugin_basename;

    public function __construct($plugin_file, $current_version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_version = $current_version;
        $this->plugin_basename = plugin_basename($plugin_file);

        // Hook into plugin update check
        add_filter('plugins_api', array($this, 'plugins_api_handler'), 20, 3);
        add_filter('pre_update_option_active_plugins', array($this, 'clear_transient'));
        add_filter('site_transient_update_plugins', array($this, 'inject_update_data'), 10, 2);
    }

    /**
     * Hook into site_transient_update_plugins to inject update data
     */
    public function inject_update_data($transient) {
        if (empty($transient->response)) {
            return $transient;
        }

        // Check if our plugin needs updating
        if (!isset($transient->response[$this->plugin_basename])) {
            $update_data = $this->get_github_update_data();

            if ($update_data && version_compare($this->plugin_version, ltrim($update_data->tag_name, 'v'), '<')) {
                $plugin_data = $this->get_plugin_data();

                $transient->response[$this->plugin_basename] = (object) array(
                    'new_version' => ltrim($update_data->tag_name, 'v'),
                    'slug'        => 'cookienod-extended',
                    'plugin'      => $this->plugin_basename,
                    'url'         => $plugin_data['PluginURI'],
                    'package'     => (isset($update_data->assets[0]->browser_download_url) && $update_data->assets[0]->browser_download_url) ? $update_data->assets[0]->browser_download_url : ($update_data->zipball_url ?? ''),
                    'icons'       => array(),
                    'banners'     => array(),
                    'banners_rtl' => array(),
                    'tested'      => $plugin_data['TestedWP'] ?? '',
                    'requires'    => $plugin_data['RequiresWP'] ?? '',
                    'requires_php'=> $plugin_data['RequiresPHP'] ?? '',
                    'sections'    => array(
                        'changelog' => $update_data->body ?? '',
                    ),
                );
            }
        }

        return $transient;
    }

    /**
     * Clear update transients when plugins are activated
     */
    public function clear_transient($plugins) {
        delete_transient('cookienod_github_update_data');
        delete_site_transient('update_plugins');
        return $plugins;
    }

    /**
     * Handle plugins_api to provide update info
     */
    public function plugins_api_handler($res, $action, $args) {
        // Only handle plugin information request for our plugin
        if ($action !== 'plugin_information') {
            return $res;
        }

        if (!isset($args->slug) || $args->slug !== 'cookienod') {
            return $res;
        }

        $update_data = $this->get_github_update_data();

        if (!$update_data) {
            return $res;
        }

        $plugin_data = $this->get_plugin_data();

        return (object) array(
            'slug'          => 'cookienod',
            'name'          => $plugin_data['Name'],
            'new_version'   => ltrim($update_data->tag_name, 'v'),
            'version'       => ltrim($update_data->tag_name, 'v'),
            'author'        => $plugin_data['Author'],
            'author_profile'=> 'https://cookienod.com',
            'download_link' => (isset($update_data->assets[0]->browser_download_url) && $update_data->assets[0]->browser_download_url) ? $update_data->assets[0]->browser_download_url : ($update_data->zipball_url ?? ''),
            'trunk'         => (isset($update_data->assets[0]->browser_download_url) && $update_data->assets[0]->browser_download_url) ? $update_data->assets[0]->browser_download_url : ($update_data->zipball_url ?? ''),
            'requires'      => $plugin_data['RequiresWP'],
            'tested'        => $plugin_data['TestedWP'],
            'requires_php'  => $plugin_data['RequiresPHP'],
            'compatibility' => (object) array(),
            'sections'       => array(
                'changelog'     => $update_data->body ?? '',
                'description'   => $plugin_data['Description'],
                'installation' => __('Install via WordPress admin or upload the zip file from GitHub releases.', 'cookienod-extended'),
            ),
        );
    }

    /**
     * Get plugin data from main plugin file
     */
    private function get_plugin_data() {
        $plugin_data = get_plugin_data($this->plugin_file);

        return array(
            'Name'          => $plugin_data['Name'] ?? '',
            'PluginURI'     => $plugin_data['PluginURI'] ?? '',
            'Version'       => $plugin_data['Version'] ?? '',
            'Description'   => $plugin_data['Description'] ?? '',
            'Author'        => $plugin_data['Author'] ?? '',
            'AuthorURI'     => $plugin_data['AuthorURI'] ?? '',
            'TestedWP'      => $plugin_data['TestedWP'] ?? '',
            'RequiresWP'    => $plugin_data['RequiresWP'] ?? '',
            'RequiresPHP'   => $plugin_data['RequiresPHP'] ?? '',
        );
    }

    /**
     * Fetch update data from GitHub API
     */
    private function get_github_update_data() {
        $cached = get_transient('cookienod_github_update_data');

        if ($cached !== false) {
            return $cached;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . $this->github_repo . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/vnd.github+json',
                ),
            )
        );

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        if (!$data || !isset($data->tag_name)) {
            return null;
        }

        set_transient('cookienod_github_update_data', $data, HOUR_IN_SECONDS);

        return $data;
    }
}