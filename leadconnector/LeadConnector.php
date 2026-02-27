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
 * Version:           3.0.23
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

// Constants That are required by plugin that are not part of the default config.php 
define('LEAD_CONNECTOR_OAUTH_CALLBACK_URL', get_option('siteurl', '__missing_siteurl__'));

/**
 * Add custom query variables to WordPress
 */
function lead_connector_add_custom_query_vars($vars) {
    $vars[] = 'code';
    $vars[] = 'lc_code';
    $vars[] = 'elementor_highlight';
    // Add any additional custom parameters here
    return $vars;
}
add_filter('query_vars', 'lead_connector_add_custom_query_vars');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lc-activator.php
 */
function activate_lead_connector()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-lc-activator.php';
    LeadConnector_Activator::activate();
    
    // Register custom query vars and flush rewrite rules
    lead_connector_add_custom_query_vars(array());
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lc-deactivator.php
 */
function deactivate_lead_connector()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-lc-deactivator.php';
    LeadConnector_Deactivator::deactivate();
    
    // Flush rewrite rules on deactivation
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_lead_connector');
register_deactivation_hook(__FILE__, 'deactivate_lead_connector');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-lc.php';
require plugin_dir_path(__FILE__) . 'includes/lc-update-functions.php';

// Add Elementor CLI
require plugin_dir_path(__FILE__) . 'includes/lc-elementor-cli.php';
require plugin_dir_path(__FILE__) . 'includes/SeoOverrides/seo-overrides.php';

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
    require_once plugin_dir_path(__FILE__) . 'includes/State/StateUpdate.php';
    
    $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
    try {
        if(isset($options[lead_connector_constants\lc_options_location_id])) {
            $event = new StateUpdate("WORDPRESS LC PLUGIN UNINSTALLED", [
                "locationId" => $options[lead_connector_constants\lc_options_location_id],
            ]);
            $event->send();
        }
    } catch(Exception $e) {
        // Log error if needed
    }
    
    // Clean up options and other data if needed
}

function custom_elementor_styles() {
    // Only load on pages that use Elementor
    if (!did_action('elementor/loaded') && !class_exists('\Elementor\Plugin')) {
        return;
    }
    
    // Check if current page is built with Elementor
    $post_id = get_the_ID();
    if (!$post_id || !get_post_meta($post_id, '_elementor_edit_mode', true)) {
        return;
    }

    // Register the CSS file
    wp_register_style(
        'elementor-overrides',
        plugin_dir_url(__FILE__) . 'assets/css/custom-elementor.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_style('elementor-overrides');
}
add_action('wp_enqueue_scripts', 'custom_elementor_styles');

/**
 * Load theme fixes CSS on all frontend pages
 * Fixes responsive header padding for block themes
 */
function lead_connector_theme_fixes() {
    $css_file = plugin_dir_path(__FILE__) . 'assets/css/theme-fixes.css';
    $version = file_exists($css_file) ? filemtime($css_file) : LEAD_CONNECTOR_VERSION;
    
    wp_enqueue_style(
        'lc-theme-fixes',
        plugin_dir_url(__FILE__) . 'assets/css/theme-fixes.css',
        array(),
        $version
    );
}
add_action('wp_enqueue_scripts', 'lead_connector_theme_fixes');

