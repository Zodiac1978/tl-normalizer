<?php
/**
 * Plugin Name: Normalizer
 * Plugin URI: https://github.com/Zodiac1978/tl-normalizer
 * Description: Normalizes UTF-8 input to Normalization Form C.
 * Version: 2.0.6
 * Author: Torsten Landsiedel
 * Author URI: http://torstenlandsiedel.de
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: normalizer
 * Domain Path: /languages
 */
define( 'TLN_VERSION', '2.0.6' );

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Why?
 *
 * For everyone getting this warning from W3C: "Text run is not in Unicode Normalization Form C."
 * http://www.w3.org/International/docs/charmod-norm/#choice-of-normalization-form
 *
 * As falling back to polyfill it's not required to have PHP 5.3+ or have the PHP-Normalizer-extension (intl and icu) installed.
 * But for performance reasons it's best.
 * See: http://php.net/manual/en/normalizer.normalize.php
 */

// See https://core.trac.wordpress.org/ticket/30130
// See also https://github.com/tinymce/tinymce/issues/1971

/*
Thank you very much for this code, Gary Pendergast!
http://pento.net/2014/02/18/dont-let-your-plugin-be-activated-on-incompatible-sites/
*/

class TLNormalizer {

	static $plugin_basename = null; // Handy for testing.

	// Use a high (ie low number) priority to beat other filters.
	var $priority = 6;

	// Trying to choose the earliest filter available, in 'db' context, so other filters can assume normalized input.
	var $post_filters = array(
		'pre_post_content', 'pre_post_title', 'pre_post_excerpt', /*'pre_post_password',*/ 'pre_post_name', 'pre_post_meta_input', 'pre_post_trackback_url',
	);

	var $comment_filters = array(
		'pre_comment_author_name', 'pre_comment_content', 'pre_comment_author_url', 'pre_comment_author_email',
	);

	var $user_filters = array(
		'pre_user_login', 'pre_user_nicename', 'pre_user_url', 'pre_user_email', 'pre_user_nickname',
		'pre_user_first_name', 'pre_user_last_name', 'pre_user_display_name', 'pre_user_description',
	);

	var $term_filters = array(
		'pre_term_name', 'pre_term_description', 'pre_term_slug',
	);

	// Whether to normalize all options.
	var $do_all_options = true;

	// Or just the WP standard texty ones.
	var $options_filters = array(
		// General.
		'pre_update_option_blogname', 'pre_update_option_blogdescription', 'pre_update_option_admin_email', 'pre_update_option_siteurl', 'pre_update_option_home',
		'pre_update_option_date_format', 'pre_update_option_time_format',
		// Writing. (Non-multisite only.)
		'pre_update_option_mailserver_url', 'pre_update_option_mailserver_url', 'pre_update_option_mailserver_login', /*'pre_update_option_mailserver_pass',*/  'pre_update_option_ping_sites',
		// Nothing texty in Reading.
		// Discussion.
		'pre_update_option_moderation_keys', 'pre_update_option_blacklist_keys',
		// Nothing texty in Media.
		// Permalinks.
		'pre_update_option_permalink_structure', 'pre_update_option_category_base', 'pre_update_option_tag_base',
	);

	var $settings_filters = array( // Network settings (multisite only).
		'pre_update_site_option_blogname', 'pre_update_site_option_blogdescription', 'pre_update_site_option_admin_email', 'pre_update_site_option_siteurl', 'pre_update_site_option_home',
		'pre_update_site_option_site_name', 'pre_update_site_option_new_admin_email', 'pre_update_site_option_illegal_names',
		/*'pre_update_site_option_limited_email_domains',*/ /*'pre_update_site_option_banned_email_domains',*/ // Stripped to ASCII.
		'pre_update_site_option_welcome_email', 'pre_update_site_option_welcome_user_email', 'pre_update_site_option_first_post',
		'pre_update_site_option_first_page', 'pre_update_site_option_first_comment', 'pre_update_site_option_first_comment_author', 'pre_update_site_option_first_comment_url',
	);

	var $menus_filters = array(
		'pre_term_name', 'pre_term_description', 'pre_term_slug', // For the menu.
		'pre_post_content', 'pre_post_title', 'pre_post_excerpt', // For menu items.
	);

	var $widget_filters = array(); // Uses 'widget_update_callback' filter.

	var $permalink_filters = array( 'sanitize_title' );

	var $customize_filters = array(); // None for initial 'customize' preview. For 'customize_save' uses options, settings, menus & widget filters.

	// These are set on 'init' action.
	var $base = ''; // Simplified $pagenow.
	var $added_filters = array(); // Array of whether filters added or not per base.

	// For testing/debugging.
	static $not_compat = false;
	var $dont_js = false, $dont_paste = false, $dont_filter = false, $no_normalizer = false;

	/**
	 * Check system compatibility, add 'init' action.
	 */
	function __construct() {

		if ( null === self::$plugin_basename ) {
			self::$plugin_basename = plugin_basename( __FILE__ );
		}

		add_action( 'admin_init', array( $this, 'check_version' ) );

		// Don't run anything else in the plugin, if we're on an incompatible system.
		if ( ! self::compatible_version() ) {
			return;
		}

		// Only need if using UTF-8.
		if ( ! in_array( get_option( 'blog_charset' ), array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * The primary sanity check, automatically disable the plugin on activation if it doesn't
	 * meet minimum requirements.
	 */
	static function activation_check() {
		if ( ! self::compatible_version() ) {
			deactivate_plugins( self::$plugin_basename );
			wp_die( __( 'The plugin "Normalizer" is not compatible with your system and can\'t be activated.', 'normalizer' ) );
		}
	}

	/**
	 * Called on 'admin_init' action.
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the versions change after activation.
	 */
	function check_version() {
		if ( ! self::compatible_version() ) {
			if ( is_plugin_active( self::$plugin_basename ) ) {
				deactivate_plugins( self::$plugin_basename );
				$admin_notices_filter = is_network_admin() ? 'network_admin_notices' : ( is_user_admin() ? 'user_admin_notices' : 'admin_notices' );
				add_action( $admin_notices_filter, array( $this, 'disabled_notice' ) );
				if ( isset( $_GET['activate'] ) ) {
					unset( $_GET['activate'] );
				}
			}
		}
	}

	/**
	 * Called on 'network_admin_notices', 'user_admin_notices' or 'admin_notices' action.
	 */
	function disabled_notice() {
		$error_message  = '<div id="message" class="updated notice is-dismissible">';
		$error_message .= '<p><strong>' . __( 'Plugin deactivated!', 'normalizer' ) . '</strong> ';
		$error_message .= esc_html__( 'The plugin "Normalizer" is not compatible with your system and has been deactivated.', 'normalizer' );
		$error_message .= '</p></div>';
		echo $error_message;
	}

	/**
	 * Whether compatible with this system.
	 */
	static function compatible_version() {

		// Totally compat! (Famous last words.)
		return ! self::$not_compat; // For testing.
	}

	/**
	 * Called on 'init' action.
	 */
	function init() {
		tln_debug_log( "dont_js=", $this->dont_js, ", dont_paste=", $this->dont_paste, ", dont_filter=", $this->dont_filter, ", no_normalizer=", $this->no_normalizer );

		// Only add filters on admin.
		if ( ! $this->dont_filter && is_admin() ) {

			$this->base = $this->get_base();
			$this->added_filters = array();

			// Posts.
			if ( 'post' === $this->base ) {
				$this->added_filters['post'] = true;

				foreach( $this->post_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}

				// Meta data needs its own filter as has its own meta_id => key/value array format.

				// Called on sanitize_post() in wp_insert_post().
				add_filter( 'pre_post_meta', array( $this, 'pre_post_meta' ), $this->priority ); // Seems to be no-op but leave it in for the mo.

				// However, the result of the above is not actually used to update the meta it seems,
				// so add individual filters based on the 'meta' field of $_POST, which is used when updating existing meta data.
				if ( ! empty( $_POST['meta'] ) && is_array( $_POST['meta'] ) ) {
					$this->add_sanitize_metas( $_POST['meta'] );
				}

				// New meta data (add new custom field metabox) uses 'metakeyselect'/'metakeyinput' and 'metavalue' fields of $_POST.
				if ( isset( $_POST['metavalue'] ) && '' !== $_POST['metavalue'] ) {
					$metakey = ! empty( $_POST['metakeyselect'] ) && '#NONE#' !== $_POST['metakeyselect'] ? $_POST['metakeyselect']
									: ( ! empty( $_POST['metakeyinput'] ) ? $_POST['metakeyinput'] : '' );
					if ( '' !== $metakey ) {
						// Put into (no id) => key/value array format.
						$this->add_sanitize_metas( array( array( 'key' => $metakey, 'value' => $_POST['metavalue'] ) ) );
					}
				}

				// For tags (post metabox). Has its own id/term array format.
				add_filter( 'pre_post_tax_input', array( $this, 'pre_post_tax_input' ), $this->priority );

				// For special image alt meta.
				add_filter( 'sanitize_post_meta__wp_attachment_image_alt', array( $this, 'sanitize_meta' ), $this->priority, 3 );
				// For attachment metadata.
				add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), $this->priority, 2 );
			}

			// Comments.
			if ( 'comment' === $this->base || 'post' === $this->base ) {
				$this->added_filters['comment'] = true;

				foreach( $this->comment_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}

				// Comment meta seems to be just internal '_wp_XXX' data.
				// add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ), $this->priority );
			}

			// Users.
			if ( 'user' === $this->base ) {
				$this->added_filters['user'] = true;

				foreach( $this->user_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}

				global $wp_version;
				if ( version_compare( $wp_version, '4.4', '>=' ) ) { // 'insert_user_meta' only available for WP >= 4.4
					// Normalize the user meta. Some are done already by the $user_filters - 'pre_user_nickname' etc.
					// Also, we can (mis-)use the 'insert_user_meta' filter to add sanitize filters for contact methods (using the passed-in $user).
					add_filter( 'insert_user_meta', array( $this, 'insert_user_meta' ), $this->priority, 3 );
				} else {
					// TODO: Anything possible?
				}
			}

			// Categories and tags.
			if ( 'term' === $this->base ) {
				$this->added_filters['term'] = true;

				foreach( $this->term_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}

				// Term meta data seems to be programmatic only currently.
			}

			// Options.
			if ( 'options' === $this->base || 'customize_save' === $this->base ) {
				$this->added_filters['options'] = true;

				if ( $this->do_all_options ) {
					add_filter( 'pre_update_option', array( $this, 'pre_update_option' ), $this->priority, 3 );
				} else {
					foreach( $this->options_filters as $filter ) {
						add_filter( $filter, array( $this, 'pre_update_option_option' ), $this->priority, 3 );
					}
				}
			}

			// Ajax preview of date/time options.
			if ( 'date_format' === $this->base ) {
				$this->added_filters['date_format'] = true;

				add_filter( 'sanitize_option_date_format', array( $this, 'sanitize_option_option' ), $this->priority, 3 );
			}
			if ( 'time_format' === $this->base ) {
				$this->added_filters['$time_format'] = true;

				add_filter( 'sanitize_option_time_format', array( $this, 'sanitize_option_option' ), $this->priority, 3 );
			}

			// Network settings. (Multisite only.)
			if ( 'settings' === $this->base || 'customize_save' === $this->base ) {
				$this->added_filters['settings'] = true;

				foreach( $this->settings_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}
			}

			// Menus.
			if ( 'menus' === $this->base || 'customize_save' === $this->base ) {
				$this->added_filters['menus'] = true;

				foreach( $this->menus_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}

				// sanitize_html_class() strips down to ASCII so not needed.
				// add_filter( 'sanitize_html_class', array( $this, 'sanitize_meta' ), $this->priority, 3 );
			}

			// Widgets.
			if ( 'widget' === $this->base || 'customize_save' === $this->base ) {
				$this->added_filters['widget'] = true;

				foreach( $this->widget_filters as $filter ) { // No-op.
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority ); // @codeCoverageIgnore
				}
				add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), $this->priority, 4 );
			}

			// Permalink (ajax).
			if ( 'permalink' === $this->base || 'customize_save' === $this->base ) {
				$this->added_filters['permalink'] = true;

				foreach( $this->permalink_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}
			}

			// Customizer.
			if ( 'customize' === $this->base ) { // Note this is for the db read-only preview stage. Base will be 'customize_save' on db write.
				// $this->added_filters['customize'] = true; // Nothing at the mo.

				foreach( $this->customize_filters as $filter ) { // No-op.
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority ); // @codeCoverageIgnore
				}
			}

			// TODO: other filters?

			// Allow easy add of extra filters.
			$extra_filters = array();
			$extra_filters = apply_filters( 'tln_extra_filters', $extra_filters );
			if ( $extra_filters ) {
				$this->added_filters['extra_filters'] = true;

				foreach( $extra_filters as $filter ) {
					add_filter( $filter, array( $this, 'tl_normalizer' ), $this->priority );
				}
			}

			if ( $this->added_filters ) {
				if ( $this->no_normalizer || ! function_exists( 'normalizer_is_normalized' ) ) {

					$this->load_tln_normalizer_class();
				}
			}
		}

		if ( ! $this->dont_js ) {
			if ( is_admin() ) {
				if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				}
			} else {
				tln_debug_log( "add action wp_enqueue_scripts" );
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}

		tln_debug_log( "base=", $this->base, ", added_filters=", $this->added_filters );
	}

	/**
	 * De filter.
	 */
	function tl_normalizer( $content ) {

		if ( ! empty( $content ) ) {

			if ( is_string( $content ) ) {

				if ( $this->no_normalizer ) { // For testing when have PHP Normalizer installed.
					if ( ! tl_normalizer_is_normalized( $content ) ) {
						$normalized = tl_normalizer_normalize( $content );

						tln_debug_log( $normalized === $content ? "no_normalizer same" : ( "no_normalizer differ\n   content=" . bin2hex( $content ) . "\nnormalized=" . bin2hex( $normalized ) ) );

						if ( false !== $normalized ) { // Not validating so don't set on error.
							$content = $normalized;
						}
					} else {
						tln_debug_log( "no_normalizer is_normalized content=" . bin2hex( $content ) );
					}
				} else {
					if ( ! normalizer_is_normalized( $content ) ) {
						$normalized = normalizer_normalize( $content );

						tln_debug_log( $normalized === $content ? "normalizer same" : ( "normalizer differ\n   content=" . bin2hex( $content ) . "\nnormalized=" . bin2hex( $normalized ) ) );

						if ( false !== $normalized ) { // Not validating so don't set on error.
							$content = $normalized;
						}
					} else {
						tln_debug_log( "normalizer is_normalized content=" . bin2hex( $content ) );
					}
				}

			} elseif ( is_array( $content ) ) { // Allow for arrays.
				foreach ( $content as $key => $value ) {
					if ( ! empty( $value ) && ( is_string( $value ) || is_array( $value ) ) ) {
						$content[ $key ] = $this->tl_normalizer( $value ); // Recurse.
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Load the TLN_Normalizer class.
	 */
	function load_tln_normalizer_class() {

		$dirname = dirname( __FILE__ );

		// Load the Symfony polyfill https://github.com/symfony/polyfill/tree/master/src/Intl/Normalizer
		require $dirname . '/Symfony/Normalizer.php';

		if ( ! function_exists( 'normalizer_is_normalized' ) ) {

			function normalizer_is_normalized( $s, $form = TLN_Normalizer::NFC ) {
				return TLN_Normalizer::isNormalized( $s, $form );
			}

			function normalizer_normalize( $s, $form = TLN_Normalizer::NFC ) {
				return TLN_Normalizer::normalize( $s, $form );
			}
		}

		if ( $this->no_normalizer ) { // For testing when have PHP Normalizer.

			if ( ! function_exists( 'tl_normalizer_is_normalized' ) ) {

				function tl_normalizer_is_normalized( $s, $form = TLN_Normalizer::NFC ) {
					return TLN_Normalizer::isNormalized( $s, $form );
				}

				function tl_normalizer_normalize( $s, $form = TLN_Normalizer::NFC ) {
					return TLN_Normalizer::normalize( $s, $form );
				}
			}
		}
	}

	/**
	 * Called on 'pre_post_meta' filter.
	 * Filter for post meta data. Which although called seems isn't actually used to update the post meta. Fallback is add_sanitize_meta() below.
	 */
	function pre_post_meta( $arr ) {
		if ( is_array( $arr ) ) {

			// Allow exclusion of keys.
			$exclude_keys = array_flip( apply_filters( 'tln_exclude_post_meta_keys', array(), $arr, 'pre_post_meta' /*context*/ ) );

			foreach ( $arr as $meta_id => $entry ) {
				if ( isset( $entry['key'] ) && is_string( $entry['key'] ) && ! empty( $entry['value'] ) ) {
					$key = wp_unslash( $entry['key'] ); // NOTE: meta keys WON'T be normalized (not sanitized by WP).
					$value = $entry['value']; // Will be slashed (single/double quote, backslash & nul) but doesn't affect normalization so don't bother unslashing/reslashing.

					if ( '' !== $key && '_' !== $key[0] && ! isset( $exclude_keys[ $key ] ) ) {
						$arr[ $meta_id ] = array( 'key' => $key, 'value' => $this->tl_normalizer( $value ) );
					}
				}
			}
		}

		return $arr;
	}

	/**
	 * Add individual filters for metas. Also fallback for above, seeing as it doesn't seem to do anything.
	 * Note passed in raw $_POST array, same meta_id (if available) => key/value format as above.
	 */
	function add_sanitize_metas( $arr ) {

		// Allow exclusion of keys.
		$exclude_keys = array_flip( apply_filters( 'tln_exclude_post_meta_keys', array(), $arr, 'add_sanitize_metas' /*context*/ ) );

		foreach ( $arr as $entry ) {
			if ( isset( $entry['key'] ) && is_string( $entry['key'] ) && ! empty( $entry['value'] ) ) {
				$key = wp_unslash( $entry['key'] ); // NOTE: meta keys WON'T be normalized (not sanitized by WP).

				if ( '' !== $key && '_' !== $key[0] && ! isset( $exclude_keys[ $key ] ) ) {
					add_filter( "sanitize_post_meta_$key", array( $this, 'sanitize_meta' ), $this->priority, 3 );
				}
			}
		}

		return $arr;
	}

	/**
	 * Called on 'wp_update_attachment_metadata' filter.
	 */
	function wp_update_attachment_metadata( $data, $post_id ) {

		// Allow exclusion of keys.
		$exclude_keys = array_flip( apply_filters( 'tln_exclude_attachment_meta_keys', array(), $data, $post_id ) );

		foreach ( $data as $key => $value ) {
			if ( ! empty( $value ) && '' !== $key && '_' !== $key[0] && ! isset( $exclude_keys[ $key ] ) ) {
				$data[ $key ] = $this->tl_normalizer( $value );
			}
		}

		return $data;
	}

	/**
	 * Called on 'pre_post_tax_input' filter.
	 */
	function pre_post_tax_input( $arr ) {
		if ( is_array( $arr ) ) {
			foreach ( $arr as $taxonomy => $terms ) {
				tln_debug_log( "terms=", $terms );
				foreach ( $terms as $idx => $term ) {
					if ( ! empty( $term ) && is_string( $term ) && ! ctype_digit( $term ) ) { // Exclude ids.
						$arr[ $taxonomy ][ $idx ] = $this->tl_normalizer( $term );
					}
				}
			}
		}

		return $arr;
	}

	/**
	 * Called on 'insert_user_meta' filter.
	 */
	function insert_user_meta( $meta, $user, $update ) {

		// Allow exclusion of keys.
		$exclude_keys = array( 'nickname', 'first_name', 'last_name', 'description' ); // These are already covered by the 'pre_user_XXX' filters.
		$exclude_keys = array_flip( apply_filters( 'tln_exclude_user_meta_keys', $exclude_keys, $meta, $user, $update ) );

		foreach ( $meta as $key => $value ) {
			if ( ! empty( $value ) && '' !== $key && '_' !== $key[0] && ! isset( $exclude_keys[ $key ] ) ) {
				if ( ( is_string( $value ) && 'false' !== $value && 'true' !== $value ) || is_array( $value ) ) { // Exclude boolean strings.
					$meta[ $key ] = $this->tl_normalizer( $value );
				}
			}
		}

		// Use the passed-in $user to get the contact methods and add sanitize filters.
		foreach ( wp_get_user_contact_methods( $user ) as $key => $label /*Have no interest in the $label*/ ) {
			// We don't have access to the newly updated $userdata so can't normalize directly even if we wanted to.
			if ( '' !== $key && '_' !== $key[0] && ! isset( $exclude_keys[ $key ] ) ) {
				add_filter( "sanitize_user_meta_$key", array( $this, 'sanitize_meta' ), $this->priority, 3 );
			}
		}

		return $meta;
	}

	/**
	 * Called on 'pre_update_option' filter.
	 * Called on all options.
	 */
	function pre_update_option( $value, $option, $old_value ) {
		if ( ! empty( $value ) ) {
			// Allow exclusion of options.
			$exclude_options = array();
			$exclude_options = array_flip( apply_filters( 'tln_exclude_options', $exclude_options, $value, $option, $old_value ) );

			if ( ! isset( $exclude_options[ $option ] ) ) {
				$value = $this->tl_normalizer( $value );
			}
		}

		return $value;
	}

	/**
	 * Called on 'pre_update_option_$option' filter.
	 * Called on individual options. Just passthru to pre_update_option().
	 */
	function pre_update_option_option( $value, $old_value, $option = null /*For WP < 4.3 compat*/ ) {
		return $this->pre_update_option( $value, $option, $old_value ); // Note re-ordering of args.
	}

	/**
	 * Called on 'sanitize_option_$option' filter.
	 * For date/time format ajax preview. Just passthru to tl_normalizer().
	 */
	function sanitize_option_option( $value, $option, $original_value = null /*For WP < 4.3 compat*/ ) {
		return $this->tl_normalizer( $value );
	}

	/**
	 * Called on 'widget_update_callback' filter.
	 */
	function widget_update_callback( $instance, $new_instance, $old_instance, $this_widget ) {

		// Allow exclusion of keys.
		$exclude_keys = array_flip( apply_filters( 'tln_exclude_widget_instance_keys', array(), $instance, $new_instance, $old_instance, $this_widget ) );

		foreach ( $instance as $key => $value ) {
			if ( ! empty( $value ) && ! isset( $exclude_keys[ $key ] ) ) {
				$instance[ $key ] = $this->tl_normalizer( $value );
			}
		}

		return $instance;
	}

	/**
	 * Called on 'sanitize_{$meta_type}_meta_{$meta_key}' filter.
	 * Just a passthru to tl_normalizer() for the mo.
	 */
	function sanitize_meta( $meta_value, $meta_key, $meta_type ) {
		return $this->tl_normalizer( $meta_value );
	}

	/**
	 * Called on 'admin_enqueue_scripts' and 'wp_enqueue_scripts' actions.
	 */
	function enqueue_scripts() {
		$suffix = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? '' : '.min';
		$rangyinputs_suffix = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? '-src' : '';

		// Load IE8 Array.prototype.reduceRight polyfill for unorm.
		wp_enqueue_script( 'tln-ie8', plugins_url( "js/ie8{$suffix}.js", __FILE__ ), array(), TLN_VERSION );

		global $wp_scripts; // For < 4.2 compat, don't use wp_script_add_data().
		$wp_scripts->add_data( 'tln-ie8', 'conditional', 'lt IE 9' );

		// Load the javascript normalize polyfill https://github.com/walling/unorm
		wp_enqueue_script( 'tln-unorm', plugins_url( "unorm/lib/unorm.js", __FILE__ ), array( 'tln-ie8' ), '1.4.1' ); // Note unorm doesn't come with minified so don't use.

		// Load the getSelection/setSelection jquery plugin https://github.com/timdown/rangyinputs
		wp_enqueue_script( 'tln-rangyinputs', plugins_url( "rangyinputs/rangyinputs-jquery{$rangyinputs_suffix}.js", __FILE__ ), array( 'jquery' ), '1.2.0' );

		// Our script. Normalizes on paste in tinymce and in admin input/textareas and in some media stuff and in front-end input/textareas.
		wp_enqueue_script( 'tl-normalize', plugins_url( "js/tl-normalize{$suffix}.js", __FILE__ ), array( 'jquery', 'tln-rangyinputs', 'tln-unorm' ), TLN_VERSION );

		// Our parameters.
		$params = array(
			'p' => array( // Gets around WP stringifying direct localize elements.
				'script_debug' => defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG,
				'dont_paste' => $this->dont_paste,
			),
		);
		$params = apply_filters( 'tln_params', $params );
		wp_localize_script( 'tl-normalize', 'tln_params', $params );

		// Glue.
		add_action( is_admin() ? 'admin_print_footer_scripts' : 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ) );
	}

	/**
	 * Called on 'admin_print_footer_scripts' and 'wp_print_footer-scripts' actions.
	 */
	function print_footer_scripts() {
		$is_admin = is_admin();
		?>
<script type="text/javascript">
/*jslint ass: true, nomen: true, plusplus: true, regexp: true, vars: true, white: true, indent: 4 */
/*global jQuery, tl_normalize */

( function ( $ ) {
	'use strict';

	// TinyMCE editor init.
	tl_normalize.tinymce_editor_init();

	// jQuery ready.
	$( function () {

<?php if ( $is_admin ) : ?>

		tl_normalize.admin_ready();

<?php else : /*Front end*/ ?>

		tl_normalize.front_end_ready();

<?php endif; ?>

	} );

<?php if ( $is_admin ) : ?>

	// Customizer - do outside jQuery ready otherwise will miss 'ready' event.
	tl_normalize.customizer_ready();

<?php endif; ?>

} )( jQuery );
</script>
		<?php
	}

	/**
	 * Standardize what page we're on.
	 */
	function get_base() {
		global $pagenow;
		tln_debug_log( "pagenow=", $pagenow, ", action=", isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : "(none)" );

		$base = $pagenow;

		if ( '.php' === substr( $base, -4 ) ) {
			$base = substr( $base, 0, -4 );
		}

		if ( 'admin-ajax' === $base && ! empty( $_REQUEST['action'] ) ) {
			$base = $_REQUEST['action'];
			if ( 'inline-' === substr( $base, 0, 7 ) || 'sample-' === substr( $base, 0, 7 ) || 'update-' === substr( $base, 0, 7 ) ) {
				$base = substr( $base, 7 );
			}
			if ( 'replyto-' === substr( $base, 0, 8 ) ) {
				$base = substr( $base, 8 );
			}
		}

		if ( 'async-upload' === $base && ! empty( $_REQUEST['action'] ) ) {
			$base = $_REQUEST['action'];
			if ( 'upload-' === substr( $base, 0, 7 ) ) {
				$base = substr( $base, 7 );
			}
		}

		if ( '-add' === substr( $base, -4 ) || '-new' === substr( $base, -4 ) ) {
			$base = substr( $base, 0, -4 );
		}

		if ( '-edit' === substr( $base, -5 ) ) {
			$base = substr( $base, 0, -5 );
		}

		if ( 'add-' === substr( $base, 0, 4 ) || 'nav-' === substr( $base, 0, 4 ) || 'new-' === substr( $base, 0, 4 ) ) {
			$base = substr( $base, 4 );
		}

		if ( 'edit-' === substr( $base, 0, 5 ) || 'save-' === substr( $base, 0, 5 ) ) {
			$base = substr( $base, 5 );
		}

		if ( 'attachment' === $base || 'media' === $base || 'meta' === $base || 'save' === $base ) {
			$base = 'post';
		}

		if ( 'profile' === $base ) {
			$base = 'user';
		}

		if ( 'category' === $base || 'tag' == $base || 'tags' === $base || 'tax' === $base ) {
			$base = 'term';
		}

		if ( 'widgets' === $base ) {
			$base = 'widget';
		}

		return $base;
	}
}

load_plugin_textdomain( 'normalizer', false, basename( dirname( __FILE__ ) ) . '/languages' );

// Debug functions - no-ops unless WP_DEBUG is set.
if ( ! function_exists( 'tln_debug_log' ) ) {
	require dirname( __FILE__ ) . '/debug.php';
}

global $tlnormalizer;
$tlnormalizer = new TLNormalizer();

register_activation_hook( __FILE__, array( 'TLNormalizer', 'activation_check' ) );
