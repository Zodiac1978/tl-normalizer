<?php
/**
 * Test general tl-normalize functionality.
 *
 * @group tln
 * @group tln_tl_normalize
 */
class Tests_TLN_TL_Normalize extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = false;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer ) = self::$normalizer_state;
	}

	/**
	 * @ticket tln_extra_filters
	 */
	function test_extra_filters() {
		$decomposed_str = "u\xcc\x88"; // u umlaut.

		global $pagenow;
		$pagenow = 'admin-ajax.php';
		set_current_screen( $pagenow );
		$_REQUEST['action'] = 'replyto-comment';

		add_filter( 'tln_extra_filters', array( $this, 'tln_extra_filters_filter' ) );

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'extra_filters', $tlnormalizer->added_filters );

		add_filter( 'tln_extra_filter', array( $this, 'tln_extra_filter' ) );

		apply_filters( 'tln_extra_filter', 'Content' . $decomposed_str );
	}

	function tln_extra_filters_filter( $extra_filters ) {
		$extra_filters[] = 'tln_extra_filter';
		return $extra_filters;
	}

	function tln_extra_filter( $content ) {
		$this->assertTrue( TLN_Normalizer::isNormalized( $content ) );
		if ( class_exists( 'Normalizer' ) ) $this->assertTrue( Normalizer::isNormalized( $content ) );
	}

	/**
	 * @ticket tln_print_scripts
	 */
	function test_print_scripts() {
		global $pagenow;
		$pagenow = 'front.php';
		set_current_screen( $pagenow );

		$this->assertFalse( is_admin() ) ;

		global $wp_scripts;
		$old_wp_scripts = $wp_scripts;

		do_action( 'init' );

		do_action( 'wp_enqueue_scripts' );

		ob_start();
		do_action( 'wp_print_footer_scripts' );
		$out = ob_get_clean();

		$this->assertTrue( false !== strpos( $out, 'unorm.js' ) );
		$this->assertTrue( false !== strpos( $out, 'rangyinputs-jquery' ) );
		$this->assertTrue( false !== strpos( $out, 'var tl_normalize =' ) );

		$wp_scripts = $old_wp_scripts;

		global $pagenow;
		$pagenow = 'index.php';
		set_current_screen( $pagenow );

		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		do_action( 'admin_enqueue_scripts' );

		ob_start();
		do_action( 'admin_print_footer_scripts' );
		$out = ob_get_clean();

		$this->assertTrue( false !== strpos( $out, 'unorm.js' ) );
		$this->assertTrue( false !== strpos( $out, 'rangyinputs-jquery' ) );
		$this->assertTrue( false !== strpos( $out, 'var tl_normalize =' ) );
	}

	/**
	 * @ticket tln_tl_normalizer
	 */
	function test_tl_normalizer() {
		$decomposed_str = "u\xcc\x88"; // u umlaut.
		$composed_str = "\xc3\xbc";

		$content = 'Content' . $decomposed_str;
		$expected = 'Content' . $composed_str;

		$tln = new TLNormalizer();
		$this->assertFalse( $tln->no_normalizer );

		$out = $tln->tl_normalizer( $content );
		$this->assertSame( $expected, $out );

		$out = $tln->tl_normalizer( array( $content ) );
		$this->assertSame( array( $expected ), $out );

		$out = $tln->tl_normalizer( true );
		$this->assertTrue( $out );

		$out = $tln->tl_normalizer( 1.5 );
		$this->assertSame( 1.5, $out );
	}

	/**
	 * @ticket tln_compat
	 */
	function test_compat() {
		$decomposed_str = "u\xcc\x88"; // u umlaut.

		global $wp_filter;
		$old_wp_filter = $wp_filter;

		$wp_filter = array();

		TLNormalizer::$plugin_basename = null;

		$tln = new TLNormalizer();

		$tln->activation_check();

		$this->assertTrue( $tln->compatible_version() );
		$this->assertSame( 10, has_filter( 'admin_init', array( $tln, 'check_version' ) ) );
		$this->assertSame( 10, has_filter( 'init', array( $tln, 'init' ) ) );

		TLNormalizer::$not_compat = true;
		TLNormalizer::$plugin_basename = WP_PLUGIN_DIR . '/normalizer/tl-normalize.php';

		$wp_filter = array();

		$tln = new TLNormalizer();

		$this->assertFalse( $tln->compatible_version() );
		$this->assertSame( 10, has_filter( 'admin_init', array( $tln, 'check_version' ) ) );
		$this->assertFalse( has_filter( 'init', array( $tln, 'init' ) ) );

		$old_plugins = $current = get_site_option( 'active_sitewide_plugins', array() );
		$current[TLNormalizer::$plugin_basename] = time();
		$_GET['activate'] = true;
		update_site_option( 'active_sitewide_plugins', $current );

		do_action( 'init' );

		do_action( 'admin_init' );

		$admin_notices_filter = is_network_admin() ? 'network_admin_notices' : ( is_user_admin() ? 'user_admin_notices' : 'admin_notices' );
		$this->assertSame( 10, has_filter( $admin_notices_filter, array( $tln, 'disabled_notice' ) ) );
		$this->assertFalse( isset( $_GET['activate'] ) );

		ob_start();
		do_action( $admin_notices_filter );
		$out = ob_get_clean();
		$this->assertTrue( false !== stripos( $out, 'deactivated' ) );

		add_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ) );
		$out = '';
		try {
			$tln->activation_check();
		} catch ( WPDieException $e ) {
			$out = $e->getMessage();
			unset( $e );
		}
		$this->assertTrue( false !== stripos( $out, 'activated' ) );

		update_option( 'blog_charset', 'latin1' );
		TLNormalizer::$not_compat = false;

		$tln = new TLNormalizer();

		$this->assertSame( 10, has_filter( 'admin_init', array( $tln, 'check_version' ) ) );
		$this->assertFalse( has_filter( 'init', array( $tln, 'init' ) ) );

		$wp_filter = $old_wp_filter;
		update_site_option( 'active_sitewide_plugins', $old_plugins );
	}

	/**
	 * @ticket tln_get_base
	 * @dataProvider data_get_base
	 */
	function test_get_base( $page, $action, $expected ) {
		global $pagenow;

		if ( ! $page ) {
			$page = 'admin-ajax.php';
		}

		$pagenow = $page;
		set_current_screen( $pagenow );

		if ( $action ) {
			$_REQUEST['action'] = $action;
		} else {
			unset( $_REQUEST['action'] );
		}

		global $tlnormalizer;
		$out = $tlnormalizer->get_base();

		$this->assertSame( $expected, $out );
	}

	function data_get_base() {
		return array(
			array( 'post.php', null, 'post' ), // Edit post/attachment/page.
			array( 'post-new.php', null, 'post' ), // Add post/page.
			array( null, 'inline-save', 'post' ), // Quick edit post/page.

			array( null, 'add-meta', 'post' ), // Add/update custom field.

			array( null, 'save-attachment', 'post' ), // Update media.
			array( 'async-upload.php', 'upload-attachment', 'post' ), // Add media.
			array( 'media-new.php', null, 'post' ), // Add media no-js.

			array( 'comment.php', null, 'comment' ), // Edit comment.
			array( null, 'edit-comment', 'comment' ), // Quick edit comment.
			array( null, 'replyto-comment', 'comment' ), // Reply to comment, add comment (post/attachment/page).

			array( 'user-edit.php', null, 'user' ), // Edit user.
			array( 'user-new.php', null, 'user' ), // New user.
			array( 'profile.php', null, 'user' ), // Edit current user.

			array( null, 'add-tag', 'term' ), // Add category/tag.
			array( null, 'inline-save-tax', 'term' ), // Save category/tag.
			array( null, 'add-category', 'term' ), // Add category (post/attachment).

			array( 'options', null, 'options' ), // Options.
			array( null, 'date_format', 'date_format' ), // Date format preview.
			array( null, 'time_format', 'time_format' ), // Time format preview.

			array( 'settings', null, 'settings' ), // Settings (multisite network options).

			array( 'nav-menus.php', null, 'menus' ), // Add/update menus.

			array( null, 'save-widget', 'widget' ), //  Update widget.
			array( null, 'update-widget', 'widget' ), //  Update widget customizer.
			array( 'widgets.php', null, 'widget' ), //  Add/update widgets (no-js).

			array( null, 'sample-permalink', 'permalink' ), // Permalink preview.

			array( null, 'customize', 'customize' ), // Customizer preview.
			array( null, 'customize_save', 'customize_save' ), // Customizer update.
		);
	}

}
