<?php

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('elementor template-import', function ($args, $assoc_args) {
        $template_id = isset($assoc_args['template_id']) ? intval($assoc_args['template_id']) : 0;
        $page_id = isset($assoc_args['page_id']) ? intval($assoc_args['page_id']) : 0;

        if (!$template_id || !$page_id) {
            WP_CLI::error('Both --template_id and --page_id are required.');
        }

        // Check if Elementor is active
        if (!class_exists('Elementor\Plugin')) {
            WP_CLI::error('Elementor plugin is not active.');
        }

        // Validate the template
        $template_post = get_post($template_id);
        if (!$template_post || $template_post->post_type !== 'elementor_library') {
            WP_CLI::error("Template ID {$template_id} is not a valid Elementor template.");
        }

        // Validate the target page
        $target_post = get_post($page_id);
        if (!$target_post || $target_post->post_type !== 'page') {
            WP_CLI::error("Page ID {$page_id} is not a valid WordPress page.");
        }

        // Fetch template content and settings
        $template_data = get_post_meta($template_id, '_elementor_data', true);
        $page_settings = get_post_meta($template_id, '_elementor_page_settings', true);

        if (!$template_data) {
            WP_CLI::error("Template ID {$template_id} has no _elementor_data.");
        }

        // Import meta values from the template
        $edit_mode = get_post_meta($template_id, '_elementor_edit_mode', true);
        $template_type = get_post_meta($template_id, '_elementor_template_type', true);
        $elementor_version = get_post_meta($template_id, '_elementor_version', true);
        $wp_page_template = get_post_meta($template_id, '_wp_page_template', true);

        if ($edit_mode) {
            update_post_meta($page_id, '_elementor_edit_mode', $edit_mode);
        }
        if ($template_type) {
            update_post_meta($page_id, '_elementor_template_type', $template_type);
        }
        if ($elementor_version) {
            update_post_meta($page_id, '_elementor_version', $elementor_version);
        }

        /* Kept for backup */
        // if ($wp_page_template) {
        //     update_post_meta($page_id, '_wp_page_template', $wp_page_template);
        // }else {
        update_post_meta($page_id, '_wp_page_template', 'elementor_theme');
        // }

        update_post_meta($page_id, '_elementor_data', maybe_unserialize($template_data));
        if (!empty($page_settings)) {
            update_post_meta($page_id, '_elementor_page_settings', maybe_unserialize($page_settings));
        }

        // Optional: clear Elementor cache
        if (method_exists('\Elementor\Plugin', 'instance')) {
            $elementor = \Elementor\Plugin::instance();
            if (method_exists($elementor, 'files_manager')) {
                $elementor->files_manager->clear_cache();
            }
        }

        WP_CLI::success("Template {$template_id} imported successfully into page {$page_id}.");
    });
}

?>