<?php
/**
 * Lead Connector Native Template Handler
 *
 * This file is loaded by the `template_include` filter in the main plugin
 * when a funnel step is set to display natively with WordPress headers and footers.
 */

// --- 1. Get All Data ---
$post_id = get_the_ID();
$funnel_step_url = get_post_meta( $post_id, 'lc_step_url', true );

// Get tracking codes and other settings
$lc_post_meta = get_post_meta( $post_id, 'lc_step_meta', true );
$lc_step_trackingCode = get_post_meta( $post_id, 'lc_step_trackingCode', true );
$lc_funnel_tracking_code = get_post_meta( $post_id, 'lc_funnel_tracking_code', true );
$lc_use_site_favicon = get_post_meta( $post_id, 'lc_use_site_favicon', true );

// --- 2. Instantiate Admin Class to Use Helper Methods ---
// Load the admin class file if not already loaded
if ( ! class_exists( 'LeadConnector_Admin' ) ) {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-lc-admin.php';
}

// Get plugin name and version from constants or defaults
$plugin_name = defined( 'LEAD_CONNECTOR_PLUGIN_NAME' ) ? LEAD_CONNECTOR_PLUGIN_NAME : 'LeadConnector';
$plugin_version = defined( 'LEAD_CONNECTOR_VERSION' ) ? LEAD_CONNECTOR_VERSION : '3.0.15';

// Instantiate the admin class to access its methods
$lc_admin = new LeadConnector_Admin( $plugin_name, $plugin_version );

// Use the admin class methods to get properly formatted meta fields and tracking codes
$post_meta_fields = $lc_admin->get_meta_fields( $lc_post_meta );
$head_tracking_code = $lc_admin->get_tracking_code( $lc_funnel_tracking_code, true, true );
$head_tracking_code .= $lc_admin->get_tracking_code( $lc_step_trackingCode, true );

$footer_tracking_code = $lc_admin->get_tracking_code( $lc_funnel_tracking_code, false, true );
$footer_tracking_code .= $lc_admin->get_tracking_code( $lc_step_trackingCode, false );

// Favicon Logic
$favicon_code = '';
if ( $lc_use_site_favicon ) {
    $favicon_url = get_site_icon_url();
    if ( $favicon_url ) {
        $favicon_code = '<link rel="icon" type="image/x-icon" href="' . esc_url( $favicon_url ) . '">';
    }
}

// --- 2b. Fetch Native HTML Content ---
$native_html_content = '';
$native_head_content = '';
$native_body_content = '';

// Fetch the HTML content using the get_page_iframe_native method
$native_html_content = $lc_admin->get_page_iframe_native( 
    $funnel_step_url, 
    $lc_post_meta, 
    $lc_step_trackingCode, 
    $lc_funnel_tracking_code, 
    $lc_use_site_favicon 
);

// Log for debugging if WP_DEBUG is enabled
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'LeadConnector Template Handler - Post ID: ' . $post_id );
    error_log( 'LeadConnector Template Handler - Funnel URL: ' . $funnel_step_url );
    error_log( 'LeadConnector Template Handler - Content received: ' . ( empty( $native_html_content ) ? 'EMPTY' : strlen( $native_html_content ) . ' bytes' ) );
}

// Extract head and body content from the fetched HTML
if ( ! empty( $native_html_content ) ) {
    // Use regex-based extraction to preserve HTML5 elements like video, source, etc.
    // DOMDocument has issues with HTML5 self-closing tags like <source>
    
    // Extract HEAD content using regex
    if ( preg_match( '/<head[^>]*>(.*?)<\/head>/is', $native_html_content, $head_matches ) ) {
        $head_content = $head_matches[1];
        // Remove title tags as WordPress will handle those
        $native_head_content = preg_replace( '/<title[^>]*>.*?<\/title>/is', '', $head_content );
    }
    
    // Extract BODY content using regex to preserve HTML5 elements
    if ( preg_match( '/<body[^>]*>(.*)<\/body>/is', $native_html_content, $body_matches ) ) {
        $native_body_content = $body_matches[1];
    } else {
        // If no body tag found, try to use the entire content
        // Remove doctype, html, head tags if present
        $native_body_content = preg_replace( '/^.*?<body[^>]*>/is', '', $native_html_content );
        $native_body_content = preg_replace( '/<\/body>.*$/is', '', $native_body_content );
        
        // If still no body content extracted, use original content
        if ( empty( trim( $native_body_content ) ) ) {
            $native_body_content = $native_html_content;
        }
    }
}


// --- 3. Add custom body class for this page ---
add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'lc-native-page';
    return $classes;
} );

// --- 4. Add all head content to wp_head ---
add_action( 'wp_head', function () use ( $favicon_code, $post_meta_fields, $head_tracking_code, $native_head_content ) {
    // Output favicon if using site favicon
    if ( ! empty( $favicon_code ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above
        echo $favicon_code;
    }
    
    // Output meta fields from post settings
    if ( ! empty( $post_meta_fields ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal source
        echo $post_meta_fields;
    }
    
    // Output native HTML head content (CSS, JS, meta from fetched HTML)
    if ( ! empty( $native_head_content ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal source from app.leadconnectorhq.com
        echo $native_head_content;
    }
    
    // Output tracking codes
    if ( ! empty( $head_tracking_code ) ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal source
        echo $head_tracking_code;
    }
    ?>
    
    <style>
        /* Hide admin bar if present */
        #wpadminbar {
            display: none !important;
        }

        html {
            margin-top: 0 !important;
        }

        /* Styles for native content */
        body.lc-native-page .lc-native-content {
            width: 100%;
        }

        /* Remove padding from entry content for edge-to-edge display */
        body.lc-native-page .entry-content {
            padding: 0 !important;
            margin: 0 !important;
        }
    </style>
    
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        // Polyfill for crypto.randomUUID (required for Loom embeds in non-HTTPS contexts)
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID !== 'function') {
            crypto.randomUUID = function() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0;
                    var v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            };
        }
        
        // Ensure body class is added even if filter didn't work
        document.addEventListener('DOMContentLoaded', function() {
            if (document.body && !document.body.classList.contains('lc-native-page')) {
                document.body.classList.add('lc-native-page');
            }
        });
    </script>
    <?php
} );

// --- 5. Add footer tracking code before wp_footer ---
add_action( 'wp_footer', function () use ( $footer_tracking_code ) {
    echo $footer_tracking_code;
} );

// --- 6. Render the Page ---

// Use WordPress's natural template loading
// This approach works with block themes, classic themes, page builders like Divi, Elementor, etc.

get_header();

// Check if we're in the WordPress loop
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        ?>
        <main id="main" class="site-main">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php if ( ! empty( $native_body_content ) ) : ?>
                    <div class="lc-native-content">
                        <?php 
                        // Outputting unescaped HTML from a trusted internal source
                        // The get_page_iframe_native method restricts the host to app.leadconnectorhq.com
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal HTML source
                        echo $native_body_content; 
                        ?>
                    </div>
                <?php else : ?>
                    <div class="entry-content" style="padding: 2em; text-align: center;">
                        <h2>Error: Failed to load funnel content.</h2>
                        <p>Please check if the funnel URL is valid.</p>
                        <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                            <div style="background: #f0f0f0; padding: 1em; margin: 1em 0; text-align: left; border-radius: 4px;">
                                <h3>Debug Information:</h3>
                                <p><strong>Post ID:</strong> <?php echo esc_html( $post_id ); ?></p>
                                <p><strong>Funnel URL:</strong> <?php echo esc_html( $funnel_step_url ); ?></p>
                                <p><strong>Content Status:</strong> <?php 
                                    if ( empty( $native_html_content ) ) {
                                        echo 'Empty/No content received';
                                    } elseif ( strpos( $native_html_content, '<!-- Error' ) === 0 ) {
                                        echo 'Error returned: ' . esc_html( $native_html_content );
                                    } else {
                                        echo 'Content received but parsing failed';
                                    }
                                ?></p>
                                <p style="font-size: 0.9em; color: #666;">Check error logs for more details.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        </main>
        <?php
    }
} else {
    // Fallback if post loop doesn't work
    ?>
    <main id="main" class="site-main">
        <?php if ( ! empty( $native_body_content ) ) : ?>
            <div class="lc-native-content">
                <?php 
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted internal HTML source
                echo $native_body_content; 
                ?>
            </div>
        <?php else : ?>
            <div style="padding: 2em; text-align: center;">
                <h2>Error: Failed to load funnel content.</h2>
                <p>Please check if the funnel URL is valid.</p>
            </div>
        <?php endif; ?>
    </main>
    <?php
}

get_footer();