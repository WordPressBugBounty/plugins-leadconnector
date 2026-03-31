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

        // Elementor highlight functionality
        add_action('wp_head', array($this, 'inject_elementor_highlight_styles'), 999);

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

                    add_action('wp_footer', function () use ($widgetId) {
                        $config = wp_json_encode(array(
                            'src'            => LC_CHAT_WIDGET_SRC,
                            'resourcesUrl'   => LC_CHAT_WIDGET_RESOURCES_URL,
                            'widgetId'       => $widgetId,
                            'serverUrl'      => LC_CHAT_WIDGET_SERVER_URL,
                            'marketplaceUrl' => LC_CHAT_WIDGET_MARKETPLACE_URL,
                        ));

                        echo '<script data-no-optimize="1" data-no-minify="1">'
                            . '(function(){var c=' . $config . ';'
                            . 'var s=document.createElement("script");'
                            . 's.src=c.src;'
                            . 's.setAttribute("data-resources-url",c.resourcesUrl);'
                            . 's.setAttribute("data-widget-id",c.widgetId);'
                            . 's.setAttribute("data-server-u-r-l",c.serverUrl);'
                            . 's.setAttribute("data-marketplace-u-r-l",c.marketplaceUrl);'
                            . 'document.body.appendChild(s);'
                            . '})();</script>';
                    });
                }
            }
        }
    }

    /**
     * Inject Elementor highlight styles when elementor_highlight=true parameter is present
     * 
     * @since    1.0.0
     */
    public function inject_elementor_highlight_styles()
    {
        // Check if elementor_highlight parameter is present and set to 'true'
        $elementor_highlight = isset($_GET['elementor_highlight']) && $_GET['elementor_highlight'] === 'true';
        
        if (!$elementor_highlight) {
            return;
        }

        // Magic wand SVG icon (encoded for use in CSS)
        // Classic magic wand with star at the end
        $magic_wand_svg = urlencode('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23ef4444\'%3E%3Cpath d=\'M17 5h2v14h-2V5zm-4 1h2v12h-2V6zm-4 1h2v10h-2V7zm-4 1h2v8H5V8zM12 2l2 2-2 2-2-2 2-2zm0 16l2 2-2 2-2-2 2-2z\'/%3E%3C/svg%3E');

        ?>
        <style id="leadconnector-elementor-highlight">
            /* Custom Hero Classes - Red highlight */
            .hero-heading-class:hover,
            .hero-description-class:hover,
            .about-us-story-content-class:hover,
            .about-us-story-header-class:hover {
                outline: 2px dashed #84ADFF !important;
                outline-offset: 4px !important;
                position: relative !important;
                cursor: pointer !important;
            }

            /* Elementor Heading Widget - Magic wand icon on hover */
            /* .elementor-widget-heading:hover::before,
            .elementor-element.elementor-widget-heading:hover::before {
                content: '';
                position: absolute;
                top: 4px;
                right: 4px;
                width: 20px;
                height: 20px;
                background-image: url('<?php echo $magic_wand_svg; ?>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                z-index: 9999;
                pointer-events: none;
            } */

            /* Image widgets - highlight on hover  */
            .hero-image-class,
            .about-us-image-class {
                position: relative !important;
            }
            .hero-image-class:hover,
            .about-us-image-class:hover {
                cursor: pointer !important;
            }

            .hero-image-class img,
            .about-us-image-class img {
                transition: filter 0.2s ease;
            }
            .hero-image-class:hover img,
            .about-us-image-class:hover img {
                filter: blur(3px);
            }

            /* Regenerate Image overlay button - !important to override Elementor button styles */
            .lc-regenerate-image-overlay {
                position: absolute !important;
                z-index: 9999 !important;
                display: none;
                align-items: center !important;
                padding: 10px 20px !important;
                background: #fff !important;
                color: #1a1a1a !important;
                border: none !important;
                border-radius: 999px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                cursor: pointer !important;
                white-space: nowrap !important;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15) !important;
                transition: transform 0.15s ease, box-shadow 0.15s ease !important;
                line-height: 1 !important;
                text-transform: none !important;
                letter-spacing: normal !important;
                min-height: auto !important;
            }
            .lc-regenerate-image-overlay:hover {
                transform: translate(-50%, -50%) scale(1.05) !important;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
                background: #fff !important;
                color: #1a1a1a !important;
            }
            .hero-image-class:hover .lc-regenerate-image-overlay,
            .about-us-image-class:hover .lc-regenerate-image-overlay {
                display: flex;
            }
        </style>
        <script id="leadconnector-elementor-hover-events">
            (function() {
                'use strict';
                
                var highlightEnabled = true;
                
                // Throttle function to limit event frequency
                function throttle(func, limit) {
                    let inThrottle;
                    return function() {
                        const args = arguments;
                        const context = this;
                        if (!inThrottle) {
                            func.apply(context, args);
                            inThrottle = true;
                            setTimeout(() => inThrottle = false, limit);
                        }
                    };
                }
                
                // Get full text content from element, including nested elements
                function getFullText(element) {
                    // For heading widgets, get direct text content
                    return (element.textContent || element.innerText || '').trim();
                }
                
                // Send message to parent window
                function sendToParent(eventType, element, elementType) {
                    try {
                        if (window.parent && window.parent !== window) {
                            const rect = element.getBoundingClientRect();
                            const elementData = {
                                type: eventType, // 'elementor-click'
                                elementType: elementType, // 'heading', 'description', 'about-us-story-header', 'about-us-story-content'
                                selector: element.className ? element.className.split(' ').find(cls => cls.includes('hero-heading-class') || cls.includes('hero-description-class') || cls.includes('about-us-story-header-class') || cls.includes('about-us-story-content-class')) || '' : '',
                                classes: element.className || '',
                                position: {
                                    x: Math.round(rect.left),
                                    y: Math.round(rect.top),
                                    width: Math.round(rect.width),
                                    height: Math.round(rect.height)
                                },
                                text: (elementType === 'heading' || elementType === 'description' || elementType === 'about-us-story-header' || elementType === 'about-us-story-content') ? getFullText(element) : null
                            };
                            
                            window.parent.postMessage({
                                source: 'leadconnector-elementor-iframe',
                                ...elementData
                            }, '*'); // Using '*' for cross-origin - parent should validate origin
                        }
                    } catch (e) {
                        // Silently fail if postMessage fails (e.g., cross-origin restrictions)
                        console.warn('LeadConnector: Could not send message to parent', e);
                    }
                }
                
                // Send click function (no throttling needed for clicks)
                const sendClick = function(element, elementType) {
                    if (!highlightEnabled) return;
                    sendToParent('elementor-click', element, elementType);
                };
                
                // Track elements that already have listeners to prevent duplicates
                const elementsWithListeners = new WeakSet();
                
                // Helper function to determine element type from class
                function getElementType(element) {
                    if (element.classList.contains('hero-heading-class')) return 'heading';
                    if (element.classList.contains('hero-description-class')) return 'description';
                    if (element.classList.contains('about-us-story-header-class')) return 'about-us-story-header';
                    if (element.classList.contains('about-us-story-content-class')) return 'about-us-story-content';
                    return null;
                }
                
                // Initialize event listeners when DOM is ready
                function initEventListeners() {
                    // All custom classes - click events
                    const customElements = document.querySelectorAll('.hero-heading-class, .hero-description-class, .about-us-story-content-class, .about-us-story-header-class');
                    
                    customElements.forEach(function(element) {
                        // Skip if this element already has a listener
                        if (elementsWithListeners.has(element)) {
                            return;
                        }
                        
                        // Create the click handler
                        const clickHandler = function(e) {
                            // Check if the click is on this element or any child element
                            const clickedElement = e.target;
                            const targetElement = element;
                            
                            // Verify the clicked element is within our target element (handles child clicks)
                            if (!targetElement.contains(clickedElement) && clickedElement !== targetElement) {
                                return; // Click was outside our element, ignore
                            }
                            
                            // Determine element type based on class
                            const elementType = getElementType(element);
                            if (elementType) {
                                sendClick(element, elementType);
                            }
                            // Don't stop propagation to allow normal elementor behavior
                        };
                        
                        // Attach the listener with capture: true to catch events early
                        element.addEventListener('click', clickHandler, { passive: true, capture: true });
                        
                        // Store the handler reference on the element for potential cleanup
                        element._leadConnectorClickHandler = clickHandler;
                        
                        // Mark this element as having a listener
                        elementsWithListeners.add(element);
                    });
                    
                    // Image elements - inject overlay button and click handler
                    // Scoped to hero/about-us template classes only so the plugin can ship without FE/BE and
                    // without attaching to every .elementor-widget-image (backward compatible for existing sites).
                    var imageElements = document.querySelectorAll('.hero-image-class, .about-us-image-class');
                    imageElements.forEach(function(element) {
                        if (elementsWithListeners.has(element)) return;

                        var img = element.querySelector('img');
                        if (!img) { elementsWithListeners.add(element); return; }

                        if (window.getComputedStyle(element).position === 'static') {
                            element.style.position = 'relative';
                        }

                        var overlay = document.createElement('button');
                        overlay.className = 'lc-regenerate-image-overlay';
                        if (!highlightEnabled) overlay.style.display = 'none';
                        overlay.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-right:6px;vertical-align:middle;display:inline-block"><path d="m21.64 3.64-1.28-1.28a1.21 1.21 0 0 0-1.72 0L2.36 18.64a1.21 1.21 0 0 0 0 1.72l1.28 1.28a1.2 1.2 0 0 0 1.72 0L21.64 5.36a1.2 1.2 0 0 0 0-1.72"/><path d="m14 7 3 3"/><path d="M5 6v4"/><path d="M19 14v4"/><path d="M10 2v2"/><path d="M7 8H3"/><path d="M21 16h-4"/><path d="M11 3H9"/></svg>Regenerate Image';
                        element.appendChild(overlay);

                        // Position overlay centered over the actual <img>
                        function positionOverlay() {
                            var imgRect = img.getBoundingClientRect();
                            var parentRect = element.getBoundingClientRect();
                            overlay.style.left = ((imgRect.left - parentRect.left) + imgRect.width / 2) + 'px';
                            overlay.style.top = ((imgRect.top - parentRect.top) + imgRect.height / 2) + 'px';
                            overlay.style.transform = 'translate(-50%, -50%)';
                        }

                        // Reposition on every hover so it's always correct
                        element.addEventListener('mouseenter', positionOverlay);
                        // Also position after image loads
                        if (!img.complete) { img.addEventListener('load', positionOverlay); }
                        positionOverlay();


                        var elementType = element.classList.contains('hero-image-class')
                            ? 'hero-image-class'
                            : (element.classList.contains('about-us-image-class') ? 'about-us-image-class' : '');

                        overlay.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (!highlightEnabled) return;

                            var img = element.querySelector('img');
                            var imgSrc = img ? (img.getAttribute('src') || '') : '';
                            var naturalWidth = img ? img.naturalWidth : 0;
                            var naturalHeight = img ? img.naturalHeight : 0;

                            try {
                                if (window.parent && window.parent !== window) {
                                    window.parent.postMessage({
                                        source: 'leadconnector-elementor-iframe',
                                        type: 'image-click',
                                        elementType: elementType,
                                        imageUrl: imgSrc,
                                        dimensions: { width: naturalWidth, height: naturalHeight }
                                    }, '*');
                                }
                            } catch (err) {
                                console.warn('LeadConnector: Could not send image-click to parent', err);
                            }
                        }, { capture: true });

                        elementsWithListeners.add(element);
                    });

                    // Other Elementor elements (not headings or text-editor) - COMMENTED OUT: will visit later
                    /* const otherElements = document.querySelectorAll('.elementor-element:not(.elementor-widget-heading):not(.elementor-element.elementor-widget-heading):not(.elementor-widget-text-editor):not(.elementor-element.elementor-widget-text-editor):not(.elementor-section)');
                    otherElements.forEach(function(element) {
                        element.addEventListener('mouseenter', function(e) {
                            sendHover(element, 'element');
                        }, { passive: true });
                        
                        element.addEventListener('mouseleave', function(e) {
                            sendUnhover(element, 'element');
                        }, { passive: true });
                    }); */
                    
                    // Elementor sections - COMMENTED OUT: will visit later
                    /* const sections = document.querySelectorAll('.elementor-section');
                    sections.forEach(function(element) {
                        element.addEventListener('mouseenter', function(e) {
                            sendHover(element, 'section');
                        }, { passive: true });
                        
                        element.addEventListener('mouseleave', function(e) {
                            sendUnhover(element, 'section');
                        }, { passive: true });
                    }); */
                }
                
                // Wait for DOM to be ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initEventListeners);
                } else {
                    // DOM already ready, but wait a bit for Elementor elements to be rendered
                    setTimeout(initEventListeners, 500);
                }
                
                // Also try after a longer delay in case elements load dynamically
                setTimeout(initEventListeners, 1500);
                
                // Listen for messages from parent window (Vue.js app)
                function handleParentMessage(event) {
                    // Optional: Validate origin for security
                    // if (event.origin !== 'expected-origin') return;
                    
                    // Check if message is from our parent app
                    if (event.data && event.data.source === 'leadconnector-parent-app') {
                        const messageType = event.data.type;
                        const messageData = event.data.data;
                        
                        if (messageType === 'toggleHighlight') {
                            highlightEnabled = !!(messageData && messageData.enabled);
                            var styleTag = document.getElementById('leadconnector-elementor-highlight');
                            if (styleTag) styleTag.disabled = !highlightEnabled;
                            // Hide/show all regenerate overlay buttons
                            var overlays = document.querySelectorAll('.lc-regenerate-image-overlay');
                            for (var oi = 0; oi < overlays.length; oi++) {
                                // When disabled: force hide with inline display:none
                                // When enabled: clear inline style so CSS :hover rules take over
                                overlays[oi].style.display = highlightEnabled ? '' : 'none';
                            }
                        }
                        if (messageType === 'updateContent') {
                            updateHeroContent(messageData);
                        }
                        if (messageType === 'updateColors') {
                            applyPreviewColors(messageData);
                        }
                    }
                }
                
                function normalizeHex(value) {
                    if (!value || typeof value !== 'string') return '';
                    value = value.trim();
                    var m = value.match(/^#([0-9A-Fa-f]{6})$/);
                    if (m) return '#' + m[1].toLowerCase();
                    var m8 = value.match(/^#([0-9A-Fa-f]{6})([0-9A-Fa-f]{2})$/);
                    if (m8) return '#' + m8[1].toLowerCase();
                    var rgb = value.match(/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/);
                    if (rgb) {
                        var r = parseInt(rgb[1], 10); var g = parseInt(rgb[2], 10); var b = parseInt(rgb[3], 10);
                        return '#' + [r, g, b].map(function(n) { var h = n.toString(16); return h.length === 1 ? '0' + h : h; }).join('');
                    }
                    var rgba = value.match(/^rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*[\d.]+\s*\)$/);
                    if (rgba) {
                        var r = parseInt(rgba[1], 10); var g = parseInt(rgba[2], 10); var b = parseInt(rgba[3], 10);
                        return '#' + [r, g, b].map(function(n) { var h = n.toString(16); return h.length === 1 ? '0' + h : h; }).join('');
                    }
                    return value;
                }
                
                var COLOR_KEYS = ['primary', 'secondary', 'accent', 'light_neutral', 'light_neutral_text'];

                function escapeRegex(str) {
                    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                }

                function applyPreviewColors(data) {
                    var currentColors = data && data.current;
                    var newColors = data && data.new;

                    if (!currentColors || !newColors) {
                        return;
                    }

                    var replacements = [];
                    for (var k = 0; k < COLOR_KEYS.length; k++) {
                        var key = COLOR_KEYS[k];
                        var orig = normalizeHex(currentColors[key]);
                        var target = normalizeHex(newColors[key]);
                        if (orig && target && orig !== target) {
                            replacements.push({ orig: orig, target: target, key: key });
                        }
                    }

                    if (replacements.length === 0) {
                        return;
                    }

                    var updated = 0;

                    // Replace in <style> tags
                    var styleTags = document.querySelectorAll('style');
                    for (var s = 0; s < styleTags.length; s++) {
                        var text = styleTags[s].textContent;
                        var mod = false;
                        for (var r = 0; r < replacements.length; r++) {
                            var re = new RegExp(escapeRegex(replacements[r].orig), 'gi');
                            var m = text.match(re);
                            if (m) { updated += m.length; text = text.replace(re, replacements[r].target); mod = true; }
                        }
                        if (mod) styleTags[s].textContent = text;
                    }

                    // Replace inline / computed styles on elements
                    var nodes = document.body.querySelectorAll('*');
                    for (var i = 0; i < nodes.length; i++) {
                        var el = nodes[i];
                        var cs = window.getComputedStyle(el);
                        var bgHex = normalizeHex(cs.backgroundColor);
                        var fgHex = normalizeHex(cs.color);
                        for (var r = 0; r < replacements.length; r++) {
                            if (bgHex === replacements[r].orig) { el.style.backgroundColor = replacements[r].target; updated++; break; }
                        }
                        for (var r = 0; r < replacements.length; r++) {
                            if (fgHex === replacements[r].orig) { el.style.color = replacements[r].target; updated++; break; }
                        }
                    }
                }
                
                // Helper function to get target class from element type
                function getTargetClass(elementType) {
                    const classMap = {
                        'heading': 'hero-heading-class',
                        'description': 'hero-description-class',
                        'about-us-story-header': 'about-us-story-header-class',
                        'about-us-story-content': 'about-us-story-content-class'
                    };
                    return classMap[elementType] || null;
                }
                
                // Function to update element content
                function updateHeroContent(data) {
                    if (!data || !data.elementType || !data.content) {
                        console.warn('LeadConnector: Invalid updateContent data', data);
                        return;
                    }
                    
                    // Determine which class to target based on elementType
                    const targetClass = getTargetClass(data.elementType);
                    
                    if (!targetClass) {
                        console.warn('LeadConnector: Unknown elementType', data.elementType);
                        return;
                    }
                    
                    // Find all elements with the target class
                    const elements = document.querySelectorAll('.' + targetClass);
                    
                    if (elements.length === 0) {
                        console.warn('LeadConnector: No elements found with class', targetClass);
                        return;
                    }
                    
                    // Update content for each element
                    elements.forEach(function(element) {
                        // Try to find the text content container
                        // For Elementor heading widgets, look for the heading tag (h1, h2, h3, etc.)
                        let contentElement = element.querySelector('h1, h2, h3, h4, h5, h6, .elementor-heading-title');
                        
                        // For text-editor/widgets, look for the editor container
                        if (!contentElement) {
                            contentElement = element.querySelector('.elementor-widget-container, .elementor-text-editor');
                        }
                        
                        // Fallback to the element itself
                        if (!contentElement) {
                            contentElement = element;
                        }
                        
                        // Update only the text content (preserves styles and HTML structure)
                        if (data.content) {
                            // Use textContent to update only text, not HTML
                            // This preserves existing styles, classes, and structure
                            contentElement.textContent = data.content;
                        }
                    });
                    
                    // Send confirmation back to parent
                    try {
                        if (window.parent && window.parent !== window) {
                            window.parent.postMessage({
                                source: 'leadconnector-elementor-iframe',
                                type: 'content-updated',
                                elementType: data.elementType,
                                success: true
                            }, '*');
                        }
                    } catch (e) {
                        console.warn('LeadConnector: Could not send confirmation to parent', e);
                    }
                }
                
                // Add message listener
                window.addEventListener('message', handleParentMessage);
                
            })();
        </script>
        <?php
    }

}
