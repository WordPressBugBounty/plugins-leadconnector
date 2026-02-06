<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/includes
 * @author     Harsh Kurra
 */
class LeadConnector_Loader
{

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    private $plugin_name;
    private $version ;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct()
    {

        $this->actions = array();
        $this->filters = array();

        if (defined('LEAD_CONNECTOR_VERSION')) {
            $this->version = LEAD_CONNECTOR_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = LEAD_CONNECTOR_PLUGIN_NAME;

    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress action that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the action is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {

        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;

    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
    

        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);

        function lc_sanitize_and_escape($value, $context = 'text') {
            if (empty($value)) {
                return '';
            }
        
            $value = trim($value);
            $value = preg_replace('/\bon[a-z]+\s*=\s*["\'][^"\']*["\']/i', '', $value);
            $value = wp_strip_all_tags($value);
            $value = str_replace(['"', "'"], '', $value);
        
            switch ($context) {
                case 'url':
                    return esc_url_raw($value);
                case 'id':
                case 'attr':
                    return esc_attr($value);
                case 'text':
                default:
                    return esc_html($value);
            }
        }
   
        function lc_init_emails($phpmailer) {

            $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
            $phpmailer->isSMTP();
            $phpmailer->Host = $options[lead_connector_constants\lc_options_email_smtp_server]; ;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $options[lead_connector_constants\lc_options_email_smtp_port];
            $phpmailer->Username = $options[lead_connector_constants\lc_options_email_smtp_email];
            $phpmailer->Password = $options[lead_connector_constants\lc_options_email_smtp_password];
            $phpmailer->From = $options[lead_connector_constants\lc_options_email_smtp_email];

        }

        if(isset($options[lead_connector_constants\lc_options_email_smtp_enabled])){
            if($options[lead_connector_constants\lc_options_email_smtp_enabled] == 'true') {
                add_filter("phpmailer_init", "lc_init_emails", 10, 1);
            }
        }


        function lc_phone_number_embed($params) {

            $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
            $location_id = lc_sanitize_and_escape( $options[ lead_connector_constants\lc_options_location_id ] ?? '', 'id' );
            $pool_id = lc_sanitize_and_escape( $params['id'] ?? '', 'id' );
       
           return "
                <script src=\"https://api.leadconnectorhq.com/loc/".$location_id."\/pool/".$pool_id."/number_pool.js\"></script>
                <script src=\"https://api.leadconnectorhq.com/js/user_session.js\"></script>
           ";
        }

        add_shortcode('lc_phone_number_pool', 'lc_phone_number_embed');


        function lc_forms_embed( $params ){

            $formId = isset( $params['id'] ) ? lc_sanitize_and_escape( $params['id'], 'id' ) : '';
            $formTitle = isset( $params['title'] ) ? lc_sanitize_and_escape( $params['title'], 'text' ) : '';
            return "
                <iframe src=\"https://api.leadconnectorhq.com/widget/form/$formId\"
                    style=\"width:100%;height:100%;border:none;\" id=\"inline-".$formId."\"
                    data-layout=\"{'id':'INLINE','minimizedTitle':'','isLeftAligned':true,'isRightAligned':false,'allowMinimize':false}\"
                    data-trigger-type=\"alwaysShow\" data-trigger-value=\"\" data-activation-type=\"alwaysActivated\" data-activation-value=\"\"
                    data-deactivation-type=\"neverDeactivate\" data-deactivation-value=\"\" data-form-name=\"$formTitle\" data-height=\"600\"
                    data-layout-iframe-id=\"inline-$formId\" data-form-id=\"$formId\" title=\"$formTitle\">
                </iframe>
                <script src=\"https://link.msgsndr.com/js/form_embed.js\"></script>
            ";
        }

        add_shortcode('lc_form', 'lc_forms_embed');

        function lc_calendar_embed( $params ){

            // Accept either slug or id; prefer slug. If only id is provided, use it as slug fallback
            $slug = isset($params['slug']) ? lc_sanitize_and_escape($params['slug'], 'id') : '';
            $id   = isset($params['id'])   ? lc_sanitize_and_escape($params['id'], 'id')   : '';
            if ($slug === '' && $id !== '') {
                $slug = $id;
            }

            // Optional overrides
            $height = isset($params['height']) ? lc_sanitize_and_escape($params['height'], 'attr') : '750';
            $class  = isset($params['class'])  ? lc_sanitize_and_escape($params['class'], 'attr')  : '';
            $title  = isset($params['title'])  ? lc_sanitize_and_escape($params['title'], 'text')  : 'LeadConnector Calendar';

            if ($slug === '') {
                return '<div class="lc-calendar-error">Calendar slug or id is required.</div>';
            }

            $iframe_base = LC_CALENDAR_IFRAME_BASE_URL;
            $script_src = LC_FORM_EMBED_SCRIPT_URL;

            // Use id in booking path per reference
            $iframe_src = sprintf('%s/widget/booking/%s', $iframe_base, $slug);
            $iframe_id = $slug . '_' . time();

            return sprintf(
                '<iframe src="%s" style="width: 100%%;border:none;overflow: hidden;" scrolling="no" id="%s" class="%s" title="%s"></iframe><br><script src="%s" type="text/javascript"></script>',
                esc_url($iframe_src),
                esc_attr($iframe_id),
                esc_attr($class),
                esc_attr($title),
                esc_url($script_src)
            );
        }

        add_shortcode('lc_calendar', 'lc_calendar_embed');

        function lc_surveys_embed( $params ){

            $surveyId = isset( $params['id'] ) ? lc_sanitize_and_escape( $params['id'], 'id' ) : '';
            $surveyTitle = isset( $params['title'] ) ? lc_sanitize_and_escape( $params['title'], 'text' ) : '';
            return "
                <iframe src=\"".LC_SURVEY_WIDGET_BASE_URL."/widget/survey/$surveyId\"
                    style=\"border:none;width:100%;\" scrolling=\"no\" id=\"$surveyId\"
                    title=\"$surveyTitle\">
                </iframe>
                <script src=\"".LC_FORM_EMBED_SCRIPT_URL."\"></script>
            ";
        }

        add_shortcode('lc_survey', 'lc_surveys_embed');

        function lc_quizzes_embed( $params ){

            $quizId = isset( $params['id'] ) ? lc_sanitize_and_escape( $params['id'], 'id' ) : '';
            $quizTitle = isset( $params['title'] ) ? lc_sanitize_and_escape( $params['title'], 'text' ) : '';
            return "
                <iframe src=\"".LC_QUIZ_WIDGET_BASE_URL."/widget/quiz/$quizId\"
                    style=\"border:none;width:100%;\" scrolling=\"no\" id=\"$quizId\"
                    title=\"$quizTitle\">
                </iframe>
                <script src=\"".LC_FORM_EMBED_SCRIPT_URL."\"></script>
            ";
        }

        add_shortcode('lc_quiz', 'lc_quizzes_embed');
        
        /**
         * Reviews Widget Shortcode Handler
         * 
         * Usage: [lc_reviews_widget id='widgetId' title='Widget Title']
         * 
         * @param array $params Shortcode parameters
         * @return string HTML output for the reviews widget
         */
        function lc_reviews_widget_embed($params) {
            $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
            $location_id = lc_sanitize_and_escape( $options[ lead_connector_constants\lc_options_location_id ] ?? '', 'id' );
            $widgetId = isset($params['id']) ? lc_sanitize_and_escape($params['id'], 'id') : '';
            $widgetTitle = isset($params['title']) ? lc_sanitize_and_escape($params['title'], 'text') : 'Review Widget';
            
            // Check if current user is admin
            $isAdmin = current_user_can('administrator');
            
            // Build URL parameters
            $urlParams = "widgetId=$widgetId&source=lcwordpress";
            if ($isAdmin) {
                $urlParams .= "&isPreview=true";
            }
            
            return "<style>iframe.lc_reviews_widget{width: inherit !important;}</style><script type='text/javascript' src='" . LC_REPUTATION_WIDGET_SCRIPT_URL . "'></script><iframe class='lc_reviews_widget' src='" . LC_REPUTATION_WIDGET_BASE_URL . "/$location_id?$urlParams' frameborder='0' scrolling='no' style='min-width: 100%; width: 100%;'></iframe>";
        }

        add_shortcode('lc_reviews_widget', 'lc_reviews_widget_embed');

        function schedule_oauth_refresh_cron(){
            // Schedules the event if it's NOT already scheduled.
            if ( ! wp_next_scheduled ( 'lc_twicedaily_refresh_req_v2' ) ) {
                wp_schedule_event( time(), 'twicedaily', 'lc_twicedaily_refresh_req_v2' );
            }

        }


        if(isset($options[lead_connector_constants\lc_options_oauth_refresh_token]) && $options[lead_connector_constants\lc_options_oauth_refresh_token] !== "") {
            add_action( 'init', 'schedule_oauth_refresh_cron' );
        }

        $plugin_name =  LEAD_CONNECTOR_PLUGIN_NAME;
        $plugin_version = LEAD_CONNECTOR_VERSION;
        $oauth_refresh_schedule_hook = function() use ($plugin_name, $plugin_version) {

            $lcAdmin = new LeadConnector_Admin(
                $plugin_name,
                $plugin_version
            );
            $lcAdmin->refresh_oauth_token();

        };
        add_action( 'lc_twicedaily_refresh_req_v2', $oauth_refresh_schedule_hook );

        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

    }

}
