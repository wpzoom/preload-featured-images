<?php
/**
 * Plugin Name: Preload Featured Images
 * Plugin URI:  https://wordpress.org/plugins/preload-featured-images/
 * Description: Preload featured images in single post to get higher PageSpeed Score.
 * Version:     1.1.0
 * Author:      WPZOOM
 * Author URI:  https://wpzoom.com/
 * Text Domain: preload-featured-images
 * License:     GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package WPZOOM_Preload_Featured_Images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The main PHP class for Preload Featured Images.
 */
final class WPZOOM_Preload_Featured_Images {

	/**
	 * This plugin's instance.
	 *
	 * @var WPZOOM_Preload_Featured_Images
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Featured image size
	 *
	 * @var string
	 */
	private static $featured_images_size;

	/**
	 * Featured image size for mobile
	 *
	 * @var string
	 */
	private static $featured_mobile_images_size;

	/**
	 * Theme name
	 *
	 * @var string
	 */
	private static $theme;

	/**
	 * Main WPZOOM_Preload_Featured_Images Instance.
	 *
	 * Insures that only one instance of WPZOOM_Preload_Featured_Images exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0.0
	 * @static
	 * @return object|WPZOOM_Preload_Featured_Images The one true WPZOOM_Preload_Featured_Images
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new WPZOOM_Preload_Featured_Images();
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

		add_action( 'wp_head', array( $this, 'preload_featured_images' ), 5 );
	
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
		load_plugin_textdomain( 'preload-featured-images', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add plugin page to menu settings
	 *
	 * @since 1.0.0
	 */
	public function add_settings_page() {

		add_options_page(
			'Preload Featured Images by WPZOOM',
			'Preload Featured Images',
			'manage_options',
			'preload-featured-images',
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
			wp_die( __( 'You do not have enough permission to view this page', 'preload-featured-images' ) );
		}

		printf('<div class="wrap">
			<h2>%s</h2>
			<ul>
				<li><a href="https://wordpress.org/support/plugin/preload-featured-images" target="_blank" >%s</a></li>
			</ul>', esc_html__( 'Preload Featured Images', 'preload-featured-images' ), esc_html__( 'Support Forum on WordPress.org', 'preload-featured-images' )
		);

		printf( '<form method="post" action="options.php">' );
		settings_fields( 'preload_featured_images_option_group' );
		do_settings_sections( 'preload-featured-images' );
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
				'preload_featured_images_option_group',
				'preload_featured_images_option_name',
				array( $this, 'sanitize_field' )
		);
		add_settings_section(
			'preload_featured_images_setting_section',
			esc_html__( 'Preload Featured Images Settings', 'preload-featured-images' ),
			array( $this, 'section_info' ),
			'preload-featured-images'
		);
		add_settings_field(
				'image_size',
				esc_html__( 'Featured Image Size', 'preload-featured-images'), array( $this, 'select_field_image_sizes' ),
				'preload-featured-images',
				'preload_featured_images_setting_section'
		);
	}

	/**
	 * Saniteze values from the inputs of the options form
	 *
	 * @since 1.0.0
	 */
	public function sanitize_field( $values ) {

		$sanitized   = array();
		$valid_sizes = $this->get_available_image_sizes();

		if ( isset( $values['image_size'] ) && in_array( $values['image_size'], $valid_sizes, true ) ) {
			$sanitized['image_size'] = $values['image_size'];
		}

		if ( isset( $values['mobile_image_size'] ) && in_array( $values['mobile_image_size'], $valid_sizes, true ) ) {
			$sanitized['mobile_image_size'] = $values['mobile_image_size'];
		}

		return $sanitized;
	}

	/**
	 * Get the list of selectable image sizes (registered sizes plus "full").
	 *
	 * @since 1.1.0
	 * @return string[]
	 */
	private function get_available_image_sizes() {
		$image_sizes   = get_intermediate_image_sizes();
		$image_sizes[] = 'full';
		return $image_sizes;
	}

	public function reset_option_values( $old_name, $old_theme ) {
		update_option( 'preload_featured_images_option_name', null );
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

		$default_size = $this->get_default_image_size();

		$pfi_options = get_option( 'preload_featured_images_option_name' );
		if( !isset( $pfi_options['image_size'] ) ) {
			update_option( 'preload_featured_images_option_name', array( 'image_size' => $default_size ) );
			self::$featured_images_size = $default_size;
		}
		else {
			self::$featured_images_size = $pfi_options['image_size'];
		}

	}

	/**
	 * Resolve the default featured image size for the current theme.
	 *
	 * Pure helper with no database writes, so it can run on the front end
	 * (where `admin_init` never fires) as well as on the settings page.
	 *
	 * @since 1.1.0
	 * @return string Registered image size name, or '' if the theme is unknown.
	 */
	private function get_default_image_size() {

		$default_size  = '';
		$wpzoom_themes = array(
			'foodica',
			'foodica-pro',
			'wpzoom-cookely',
			'wpzoom-gourmand'
		);
		$other_themes = array(
			'ashe'          => 'ashe-full-thumbnail',
			'astra'         => 'large',
			'divi'          => 'et-pb-post-main-image-fullwidth',
			'neve'          => 'neve-blog',
			'generatepress' => 'full',
			'oceanwp'       => 'full'
		);
		$other_themes = apply_filters( 'preload_featured_images_themes_sizes', $other_themes );

		$current_theme = get_template();

		if( in_array( $current_theme, $wpzoom_themes, true ) ) {
			if( 'wpzoom-cookely' === $current_theme || 'wpzoom-gourmand' === $current_theme ) {
				$default_size = 'single-normal';
			}
			elseif( 'foodica' === $current_theme || 'foodica-pro' === $current_theme ) {
				$default_size = class_exists( 'WPZOOM' ) ? 'loop-large' : 'foodica-loop-sticky';
			}
		}
		elseif( isset( $other_themes[ $current_theme ] ) ) {
			$default_size = $other_themes[ $current_theme ];
		}

		return $default_size;
	}

	/**
	 * The select field of the featured image size
	 *
	 * @since 1.0.0
	 */
	public function select_field_image_sizes() {

		$pfi_options = get_option( 'preload_featured_images_option_name' );
		if( empty( $pfi_options['mobile_image_size'] ) ) {
			self::$featured_mobile_images_size = self::$featured_images_size;
		}
		else {
			self::$featured_mobile_images_size = $pfi_options['mobile_image_size'];
		}

		//print_r( self::$featured_mobile_images_size );

		$image_sizes = $this->get_available_image_sizes();

		echo '<select name="preload_featured_images_option_name[image_size]" id="wpzoom_preload_featured_images_size">';
		foreach( $image_sizes as $size ) { 
			echo '<option ' . selected( $size, self::$featured_images_size, false ) . ' value="' . esc_attr( $size ) . '">' . esc_html( $size ) . '</option>';
		}
		echo '</select><p class="description">'. wp_kses_post( __( 'Select the image size for the Featured Image on single post pages <strong>(Desktop)</strong>', 'preload-featured-images' ) ) . '</p>';

		echo '</br>';
		echo '<select name="preload_featured_images_option_name[mobile_image_size]" id="wpzoom_preload_featured_mobile_images_size">';
		foreach( $image_sizes as $size ) { 
			echo '<option ' . selected( $size, self::$featured_mobile_images_size, false ) . ' value="' . esc_attr( $size ) . '">' . esc_html( $size ) . '</option>';
		}
		echo '</select><p class="description">'. wp_kses_post( __( 'Select the image size for the Featured Image on single post pages <strong>(Mobile)</strong>', 'preload-featured-images' ) ) . '</p>';

	}


	/**
	 * Preload featured image for single posts
	 *
	 * @since 1.0.0
	 */
	public function preload_featured_images() {

		global $post;

		/** Prevent preloading for specific content types or post types */
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		/** Get post thumbnail if an attachment ID isn't specified. */
		$thumbnail_id = apply_filters( 'preload_featured_images_id', get_post_thumbnail_id( $post->ID ), $post );

		if ( ! $thumbnail_id ) {
			return;
		}

		$pfi_options  = get_option( 'preload_featured_images_option_name' );
		$default_size = $this->get_default_image_size();

		/** Resolve sizes independently of the admin (admin_init never fires on the front end). */
		$desktop_size = ! empty( $pfi_options['image_size'] ) ? $pfi_options['image_size'] : $default_size;
		$mobile_size  = ! empty( $pfi_options['mobile_image_size'] ) ? $pfi_options['mobile_image_size'] : $desktop_size;

		$desktop_size = apply_filters( 'preload_featured_images_size', $desktop_size, $post );
		$mobile_size  = apply_filters( 'preload_featured_images_mobile_size', $mobile_size, $post );

		if ( empty( $desktop_size ) && empty( $mobile_size ) ) {
			return;
		}

		/**
		 * Emit cache-safe responsive preloads using `media` attributes instead of
		 * sniffing the User-Agent with wp_is_mobile(), which is unreliable behind
		 * any page cache: the first visitor's device would decide what is cached
		 * for everyone. The browser evaluates the media query itself, so the same
		 * cached HTML works correctly for every device.
		 */
		if ( $desktop_size === $mobile_size ) {
			$this->print_preload_link( $thumbnail_id, $desktop_size );
		}
		else {
			$breakpoint = (int) apply_filters( 'preload_featured_images_mobile_breakpoint', 600 );
			$this->print_preload_link( $thumbnail_id, $mobile_size, sprintf( '(max-width: %dpx)', $breakpoint ) );
			$this->print_preload_link( $thumbnail_id, $desktop_size, sprintf( '(min-width: %dpx)', $breakpoint + 1 ) );
		}

	}

	/**
	 * Print a single `<link rel="preload" as="image">` tag for the featured image.
	 *
	 * The srcset/sizes generation mirrors wp_get_attachment_image() so the
	 * preloaded resource matches the one the browser ultimately renders,
	 * avoiding a wasteful double download.
	 *
	 * `fetchpriority="high"` is REQUIRED on the link: a preloaded image without
	 * it is fetched at Low priority, and because the preload is discovered first
	 * (in the head) it wins the request — so the LCP image would download at Low
	 * priority even though the rendered <img> carries fetchpriority="high".
	 *
	 * @since 1.1.0
	 *
	 * @param int    $thumbnail_id Attachment ID of the featured image.
	 * @param string $image_size   Registered image size to preload.
	 * @param string $media        Optional media query to scope the preload to a breakpoint.
	 */
	private function print_preload_link( $thumbnail_id, $image_size, $media = '' ) {

		if ( empty( $image_size ) ) {
			return;
		}

		$image = wp_get_attachment_image_src( $thumbnail_id, $image_size );

		if ( ! $image ) {
			return;
		}

		list( $src, $width, $height ) = $image;

		$attributes = array();
		$image_meta = wp_get_attachment_metadata( $thumbnail_id );

		if ( is_array( $image_meta ) ) {
			$size_array = array( absint( $width ), absint( $height ) );
			$srcset     = wp_calculate_image_srcset( $size_array, $src, $image_meta, $thumbnail_id );
			$sizes      = wp_calculate_image_sizes( $size_array, $src, $image_meta, $thumbnail_id );

			if ( $srcset && $sizes ) {
				$attributes['imagesrcset'] = $srcset;
				$attributes['imagesizes']  = $sizes;
			}
		}

		if ( ! empty( $media ) ) {
			$attributes['media'] = $media;
		}

		$attr_html = '';
		foreach ( $attributes as $name => $value ) {
			$attr_html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}

		printf( '<link rel="preload" as="image" fetchpriority="high" href="%s"%s />' . "\n", esc_url( $src ), $attr_html );
	}

}

add_action( 'init', 'WPZOOM_Preload_Featured_Images::instance' );