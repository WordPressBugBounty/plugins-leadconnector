<?php
/**
 *
 * LeadConnector Plugin
 * Copyright (C) 2020-2026 LeadConnector
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * @package LeadConnector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-leadconnector-custom-value-string-replacable.php';

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.leadconnectorhq.com
 * @since      1.0.0
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/public
 */

/**
 * Public-facing hooks: custom-value replacement, shortcodes, and front-end assets.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    LeadConnector
 * @subpackage LeadConnector/public
 */
class LeadConnector_Public {




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
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-leadconnector-constants.php';
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Add filters for custom value placeholders across various content types.

		// Post/Page Content.
		add_filter( 'the_content', array( $this, 'replace_custom_value_placeholders' ), 20 );
		add_filter( 'the_excerpt', array( $this, 'replace_custom_value_placeholders' ), 20 );

		// Titles (plain-text contexts — escape the full returned string).
		add_filter( 'the_title', array( $this, 'replace_custom_value_placeholders_in_text' ), 20 );
		add_filter( 'wp_title', array( $this, 'replace_custom_value_placeholders_in_text' ), 20 );
		add_filter( 'document_title_parts', array( $this, 'replace_custom_value_in_title_parts' ), 20 );
		add_filter( 'pre_get_document_title', array( $this, 'replace_custom_value_placeholders_in_text' ), 20 );

		// Widget Content.
		add_filter( 'widget_text', array( $this, 'replace_custom_value_placeholders' ), 20 );
		add_filter( 'widget_text_content', array( $this, 'replace_custom_value_placeholders' ), 20 );
		add_filter( 'widget_title', array( $this, 'replace_custom_value_placeholders_in_text' ), 20 );

		// Meta Description and SEO.
		add_filter( 'get_the_excerpt', array( $this, 'replace_custom_value_placeholders' ), 20 );
		add_filter( 'meta_description', array( $this, 'replace_custom_value_placeholders_in_text' ), 20 );

		// Block content (navigation blocks are intentionally excluded — see
		// replace_custom_value_in_blocks()).
		add_filter( 'render_block', array( $this, 'replace_custom_value_in_blocks' ), 20, 2 );

		// Comments.
		add_filter( 'comment_text', array( $this, 'replace_custom_value_placeholders' ), 20 );
		add_filter( 'comment_excerpt', array( $this, 'replace_custom_value_placeholders' ), 20 );

		// Elementor highlight functionality.
		add_action( 'wp_head', array( $this, 'inject_elementor_highlight_styles' ), 999 );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'leadconnector-public', plugin_dir_url( __FILE__ ) . 'css/leadconnector-public.css', array(), $this->version, 'all' );

		if ( get_query_var( 'elementor_highlight' ) === 'true' ) {
			wp_enqueue_style(
				'leadconnector-elementor-highlight',
				plugin_dir_url( __FILE__ ) . 'css/leadconnector-elementor-highlight.css',
				array(),
				$this->version
			);
		}
	}


	/**
	 * Validates and sanitizes content before processing
	 *
	 * @param mixed $content The content to validate.
	 * @return LeadConnector_Custom_Value_String_Replacable Sanitized content wrapper.
	 */
	private function sanitize_content( $content ): LeadConnector_Custom_Value_String_Replacable {

		// Handle null or empty content.
		if ( null === $content ) {
			return new LeadConnector_Custom_Value_String_Replacable( null, false );
		}

		// Handle WP_Post objects.
		if ( $content instanceof WP_Post ) {
			return new LeadConnector_Custom_Value_String_Replacable( $content, false );
		}

		// Handle arrays (like from ACF fields).
		if ( is_array( $content ) ) {
			return new LeadConnector_Custom_Value_String_Replacable( $content, false );
		}

		// Handle objects that implement __toString().
		if ( is_object( $content ) && method_exists( $content, '__toString' ) ) {
			return new LeadConnector_Custom_Value_String_Replacable( $content, false );
		}

		// Handle scalar values (string, int, float, bool).
		if ( is_scalar( $content ) ) {
			return new LeadConnector_Custom_Value_String_Replacable( $content, true );
		}

		// Return empty string for unsupported types.
		return new LeadConnector_Custom_Value_String_Replacable( '', false );
	}

	/**
	 * Replace {{custom_values.*}} placeholders for HTML output contexts.
	 *
	 * Each substituted value is individually escaped with esc_html() so it is
	 * safe to render inside HTML markup (e.g. the_content, widget_text). The
	 * surrounding content is left untouched — this method is intended for
	 * filters whose return value is rendered as HTML.
	 *
	 * @param mixed $content The content to process.
	 * @return mixed Content with placeholders replaced.
	 */
	public function replace_custom_value_placeholders( $content ) {
		return $this->replace_custom_value_placeholders_internal( $content, true );
	}

	/**
	 * Replace custom value placeholders in title / plain-text filter callbacks.
	 *
	 * Each substituted custom value is individually escaped with esc_html()
	 * while the surrounding content is left untouched. This prevents
	 * double-encoding of already-safe markup and avoids breaking third-party
	 * plugins (e.g. WPML) that legitimately inject HTML into title filters.
	 *
	 * @param mixed $content The content to process.
	 * @return mixed Content with placeholders replaced (values escaped).
	 */
	public function replace_custom_value_placeholders_in_text( $content ) {
		return $this->replace_custom_value_placeholders_internal( $content, true );
	}

	/**
	 * Shared placeholder substitution used by both the HTML- and text-context
	 * public wrappers.
	 *
	 * @param mixed $content              The content to process.
	 * @param bool  $escape_substitutions Whether to esc_html() each substituted
	 *                                    value individually. Pass true for HTML
	 *                                    output contexts and false when the
	 *                                    caller will escape the full result.
	 * @return mixed Content with placeholders replaced (substitutions
	 *               conditionally escaped per $escape_substitutions).
	 */
	private function replace_custom_value_placeholders_internal( $content, $escape_substitutions ) {
		$sanitized_content = $this->sanitize_content( $content );
		if ( ! $sanitized_content->is_valid() ) {
			return $content;
		}

		$content_string = $sanitized_content->get_content();
		if ( ! is_string( $content_string ) ) {
			return $content;
		}

		// PERFORMANCE (#C5): on every typical front-end render the vast
		// majority of strings contain no `{{custom_values.}}` marker. Skip
		// the regex pass and the LeadConnector_CustomValues construction
		// when the marker is absent. strpos() is ~50x cheaper than the
		// equivalent preg_match() and avoids touching the database.
		if ( false === strpos( $content_string, '{{' ) || false === strpos( $content_string, 'custom_values.' ) ) {
			return $content;
		}

		$custom_values = $this->get_custom_values_instance();
		if ( null === $custom_values ) {
			return $content;
		}

		try {
			return preg_replace_callback(
				'/\{\{\s*custom_values\.(\w+)\s*\}\}/',
				function ( $matches ) use ( $custom_values, $escape_substitutions ) {
					$field_key = trim( $matches[1] );
					if ( empty( $field_key ) ) {
						return '';
					}

					$value = $custom_values->get_value( $field_key );
					if ( null === $value ) {
						return '';
					}

					$value = (string) $value;
					return $escape_substitutions ? esc_html( $value ) : $value;
				},
				$content_string
			);
		} catch ( Exception $e ) {
			LeadConnector_Logger::get_instance()->warning(
				'Error in replace_custom_value_placeholders_internal: ' . $e->getMessage()
			);
			return $content;
		}
	}

	/**
	 * Lazy, per-request shared LeadConnector_CustomValues instance (#C5).
	 *
	 * The 15+ placeholder filters registered in the constructor previously
	 * called `require_once` and `new LeadConnector_CustomValues()` on every
	 * filter pass, which adds dozens of redundant object constructions per
	 * request on a typical archive page. This helper memoizes the instance
	 * for the lifetime of the request and returns null only when the
	 * underlying class file cannot be loaded.
	 *
	 * @return LeadConnector_CustomValues|null
	 */
	private function get_custom_values_instance() {
		static $cached_instance = null;
		static $resolved        = false;

		if ( $resolved ) {
			return $cached_instance;
		}

		require_once plugin_dir_path( __DIR__ ) . 'includes/CustomValues/class-leadconnector-customvalues.php';
		if ( class_exists( 'LeadConnector_CustomValues' ) ) {
			$cached_instance = new LeadConnector_CustomValues();
		}

		$resolved = true;
		return $cached_instance;
	}

	/**
	 * Replace custom value placeholders in document title parts
	 *
	 * @param array $title_parts The title parts array.
	 * @return array The processed title parts.
	 */
	public function replace_custom_value_in_title_parts( $title_parts ) {
		if ( ! is_array( $title_parts ) ) {
			return $title_parts;
		}

		foreach ( $title_parts as $key => $part ) {
			if ( is_string( $part ) ) {
				$title_parts[ $key ] = $this->replace_custom_value_placeholders_in_text( $part );
			}
		}
		return $title_parts; // This is escaped and data type is of type array.
	}

	/**
	 * Replace custom value placeholders in block content.
	 *
	 * Runs for every rendered block via the `render_block` filter. This is
	 * required because block themes / Full Site Editing render the home and
	 * other front-end templates block-by-block — `core/post-content` calls
	 * `do_blocks( get_the_content() )` directly and never triggers the
	 * `the_content` filter, so a placeholder typed into a Paragraph or
	 * Heading block on the home page would otherwise never be substituted.
	 *
	 * Navigation blocks (`core/navigation*`, `core/page-list`) are excluded
	 * because placeholder substitution in menu markup breaks theme navigation.
	 *
	 * Performance: a cheap substring check skips the regex pass entirely for
	 * blocks that do not contain a placeholder, which is the vast majority of
	 * blocks on a typical page.
	 *
	 * @param string $block_content The block content about to be rendered.
	 * @param array  $block         The full block, including name and attributes.
	 * @return string
	 */
	public function replace_custom_value_in_blocks( $block_content, $block ) {
		if ( $this->leadconnector_is_navigation_block( $block ) ) {
			return $block_content;
		}

		if ( ! is_string( $block_content ) || '' === $block_content ) {
			return $block_content;
		}

		if ( false === strpos( $block_content, 'custom_values.' ) ) {
			return $block_content;
		}

		return $this->replace_custom_value_placeholders( $block_content );
	}

	/**
	 * Whether a rendered block belongs to WordPress navigation UI.
	 *
	 * Custom-value substitution is skipped for these blocks because themes
	 * (e.g. Astra) and the block editor pass structured markup or non-scalar
	 * titles through navigation filters; rewriting that output breaks menus.
	 *
	 * @param array $block Full block payload from the render_block filter.
	 * @return bool
	 */
	private function leadconnector_is_navigation_block( $block ) {
		if ( ! is_array( $block ) || empty( $block['blockName'] ) || ! is_string( $block['blockName'] ) ) {
			return false;
		}

		$block_name = $block['blockName'];

		if ( 0 === strpos( $block_name, 'core/navigation' ) ) {
			return true;
		}

		return in_array( $block_name, array( 'core/page-list' ), true );
	}

	/**
	 * Register and enqueue the public-facing JavaScript.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {

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

		$options             = get_option( LEAD_CONNECTOR_OPTION_NAME );
		$heading             = '';
		$sub_heading         = '';
		$enabled_text_widget = 0;
		if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_ENABLE_TEXT_WIDGET ] ) ) {
			$enabled_text_widget = esc_attr( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_ENABLE_TEXT_WIDGET ] );
		}

		$text_widget_error = '';
		if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_ERROR ] ) ) {
			$text_widget_error = esc_attr( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_ERROR ] );
		}
		if ( get_query_var( 'elementor_highlight' ) === 'true' ) {
			wp_enqueue_script(
				'leadconnector-elementor-highlight',
				plugin_dir_url( __FILE__ ) . 'js/leadconnector-elementor-highlight.js',
				array(),
				$this->version,
				true // load in footer — DOM must be ready for element queries.
			);
		}

		if ( 1 === (int) $enabled_text_widget && isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_LOCATION_ID ] ) ) {
			$location_id = $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_LOCATION_ID ];

			if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_HEADING ] ) ) {
				$heading = esc_attr( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_HEADING ] );
			}
			if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_SUB_HEADING ] ) ) {
				$sub_heading = esc_attr( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_SUB_HEADING ] );
			}

			$use_email_field      = '0';
			$chat_widget_settings = null;
			$using_old_widget     = true;
			if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_SETTINGS ] ) ) {
				$chat_widget_settings = json_decode( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_SETTINGS ] );
				if ( ! isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_SELECTED_CHAT_WIDGET_ID ] ) ) {
					$widget_id = leadconnector_api_prop( $chat_widget_settings, 'widgetId' );
					if ( $chat_widget_settings && null !== $widget_id ) {
						$options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_SELECTED_CHAT_WIDGET_ID ] = $widget_id;
						update_option( LEAD_CONNECTOR_OPTION_NAME, $options );
					}
				}
			}
			if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_USE_EMAIL_FILED ] ) ) {
				$use_email_field = esc_attr( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_TEXT_WIDGET_USE_EMAIL_FILED ] );
			}

			if ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_SELECTED_CHAT_WIDGET_ID ] ) ) {
				$using_old_widget = false;
			}

			if ( $using_old_widget ) {
				wp_enqueue_script( 'leadconnector-text-widget', LEAD_CONNECTOR_CDN_BASE_URL . 'loader.js', array(), $this->version, false );
				wp_enqueue_script( 'leadconnector-public', plugin_dir_url( __FILE__ ) . 'js/leadconnector-public.js', array( 'jquery' ), $this->version, false );

				$safe_widget_settings = array();
				if ( is_object( $chat_widget_settings ) || is_array( $chat_widget_settings ) ) {
					$settings_array = (array) $chat_widget_settings;
					foreach ( $settings_array as $key => $value ) {
						if ( is_scalar( $value ) ) {
							$safe_widget_settings[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
						}
					}
				}

				wp_localize_script(
					'leadconnector-public',
					'leadconnector_public_js',
					array(
						'text_widget_location_id'     => sanitize_text_field( $location_id ),
						'text_widget_heading'         => $heading,
						'text_widget_sub_heading'     => $sub_heading,
						'text_widget_error'           => $text_widget_error,
						'text_widget_use_email_field' => $use_email_field,
						'text_widget_settings'        => $safe_widget_settings,
						'text_widget_cdn_base_url'    => esc_url_raw( LEAD_CONNECTOR_CDN_BASE_URL ),
					)
				);
			} elseif ( isset( $options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_SELECTED_CHAT_WIDGET_ID ] ) ) {

				$widget_id = sanitize_text_field(
					$options[ lead_connector_constants\LEADCONNECTOR_OPTIONS_SELECTED_CHAT_WIDGET_ID ]
				);

				$widget_src      = defined( 'LEADCONNECTOR_CHAT_WIDGET_SRC' ) ? esc_url_raw( LEADCONNECTOR_CHAT_WIDGET_SRC ) : '';
				$resources_url   = defined( 'LEADCONNECTOR_CHAT_WIDGET_RESOURCES_URL' ) ? esc_url_raw( LEADCONNECTOR_CHAT_WIDGET_RESOURCES_URL ) : '';
				$server_url      = defined( 'LEADCONNECTOR_CHAT_WIDGET_SERVER_URL' ) ? esc_url_raw( LEADCONNECTOR_CHAT_WIDGET_SERVER_URL ) : '';
				$marketplace_url = defined( 'LEADCONNECTOR_CHAT_WIDGET_MARKETPLACE_URL' ) ? esc_url_raw( LEADCONNECTOR_CHAT_WIDGET_MARKETPLACE_URL ) : '';

				wp_enqueue_script(
					'leadconnector-chat-widget',
					$widget_src,
					array(),
					$this->version,
					true
				);

				// Inject the required data-* attributes via script_loader_tag filter.
				add_filter(
					'script_loader_tag',
					function ( $tag, $handle ) use ( $widget_id, $resources_url, $server_url, $marketplace_url ) {
						if ( 'leadconnector-chat-widget' !== $handle ) {
							return $tag;
						}
						// Replace only the first <script occurrence to avoid double-replacement.
						return preg_replace(
							'/(<script\b)/i',
							'$1'
							. ' data-resources-url="' . esc_attr( $resources_url ) . '"'
							. ' data-widget-id="' . esc_attr( $widget_id ) . '"'
							. ' data-server-u-r-l="' . esc_attr( $server_url ) . '"'
							. ' data-marketplace-u-r-l="' . esc_attr( $marketplace_url ) . '"'
							. ' data-no-optimize="1" data-no-minify="1"',
							$tag,
							1
						);
					},
					10,
					2
				);
			}
		}
	}

	/**
	 * Inject Elementor highlight styles/scripts (stub — assets now enqueued).
	 *
	 * @since 1.0.0
	 */
	public function inject_elementor_highlight_styles() {
		/*
		 * Assets are now delivered by enqueue_styles() / enqueue_scripts() via
		 * wp_enqueue_style( 'leadconnector-elementor-highlight' ) and
		 * wp_enqueue_script( 'leadconnector-elementor-highlight' ).
		 * Nothing to output here.
		 */
	}
}
