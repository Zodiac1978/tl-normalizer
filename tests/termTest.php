<?php
/**
 * Test term filters.
 *
 * @group tln
 * @group tln_term
 */
class Tests_TLN_Term extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;

		global $pagenow;
		$pagenow = 'edit-tags.php';
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
	}

	function tearDown() {
		parent::tearDown();
		if ( ! method_exists( 'WP_UnitTestCase', 'wpSetUpBeforeClass' ) ) { // Hack for WP testcase.php versions prior to 4.4
			self::wpTearDownAfterClass();
		}
	}

    /**
     */
	function test_term() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'term', $tlnormalizer->added_filters );

		$decomposed_str = "u\xcc\x88"; // u umlaut.

		$name = 'term name ' . $decomposed_str;
		$args = array(
			'description' => 'Term description ' . $decomposed_str,
		);
		$cat = 'category';

		$result = wp_insert_term( $name, $cat, $args );

		$this->assertTrue( is_array( $result ) );
		$this->assertTrue( is_numeric( $result['term_id'] ) );

		$id = $result['term_id'];
		$this->assertTrue( $id > 0 );

		// fetch the term and make sure it matches
		$out = get_term( $id );
		$this->assertInstanceOf( 'WP_Term', $out );

		$this->assertSame( TLN_Normalizer::normalize( $name ), $out->name );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $name ), $out->name );
		$this->assertSame( TLN_Normalizer::normalize( $args['description'] ), $out->description );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $args['description'] ), $out->description );
	}
}
