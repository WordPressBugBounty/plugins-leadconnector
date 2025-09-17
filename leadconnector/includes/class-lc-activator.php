<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 */
class LeadConnector_Activator {

    /**
     * Initialize plugin tables and data on activation
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Get plugin options
        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        if (!is_array($options)) {
            $options = array();
        }

        $locationId = isset($options[lead_connector_constants\lc_options_location_id]) ? 
            $options[lead_connector_constants\lc_options_location_id] : '';

        // Check if this is a new installation or upgrade
        $installed_version = isset($options['version']) ? $options['version'] : '0.0.0';
        $current_version = LEAD_CONNECTOR_VERSION;


        // Store activation time for new installations
        if (!isset($options['activated_time'])) {
            $options['activated_time'] = time();
        }

        // Store the current version
        $options['version'] = $current_version;
        update_option(LEAD_CONNECTOR_OPTION_NAME, $options);

        // Run version-specific upgrades
        lc_plugin_update_check();
    }
}
