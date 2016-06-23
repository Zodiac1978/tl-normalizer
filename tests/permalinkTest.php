<?php
/**
 * Test permalink filters.
 *
 * @group tln
 * @group tln_permalink
 */
class Tests_TLN_Permalink extends WP_UnitTestCase {

	static $normalizer_state = array();
	static $is_less_than_wp_4 = false;

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;

		global $wp_version;
		self::$is_less_than_wp_4 = version_compare( $wp_version, '4', '<' );

		global $pagenow;
		$pagenow = 'admin-ajax.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer ) = self::$normalizer_state;
	}

	function setUp() {
		parent::setUp();
		if ( ! method_exists( 'WP_UnitTestCase', 'wpSetUpBeforeClass' ) ) { // Hack for WP testcase.php versions prior to 4.4
			self::wpSetUpBeforeClass();
		}
		if ( self::$is_less_than_wp_4 ) {
			mbstring_binary_safe_encoding(); // For <= WP 3.9.12 compatibility - remove_accents() uses naked strlen().
		}
	}

	function tearDown() {
		if ( self::$is_less_than_wp_4 && $this->caught_deprecated && 'define()' === $this->caught_deprecated[0] ) {
			array_shift( $this->caught_deprecated );
		}
		parent::tearDown();
		if ( ! method_exists( 'WP_UnitTestCase', 'wpSetUpBeforeClass' ) ) { // Hack for WP testcase.php versions prior to 4.4
			self::wpTearDownAfterClass();
		}
		if ( self::$is_less_than_wp_4 ) {
			reset_mbstring_encoding(); // For <= WP 3.9.12 compatibility - remove_accents() uses naked strlen().
		}
	}

    /**
	 * @ticket tln_permalink_permalink
     */
	function test_permalink() {
		$this->assertTrue( is_admin() ) ;

		global $tlnormalizer;
		if ( self::$is_less_than_wp_4 ) {
			// For <= WP 3.9.12 compatibility - filters seem to get left hanging around.
			foreach( $tlnormalizer->post_filters as $filter ) {
				remove_filter( $filter, array( $tlnormalizer, 'tl_normalizer' ), $tlnormalizer->priority );
			}
		}

		$_REQUEST['action'] = 'sample-permalink';

		do_action( 'init' );

		$this->assertArrayHasKey( 'permalink', $tlnormalizer->added_filters );

		$decomposed_str = "o\xcc\x88"; // o umlaut.

		$title = 'some-post' . $decomposed_str;

		$post = $this->factory->post->create_and_get( array( 'post_title' => $title, 'post_type' => 'post' ) );

		$out = get_sample_permalink( $post->ID, $title );

		$this->assertSame( TLN_Normalizer::normalize( $title ), $out[1] );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $title ), $out[1] );

		$name = 'name' . $decomposed_str;

		$out = get_sample_permalink( $post->ID, null, $name );

		$this->assertSame( TLN_Normalizer::normalize( $name ), $out[1] );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $name ), $out[1] );
	}
}
