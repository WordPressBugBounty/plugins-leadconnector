<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/public
 * @author     Harsh Kurra
 */
class LeadConnector_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lc-constants.php';
        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Plugin_Name_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Plugin_Name_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lc-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in LeadConnector_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The LeadConnector_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        $heading = "";
        $sub_heading = "";
        $enabledTextWidget = 0;
        if (isset($options[lead_connector_constants\lc_options_enable_text_widget])) {
            $enabledTextWidget = esc_attr($options[lead_connector_constants\lc_options_enable_text_widget]);
        }

        $text_widget_error = '';
        if (isset($options[lead_connector_constants\lc_options_text_widget_error])) {
            $text_widget_error = esc_attr($options[lead_connector_constants\lc_options_text_widget_error]);
        }

        if ($enabledTextWidget == 1 && isset($options[lead_connector_constants\lc_options_location_id])) {
            $location_id = $options[lead_connector_constants\lc_options_location_id];

            if (isset($options[lead_connector_constants\lc_options_text_widget_heading])) {
                $heading = esc_attr($options[lead_connector_constants\lc_options_text_widget_heading]);
            }
            if (isset($options[lead_connector_constants\lc_options_text_widget_sub_heading])) {
                $sub_heading = esc_attr($options[lead_connector_constants\lc_options_text_widget_sub_heading]);
            }

            $use_email_field = "0";
            $chat_widget_settings = null;
            $usingOldWidget = true;
            if (isset($options[lead_connector_constants\lc_options_text_widget_settings])) {
                $chat_widget_settings = json_decode($options[lead_connector_constants\lc_options_text_widget_settings]);
                if(!isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])){
                    // Save Option Here
                    $options[lead_connector_constants\lc_options_selected_chat_widget_id] = $chat_widget_settings->widgetId;
                    update_option(LEAD_CONNECTOR_OPTION_NAME, $options);
                }
            }
            if (isset($options[lead_connector_constants\lc_options_text_widget_use_email_filed])) {
                $use_email_field = esc_attr($options[lead_connector_constants\lc_options_text_widget_use_email_filed]);
            }
            
            if(isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])){
                $usingOldWidget = false;
            }

            if($usingOldWidget){
                wp_enqueue_script($this->plugin_name . ".lc_text_widget", LEAD_CONNECTOR_CDN_BASE_URL . 'loader.js', '', $this->version, false);
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lc-public.js', array('jquery'), $this->version, false);
                wp_localize_script($this->plugin_name, 'lc_public_js',array(
                    'text_widget_location_id' => $location_id,
                    'text_widget_heading' => $heading,
                    'text_widget_sub_heading' => $sub_heading,
                    'text_widget_error' => $text_widget_error,
                    'text_widget_use_email_field' => $use_email_field,
                    "text_widget_settings" => $chat_widget_settings,
                    "text_widget_cdn_base_url" => LEAD_CONNECTOR_CDN_BASE_URL,
                ));
            }
            else{
                if(isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])){
                    
                    $widgetId = $options[lead_connector_constants\lc_options_selected_chat_widget_id];
                    $widget_code = '<script 
                        src="' . LC_CHAT_WIDGET_SRC . '"
                        data-resources-url="' . LC_CHAT_WIDGET_RESOURCES_URL . '"
                        data-widget-id="' . $widgetId . '"
                        data-server-u-r-l="' . LC_CHAT_WIDGET_SERVER_URL . '"
                        data-marketplace-u-r-l="' . LC_CHAT_WIDGET_MARKETPLACE_URL . '">
                    </script>';
                    // Add the widget code to wp_head for loading on all public pages
                    add_action('wp_head', function() use ($widget_code) {
                        echo wp_kses($widget_code, array(
                            'script' => array(
                                'src' => array(),
                                'data-resources-url' => array(),
                                'data-widget-id' => array(), 
                                'data-server-u-r-l' => array(),
                                'data-marketplace-u-r-l' => array()
                            )
                        ));
                    });
                }
            }
        }

    }

}
