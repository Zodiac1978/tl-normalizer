<?php
/**
 * Test menus filters.
 *
 * @group tln
 * @group tln_menus
 */
class Tests_TLN_Menus extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;

		global $pagenow;
		$pagenow = 'nav-menus.php';
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
	function test_menus() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'menus', $tlnormalizer->added_filters );

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$menu_data = array(
			'menu-name' => wp_slash( 'menus name ' . $decomposed_str ),
			'description' => 'Menus description ' . $decomposed_str,
		);


		$id = wp_update_nav_menu_object( 0, $menu_data );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		$out = wp_get_nav_menu_object( $id );
		if ( class_exists( 'WP_Term' ) ) {
			$this->assertInstanceOf( 'WP_Term', $out );
		} else {
			$this->assertTrue( is_object( $out ) );
		}

		$this->assertSame( TLN_Normalizer::normalize( $menu_data['menu-name'] ), $out->name );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $menu_data['menu-name'] ), $out->name );
		$this->assertSame( TLN_Normalizer::normalize( $menu_data['description'] ), $out->description );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $menu_data['description'] ), $out->description );

		$menu_date['menu-name'] = wp_slash( 'menus name2 ' . $decomposed_str );
		$menu_date['description'] = 'Menus description2 ' . $decomposed_str;

		$out = wp_update_nav_menu_object( $id, $menu_data );
		$this->assertSame( $id, $out );

		$out = wp_get_nav_menu_object( $id );
		if ( class_exists( 'WP_Term' ) ) {
			$this->assertInstanceOf( 'WP_Term', $out );
		} else {
			$this->assertTrue( is_object( $out ) );
		}

		$this->assertSame( TLN_Normalizer::normalize( $menu_data['menu-name'] ), $out->name );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $menu_data['menu-name'] ), $out->name );
		$this->assertSame( TLN_Normalizer::normalize( $menu_data['description'] ), $out->description );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $menu_data['description'] ), $out->description );
	}
}
