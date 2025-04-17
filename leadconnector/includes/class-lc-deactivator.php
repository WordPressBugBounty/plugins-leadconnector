<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 * @author     Harsh Kurra
 */
class LeadConnector_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        // Aman - Pendo Event
        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        if(isset($options[lead_connector_constants\lc_options_location_id])){
            $event = new PendoEvent("WORDPRESS LC PLUGIN DEACTIVATED", [
                "locationId" => $options[lead_connector_constants\lc_options_location_id],
            ]);
            $event->send();
        }
    }

}
