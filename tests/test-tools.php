<?php

/**
 * Test tools.
 *
 * @group tln
 * @group tln_tools
 */
class Tests_TLN_Tools extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass() {
		$dirname = dirname( dirname( __FILE__ ) );
		require_once $dirname . '/tools/functions.php';
		require_once $dirname . '/Symfony/tln_ins.php';
	}

	/**
	 * @ticket tln_utf8_regex_alts
	 */
    public function test_utf8_regex_alts() {

		$arr = array(
			/**/
			array( array( 0, 0, 0, 0 ), array( 0, 0, 0, 0xa ), '[\x00-\x0a]' ),
			array( array( 0, 0, 0, 0x1 ), array( 0, 0, 0xc2, 0xa0 ), '[\x01-\x7f]|\xc2[\x80-\xa0]' ),
			array( array( 0, 0, 0xc2, 0x80 ), array( 0, 0, 0xc2, 0x85 ), '\xc2[\x80-\x85]' ),
			array( array( 0, 0, 0xc2, 0x80 ), array( 0, 0, 0xc2, 0x81 ), '\xc2[\x80\x81]' ),
			array( array( 0, 0, 0xc2, 0x80 ), array( 0, 0, 0xc3, 0x81 ), '\xc2[\x80-\xbf]|\xc3[\x80\x81]' ),
			array( array( 0, 0, 0xc2, 0x80 ), array( 0, 0, 0xc3, 0xbf ), '[\xc2\xc3][\x80-\xbf]' ),
			array( array( 0, 0, 0xc3, 0x80 ), array( 0, 0, 0xd0, 0x80 ), '[\xc3-\xcf][\x80-\xbf]|\xd0\x80' ),
			array( array( 0, 0, 0xc4, 0x81 ), array( 0, 0, 0xdf, 0xbf ), '\xc4[\x81-\xbf]|[\xc5-\xdf][\x80-\xbf]' ),
			array( array( 0, 0, 0xd2, 0x81 ), array( 0, 0, 0xdf, 0xbe ), '\xd2[\x81-\xbf]|[\xd3-\xde][\x80-\xbf]|\xdf[\x80-\xbe]' ),
			array( array( 0, 0xe0, 0x83, 0xbe ), array( 0, 0xe0, 0x84, 0x80 ), '\xe0(?:\x83[\xbe\xbf]|\x84\x80)' ),
			array( array( 0, 0xe0, 0x83, 0xbe ), array( 0, 0xe0, 0x84, 0x90 ), '\xe0(?:\x83[\xbe\xbf]|\x84[\x80-\x90])' ),
			array( array( 0, 0xe0, 0x83, 0x80 ), array( 0, 0xe0, 0x84, 0xbf ), '\xe0[\x83\x84][\x80-\xbf]' ),
			array( array( 0, 0xe0, 0x83, 0xbd ), array( 0, 0xe1, 0x84, 0x91 ), '\xe0(?:\x83[\xbd-\xbf]|[\x84-\xbf][\x80-\xbf])|\xe1(?:[\x80-\x83][\x80-\xbf]|\x84[\x80-\x91])' ),
			array( array( 0, 0xe0, 0x83, 0xbd ), array( 0, 0xe2, 0x84, 0x91 ),
				'\xe0(?:\x83[\xbd-\xbf]|[\x84-\xbf][\x80-\xbf])|\xe1[\x80-\xbf][\x80-\xbf]|\xe2(?:[\x80-\x83][\x80-\xbf]|\x84[\x80-\x91])' ),
			array( array( 0, 0, 0xc3, 0x9e ), array( 0, 0xe0, 0x85, 0x80 ), '\xc3[\x9e-\xbf]|[\xc4-\xdf][\x80-\xbf]|\xe0(?:[\x80-\x84][\x80-\xbf]|\x85\x80)' ),
			array( array( 0, 0, 0, 0x7e ), array( 0, 0xe0, 0x86, 0x81 ), '[\x7e\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0(?:[\x80-\x85][\x80-\xbf]|\x86[\x80\x81])' ),
			array( array( 0xf0, 0x83, 0xbe, 0x90 ), array( 0xf0, 0x84, 0x80, 0x80 ), '\xf0(?:\x83(?:\xbe[\x90-\xbf]|\xbf[\x80-\xbf])|\x84\x80\x80)' ),
			array( array( 0xf0, 0x83, 0xbb, 0x90 ), array( 0xf1, 0x85, 0x81, 0x81 ),
				'\xf0(?:\x83(?:\xbb[\x90-\xbf]|[\xbc-\xbf][\x80-\xbf])|[\x84-\xbf][\x80-\xbf][\x80-\xbf])|\xf1(?:[\x80-\x84][\x80-\xbf][\x80-\xbf]|\x85(?:\x80[\x80-\xbf]|\x81[\x80\x81]))' ),
			array( array( 0xf0, 0x83, 0x80, 0x80 ), array( 0xf0, 0x84, 0xbf, 0xbf ), '\xf0[\x83\x84][\x80-\xbf][\x80-\xbf]' ),
			array( array( 0xf0, 0x84, 0xbe, 0x90 ), array( 0xf4, 0x84, 0xbe, 0x90 ),
				'\xf0(?:\x84(?:\xbe[\x90-\xbf]|\xbf[\x80-\xbf])|[\x85-\xbf][\x80-\xbf][\x80-\xbf])|[\xf1-\xf3][\x80-\xbf][\x80-\xbf][\x80-\xbf]|\xf4(?:[\x80-\x83][\x80-\xbf][\x80-\xbf]|\x84(?:[\x80-\xbd][\x80-\xbf]|\xbe[\x80-\x90]))' ),
			array( array( 0, 0xe3, 0x81, 0xa0 ), array( 0xf2, 0x84, 0xaf, 0xb0 ),
				'\xe3(?:\x81[\xa0-\xbf]|[\x82-\xbf][\x80-\xbf])|[\xe4-\xef][\x80-\xbf][\x80-\xbf]|[\xf0\xf1][\x80-\xbf][\x80-\xbf][\x80-\xbf]|\xf2(?:[\x80-\x83][\x80-\xbf][\x80-\xbf]|\x84(?:[\x80-\xae][\x80-\xbf]|\xaf[\x80-\xb0]))' ),
			array( array( 0, 0, 0xd1, 0xbe ), array( 0xf3, 0x84, 0x80, 0xb0 ),
				'\xd1[\xbe\xbf]|[\xd2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf][\x80-\xbf]|[\xf0-\xf2][\x80-\xbf][\x80-\xbf][\x80-\xbf]|\xf3(?:[\x80-\x83][\x80-\xbf][\x80-\xbf]|\x84\x80[\x80-\xb0])' ),
			array( array( 0, 0, 0, 0 ), array( 0xf4, 0x8f, 0xbf, 0xbf ),
				'[\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf][\x80-\xbf]|[\xf0-\xf3][\x80-\xbf][\x80-\xbf][\x80-\xbf]|\xf4[\x80-\x8f][\x80-\xbf][\x80-\xbf]' ),
			array( array( 0, 0, 0, 0 ), array( 0, 0, 0, 0 ), '\x00' ),
			array( array( 0, 0, 0, 1 ), array( 0, 0, 0, 1 ), '\x01' ),
			array( array( 0, 0, 0xc2, 0x80 ), array( 0, 0, 0xc2, 0x80 ), '\xc2\x80' ),
			array( array( 0, 0xe0, 0x80, 0x80 ), array( 0, 0xe0, 0x80, 0x80 ), '\xe0\x80\x80' ),
			array( array( 0xf0, 0x80, 0x80, 0x80 ), array( 0xf0, 0x80, 0x80, 0x80 ), '\xf0\x80\x80\x80' ),
			/**/
		);

		foreach ( $arr as list( $c1, $c2, $expected ) ) {
			$ranges = array();
			tln_utf8_4range( $ranges, $c1, $c2 );
			$actual = tln_utf8_regex_alts( $ranges );
			$this->assertSame( $expected, $actual );
		}
    }

	/**
	 * @ticket tln_u_equivalence
	 */
    public function test_u_equivalence() {
		global $tln_nfc_noes, $tln_nfc_maybes_or_reorders;
		$this->assertTrue( is_array( $tln_nfc_noes ) );

		foreach ( $tln_nfc_noes as $no ) {
			$chr = tln_utf8_chr( $no );
			$this->assertTrue( tln_in_nfc_noes( $chr ) );
			$this->assertTrue( tln_in_nfc_noes_u( $chr ) );
		}
		foreach ( $tln_nfc_maybes_or_reorders as $maybe_or_reorder ) {
			$chr = tln_utf8_chr( $maybe_or_reorder );
			$this->assertTrue( tln_in_nfc_maybes_or_reorders( $chr ) );
			$this->assertTrue( tln_in_nfc_maybes_or_reorders_u( $chr ) );
		}
	}

	/**
	 * @ticket tln_utf8_chr
	 */
    public function test_utf8_chr() {
		$this->assertSame( "\x00", tln_utf8_chr( 0 ) );
		$this->assertSame( "\x01", tln_utf8_chr( 1 ) );
		$this->assertSame( "\xf4\x8f\xbf\xbe", tln_utf8_chr( 0x10fffe ) );
		$this->assertSame( "\xf4\x8f\xbf\xbf", tln_utf8_chr( 0x10ffff ) );
		$this->assertSame( "\xf4\x8f\xbf\xbf", tln_utf8_chr( 0x120000 ) );
	}
}
