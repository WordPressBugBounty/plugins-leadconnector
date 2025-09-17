<?php
/**
 * Plugin update functions.
 *
 * @package LeadConnector
 * @subpackage Includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Update for version 3.0.11
 *
 * Creates the custom values table.
 */
function update_v_3_0_11(){
    // Create the custom values table
    global $wpdb;
    $table_name = $wpdb->prefix . 'lc_custom_values';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if table exists using get_var with prepare to prevent SQL injection
    $table_exists = $wpdb->get_var(
        $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
    );

    if ($table_exists !== $table_name) {
        // Add if not exists to prevent errors if table somehow exists
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            field_key varchar(255) NOT NULL,
            field_id varchar(100) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY field_key (field_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Use dbDelta which is the proper way to create/update tables in WordPress
        dbDelta($sql);

        // Log table creation for debugging purposes
        error_log(sprintf('LeadConnector: Created custom values table %s', $table_name));
    }

    
}

/**
 * Check for plugin updates and perform necessary upgrades.
 *
 * This function checks the installed version of the plugin and runs any
 * necessary update functions sequentially.
 *
 * @since 3.0.11
 */
function lc_plugin_update_check() {
    $installed_version = get_option('lead_connector_version', '0.0.0');

    if (version_compare($installed_version, LEAD_CONNECTOR_VERSION, '>=')) {
        return;
    }

    /**
     * This is stored as version number and functions to call
     */
    $updates = [
        '3.0.11' => 'update_v_3_0_11',
    ];

    foreach ($updates as $version => $function_name) {
        if (version_compare($installed_version, $version, '<')) {
            if (function_exists($function_name)) {
                call_user_func($function_name);
            }
        }
    }

    update_option('lead_connector_version', LEAD_CONNECTOR_VERSION);
}
add_action('plugins_loaded', 'lc_plugin_update_check');
