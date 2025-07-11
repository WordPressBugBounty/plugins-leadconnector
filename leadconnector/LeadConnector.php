<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           LeadConnector
 *
 * @wordpress-plugin
 * Plugin Name:       LeadConnector
 * Plugin URI:        https://www.leadconnectorhq.com/wp_plugin
 * Description:       This plugin helps you to add the lead connector widgets to your website.
 * Version:           3.0.10.1
 * Author:            LeadConnector
 * Author URI:        https://www.leadconnectorhq.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       leadconnector
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    define('LEAD_CONNECTOR_VERSION', '3.0.7');
    define('LEAD_CONNECTOR_PLUGIN_NAME', 'LeadConnector');

    define('LEAD_CONNECTOR_OPTION_NAME', 'lead_connector_plugin_options');
    define('LEAD_CONNECTOR_CDN_BASE_URL', 'https://widgets.leadconnectorhq.com/');

    // Production App 
    define('LEAD_CONNECTOR_BASE_URL', 'https://rest.leadconnectorhq.com/');
    define('LEAD_CONNECTOR_DISPLAY_NAME', 'LeadConnector');

    // Staging App
    define('LEAD_CONNECTOR_OAUTH_CLIENT_ID', '6705407d183014f80462d9f1-m20kdypv');
}


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lc-activator.php
 */
function activate_lead_connector()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-lc-activator.php';
    LeadConnector_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lc-deactivator.php
 */
function deactivate_lead_connector()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-lc-deactivator.php';
    LeadConnector_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_lead_connector');
register_deactivation_hook(__FILE__, 'deactivate_lead_connector');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-lc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_lead_connector()
{

    $plugin = new LeadConnector();
    $plugin->run();
}
run_lead_connector();


// Code For Uninstall Hook And Deactivation Hook

register_uninstall_hook(__FILE__, 'lead_connector_uninstall_plugin');

function lead_connector_uninstall_plugin() {
    require_once plugin_dir_path(__FILE__) . 'includes/Pendo/PendoEvent.php';
    
    $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
    try {
        if(isset($options[lead_connector_constants\lc_options_location_id])) {
            $event = new PendoEvent("WORDPRESS LC PLUGIN UNINSTALLED", [
                "locationId" => $options[lead_connector_constants\lc_options_location_id],
            ]);
            $event->send();
        }
    } catch(Exception $e) {
        // Log error if needed
    }
    
    // Clean up options and other data if needed
}
