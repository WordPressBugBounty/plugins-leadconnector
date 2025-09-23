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



class LC_CustomValueStringReplacableObject
{
    /**
     * @var mixed
     */
    private $content;

    /**
     * @var bool
     */
    private $isValid;

    /**
     * LC_CustomValueStringReplacableObject constructor.
     *
     * @param mixed $content
     * @param bool $isValid
     */
    public function __construct($content, $isValid)
    {
        $this->content = $content;
        $this->isValid = (bool) $isValid;
    }

    /**
     * Get the content value.
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Check if the return value is valid.
     */
    public function isValid()
    {
        return $this->isValid ?? false;
    }
}



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

        // Add filters for custom value placeholders across various content types

        // Post/Page Content
        add_filter('the_content', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('the_excerpt', array($this, 'replace_custom_value_placeholders'), 20);

        // Titles
        add_filter('the_title', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('wp_title', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('document_title_parts', array($this, 'replace_custom_value_in_title_parts'), 20);
        add_filter('pre_get_document_title', array($this, 'replace_custom_value_placeholders'), 20);

        // Widget Content
        add_filter('widget_text', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('widget_text_content', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('widget_title', array($this, 'replace_custom_value_placeholders'), 20);

        // Meta Description and SEO
        add_filter('get_the_excerpt', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('meta_description', array($this, 'replace_custom_value_placeholders'), 20);


        // Navigation
        add_filter('nav_menu_item_title', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('wp_nav_menu_items', array($this, 'replace_custom_value_placeholders'), 20);

        // Block-based Navigation
        add_filter('render_block_core/navigation', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('render_block_core/navigation-link', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('render_block_core/page-list', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('render_block', array($this, 'replace_custom_value_in_blocks'), 20, 2);

        // Comments
        add_filter('comment_text', array($this, 'replace_custom_value_placeholders'), 20);
        add_filter('comment_excerpt', array($this, 'replace_custom_value_placeholders'), 20);

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
     * Validates and sanitizes content before processing
     * 
     * @param mixed $content The content to validate
     * @return string Sanitized content
     */
    private function sanitize_content($content): LC_CustomValueStringReplacableObject
    {   
        
        // Handle null or empty content
        if ($content === null) {
            return new LC_CustomValueStringReplacableObject(null, false);
        }

        // Handle WP_Post objects
        if ($content instanceof WP_Post) {
            return new LC_CustomValueStringReplacableObject($content, false);
        }

        // Handle arrays (like from ACF fields)
        if (is_array($content)) {
            // return implode(' ', array_map('strval', array_filter($content, 'is_scalar')));
            return new LC_CustomValueStringReplacableObject($content, false);
        }

        // Handle objects that implement __toString()
        if (is_object($content) && method_exists($content, '__toString')) {
            return new LC_CustomValueStringReplacableObject($content, false);
        }

        // Handle scalar values (string, int, float, bool)
        if (is_scalar($content)) {
            return new LC_CustomValueStringReplacableObject($content, true);
        }

        // Return empty string for unsupported types
        return new LC_CustomValueStringReplacableObject('', false);
    }

    public function replace_custom_value_placeholders($content)
    {
        $sanitized_content = $this->sanitize_content($content);
        if (!$sanitized_content->isValid()) {
            return $content;
        }
        // Initialize CustomValues class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/CutomValues/CustomValues.php';
        $custom_values = new LeadConnector_CustomValues();

        try {
            // Pattern to match {{ custom_value.key }} with optional spaces
            return preg_replace_callback('/\{\{\s*custom_values\.(\w+)\s*\}\}/', function ($matches) use ($custom_values) {
                $field_key = trim($matches[1]); // Trim any extra whitespace
                if (empty($field_key)) {
                    return '';
                }

                $value = $custom_values->getValue($field_key);
                return $value !== null ? (string) $value : ''; // Ensure string conversion
            }, $content);
        } catch (Exception $e) {
            // Log error if needed
            error_log('LeadConnector: Error in replace_custom_value_placeholders: ' . $e->getMessage());
            return $content; // Return original content on error
        }
    }

    /**
     * Replace custom value placeholders in document title parts
     * 
     * @param array $title_parts The title parts array
     * @return array The processed title parts
     */
    public function replace_custom_value_in_title_parts($title_parts)
    {
        if (!is_array($title_parts)) {
            return $title_parts;
        }

        foreach ($title_parts as $key => $part) {
            if (is_string($part)) {
                $title_parts[$key] = $this->replace_custom_value_placeholders($part);
            }
        }

        return $title_parts;
    }

    /**
     * Replace custom value placeholders in block content
     *
     * @param string $block_content The block content about to be rendered
     * @param array  $block         The full block, including name and attributes
     * @return string
     */
    public function replace_custom_value_in_blocks($block_content, $block)
    {
        // Only process navigation-related blocks or blocks that might contain navigation
        $navigation_blocks = ['core/navigation', 'core/navigation-link', 'core/page-list'];

        if (empty($block['blockName'])) {
            return $block_content;
        }

        // Process if it's a navigation block or if content contains custom_values placeholder
        if (in_array($block['blockName'], $navigation_blocks) || strpos($block_content, 'custom_values.') !== false) {
            return $this->replace_custom_value_placeholders($block_content);
        }

        return $block_content;
    }

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
                if (!isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])) {
                    if ($chat_widget_settings && isset($chat_widget_settings->widgetId)) {
                        // Save Option Here
                        $options[lead_connector_constants\lc_options_selected_chat_widget_id] = $chat_widget_settings->widgetId;
                        update_option(LEAD_CONNECTOR_OPTION_NAME, $options);
                    }
                }
            }
            if (isset($options[lead_connector_constants\lc_options_text_widget_use_email_filed])) {
                $use_email_field = esc_attr($options[lead_connector_constants\lc_options_text_widget_use_email_filed]);
            }

            if (isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])) {
                $usingOldWidget = false;
            }

            if ($usingOldWidget) {
                wp_enqueue_script($this->plugin_name . ".lc_text_widget", LEAD_CONNECTOR_CDN_BASE_URL . 'loader.js', '', $this->version, false);
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lc-public.js', array('jquery'), $this->version, false);
                wp_localize_script($this->plugin_name, 'lc_public_js', array(
                    'text_widget_location_id' => $location_id,
                    'text_widget_heading' => $heading,
                    'text_widget_sub_heading' => $sub_heading,
                    'text_widget_error' => $text_widget_error,
                    'text_widget_use_email_field' => $use_email_field,
                    "text_widget_settings" => $chat_widget_settings,
                    "text_widget_cdn_base_url" => LEAD_CONNECTOR_CDN_BASE_URL,
                ));
            } else {
                if (isset($options[lead_connector_constants\lc_options_selected_chat_widget_id])) {

                    $widgetId = $options[lead_connector_constants\lc_options_selected_chat_widget_id];
                    // Add the widget code to wp_head for loading on all public pages
                    add_action('wp_head', function () use ($widgetId) {
                        // Using proper WordPress functions to output scripts safely
                        echo sprintf(
                            '<script src="%s" data-resources-url="%s" data-widget-id="%s" data-server-u-r-l="%s" data-marketplace-u-r-l="%s"></script>',
                            esc_url(LC_CHAT_WIDGET_SRC),
                            esc_url(LC_CHAT_WIDGET_RESOURCES_URL),
                            esc_attr($widgetId),
                            esc_url(LC_CHAT_WIDGET_SERVER_URL),
                            esc_url(LC_CHAT_WIDGET_MARKETPLACE_URL)
                        );
                    });
                }
            }
        }

    }

}
