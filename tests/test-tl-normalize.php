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
		$decomposed_str = "u\xCC\x88"; // u umlaut.

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
	 * @ticket tln_misc
	 */
	function test_misc() {
		$tln = new TLNormalizer();

		$this->assertTrue( $tln->compatible_version() );

		do_action( 'init' );

		do_action( 'admin_init' );
		
		// TODO Check something.
	}
}
