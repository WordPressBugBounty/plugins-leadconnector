<?php

class LeadConnector_CustomValues{
    private $wpdb;
    private $table_name;
    private const LC_CUSTOM_VALUES_TRANSIENT_KEY = 'lc_custom_values';
    private const LC_CUSTOM_VALUES_API_ENDPOINT = "wordpress/lc-plugin/custom-values/{lcLocationId}/values?query={query}";
    private const LC_CUSTOM_VALUE_FROM_FIELD_ID_API_ENDPOINT = "wordpress/lc-plugin/custom-values/{lcLocationId}/values/{fieldId}";
    private $AdminClass;

    public function __construct(){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'lc_custom_values';
        $this->AdminClass = new LeadConnector_Admin(LEAD_CONNECTOR_PLUGIN_NAME, LEAD_CONNECTOR_VERSION);
    }

    private function get_location_id(){
        $leadConnectorOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);
        return isset($leadConnectorOptions[lead_connector_constants\lc_options_location_id]) 
            ? $leadConnectorOptions[lead_connector_constants\lc_options_location_id] 
            : null;
    }

    public function store_custom_values($custom_values_data){
        if (is_array($custom_values_data)) {
            $this->save_to_database($custom_values_data);
            $this->update_cache();
        }
    }

    private function save_to_database($custom_values){
        if (empty($custom_values)) {
            return;
        }

        $placeholders = [];
        $values = [];

        foreach ($custom_values as $value) {
            if (isset($value['fieldKey']) && isset($value['id'])) {
                $values[] = $value['fieldKey'];
                $values[] = $value['id'];
                $placeholders[] = "(%s, %s)";
            }
        }

        if (!empty($values)) {
            // Build query with placeholders
            $placeholders_string = implode(', ', $placeholders);
            $query = "INSERT INTO {$this->table_name} (field_key, field_id) VALUES {$placeholders_string} ON DUPLICATE KEY UPDATE field_id = VALUES(field_id)";
            
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is properly prefixed and placeholders are used for values
            $this->wpdb->query($this->wpdb->prepare($query, $values));
        }
    }

    private function update_cache() {
        $results = $this->wpdb->get_results(
            "SELECT field_key, field_id FROM $this->table_name",
            ARRAY_A
        );

        $cached_values = array_column($results, 'field_id', 'field_key');
        set_transient(self::LC_CUSTOM_VALUES_TRANSIENT_KEY, $cached_values, 12 * HOUR_IN_SECONDS);
    }

    private function get_field_id_from_cache($field_key){
        $cached_values = get_transient(self::LC_CUSTOM_VALUES_TRANSIENT_KEY);
        return (is_array($cached_values) && isset($cached_values[$field_key])) ? $cached_values[$field_key] : null;
    }

    private function get_field_id_from_db($field_key){
        $value_from_db = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT field_id FROM {$this->table_name} WHERE field_key = %s",
                $field_key
            )
        );
        
        if($value_from_db){
            $this->update_cache();
            return $value_from_db;
        }
        return null;
    }

    private function get_field_id_from_field_key($field_key){
        return $this->get_field_id_from_cache($field_key) ?? $this->get_field_id_from_db($field_key);
    }

    private function get_custom_values_from_api($field_key){
        $location_id = $this->get_location_id();
        if(empty($location_id)){
            return null;
        }

        $endpoint = str_replace(
            ['{lcLocationId}', '{query}'],
            [$location_id, str_replace('_', '+', $field_key)],
            self::LC_CUSTOM_VALUES_API_ENDPOINT
        );

        $response = $this->AdminClass->lc_oauth_wp_remote_v2('get', $endpoint);
        return isset($response['body']->data) ? $response['body']->data : null;
    }

    private function get_custom_value_from_field_id_api($field_id){
        $location_id = $this->get_location_id();
        if(empty($location_id)){
            return null;
        }

        $endpoint = str_replace(
            ['{lcLocationId}', '{fieldId}'],
            [$location_id, $field_id],
            self::LC_CUSTOM_VALUE_FROM_FIELD_ID_API_ENDPOINT
        );

        $response = $this->AdminClass->lc_oauth_wp_remote_v2('get', $endpoint);
        return isset($response['body']->customValue) ? $response['body']->customValue : null;
    }

    public function getValue($field_key){
        $field_key_to_value_cache_key = lead_connector_constants\lc_field_id_value_key_base . $field_key;
        $cached_values = get_transient($field_key_to_value_cache_key);
        $getNewValues = get_transient(lead_connector_constants\lc_get_new_values_cache_key) ?? false;
        if($cached_values && !$getNewValues){
            return $cached_values;
        }

        $field_id = $this->get_field_id_from_field_key($field_key);
        if(empty($field_id)){
            $custom_values_from_api = $this->get_custom_values_from_api($field_key);
            if(isset($custom_values_from_api) && is_array($custom_values_from_api)){
                $values_array = is_object($custom_values_from_api) ? (array) $custom_values_from_api : $custom_values_from_api;
                
                if (is_array($values_array) && isset($values_array[0]) && is_object($values_array[0])) {
                    $assoc_array = array();
                    foreach ($values_array as $item) {
                        if (isset($item->fieldKey)) {
                            $assoc_array[$item->fieldKey] = (array)$item;
                            if(isset($item->id) && $field_key == $item->fieldKey){
                                $field_id = $item->id;
                            }
                        }
                    }
                    $this->save_to_database($assoc_array);
                } else {
                    $this->save_to_database($values_array);
                }
                $this->update_cache();
            }
        }

        if(!empty($field_id)){
            $custom_values_from_api = $this->get_custom_value_from_field_id_api($field_id);
            if(isset($custom_values_from_api->value) && !empty($custom_values_from_api->value)){
                set_transient($field_key_to_value_cache_key, $custom_values_from_api->value, 5 * MINUTE_IN_SECONDS);
                return $custom_values_from_api->value;
            }
        }
        
        return null;
    }
    public function remove_all_cached_custom_values_transients(){
        // Delete all transients with the lc_field_id_value_ prefix
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentionally clearing transients directly for performance
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                $this->wpdb->esc_like('_transient_lc_field_id_value_') . '%',
                $this->wpdb->esc_like('_transient_timeout_lc_field_id_value_') . '%'
            )
        );
    }
}