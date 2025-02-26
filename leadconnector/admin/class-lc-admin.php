<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/admin
 * @author     Harsh Kurra
 */
class LeadConnector_Admin
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/lc-constants.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/lc-admin-display.php';

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Setting validation
     * This will get called when user click on save button
     * @since    1.0.0
     * @param      object    $input   user input field's name:value map object
     */
    public function lead_connector_setting_validate($input, $forced_save = false)
    {
        // error_log("called_setting_saved");
        // error_log(print_r($input, true));


        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        $api_key = isset($input[lead_connector_constants\lc_options_api_key]) ? $input[lead_connector_constants\lc_options_api_key] : null;
        
        if(!$api_key)
            $api_key = isset($options[lead_connector_constants\lc_options_api_key]) ? $options[lead_connector_constants\lc_options_api_key] : null ;

        $old_api_key = '';
        $old_location_id = '';
        $old_text_widget_error = "0";


        // die('reached 1.1');
        if (isset($options[lead_connector_constants\lc_options_api_key])) {
            $old_api_key = esc_attr($options[lead_connector_constants\lc_options_api_key]);
        }

        if (isset($options[lead_connector_constants\lc_options_location_id])) {
            $old_location_id = esc_attr($options[lead_connector_constants\lc_options_location_id]);
        }

        if (isset($options[lead_connector_constants\lc_options_text_widget_error])) {
            $old_text_widget_error = esc_attr($options[lead_connector_constants\lc_options_text_widget_error]);
        }

        // if (!isset($api_key) || (isset($api_key) === true && $api_key === '')) {
        //     $input[lead_connector_constants\lc_options_text_widget_error] = "1";

            // die('reached 1.2');

        //     return $input;
        // }
        $input[lead_connector_constants\lc_options_text_widget_warning_text] = "";
        $api_key = trim($api_key);

        $enabled_text_widget = 0;
        if (isset($input[lead_connector_constants\lc_options_enable_text_widget])) {
            $enabled_text_widget = esc_attr($input[lead_connector_constants\lc_options_enable_text_widget]);
        }

        // die('reached 1.3');


        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            // error_log(print_r($input, true));
        }

        if ($enabled_text_widget == 1 || $forced_save) {

            // die('reached 1.4');

            if (defined('WP_DEBUG') && true === WP_DEBUG) {
                // error_log('call API here ');
            }

            $lcChatResponse = $this->lc_wp_get('get_chat_widget');


            if (@!$lcChatResponse->error) {

                $obj = $lcChatResponse;

                $input[lead_connector_constants\lc_options_location_id] = $obj->id;
                $input[lead_connector_constants\lc_options_text_widget_error] = 0;


                if (isset($obj->settings) && isset($obj->settings->textwidget) && count((array) $obj->settings->textwidget) > 0) {
                    $input[lead_connector_constants\lc_options_text_widget_settings] = wp_json_encode($obj->settings->textwidget, JSON_UNESCAPED_UNICODE);
                } else {
                    // error_log('chat widget not configured');
                    $input[lead_connector_constants\lc_options_text_widget_warning_text] = 'Please configure chat widget first!';

                    if (isset($options[lead_connector_constants\lc_options_text_widget_heading])) {
                        $input[lead_connector_constants\lc_options_text_widget_heading] = esc_attr($options[lead_connector_constants\lc_options_text_widget_heading]);
                    }
                    if (isset($options[lead_connector_constants\lc_options_text_widget_sub_heading])) {
                        $input[lead_connector_constants\lc_options_text_widget_sub_heading] = esc_attr($options[lead_connector_constants\lc_options_text_widget_sub_heading]);
                    }
                    if (isset($options[lead_connector_constants\lc_options_text_widget_use_email_filed])) {
                        $input[lead_connector_constants\lc_options_text_widget_use_email_filed] = esc_attr($options[lead_connector_constants\lc_options_text_widget_use_email_filed]);
                    }
                }
                if (isset($obj->whiteLabelUrl)) {
                    $input[lead_connector_constants\lc_options_location_white_label_url] = $obj->whiteLabelUrl;
                } else {
                    $input[lead_connector_constants\lc_options_location_white_label_url] = "";
                }
                if (defined('WP_DEBUG') && true === WP_DEBUG) {
                    // error_log(print_r($lcChatResponse, true));
                    // error_log(print_r($obj, true));
                }
            } else {
                // error_log('Please provide correct API key, error details below');
                // error_log(print_r($lcChatResponse, true));
                $input[lead_connector_constants\lc_options_text_widget_error] = "1";
                $input[lead_connector_constants\lc_options_text_widget_error_details] = print_r($lcChatResponse, true);
            }
        } else {
            $input[lead_connector_constants\lc_options_location_id] = $old_location_id;
        }

        return $input;
    }

    /**
     * Filter Post information
     * This will get on each post to get the fields required at front-end
     * @since    1.0.0
     * @param      string    $template_id   postID
     * @param      string    $location_id   Location ID
     */
    public function get_item($template_id, $location_id)
    {
        $post = get_post($template_id);
        $user = get_user_by('id', $post->post_author);
        $date = strtotime($post->post_date);
        $funnel_step_url = get_post_meta($post->ID, "lc_step_url", true);
        $slug = get_post_meta($post->ID, "lc_slug", true);
        $lc_funnel_name = get_post_meta($post->ID, "lc_funnel_name", true);
        $lc_funnel_id = get_post_meta($post->ID, "lc_funnel_id", true);
        $lc_step_id = get_post_meta($post->ID, "lc_step_id", true);
        $lc_step_name = get_post_meta($post->ID, "lc_step_name", true);
        $lc_display_method = get_post_meta($post->ID, "lc_display_method", true);
        $lc_include_tracking_code = get_post_meta($post->ID, "lc_include_tracking_code", true);
        $lc_use_site_favicon = get_post_meta($post->ID, "lc_use_site_favicon", true);

        $base_url = get_home_url() . "/";

        $data = [
            'template_id' => $post->ID,
            'title' => $post->post_title,
            'thumbnail' => get_the_post_thumbnail_url($post),
            'date' => $date,
            'human_date' => date_i18n(get_option('date_format'), $date),
            'human_modified_date' => date_i18n(get_option('date_format'), strtotime($post->post_modified)),
            'author' => $user ? $user->display_name : "undefined",
            'status' => $post->post_status,
            'url' => $base_url . $slug,
            "slug" => $slug,
            "funnel_step_url" => $funnel_step_url,
            "lc_funnel_name" => $lc_funnel_name,
            "lc_funnel_id" => $lc_funnel_id,
            "lc_step_id" => $lc_step_id,
            "lc_step_name" => $lc_step_name,
            "location_id" => $location_id,
            "lc_display_method" => $lc_display_method,
            "lc_include_tracking_code" => $lc_include_tracking_code,
            "lc_use_site_favicon" => $lc_use_site_favicon,

        ];

        return $data;
    }

    public function lc_disconnect(){
        $newOptions[lead_connector_constants\lc_options_oauth_access_token] = "";
        $newOptions[lead_connector_constants\lc_options_oauth_refresh_token] = "";
        $newOptions[lead_connector_constants\lc_options_location_id] = "lc_disconnect";

        $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $newOptions);
        return array(
            "-success" => true,
            "options_saved" => $option_saved,
        );
    }

    /**
     * Public API handler
     * rest API call from front-end will end up here, where endpoint query param will the action or remote API path.
     * @since    1.0.0
     */
    public function lc_public_api_proxy($request)
    {

        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        $params = $request->get_query_params();
        $endpoint = $params[lead_connector_constants\lc_rest_api_endpoint_param];
        $directEndpoint = $params[lead_connector_constants\lc_rest_api_direct_endpoint_param];

        $lc_access_token = "";
        $api_key = "";
        $authorized_location_id = "";

        if (isset($options[lead_connector_constants\lc_options_location_id])) {
            $authorized_location_id = $options[lead_connector_constants\lc_options_location_id];
            $authorized_location_id = trim($authorized_location_id);
        }

        if (isset($options[lead_connector_constants\lc_options_api_key])) {
            $api_key = $options[lead_connector_constants\lc_options_api_key];
            $api_key = trim($api_key);
        }

        if (isset($options[lead_connector_constants\lc_options_oauth_access_token])) {
            $lc_access_token = $options[lead_connector_constants\lc_options_oauth_access_token];
            $lc_access_token = $this->lc_decrypt_string($lc_access_token);
        }

        if($endpoint == 'wp_regenerate_token'){
            return $this->lc_oauth_regenerate_token();
        }

        if($endpoint == "wp_validate_auth_state"){


            // This should for both api key or oauth 
            $has_connected_auth = false ;
            $connection_method = '';

            $funnelFetchResponse = array('error' => true); ;

            if($lc_access_token != '' || $api_key != ''){
                $has_connected_auth = true;
            }
             
            if($has_connected_auth){
                if($lc_access_token != ''){
                    $connection_method = 'oauth';
                }else{
                    $connection_method = 'api_key';
                }

                $funnelFetchResponse =  $this->lc_wp_get('funnels_get_list');

            }
            $funnelFetchResponse = (array) $funnelFetchResponse ;

            $is_connection_status_active = false;
            if(!array_key_exists("error", $funnelFetchResponse ))
            {
                $is_connection_status_active = true;
            }

            return array(
                "is_connection_status_active" => $is_connection_status_active,
                "has_connected_auth" => $has_connected_auth,
                "connection_method" => $connection_method ,
                "access_token" => $lc_access_token, 
                "api_key" => $api_key,
                "authorized_location_id" => $authorized_location_id,
                "options" => $options,
                "funnelFetchResponse" => $funnelFetchResponse
            );
        }

        if ($endpoint == 'wp_disconnect') {
            $newOptions[lead_connector_constants\lc_options_oauth_access_token] = "";
            $newOptions[lead_connector_constants\lc_options_oauth_refresh_token] = "";
            $newOptions[lead_connector_constants\lc_options_location_id] = "";

            $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $newOptions);
        
            return array(
                "success" => true,
                "options_saved" => $option_saved,
            );
        }

        if ($endpoint == 'wp_validate_oauth') {

            $body = json_decode($params['data']);
            $token_response = $this->lc_perform_oauth_request("validate", $body->code);
            $previous_options = get_option(LEAD_CONNECTOR_OPTION_NAME);
            if(!is_array($previous_options))
                $previous_options = array();

            $option_saved = false;

                if (!property_exists($token_response, "error")) {
                    if ($token_response->userType == 'Location') {

                        $previous_options[lead_connector_constants\lc_options_oauth_access_token] = $this->lc_encrypt_string($token_response->access_token);
                        $previous_options[lead_connector_constants\lc_options_oauth_refresh_token] = $this->lc_encrypt_string($token_response->refresh_token);
                        $previous_options[lead_connector_constants\lc_options_location_id] = $token_response->locationId;
                    }

                    $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $previous_options);
                }
            

            return array(
                "previous_options" => $previous_options,
                "options_saved" => $option_saved,
                "response" => $token_response,
            );
        }

        if ($request->get_method() == 'POST') {
            // error_log("current_user");
            // error_log(print_r($request, true));
            // error_log(print_r($request->get_body(), true));

            if ($endpoint == "wp_save_options") {
                $body = json_decode($request->get_body());

                if (isset($body->enable_text_widget)) {
                    $options[lead_connector_constants\lc_options_enable_text_widget] = $body->enable_text_widget;
                }
                if (isset($body->api_key)) {
                    $options[lead_connector_constants\lc_options_api_key] = $body->api_key;
                }

                $newOptions = $this->lead_connector_setting_validate($options, true);
                $text_widget_error = "0";
                $error_details = '';
                $warning_msg = "";
                $white_label_url = "";
                $enable_text_widget = "";
                $api_key = "";
                $location_id = "";

                if (isset($newOptions[lead_connector_constants\lc_options_text_widget_error])) {
                    $text_widget_error = esc_attr($newOptions[lead_connector_constants\lc_options_text_widget_error]);
                }
                if (isset($newOptions[lead_connector_constants\lc_options_text_widget_error_details])) {
                    $error_details = esc_attr($newOptions[lead_connector_constants\lc_options_text_widget_error_details]);
                }
                if (isset($newOptions[lead_connector_constants\lc_options_text_widget_warning_text])) {
                    $warning_msg = esc_attr($newOptions[lead_connector_constants\lc_options_text_widget_warning_text]);
                }
                if (isset($newOptions[lead_connector_constants\lc_options_location_white_label_url])) {
                    $white_label_url = (esc_attr($newOptions[lead_connector_constants\lc_options_location_white_label_url]));
                }
                if (isset($newOptions[lead_connector_constants\lc_options_enable_text_widget])) {
                    $enable_text_widget = (esc_attr($newOptions[lead_connector_constants\lc_options_enable_text_widget]));
                }
                if (isset($newOptions[lead_connector_constants\lc_options_api_key])) {
                    $api_key = (esc_attr($newOptions[lead_connector_constants\lc_options_api_key]));
                }
                if (isset($newOptions[lead_connector_constants\lc_options_location_id])) {
                    $location_id = (esc_attr($newOptions[lead_connector_constants\lc_options_location_id]));
                }

                $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $newOptions);
                if ($text_widget_error == "1") {
                    return (array(
                        'error' => true,
                        'message' => $error_details,
                    ));
                }
                return (array(
                    'success' => true,
                    'warning_msg' => $warning_msg,
                    'api_key' => $api_key,
                    "enable_text_widget" => $enable_text_widget,
                    "warning_msg" => $warning_msg,
                    "home_url" => get_home_url(),
                    "white_label_url" => $white_label_url,
                    "location_id" => $location_id,
                ));

            }

            if ($endpoint == "wp_insert_post") {
                $body = json_decode($request->get_body());

                if (defined('WP_DEBUG') && true === WP_DEBUG) {
                    // error_log("wp_insert_post");
                    // error_log(print_r($body, true));

                }

                $lc_slug = $body->lc_slug;
                $lc_step_id = $body->lc_step_id;
                $lc_step_name = $body->lc_step_name;
                $lc_funnel_id = $body->lc_funnel_id;
                $lc_funnel_name = $body->lc_funnel_name;
                $lc_step_url = $body->lc_step_url;
                $template_id = $body->template_id;
                $lc_display_method = $body->lc_display_method;
                $lc_step_meta = null;
                $lc_step_trackingCode = null;
                $lc_funnel_tracking_code = null;
                $lc_include_tracking_code = false;
                $lc_use_site_favicon = false;

                if ($body->lc_include_tracking_code == "1") {
                    $lc_include_tracking_code = true;
                }
                if ($body->lc_use_site_favicon == "1") {
                    $lc_use_site_favicon = true;
                }

                if (isset($body->lc_step_meta)) {
                    $lc_step_meta = $body->lc_step_meta;
                }

                if (isset($body->lc_funnel_tracking_code) && $lc_include_tracking_code) {
                    if (defined('WP_DEBUG') && true === WP_DEBUG) {
                        // error_log("lc_funnel_tracking_code");
                        // error_log(print_r($body->lc_funnel_tracking_code, true));
                        // error_log(print_r($body->lc_funnel_tracking_code->headerCode, true));
                        // error_log(print_r($body->lc_funnel_tracking_code->footerCode, true));
                    }

                    $lc_funnel_tracking_code = wp_json_encode($body->lc_funnel_tracking_code, JSON_UNESCAPED_UNICODE);

                }

                if (isset($body->lc_step_page_download_url) && $lc_include_tracking_code) {
                    if (defined('WP_DEBUG') && true === WP_DEBUG) {
                        // error_log("wp_insert_post_tracking");
                        // error_log(print_r($body->lc_include_tracking_code, true));
                        // error_log(print_r($body->lc_step_page_download_url, true));
                    }

                    $args = array(
                        'timeout' => 60,
                    );
                    $response_code = wp_remote_get($body->lc_step_page_download_url, $args);
                    $http_tracking_code = wp_remote_retrieve_response_code($response_code);
                    if ($http_tracking_code === 200) {
                        $body_tracking_code = wp_remote_retrieve_body($response_code);

                        $tracking_res = json_decode($body_tracking_code, false);

                        if (isset($tracking_res) && isset($tracking_res->trackingCode)) {
                            $lc_step_trackingCode = base64_encode(wp_json_encode($tracking_res->trackingCode, JSON_UNESCAPED_UNICODE));
                            if (defined('WP_DEBUG') && true === WP_DEBUG) {
                                // error_log(print_r($lc_step_trackingCode, true));
                            }
                        }
                    }
                }

                $query_args = array(
                    'meta_key' => 'lc_slug',
                    'meta_value' => $lc_slug,
                    'post_type' => lead_connector_constants\lc_custom_post_type,
                    'compare' => '=',
                );

                $the_posts = get_posts($query_args);
                if (count((array) $the_posts) && $template_id === -1) {
                    return (array(
                        'error' => true,
                        'message' => "slug '" . $lc_slug . "' already exist",
                        "code" => 1009,
                    ));

                }

                $user = wp_get_current_user();
                $user_id = get_current_user_id();
                $user_logged_in = is_user_logged_in();
                // error_log("current_user");
                // error_log(print_r($user, true));
                // error_log(print_r($user_id, true));
                // error_log(print_r($user_logged_in, true));

                $post_data = [];
                $post_data['post_title'] = "LeadConnector_funnel-" . $lc_funnel_name . "-step-" . $lc_step_name;
                $post_data['post_content'] = "";
                $post_data['post_type'] = lead_connector_constants\lc_custom_post_type;
                $post_data['post_status'] = "publish";
                if ($template_id !== -1) {
                    $post_data['ID'] = $template_id;
                }
                // $post_data['post_author'] = $post_author;

                $post_id = wp_insert_post($post_data);
                if (is_wp_error($post_id) || $post_id === 0) {
                    // error_log("fail to save the post");
                    return (array(
                        'error' => true,
                        'message' => "fail to save the post",
                    ));
                }
                if (isset($lc_slug)) {
                    update_post_meta($post_id, "lc_slug", $lc_slug);
                }
                if (isset($lc_step_id)) {
                    update_post_meta($post_id, "lc_step_id", $lc_step_id);
                }
                if (isset($lc_step_name)) {
                    update_post_meta($post_id, "lc_step_name", $lc_step_name);
                }
                if (isset($lc_funnel_id)) {
                    update_post_meta($post_id, "lc_funnel_id", $lc_funnel_id);
                }
                if (isset($lc_funnel_name)) {
                    update_post_meta($post_id, "lc_funnel_name", $lc_funnel_name);
                }
                if (isset($lc_step_url)) {
                    update_post_meta($post_id, "lc_step_url", $lc_step_url);
                }
                if (isset($lc_display_method)) {
                    update_post_meta($post_id, "lc_display_method", $lc_display_method);
                }
                if (isset($lc_step_meta)) {
                    update_post_meta($post_id, "lc_step_meta", wp_json_encode($lc_step_meta, JSON_UNESCAPED_UNICODE));
                }
                if (isset($lc_step_trackingCode)) {
                    update_post_meta($post_id, "lc_step_trackingCode", $lc_step_trackingCode);
                }
                if (isset($lc_include_tracking_code)) {
                    update_post_meta($post_id, "lc_include_tracking_code", $lc_include_tracking_code);
                }
                if (isset($lc_use_site_favicon)) {
                    update_post_meta($post_id, "lc_use_site_favicon", $lc_use_site_favicon);
                }
                if (isset($lc_funnel_tracking_code)) {
                    update_post_meta($post_id, "lc_funnel_tracking_code", $lc_funnel_tracking_code);
                }

                return (array(
                    'success' => true,
                ));
            }
            return;
        }

        if ($endpoint == "wp_get_all_posts") {

            $body = json_decode( $params['data']);

            $perPage = $body->per_page;
            $page = $body->page;

            $query_args = array(
                'post_type' => lead_connector_constants\lc_custom_post_type,
                'post_status' => 'any',
                'compare' => '=',
                "posts_per_page" => $perPage,
                "paged" => $page
            );

            $the_posts = new WP_Query($query_args);

            $templates = [];
            $location_id = "";
            if (isset($options[lead_connector_constants\lc_options_location_id])) {
                $location_id = esc_attr($options[lead_connector_constants\lc_options_location_id]);
            }

            if ($the_posts->have_posts()) :
                while ($the_posts->have_posts()) :
                    $the_posts->the_post();
                    $templates[] = $this->get_item(get_the_ID(), $location_id);
                endwhile;
            endif;
         

            $finalOutput = array(
                "funnels" => $templates,
                "pagination" => [
                    "total" => count($templates),
                    "page" => $page,
                    "per_page" => $perPage,
                    "total_pages" => $the_posts->max_num_pages,
                ],
            );

            return $finalOutput;
        }

        if ($endpoint == "wp_get_lc_options") {

            $enabled_text_widget = 0;
            $text_widget_error = "0";
            $error_details = '';
            $warning_msg = "";
            $white_label_url = "";
            $location_id = "";
            $oauth_access_token = "";

            if (isset($options[lead_connector_constants\lc_options_text_widget_error])) {
                $text_widget_error = esc_attr($options[lead_connector_constants\lc_options_text_widget_error]);
            }
            if (isset($options[lead_connector_constants\lc_options_text_widget_error_details])) {
                $error_details = esc_attr($options[lead_connector_constants\lc_options_text_widget_error_details]);
            }
            if (isset($options[lead_connector_constants\lc_options_text_widget_warning_text])) {
                $warning_msg = esc_attr($options[lead_connector_constants\lc_options_text_widget_warning_text]);
            }
            if (isset($options[lead_connector_constants\lc_options_enable_text_widget])) {
                $enabled_text_widget = esc_attr($options[lead_connector_constants\lc_options_enable_text_widget]);
            }
            if (isset($options[lead_connector_constants\lc_options_location_white_label_url])) {
                $white_label_url = (esc_attr($options[lead_connector_constants\lc_options_location_white_label_url]));
            }
            if (isset($options[lead_connector_constants\lc_options_location_id])) {
                $location_id = (esc_attr($options[lead_connector_constants\lc_options_location_id]));
            }

            if (isset($options[lead_connector_constants\lc_options_oauth_access_token])) {
                $oauth_access_token = (esc_attr($options[lead_connector_constants\lc_options_oauth_access_token]));
            }
            $data = [
                'api_key' => $api_key,
                "oauth_access_token" => $oauth_access_token,
                "enable_text_widget" => $enabled_text_widget,
                "text_widget_error" => $text_widget_error == "1" ? true : false,
                "error_details" => $error_details,
                "warning_msg" => $warning_msg,
                "home_url" => get_home_url(),
                "white_label_url" => $white_label_url,
                "location_id" => $location_id,
                "plugin_directory_url" => plugin_dir_url(dirname(__FILE__)),
                "all_options" => $options
            ];

            return $data;
        }

        if ($endpoint == "wp_delete_post") {
            $body = json_decode($params['data']);
            $post_id = $body->post_id;
            $force_delete = $body->force_delete;

            $post_info = wp_delete_post($post_id, $force_delete);
            if (!$post_info) {
                // error_log("fail to delete the post");
                return (array(
                    'error' => true,
                    'message' => "fail to delete the post",
                ));

            }
            return $post_info;
        }
        if ($directEndpoint == "true") {
            $args = array(
                'timeout' => 60,
            );
            $response = wp_remote_get($endpoint, $args);
            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $obj = json_decode($body, false);
                return $obj;
            } else {
                return (array(
                    'error' => true,
                ));
            }
        }


        if($endpoint == 'funnels_get_list'){
            return $this->lc_wp_get($endpoint);
        }

        if($endpoint == 'funnels_get_details'){
            $body = json_decode($params['data']);
            return $this->lc_wp_get($endpoint, $body);
        }

        if($endpoint == 'wp_enable_email'){
            $body = json_decode($params['data']);
            $response = $this->lc_wp_get($endpoint, $body);
            $option_saved = false ;
            if(@!$response->error){

                $newOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);


                $newOptions[lead_connector_constants\lc_options_email_smtp_enabled] = 'true';
                $newOptions[lead_connector_constants\lc_options_email_smtp_email] = $response->smtpEmail;
                $newOptions[lead_connector_constants\lc_options_email_smtp_password] = $response->smtpPassword;
                $newOptions[lead_connector_constants\lc_options_email_smtp_port] = $response->smtpPort;
                $newOptions[lead_connector_constants\lc_options_email_smtp_server] = $response->smtpServer;
                $newOptions[lead_connector_constants\lc_options_email_smtp_provider] = $response->smtpProviderName;

                $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $newOptions);
                $option_saved = true;

            }else {
                if($response->http_code == 400 && $response->error){
                    $errorBody = json_decode($response->body);
                    if($errorBody->message){
                        return array(
                            'success' => false,
                            'message' => $errorBody->message
                        );
                    }
                }
            }


            return array(
                'success' => $option_saved,
            );
        }

        if($endpoint == 'wp_email_eligibility_check'){
            $email_enabled = @$options[lead_connector_constants\lc_options_email_smtp_enabled] == 'true' ? true : false ;
            $active_email_id = @$options[lead_connector_constants\lc_options_email_smtp_email] ;

            $response = $this->lc_wp_get($endpoint);

            return array(
                'emailEnabled' => $email_enabled,
                'enabledEmailId' => $active_email_id,
                'response'=> $response
            );
        }
        
        if($endpoint == 'wp_disable_email'){
            $body = json_decode($params['data']);
            $response = $this->lc_wp_get($endpoint, $body);
            $option_saved = false;
            if(!@$response->error || @$response->http_code == 500 ){
                $newOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);

                $newOptions[lead_connector_constants\lc_options_email_smtp_enabled] = 'false';
                $newOptions[lead_connector_constants\lc_options_email_smtp_email] = null;
                $newOptions[lead_connector_constants\lc_options_email_smtp_password] = null;
                $newOptions[lead_connector_constants\lc_options_email_smtp_port] = null;
                $newOptions[lead_connector_constants\lc_options_email_smtp_server] = null;
                $newOptions[lead_connector_constants\lc_options_email_smtp_provider] = null;

                $option_saved = update_option(LEAD_CONNECTOR_OPTION_NAME, $newOptions);
            }

            return array(
                'success' => true, 
                'response' => $response,
            );

        }

        if($endpoint == 'check_smtp_plugin_conflict'){
            
            return $this->check_smtp_plugin_conflict();
        }

        if(isset($params['data'])){    
            $params = json_decode($params['data']);
        }

        return $this->lc_wp_get($endpoint, $params);

    }

    private function lc_oauth_regenerate_token()
    {
    
        $lcOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);

        if(!is_array($lcOptions))
            return array(
                "success" => false
            );

        if(!isset($lcOptions[lead_connector_constants\lc_options_oauth_refresh_token]) && $lcOptions[lead_connector_constants\lc_options_oauth_refresh_token] == '')
            return array(
                "success" => false
            );

        $lcRefreshToken = $this->lc_decrypt_string($lcOptions[lead_connector_constants\lc_options_oauth_refresh_token]);
        $token_response = $this->lc_perform_oauth_request("refresh", $lcRefreshToken , $lcOptions[lead_connector_constants\lc_options_location_id]);
        
        if (isset($token_response->response->code) && ($token_response->response->code == 200 || $token_response->response->code == 201)) {
            if (!isset($token_response->response->error) ) {
                if (isset($token_response->userType) && $token_response->userType == 'Location') {
                    $lcOptions[lead_connector_constants\lc_options_location_id] = $token_response->locationId;
                    $lcOptions[lead_connector_constants\lc_options_oauth_access_token] = $this->lc_encrypt_string($token_response->access_token);
                    $lcOptions[lead_connector_constants\lc_options_oauth_refresh_token] = $this->lc_encrypt_string($token_response->refresh_token);
                    
                    update_option(LEAD_CONNECTOR_OPTION_NAME, $lcOptions);
                }
            }
        }

        return array(
            "success" => true,
        );

    }


    public function lc_disable_auto_updates() {

        $lcOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);
        
        if(!isset($lcOptions[lead_connector_constants\lc_options_last_cron_clear_started_at])){
            return ;
        }

        if(time() - $lcOptions[lead_connector_constants\lc_options_last_cron_clear_started_at] > 300){
            return ;
        }

        // Disable WordPress core auto updates
        add_filter('auto_update_core', '__return_false');
        
        // Disable plugin auto updates 
        add_filter('auto_update_plugin', '__return_false');
        
        // Disable theme auto updates
        add_filter('auto_update_theme', '__return_false');
        
        // Disable translation auto updates
        add_filter('auto_update_translation', '__return_false');
        
        // Disable the automatic updater entirely
        add_filter('automatic_updater_disabled', '__return_true');
        
        return array(
            'success' => true,
            'message' => 'Auto updates disabled successfully'
        );
    }

    

    private function lc_wp_get($endpoint, $params = array()){

        $params = (object) $params;
        $lcOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);

        
        if($endpoint == 'wp_get_cron_count'){
            $has_cron =  wp_next_scheduled( 'lc_twicedaily_refresh_req' );

            $maintenance_mode = false;
            $is_allowed = false ; 
            if (file_exists(ABSPATH . '.maintenance')) {
                $maintenance_mode = true;
            }


            if($has_cron){
                $canDoResponse = $this->lc_wp_get("utils_can_do", array("action" => "mass_clear_cron"));
                $is_allowed = $canDoResponse->allowed ;
            }
            return array(
                'has_cron' => $has_cron,
                'maintenance_mode' => $maintenance_mode,
                'is_allowed' => $is_allowed
            );
        }

        if($endpoint == 'wp_clear_cron'){
    
            $lcOptions[lead_connector_constants\lc_options_last_cron_clear_started_at] = time();
            update_option(LEAD_CONNECTOR_OPTION_NAME, $lcOptions);
    

            // Reset the cron schedule safely using WordPress functions
            update_option('cron', []);

            // Delete the 'cron' option from the database (flush all cron jobs)
            delete_transient('doing_cron');
            delete_option('cron');
        
            // Clear all cron jobs from memory
            // wp_clear_scheduled_hook('*'); 
            wp_clear_scheduled_hook('lc_twicedaily_refresh_req');
        
            // Force clear internal cache
            wp_cache_delete('cron', 'options');
            wp_cache_delete('alloptions', 'options');   

                        

            return array(
                'success' => true,
            );
        }

        $api_key = isset($lcOptions[lead_connector_constants\lc_options_api_key]) ? $lcOptions[lead_connector_constants\lc_options_api_key] : null ;
        $lcAccessToken = isset($lcOptions[lead_connector_constants\lc_options_oauth_access_token]) ? $lcOptions[lead_connector_constants\lc_options_oauth_access_token] : null ;
        $lcAccessToken = $this->lc_decrypt_string($lcAccessToken);
        $lcLocationId = isset($lcOptions[lead_connector_constants\lc_options_location_id]) ? $lcOptions[lead_connector_constants\lc_options_location_id] : null ;

        $finalEndpoint = "";
        $authMethod = 'api_key';

        if($lcAccessToken != ''){
            $authMethod = 'oauth';
        }

        $baseHost = LEAD_CONNECTOR_BASE_URL;


        if( $authMethod == 'api_key'){

            if($endpoint == 'funnels_get_list')
                $finalEndpoint = "v1/funnels/?includeDomainId=true&includeTrackingCode=true";
            
            if($endpoint == 'get_chat_widget')
                $finalEndpoint = "v1/locations/me?includeWhiteLabelUrl=true";
            
            if($endpoint == 'funnels_get_details')
                $finalEndpoint = "v1/funnels/".$params->funnelId."/pages/?includeMeta=true&includePageDataDownloadURL=true";

        }else {
            $baseHost = LEAD_CONNECTOR_SERVICES_BASE_URL;

            if($endpoint == 'funnels_get_list')
                $finalEndpoint = "funnels/funnel/list?locationId=".$lcLocationId;
            
            if($endpoint == 'get_chat_widget')
                $finalEndpoint = "wordpress/lc-plugin/chat-widget/".$lcLocationId.'/';

            if($endpoint == 'funnels_get_details')
                throw new Exception("Endpoint not supported for oauth");

            if($endpoint == 'wp_email_eligibility_check')
                $finalEndpoint = "wordpress/lc-plugin/emails/eligibility/".$lcLocationId.'/';

            if($endpoint == 'wp_enable_email')
                $finalEndpoint = "wordpress/lc-plugin/emails/generate-smtp/".$lcLocationId.'/?domain='.$params->domain.'&emailPrefix='.$params->emailPrefix;

            if($endpoint == 'wp_disable_email')
                $finalEndpoint = "wordpress/lc-plugin/emails/delete-smtp/".$lcLocationId.'/?emailId='.$params->email;

            if($endpoint == 'forms_get_list')
                $finalEndpoint = "wordpress/lc-plugin/forms/".$lcLocationId."/?page=".$params->page."&perPage=".$params->per_page ;

            if($endpoint == 'phone_numbers_get_list')
                $finalEndpoint = "wordpress/lc-plugin/phone-system/pools/".$lcLocationId ;
            
            if($endpoint == "utils_can_do")
                $finalEndpoint = "wordpress/lc-plugin/utils/can-do/$lcLocationId/?action=".$params->action."&domain=".get_home_url() ;
        }

   
        if($finalEndpoint == ''){
            return array(
                'error' => true, 
                'message' => 'Invalid Endpoint received',
                'endpoint' => $endpoint,
                'finalEndpoint' => $finalEndpoint,
                'auth' => $authMethod, 
            );
        }



        // return array(
        //     'finalEndpoint' => $finalEndpoint,
        //     'authMethod' => $authMethod,
        //     'baseHost' => $baseHost,
        //     'lcAccessToken' => $lcAccessToken,
        //     'api_key' => $api_key
        // );

        if($authMethod == 'oauth'){
            return $this->lc_oauth_wp_remote_get($finalEndpoint, $lcAccessToken, $baseHost);
        }

        return $this->lc_wp_remote_get($finalEndpoint, $api_key, $baseHost);
    }

    /**
     * Encrypts a string using WordPress salt and key
     * @since    1.0.0
     * @param    string    $string    String to encrypt
     * @return   string    Encrypted string
     */
    private function lc_encrypt_string($string) {
        $encryption = new LC_Data_Encryption();
        return $encryption->encrypt($string);
    }

    /**
     * Decrypts a string using WordPress salt and key
     * @since    1.0.0
     * @param    string    $encrypted    String to decrypt
     * @return   string    Decrypted string
     */
    private function lc_decrypt_string($encrypted) {
        $encryption = new LC_Data_Encryption();
        return $encryption->decrypt($encrypted);
    }

    

    private function lc_perform_oauth_request($endpoint = "validate" , $code = null, $locationId = null){

        if($code == null || $code == '')
            return array(
                "error" => true,
                "message" => "Invalid Refresh Token or Auth Code Not Received"
            );

        $finalEndpoint = "wordpress/lc-plugin/oauth/".$endpoint."?lcVersion=".LEAD_CONNECTOR_VERSION;
    
        $body = array();

        if($endpoint == "validate"){
            $body['code'] = $code;
        }else {
            $body['refresh_token'] = $code;
        }

        if($locationId != null && $endpoint == "refresh"){
            $finalEndpoint = "wordpress/lc-plugin/oauth/location/:locationId/refresh?locationId=".$locationId."&lcVersion=".LEAD_CONNECTOR_VERSION;
        }

        $args = array(
            'timeout' => 60,
            'body' => $body,
            'headers' => array(
                'X-LC-Version' => LEAD_CONNECTOR_VERSION,
            ),
        );

        // LEAD_CONNECTOR_SERVICES_BASE_URL
        $response = wp_remote_post(LEAD_CONNECTOR_SERVICES_BASE_URL . $finalEndpoint, $args);
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);


        if ($http_code === 200 || $http_code === 201) {
            $obj = json_decode($body, false);
            return $obj;
        } else {
            return (object) (array(
                'error' => true,
                'http_code'=> $http_code, 
                'body' => $body
            ));
        }

    }



    private function lc_oauth_wp_remote_get($endpoint, $lcAccessToken, $baseHost = LEAD_CONNECTOR_SERVICES_BASE_URL)
    {
        $attempt = 1;
        $lcOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);
        $lcAccessToken = $lcOptions[lead_connector_constants\lc_options_oauth_access_token];
        $lcAccessToken = $this->lc_decrypt_string($lcAccessToken);
        $http_code = 100 ;
        $tokenRegenerationStatus = null ;
        $all_attempts = array();
        while ($attempt <= 2) {

            $args = array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => "Bearer " . $lcAccessToken,
                    'Accept' => 'application/json',
                    'version' => '2021-04-15', 
                    'X-LC-Version' => LEAD_CONNECTOR_VERSION,
                ),
            );

            $finalURL = LEAD_CONNECTOR_SERVICES_BASE_URL . $endpoint;
            if (strpos($finalURL, '?') !== false) {
                $finalURL .= '&lcVersion=' . LEAD_CONNECTOR_VERSION;
            } else {
                $finalURL .= '?lcVersion=' . LEAD_CONNECTOR_VERSION;
            }

            $response = wp_remote_get(LEAD_CONNECTOR_SERVICES_BASE_URL . $endpoint, $args);
            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            array_push($all_attempts, array(
                'http_code' => $http_code,
                'body' => $body,
            ));

            if ($http_code === 200 || $http_code === 201) {
                $obj = json_decode($body, false);
                return $obj;
            } else if ($http_code == 401) {
                $tokenRegenerationStatus = $this->lc_oauth_regenerate_token();
            } else {
                return (object) (array(
                    'error' => true,
                    'http_code'=> $http_code, 
                    'body' => $body,
                    'from' => 'oauth_wp_remote_get'
                ));
            }

            $attempt++;
        }

        // if($tokenRegenerationStatus->response->status != 200 && $tokenRegenerationStatus->response->status != 201){
            // return $this->lc_disconnect();
        // }

        return (array(
            'error' => true,
            'http_code' => $http_code,
            "token_refresh_status" => $tokenRegenerationStatus,
            "lcAccessToken" => $lcAccessToken,
            'attempt' => $attempt,
            "all_attempts" => $all_attempts,
        ));
    }

    private function lc_wp_remote_get($endpoint, $api_key, $baseHost = LEAD_CONNECTOR_BASE_URL)
    {

         //else call Remote API here
         $args = array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        );
        $response = wp_remote_get(LEAD_CONNECTOR_BASE_URL . $endpoint, $args);
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $obj = json_decode($body, false);
            return $obj;
        } else {
            return (array(
                'error' => true,
                'http_code' => $http_code,
                'from' => 'wp_remote_get_api'
            ));
        }

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/lc-admin.css', array(), $this->version, 'all');

        if ($hook != 'toplevel_page_lc-plugin') {
            return;
        }

        // // error_log(print_r($_SERVER['REMOTE_ADDR'], true));
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined LeadConnector_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The LeadConnector_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        $css_to_load = plugin_dir_url(__FILE__) . 'app.css';
        $vendor_css_to_load = plugin_dir_url(__FILE__) . 'app2.css';

        wp_enqueue_style('vue_lead_connector_app', $css_to_load, array(), $this->version, 'all');
        // wp_enqueue_style('vue_lead_connector_vendor', $vendor_css_to_load, array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {

        if ($hook != 'toplevel_page_lc-plugin') {
            return;
        }
        // // error_log(print_r($hook, true));
        // // error_log(print_r($_SERVER['REMOTE_ADDR'], true));

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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lc-admin.js', array('jquery'), $this->version, false);
        if (in_array($_SERVER['REMOTE_ADDR'], array('10.255.0.2', '::1'))) {
            // DEV Vue dynamic loading
            $js_to_load = plugin_dir_url(__FILE__) . 'app.js';
            $vendor_js_to_load = plugin_dir_url(__FILE__) . 'chunk-vendors.js';

            // wp_enqueue_script('vue_lead_connector_vendor_js', $vendor_js_to_load, '', wp_rand(11, 1000), true);

        } else {
            $js_to_load = plugin_dir_url(__FILE__) . 'app.js';
            $vendor_js_to_load = plugin_dir_url(__FILE__) . 'chunk-vendors.js';
            // wp_enqueue_script('vue_lead_connector_vendor_js', $vendor_js_to_load, '', wp_rand(11, 1000), true);
        }

        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        $enabledTextWidget = 0;
        if (isset($options[lead_connector_constants\lc_options_enable_text_widget])) {
            $enabledTextWidget = esc_attr($options[lead_connector_constants\lc_options_enable_text_widget]);
        }

        wp_localize_script(
            $this->plugin_name,
            'lc_admin_settings',
            array(
                'enable_text-widget' => $enabledTextWidget,
                'proxy_url' => rest_url('lc_public_api/v1/proxy'),
                'nonce' => wp_create_nonce('wp_rest'),
            )
        );

        wp_enqueue_script('vue_lead_connector_js', $js_to_load, '', wp_rand(10, 1000), array(
            'type'  => 'module',
            ));

            function lc_add_type_attribute($tag, $handle, $src) {
                // if not your script, do nothing and return original $tag
                if ( 'vue_lead_connector_js' !== $handle ) {
                    return $tag;
                }
                // change the script tag by adding type="module" and return it.
                $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
                return $tag;
            }

            add_filter('script_loader_tag', 'lc_add_type_attribute' , 10, 3);
    }


 

    /**
     * Top level menu callback function
     */

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function wporg_options_page()
    {
        add_menu_page(
            __('LeadConnector', 'LeadConnector'),
            __('LeadConnector', 'LeadConnector'),
            'manage_options',
            'lc-plugin',
            'lead_connector_render_plugin_settings_page',
            'dashicons-admin-generic',
            '58.5'
        );
    }
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function register_settings()
    {
        // add_settings_section('api_settings_inputs', __('leadconnector', 'LeadConnector'), 'lead_connector_section_text1', 'lead_connector_plugin');

        register_setting(LEAD_CONNECTOR_OPTION_NAME, LEAD_CONNECTOR_OPTION_NAME, array(
            'sanitize_callback' => array($this, 'lead_connector_setting_validate'),
        ));
    }

    public function admin_rest_api_init()
    {
        register_rest_route('lc_public_api/v1', '/proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::READABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'lc_public_api_proxy'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('lc_public_api/v1', 'proxy', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods' => WP_REST_Server::CREATABLE,
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'lc_public_api_proxy'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

    }

    public function refresh_oauth_token (){
        return $this->lc_oauth_regenerate_token();
    }
    public function register_custom_post()
    {
        $labels = array(
            'name' => _x('Funnels', 'lead connector funnels'),
            'singular_name' => _x('Funnel', 'post type singular name'),
            'add_new' => _x('Add New', 'Funnel'),
        );
        $args = array(
            'labels' => $labels,
            'description' => 'post your CRM account funnels on wordpress',
            'public' => false,
            'has_archive' => true,
            'supports' => array(''),
            'rewrite' => array('slug' => 'funnels'),
            'register_meta_box_cb' => array($this, "remove_save_box"),
            'hide_post_row_actions' => array('trash'),
        );
        register_post_type(lead_connector_constants\lc_custom_post_type, $args);

    }

    public function get_meta_fields($lc_post_meta)
    {
        $default_meta_fields = '
        <meta  charset="utf-8" />
        <meta
            name="viewport"
            content="width=device-width, user-scalable=no"
        />
        <meta
            name="description"
            content="description"
        />
        <meta  property="og:type" content="website" />
        <meta
            property="twitter:type"
            content="website"
        />
        <meta property="robots" content="noindex" />
        ';
        try {

            if (isset($lc_post_meta)) {

                $lc_post_meta = json_decode($lc_post_meta);
                if (isset($lc_post_meta->title) && $lc_post_meta->title !== '') {
                    $title_meta = '<title>' . $lc_post_meta->title . '</title>
                    <meta property="og:title" content="' . $lc_post_meta->title . '" />
                ';
                    $default_meta_fields .= $title_meta;
                }
                if (isset($lc_post_meta->description) && $lc_post_meta->description !== '') {
                    $description_meta = '<meta property="description" content="' . $lc_post_meta->description . '"/>
                    <meta property="og:description" content="' . $lc_post_meta->description . '" />
                ';
                    $default_meta_fields .= $description_meta;
                }

                if (isset($lc_post_meta->author) && $lc_post_meta->author !== '') {
                    $author_meta = '<meta property="author" content="' . $lc_post_meta->author . '"/>
                    <meta property="og:author" content="' . $lc_post_meta->author . '" />
                ';
                    $default_meta_fields .= $author_meta;
                }

                if (isset($lc_post_meta->imageUrl) && $lc_post_meta->imageUrl !== '') {
                    $imageUrl_meta = '<meta property="image" content="' . $lc_post_meta->imageUrl . '"/>
                    <meta property="og:image" content="' . $lc_post_meta->imageUrl . '" />
                ';
                    $default_meta_fields .= $imageUrl_meta;
                }
                if (isset($lc_post_meta->keywords) && $lc_post_meta->keywords !== '') {
                    $keywords_meta = '<meta property="keywords" content="' . $lc_post_meta->keywords . '"/>
                    <meta property="og:keywords" content="' . $lc_post_meta->keywords . '" />
                ';
                    $default_meta_fields .= $keywords_meta;
                }
                if (isset($lc_post_meta->customMeta) && count((array) $lc_post_meta->customMeta) > 0) {
                    foreach ($lc_post_meta->customMeta as $customMeta) {
                        if (isset($customMeta)) {

                            if (
                                isset($customMeta->name) && $customMeta->name !== '' &&
                                isset($customMeta->content) && $customMeta->content !== ''
                            ) {
                                $custom_meta = '<meta property="' . $customMeta->name . '" content="' . $customMeta->content . '"/>';
                                $default_meta_fields .= $custom_meta;
                            }

                        }
                    }

                }

            }
        } catch (Exception $e) {
            // error_log("failed to parse the post meta");
            // error_log(print_r($e, true));

        }
        return $default_meta_fields;

    }

    public function get_tracking_code($lc_post_tracking_code, $is_header, $is_funnel = false)
    {
        if (defined('WP_DEBUG') && true === WP_DEBUG) {
            // error_log("get_tracking_code");
            // error_log(print_r($lc_post_tracking_code, true));
        }

        $default_tracking_code = ' ';
        try {
            if (isset($lc_post_tracking_code)) {

                if (!$is_funnel) {
                    $lc_post_tracking_code = base64_decode($lc_post_tracking_code);
                }
                $lc_post_tracking_code = json_decode($lc_post_tracking_code);

                if ($is_header && isset($lc_post_tracking_code->headerCode) && $lc_post_tracking_code->headerCode !== '') {
                    $default_tracking_code = ($lc_post_tracking_code->headerCode);
                }
                if (!$is_header && isset($lc_post_tracking_code->footerCode) && $lc_post_tracking_code->footerCode !== '') {
                    $default_tracking_code = $lc_post_tracking_code->footerCode;
                }
                if ($is_funnel) {
                    $default_tracking_code = base64_decode($default_tracking_code);
                }

            }
        } catch (Exception $e) {
            // error_log("failed to parse the tracking code");
            // error_log(print_r($e, true));

        }
        return $default_tracking_code;

    }

    public function get_page_iframe($funnel_step_url, $lc_post_meta, $lc_step_trackingCode, $lc_funnel_tracking_code, $lc_use_site_favicon)
    {
        $post_meta_fields = $this->get_meta_fields($lc_post_meta);
        $head_tracking_code = $this->get_tracking_code($lc_funnel_tracking_code, true, true);
        $head_tracking_code .= $this->get_tracking_code($lc_step_trackingCode, true);

        $footer_tracking_code = $this->get_tracking_code($lc_funnel_tracking_code, false, true);
        $footer_tracking_code .= $this->get_tracking_code($lc_step_trackingCode, false);
        $favicon_code = '';

        $widget_url = LEAD_CONNECTOR_CDN_BASE_URL . 'loader.js';

        if ($lc_use_site_favicon) {
            $favicon_url = get_site_icon_url();
            $favicon_code = ' <link  rel="icon" type="image/x-icon" href="' . $favicon_url . '">';
        }
        // wp_enqueue_script($this->plugin_name . ".lc_text_widget", LEAD_CONNECTOR_CDN_BASE_URL . 'loader.js');
        // wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/lc-public.js', array('jquery'), $this->version, false);
        // wp_localize_script($this->plugin_name, 'lc_public_js',
        //     array(
        //         'text_widget_location_id' => $location_id,
        //         'text_widget_heading' => $heading,
        //         'text_widget_sub_heading' => $sub_heading,
        //         'text_widget_error' => $text_widget_error,
        //         'text_widget_use_email_field' => $use_email_field,
        //         "text_widget_settings" => $chat_widget_settings,
        //     ));

        return '<!DOCTYPE html>
            <head>
            ' . $favicon_code . '
            ' . $post_meta_fields . '
            ' . $head_tracking_code . '
                <style>
                    body {
                        margin: 0;            /* Reset default margin */
                    }
                    iframe {
                        display: block;       /* iframes are inline by default */
                        border: none;         /* Reset default border */
                        height: 100vh;        /* Viewport-relative units */
                        width: 100vw;
                    }
                </style>
                <meta name="viewport" content="width=device-width, initial-scale=1">
            </head>
            <body>

                <iframe width="100%" height="100%" src="' . $funnel_step_url . '" frameborder="0" allowfullscreen></iframe>
                 ' . $footer_tracking_code . '
            </body>
        </html>';
    }

    public function process_page_request()
    {

        $full_request_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $request_url_parts = explode("?", $full_request_url);
        $request_url = $request_url_parts[0];
        $base_url = get_home_url() . "/";
        $slug = str_replace($base_url, "", $request_url);
        $slug = rtrim($slug, '/');
        if ($slug != '') {
            $query_args = array(
                'meta_key' => 'lc_slug',
                'meta_value' => $slug,
                'post_type' => lead_connector_constants\lc_custom_post_type,
                'compare' => '=',
            );

            $the_posts = get_posts($query_args);
            $lc_page = current($the_posts);

            if ($lc_page) {
                status_header(200);
                $post_id = $lc_page->ID;
                $funnel_step_url = get_post_meta($post_id, "lc_step_url", true);
                $lc_display_method = get_post_meta($post_id, "lc_display_method", true);
                $lc_post_meta = get_post_meta($post_id, "lc_step_meta", true);
                $lc_step_trackingCode = get_post_meta($post_id, "lc_step_trackingCode", true);
                $lc_funnel_tracking_code = get_post_meta($post_id, "lc_funnel_tracking_code", true);
                $lc_include_tracking_code = get_post_meta($post_id, "lc_include_tracking_code", true);
                $lc_use_site_favicon = get_post_meta($post_id, "lc_use_site_favicon", true);

                if (!$lc_include_tracking_code) {
                    $lc_step_trackingCode = null;
                    $lc_funnel_tracking_code = null;
                }

                if ($lc_display_method == "iframe") {
                    $content = $this->get_page_iframe($funnel_step_url, $lc_post_meta, $lc_step_trackingCode, $lc_funnel_tracking_code, $lc_use_site_favicon);
                    $allowed_html = array(
                        'head' => array(),
                        'style' => array(),
                        'body' => array(),
                        'html' => array(),
                        'title' => array(),
                        'script' => array(),
                        'noscript' => array(),
                        //links
                        'iframe' => array(
                            'href' => array(),
                            'src' => array(),
                            'width' => array(),
                            'height' => array(),
                            'frameborder' => array(),
                            'allowfullscreen' => array()
                        ),
                        'meta' => array(
                            'property' => array(),
                            'content' => array(),
                            'name' => array()
                        ),
                        'link' => array(
                            'rel' => array(),
                            'type' => array(),
                            'href' => array()
                        )
                    );
                    // echo $content;
                    echo wp_kses($content, $allowed_html);
                } else {
                    wp_redirect($funnel_step_url, 301);
                }
                exit();
            }

        }

    }

    public function check_smtp_plugin_conflict()
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $could_have_conflict = false;
        $plugins_with_conflict = "";
        $active_plugins = get_option('active_plugins');
        foreach ($plugins as $plugin_key => $plugin) { // get the key of array plugins aswell 
            if (strpos($plugin['Name'], 'SMTP') !== false) {
                if(in_array($plugin_key, $active_plugins)){
                    $could_have_conflict = true;

                    if($plugins_with_conflict == ""){
                        $plugins_with_conflict .= $plugin['Name'];
                    }else{
                        $plugins_with_conflict .= ", " . $plugin['Name'] . ", ";
                    }
                }
            }
        }
       
        return array(
            'could_have_conflict' => $could_have_conflict,
            'plugins' => $plugins_with_conflict,
        );
    }
}
