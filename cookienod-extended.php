<?php
/**
 * Plugin Name: CookieNod Extended - Cookie Consent & Scanner
 * Plugin URI: https://cookienod.com
 * Update URI: https://github.com/cookienod/cookienod-extended
 * Description: GDPR/CCPA compliant cookie consent manager with automated cookie scanning and consent controls.
 * Version: 1.0.1
 * Author: CookieNod Team
 * Author URI: https://cookienod.com/about
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cookienod-extended
 * Domain Path: /languages
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('COOKIENOD_EXTENDED_VERSION', '1.0.1');
define('COOKIENOD_EXTENDED_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOKIENOD_EXTENDED_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COOKIENOD_EXTENDED_PLUGIN_FILE', __FILE__);
define('COOKIENOD_API_ENDPOINT', 'https://api.cookienod.com');

// Load required files
require_once COOKIENOD_EXTENDED_PLUGIN_DIR . 'includes/class-core.php';
require_once COOKIENOD_EXTENDED_PLUGIN_DIR . 'includes/class-updater.php';

/**
 * Initialize the plugin
 *
 * @return CookieNodExtended_Core
 */
function cookienod_extended_wp() {
    return CookieNodExtended_Core::get_instance();
}

// Initialize GitHub updater
new CookieNod_Updater(__FILE__, COOKIENOD_EXTENDED_VERSION);

// Start the plugin
add_action('plugins_loaded', 'cookienod_extended_wp');

// Activation hook
register_activation_hook(__FILE__, 'cookienod_extended_activate');

/**
 * Plugin activation
 */
function cookienod_extended_activate() {

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    // Deactivate the free plugin if it's active
    if ( is_plugin_active( 'cookienod/cookienod.php' ) ) {
        deactivate_plugins( 'cookienod/cookienod.php' );
    }

    // Create database tables
    require_once COOKIENOD_EXTENDED_PLUGIN_DIR . 'includes/class-database.php';
    $database = new CookieNod_Database();
    $database->create_tables();

    // Set default options
    $default_options = array(
        'api_key'         => '',
        'block_mode'      => 'auto',
        'banner_position' => 'bottom',
        'banner_theme'    => 'light',
    );
    add_option('cookienod_wp_options', $default_options);
}
