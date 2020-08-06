<?php
/**
 * Plugin Name: Normalizer
 * Plugin URI: https://github.com/Zodiac1978/tl-normalizer
 * Description: Normalizes content, excerpt, title and comment content to Normalization Form C.
 * Version: 1.1.0
 * Author: Torsten Landsiedel
 * Author URI: http://torstenlandsiedel.de
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: normalizer
 * Domain Path: /languages
 *
 * @package WordPress\Normalizer
 */

/**
 * Normalize to NFC class
 */
class TLNormalizer {

	/**
	 * Constructor function for TLNormalizer.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'check_version' ) );

		// Don't run anything else in the plugin, if we're on an incompatible PHP.
		if ( ! self::compatible_version() ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ), 0 );

		// Adding function to all available sanitize filters.
		add_filter( 'sanitize_email', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_file_name', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_html_class', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_key', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_mime_type', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_sql_orderby', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_text_field', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_title', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_title_for_query', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_title_with_dashes', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'sanitize_user', array( $this, 'normalize_to_nfc' ) );

		// Adding function to title, content, comment and excerpt before save to db.
		add_filter( 'content_save_pre', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'title_save_pre', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'pre_comment_content', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'excerpt_save_pre', array( $this, 'normalize_to_nfc' ) );

		// Adding special function to widget callback.
		add_filter( 'widget_update_callback', array( $this, 'normalize_to_nfc_widget' ) );

		// Adding function to special case ACF values.
		add_filter( 'acf/update_value', array( $this, 'normalize_to_nfc' ) );

		// Adding function to output of Beaver Builder/Elementor (before save would be better but is not available).
		add_filter( 'fl_builder_render_module_content', array( $this, 'normalize_to_nfc' ) );
		add_filter( 'elementor/frontend/the_content', array( $this, 'normalize_to_nfc' ) );

	}

	/**
	 * Init when WordPress Initialises.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		// Set up localisation.
		$this->load_plugin_textdomain();
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.1.0
	 */
	private function load_plugin_textdomain() {
		if ( is_admin() ) {
			load_plugin_textdomain( 'normalizer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
	}

	/**
	 * The primary sanity check, automatically disable the plugin on activation if it doesn't meet minimum requirements.
	 *
	 * @return void
	 */
	private static function activation_check() {
		if ( ! self::compatible_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'Your PHP version is not 5.3.0 or later or your PHP is missing the required extension (intl).', 'normalizer' ) );
		}
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way, or the versions change after activation.
	 *
	 * @return void
	 */
	private function check_version() {
		if ( ! self::compatible_version() ) {
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				add_action( 'admin_notices', array( $this, 'disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	/**
	 * Show error message.
	 *
	 * @return void
	 */
	private function disabled_notice() {
		$error_message  = '<div class="notice notice-error is-dismissible">';
		$error_message .= '<p><strong>' . esc_html__( 'Plugin deactivated!', 'normalizer' ) . '</strong> ';
		$error_message .= esc_html__( 'Your PHP version is not 5.3.0 or later or your PHP is missing the required extension (intl).', 'normalizer' );
		$error_message .= '</p></div>';
		echo $error_message;
	}


	/**
	 * Compatible version check.
	 *
	 * @return bool
	 */
	private static function compatible_version() {

		// Add sanity checks for other version requirements here.
		if ( ! function_exists( 'normalizer_normalize' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Normalize to NFC
	 *
	 * @param  string $content Original content.
	 * @return string $content Normalized content.
	 */
	public function normalize_to_nfc( $content ) {

		/* Why?
		 *
		 * For everyone getting this warning from W3C: "Text run is not in Unicode Normalization Form C."
		 * http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form
		 *
		 * Requires PHP 5.3+
		 * Be sure to have the PHP-Normalizer-extension (intl and icu) installed.
		 * See: http://php.net/manual/en/normalizer.normalize.php
		 */
		if ( ! normalizer_is_normalized( $content, Normalizer::FORM_C ) ) {
			$content = normalizer_normalize( $content, Normalizer::FORM_C );
		}

		return $content;
	}

	/**
	 * Normalize to NFC for Arrays
	 *
	 * @param  array $instance Orignal array.
	 * @return array $instance Normalized array.
	 */
	public function normalize_to_nfc_widget( $instance ) {

		/*
		 * Why?
		 *
		 * For everyone getting this warning from W3C: "Text run is not in Unicode Normalization Form C."
		 * http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form
		 *
		 * Requires PHP 5.3+
		 * Be sure to have the PHP-Normalizer-extension (intl and icu) installed.
		 * See: http://php.net/manual/en/normalizer.normalize.php
		 */

		foreach ( $instance as $key => $value ) {
			if ( ! normalizer_is_normalized( $instance[ $key ], Normalizer::FORM_C ) ) {
				$instance[ $key ] = normalizer_normalize( $instance[ $key ], Normalizer::FORM_C );
			}
		}

		return $instance;
	}

}

global $normalizer;
$normalizer = new TLNormalizer();

register_activation_hook( __FILE__, array( 'TLNormalizer', 'activation_check' ) );
