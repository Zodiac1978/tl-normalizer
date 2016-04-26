<?php

/**
 * Test options filters
 *
 * @group tln
 * @group tln_options
 */
class Tests_TLN_Normalizer_Options extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;

		global $pagenow;
		$pagenow = 'options.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter ) = self::$normalizer_state;
	}

    /**
     * @covers TLNormalizer::init
     */
	function test_options() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->added_filters['options'] );

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$in = 'Blogname' . $decomposed_str;

		update_option( 'blogname', $in );

		$out = get_option( 'blogname' );

		$this->assertEquals( Normalizer::normalize( $in ), $out );
	}
}
