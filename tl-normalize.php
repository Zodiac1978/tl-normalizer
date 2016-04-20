<?php
/**
 * Plugin Name: Normalizer
 * Plugin URI: https://github.com/Zodiac1978/tl-normalizer
 * Description: Normalizes content, excerpt, title and comment content to Normalization Form C.
 * Version: 2.0.2
 * Author: Torsten Landsiedel
 * Author URI: http://torstenlandsiedel.de
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: normalizer
 * Domain Path: /languages
 */

// See https://core.trac.wordpress.org/ticket/30130
// See also https://github.com/tinymce/tinymce/issues/1971

/*
Thank you very much for this code, Gary Pendergast!
http://pento.net/2014/02/18/dont-let-your-plugin-be-activated-on-incompatible-sites/
*/

class TLNormalizer {

	var $no_js = false; // For testing/debugging.
	var $no_filters = false; // For testing/debugging.

	function __construct() {

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

	// The primary sanity check, automatically disable the plugin on activation if it doesn't
	// meet minimum requirements.
	static function activation_check() {
		if ( ! self::compatible_version() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Can\'t happen.', 'normalizer' ) );
		}
	}

	// The backup sanity check, in case the plugin is activated in a weird way,
	// or the versions change after activation.
	function check_version() {
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

	function disabled_notice() {
		$error_message  = '<div id="message" class="updated notice is-dismissible">';
		$error_message .= '<p><strong>' . __( 'Plugin deactivated!' ) . '</strong> ';
		$error_message .= esc_html__( 'Can\'t happen.', 'normalizer' );
		$error_message .= '</p></div>';
		echo $error_message;
	}

	static function compatible_version() {

		// Totally compat! (Famous last words.)
		return true;
	}

	/**
	 * Called on 'init' action.
	 */
	function init() {

		if ( ! function_exists( 'normalizer_is_normalized' ) ) {
			$dirname = dirname( __FILE__ );

			// To avoid a PCRE dependency, the Symfony Normalizer polyfill has been modified to call tl_check_valid_utf8() instead of using preg_match() in UTF-8 mode when checking UTF-8 validity.

			// If the version of PCRE is that which came with PHP before 5.3.4 (when 5 and 6 octet sequences were allowed) or if it isn't compiled with UTF-8 support...
			if ( version_compare( PHP_VERSION, '5.3.4', '<' ) || false === preg_match( '//u', '' ) ) {
				// Load utf-8 validator tl_check_valid_utf8() https://hsivonen.fi/php-utf8/
				require $dirname . '/php-utf8/utf8.php';
			} else { // Use PCRE.
				function tl_check_valid_utf8( $string, $and_nfc_normalized = false ) {
					if ( $and_nfc_normalized ) {
						return preg_match( '//u', $string ) && ! preg_match( '/[^\x00-\x{2FF}]/u', $string ); // Original Normalizer validity and rough not-NFC normalized check.
					}
					return preg_match( '//u', $string ); // Original Normalizer validity check.
				}
			}
			// Load the Symfony polyfill https://github.com/symfony/polyfill/tree/master/src/Intl/Normalizer
			require $dirname . '/Symfony/Normalizer.php';
		}

		if ( ! $this->no_filters ) {
			// Add them here. Too late otherwise.
			// TODO: work out which filters to use, and when.
			add_filter( 'content_save_pre', array( $this, 'tl_normalizer' ) );
			add_filter( 'title_save_pre' , array( $this, 'tl_normalizer' ) );
			add_filter( 'pre_comment_content' , array( $this, 'tl_normalizer' ) );
			add_filter( 'excerpt_save_pre' , array( $this, 'tl_normalizer' ) );
		}

		if ( ! $this->no_js ) {
			if ( is_admin() ) {
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				}
			} else {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}
	}

	/**
	 * Called on 'admin_enqueue_scripts' and 'wp_enqueue_scripts' actions.
	 */
	function enqueue_scripts() {
		$suffix = ''; // defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? '' : '.min'; // Don't bother with minifieds for the mo.

		// Load the javascript polyfill https://github.com/walling/unorm
		wp_enqueue_script( 'tln-unorm', plugins_url( 'unorm/lib/unorm' . $suffix . '.js', __FILE__ ), array(), '1.0.0' );

		// Our script. Normalizes on paste in tinymce and in admin input/textareas and in some media stuff and in front-end input/textareas.
		if ( is_admin() ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'print_footer_scripts' ) );
		} else {
			add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ) );
		}
	}

	/**
	 * Called on 'admin_print_footer_scripts' and 'wp_print_footer_scripts' actions.
	 */
	function print_footer_scripts() {
		$is_admin = is_admin();
		?>
<script type="text/javascript">
/*jslint ass: true, nomen: true, plusplus: true, regexp: true, vars: true, white: true, indent: 4 */
/*global jQuery, wp */
/*exported tl_normalize */
var tl_normalize = tl_normalize || {}; // Our namespace.

( function ( $ ) {
	'use strict';

	/**
	 * Helper to normalize text pasted into text-like inputs and textareas.
	 */
	tl_normalize.input_textarea_normalize_on_paste = function ( context ) {
		// TODO: Other types: "email", "password", "url" ??
		$( 'input[type="text"], input[type="search"], textarea', context ).on( 'paste', function ( event ) {
			var $el = $(this);
			if ( $el.val().normalize ) {
				// http://stackoverflow.com/a/1503425/664741
				setTimeout( function () {
					<?php if ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) ?> var before = $el.val();
					$el.val( $el.val().normalize() );
					<?php if ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) ?> tl_normalize.dmp_before_and_after( before, $el.val() );
				} );
			}
		} );
	};

	/**
	 * Debug helper - dump before and after.
	 */
	<?php if ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) : ?>
	tl_normalize.dmp_before_and_after = function ( before, after ) {
		var i, before_dmp = '', after_dmp = '';
		if ( before === after ) {
			console.log( 'normalize same' );
		} else {
			for ( i = 0; i < before.length; i++ ) {
				before_dmp += ( '0000' + before.charCodeAt( i ).toString( 16 ) ).slice( -4 ) + ' ';
			}
			for ( i = 0; i < after.length; i++ ) {
				after_dmp += ( '0000' + after.charCodeAt( i ).toString( 16 ) ).slice( -4 ) + ' ';
			}
			console.log( 'normalize different\nbefore_dmp=%s\n after_dmp=%s', before_dmp, after_dmp );
		}
	};
	<?php endif; ?>

	/**
	 * Normalize text pasted into tinymce.
	 */
	$( document ).on( 'tinymce-editor-init', function ( event, editor ) {
		// Using PastePreProcess, which is fired with the paste as a HTML string set in event.content.
		// Easy option, may not be the best.
		editor.on( 'PastePreProcess', function( event ) {
			if ( event.content && event.content.length && event.content.normalize ) {
				<?php if ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) ?> var before = event.content;
				event.content = event.content.normalize();
				<?php if ( defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ) ?> tl_normalize.dmp_before_and_after( before, event.content );
			}
		} );
	} );

	$( function () {
<?php if ( $is_admin ) : ?>
		var $wpcontent = $( '#wpcontent' ), old_details_render, old_display_render;

		// Any standard admin text input or textarea. May need refining.
		if ( $wpcontent.length ) {
			tl_normalize.input_textarea_normalize_on_paste( $wpcontent );
		}

		// Media.
	 	if ( wp && wp.media && wp.media.view ) {
			if ( wp.media.view.Attachment && wp.media.view.Attachment.Details ) {
				// Override render. Probably not the best option.
				old_details_render = wp.media.view.Attachment.Details.prototype.render;
				wp.media.view.Attachment.Details.prototype.render = function () {
					old_details_render.apply( this, arguments );
					tl_normalize.input_textarea_normalize_on_paste( this.$el );
				};
			}
			if ( wp.media.view.Settings && wp.media.view.Settings.AttachmentDisplay ) {
				// Override render. Again, probably not the best option.
				old_display_render = wp.media.view.Settings.AttachmentDisplay.prototype.render;
				wp.media.view.Settings.AttachmentDisplay.prototype.render = function () {
					old_display_render.apply( this, arguments );
					tl_normalize.input_textarea_normalize_on_paste( this.$el );
				};
			}
			// TODO: Other media stuff.
		}

		// TODO: Customizer.
	 	if ( wp && wp.customize ) {
		}
		// TODO: Other stuff.

<?php else : /*Front end*/ ?>

		// Any standard text input or textarea. May need refining.
		tl_normalize.input_textarea_normalize_on_paste();
<?php endif; ?>
	} );

} )( jQuery );
</script>
		<?php
	}

	function tl_normalizer( $content ) {

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
		if ( '' !== $content && ! normalizer_is_normalized( $content ) ) {
			$normalized = normalizer_normalize( $content );
			if ( false !== $normalized ) { // Not validating so don't set on error.
				$content = $normalized;
			}
		}

		return $content;
	}
}

global $tlnormalizer;
$tlnormalizer = new TLNormalizer();

register_activation_hook( __FILE__, array( 'TLNormalizer', 'activation_check' ) );
