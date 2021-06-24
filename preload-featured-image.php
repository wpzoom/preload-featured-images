<?php
/**
 * Plugin Name: Preload Featured Image by WPZOOM
 * Plugin URI:  https://wordpress.org/plugins/preload-featured-image
 * Description: Prelod featured image on single post to get faster page loading experience.
 * Version:     1.0.0
 * Author:      WPZOOM
 * Author URI:  http://wpzoom.com/
 * Text Domain: preload-featured-image
 * License:     GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package WPZOOM_Preload_Featured_Image
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The main PHP class for Preload Featured Image.
 */
final class WPZOOM_Preload_Featured_Image {

	/**
	 * This plugin's instance.
	 *
	 * @var WPZOOM_Preload_Featured_Image
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Featured image size
	 *
	 * @var string
	 */
	private static $featured_image_size;

	/**
	 * Theme name
	 *
	 * @var string
	 */
	private static $theme;

	/**
	 * Main WPZOOM_Preload_Featured_Image Instance.
	 *
	 * Insures that only one instance of WPZOOM_Preload_Featured_Image exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static
	 * @return object|WPZOOM_Preload_Featured_Image The one true WPZOOM_Preload_Featured_Image
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPZOOM_Preload_Featured_Image();
		}
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'init', array( $this, 'i18n' ) );

		add_action( 'after_switch_theme', array( $this, 'reset_option_values' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'option_panel_init' ) );

		add_action( 'wp_head', array( $this, 'preload_featured_image' ), 5 );
	
	}

	/**
	 * Load Textdomain
	 *
	 * Load plugin localization files.
	 *
	 * Fired by `init` action hook.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function i18n() {
		load_plugin_textdomain( 'preload-featured-image', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add plugin page to menu settings
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {

		add_options_page(
			'Preload Featured Image by WPZOOM',
			'Preload Featured Image',
			'manage_options',
			'preload-featured-image',
			array( $this, 'create_settings_page' )
		);

	}

	/**
	 * Add settings panel to select the size for the feature image
	 *
	 * @since 1.0.0
	 */
	public function create_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ){
			wp_die( __( 'You do not have enough permission to view this page', 'preload-featured-image' ) );
		}

		printf('<div class="wrap">
			<h2>%s</h2>
			<ul>
				<li><a href="https://wordpress.org/support/plugin/preload-featured-image" target="_blank" >%s</a></li>
			</ul>', esc_html__( 'Preload Featured Image', 'preload-featured-image' ), esc_html__( 'Support Forum on WordPress.org', 'preload-featured-image' )
		);

		settings_errors();
		printf( '<form method="post" action="options.php">' );
		settings_fields( 'preload_featured_image_option_group' );
		do_settings_sections( 'preload-featured-image' );
		submit_button();
		printf( '</form></div>' );

	}

	/**
	 * Init options fields and sections
	 *
	 * @since 1.0.0
	 */
	public function option_panel_init() {

		$this->check_theme();

		register_setting(
				'preload_featured_image_option_group',
				'preload_featured_image_option_name',
				array( $this, 'sanitize_field' )
		);
		add_settings_section(
			'preload_featured_image_setting_section',
			esc_html__( 'Preload Featured Image Settings', 'preload-featured-image' ),
			array( $this, 'section_info' ),
			'preload-featured-image'
		);
		add_settings_field(
				'image_size',
				esc_html__( 'Featured Image Size', 'preload-featured-image'), array( $this, 'select_field_image_sizes' ),
				'preload-featured-image',
				'preload_featured_image_setting_section'
		);
	}

	/**
	 * Saniteze values from the inputs of the options form
	 *
	 * @since 1.0.0
	 */
	public function sanitize_field( $values ) {
		return $values;
	}

	public function reset_option_values( $old_name, $old_theme ) {
		update_option( 'preload_featured_image_option_name', null );
	}

	/**
	 * Output the section info
	 *
	 * @since 1.0.0
	 */
	public function section_info() {}

	/**
	 * Check if the theme is WPZOOM theme and set the correct image size for the featured image
	 *
	 * @since 1.0.0
	 */
	private function check_theme() {

		$default_size = 'full';
		$wpzoom_themes = array(
			'foodica',
			'foodica-pro',
			'wpzoom-cookely',
			'wpzoom-gourmand'
		);

		$current_theme = get_template();

		if( in_array( $current_theme, $wpzoom_themes ) ) {
			if( 'wpzoom-cookely' == $current_theme || 'wpzoom-gourmand' == $current_theme ) {
				$default_size = 'single-normal';
			}
			elseif( 'foodica' == $current_theme || 'foodica-pro' == $current_theme ) {
				if( class_exists( 'WPZOOM' ) ) {
					$default_size = 'loop-large';
				}
				else {
					$default_size = 'foodica-loop-sticky';
				}
			}
		}

		$pfi_options = get_option( 'preload_featured_image_option_name' );
		if( !isset( $pfi_options['image_size'] ) ) {
			update_option( 'preload_featured_image_option_name', array( 'image_size' => $default_size ) );
			self::$featured_image_size = $default_size;
		}
		else {
			self::$featured_image_size = $pfi_options['image_size'];
		}
		

	}

	/**
	 * The select field of the featured image size
	 *
	 * @since 1.0.0
	 */
	public function select_field_image_sizes() {

		$html_field = '';
	
		global $_wp_additional_image_sizes;
		$image_sizes = get_intermediate_image_sizes();

		$html_field = '<select name="preload_featured_image_option_name[image_size]" id="wpzoom_preload_featured_image_size">';
		foreach( $image_sizes as $size ) { 
			$html_field .=	'<option ' . selected( $size, self::$featured_image_size, false ) . ' value="' . $size . '">' . ucfirst( str_replace( '-', ' ', $size ) ) . '</option>';
		}
		$html_field .=	'</select>';
		$html_field .=	'<p class="description">'. wp_kses_post( __( 'Please, select the correct image size for the featured image on the single post', 'preload-featured-image' ) ) . '</p>';

		echo $html_field;

	}


	/**
	 * Preload featured image for single posts
	 *
	 * @since 1.0.0
	 */
	public function preload_featured_image() {

		global $post;
		
		/** Prevent preloading for specific content types or post types */
		if ( ! is_singular( 'post' ) ) {
			return;
		}
		$pfi_options = get_option( 'preload_featured_image_option_name' );
	
		/** Adjust image size based on post type or other factor. */
		$image_size = isset( $pfi_options['image_size'] ) ? $pfi_options['image_size'] : self::$featured_image_size;
		
		$image_size = apply_filters( 'preload_featured_image_size', $image_size, $post );
		
		/** Get post thumbnail if an attachment ID isn't specified. */
		$thumbnail_id = apply_filters( 'preload_featured_image_id', get_post_thumbnail_id( $post->ID ), $post );

		/** Get the image */
		$image = wp_get_attachment_image_src( $thumbnail_id, $image_size );
		$src = '';
		$additional_attr_array = array();
		$additional_attr = '';

		if ( $image ) {
			list( $src, $width, $height ) = $image;

			/**
			 * The following code which generates the srcset is plucked straight
			 * out of wp_get_attachment_image() for consistency as it's important
			 * that the output matches otherwise the preloading could become ineffective.
			 */
			$image_meta = wp_get_attachment_metadata( $thumbnail_id );

			if ( is_array( $image_meta ) ) {
				$size_array = array( absint( $width ), absint( $height ) );
				$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $thumbnail_id );
				$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $thumbnail_id );

				if ( $srcset && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
					$additional_attr_array['imagesrcset'] = $srcset;

					if ( empty( $attr['sizes'] ) ) {
						$additional_attr_array['imagesizes'] = $sizes;
					}
				}
			}

			foreach ( $additional_attr_array as $name => $value ) {
				$additional_attr .= "$name=" . '"' . $value . '" ';
			}

		} else {
			/** Early exit if no image is found. */
			return;
		}

		/** Output the link HTML tag */
		printf( '<link rel="preload" as="image" href="%s" %s />', esc_url( $src ), $additional_attr );

	}

}

add_action( 'init', 'WPZOOM_Preload_Featured_Image::instance' );