<?php
/**
 * This a version of NormalizerTest.php modified for use in WP's unit tests.
 * Test TLN_Normalizer using PCRE in UTF-8 mode.
 */

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//namespace Symfony\Polyfill\Tests\Intl\Normalizer;

//use Symfony\Polyfill\Intl\Normalizer\Normalizer as pn;
//use Normalizer as in;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @group tln
 * @group tln_utf8
 * @covers TLN_Normalizer::<!public>
 */
class Tests_TLN_Normalizer_UTF8 extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer, $tlnormalizer->no_pcre_utf8 );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = true;
		$tlnormalizer->no_normalizer = true;
		$tlnormalizer->no_pcre_utf8 = false;
		$tlnormalizer->load_tln_normalizer_class();
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer, $tlnormalizer->no_pcre_utf8 ) = self::$normalizer_state;
	}

	/**
	 * @ticket tln_constants
	 * @requires extension intl
	 */
    public function test_constants() {

        $rpn = new ReflectionClass( 'TLN_Normalizer' );
        $rin = new ReflectionClass( 'Normalizer' );

        $rpn = $rpn->getConstants();
        $rin = $rin->getConstants();

        ksort( $rpn );
        ksort( $rin );

        $this->assertSame( $rin, $rpn );
    }

    /**
	 * @ticket tln_is_normalized
     * @covers TLN_Normalizer::isNormalized
	 * @requires extension intl
     */
    public function test_is_normalized() {
		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->using_pcre_utf8 );

        $c = 'déjà';
        //$d = TLN_Normalizer::normalize( $c, TLN_Normalizer::NFD );
        $d = Normalizer::normalize( $c, Normalizer::NFD );

        // Normalizer::isNormalized() returns an integer on HHVM and a boolean on PHP
        $this->assertEquals( Normalizer::isNormalized( '' ), TLN_Normalizer::isNormalized( '' ) );
        $this->assertEquals( Normalizer::isNormalized( 'abc' ), TLN_Normalizer::isNormalized( 'abc' ) );
        $this->assertEquals( Normalizer::isNormalized( $c ), TLN_Normalizer::isNormalized( $c ) );
        $this->assertEquals( Normalizer::isNormalized( $c, Normalizer::NFC ), TLN_Normalizer::isNormalized( $c, TLN_Normalizer::NFC ) );
        $this->assertEquals( Normalizer::isNormalized( $c, Normalizer::NFD ), TLN_Normalizer::isNormalized( $c, TLN_Normalizer::NFD ) );
        $this->assertEquals( true, Normalizer::isNormalized( $d, Normalizer::NFD ) );
        $this->assertEquals( false, TLN_Normalizer::isNormalized( $d, TLN_Normalizer::NFD ) ); // False negative.
        $this->assertEquals( Normalizer::isNormalized( $d, Normalizer::NFC ), TLN_Normalizer::isNormalized( $d, TLN_Normalizer::NFC ) );
        $this->assertEquals( Normalizer::isNormalized( "\xFF" ), TLN_Normalizer::isNormalized( "\xFF" ) );

        $this->assertFalse( TLN_Normalizer::isNormalized( $d, TLN_Normalizer::NFD ) ); // The current implementation defensively says false
    }

    /**
	 * @ticket tln_normalize
     * @covers TLN_Normalizer::normalize
	 * @requires extension intl
     */
    public function test_normalize() {
		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->using_pcre_utf8 );

        $c = TLN_Normalizer::normalize( 'déjà', TLN_Normalizer::NFC ).TLN_Normalizer::normalize( '훈쇼™', TLN_Normalizer::NFD );
        $this->assertSame( $c, Normalizer::normalize( $c, Normalizer::NONE ) );
        $this->assertSame( $c, TLN_Normalizer::normalize( $c, TLN_Normalizer::NONE ) );

        $c = 'déjà 훈쇼™';
        $d = TLN_Normalizer::normalize( $c, TLN_Normalizer::NFD );
        $kc = TLN_Normalizer::normalize( $c, TLN_Normalizer::NFKC );
        $kd = TLN_Normalizer::normalize( $c, TLN_Normalizer::NFKD );

        $this->assertSame( '', TLN_Normalizer::normalize( '' ) );
        $this->assertSame( $c, Normalizer::normalize( $d ) );
        $this->assertSame( $c, Normalizer::normalize( $d, Normalizer::NFC ) );
        $this->assertSame( $d, Normalizer::normalize( $c, Normalizer::NFD ) );
        $this->assertSame( $kc, Normalizer::normalize( $d, Normalizer::NFKC ) );
        $this->assertSame( $kd, Normalizer::normalize( $c, Normalizer::NFKD ) );

        $this->assertEquals( false, TLN_Normalizer::normalize( $c, -1 ) ); // HHVM returns null, PHP returns false
        $this->assertFalse( TLN_Normalizer::normalize( "\xFF" ) );

    }

    /**
	 * @ticket tln_normalize_conformance_7_0_0
     * @covers TLN_Normalizer::normalize
     */
    public function test_normalize_conformance_7_0_0() {
		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->using_pcre_utf8 );

        $t = file( __DIR__.'/UCD-7.0.0/NormalizationTest.txt' ); // NormalizationTest-7.0.0.txt
        $c = array();

        foreach ( $t as $s ) {
            $t = explode( '#', $s );
            $t = explode( ';', $t[0] );

            if ( 6 === count( $t ) ) {
                foreach ( $t as $k => $s ) {
                    $t = explode( ' ', $s );
                    $t = array_map( 'hexdec', $t );
                    $t = array_map( __CLASS__.'::chr', $t );
                    $c[$k] = implode( '', $t );
                }

				$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFC ) );

				$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFD ) );

				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFKC ) );

				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFKD ) );
            }
        }
    }

    /**
	 * @ticket tln_normalize_conformance_8_0_0
     * @covers TLN_Normalizer::normalize
	 * @requires extension intl
     */
    public function test_normalize_conformance_8_0_0() {
		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->using_pcre_utf8 );

        $t = file( __DIR__.'/UCD-8.0.0/NormalizationTest.txt' ); // NormalizationTest-8.0.0.txt
        $c = array();

		// These 8.0.0 normalizations aren't yet supported by either the Symfony Normalizer or the PHP Normalizer version 1.1.0 (ICU version 55.1).
		$misses = array(
			17526, // 0061 059A 0316 302A 08E3 0062;0061 302A 0316 08E3 059A 0062;
			17527, // 0061 08E3 059A 0316 302A 0062;0061 302A 08E3 0316 059A 0062;
			18140, // 0061 0315 0300 05AE A69E 0062;00E0 05AE A69E 0315 0062;
			18141, // 0061 A69E 0315 0300 05AE 0062;0061 05AE A69E 0300 0315 0062;
			18252, // 0061 0315 0300 05AE FE2E 0062;00E0 05AE FE2E 0315 0062;
			18253, // 0061 FE2E 0315 0300 05AE 0062;0061 05AE FE2E 0300 0315 0062;
			18254, // 0061 0315 0300 05AE FE2F 0062;00E0 05AE FE2F 0315 0062;
			18255, // 0061 FE2F 0315 0300 05AE 0062;0061 05AE FE2F 0300 0315 0062;
			18308, // 0061 3099 093C 0334 111CA 0062;0061 0334 093C 111CA 3099 0062;
			18309, // 0061 111CA 3099 093C 0334 0062;0061 0334 111CA 093C 3099 0062;
			18360, // 0061 05B0 094D 3099 1172B 0062;0061 3099 094D 1172B 05B0 0062;
			18361, // 0061 1172B 05B0 094D 3099 0062;0061 3099 1172B 094D 05B0 0062;
		);

        foreach ( $t as $line_num => $line ) {
			$line_num++;
            $t = explode( '#', $line );
            $t = explode( ';', $t[0] );

            if ( 6 === count( $t ) ) {
                foreach ( $t as $k => $s ) {
                    $t = explode( ' ', $s );
                    $t = array_map( 'hexdec', $t );
                    $t = array_map( __CLASS__.'::chr', $t );
                    $c[$k] = implode( '', $t );
                }

				$missing = in_array( $line_num, $misses );
				if ( $missing ) {
					$this->assertSame( Normalizer::normalize( $c[0], Normalizer::NFC ), TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFC ) );
				} else {
					$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFC ), "$line_num: ${line}c[0]=" . bin2hex( $c[0] ) . "\nc[1]=" . bin2hex( $c[1] ) . "\n tln=" . bin2hex( TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFC ) ) . "\nnorm=" . bin2hex( Normalizer::normalize( $c[0], Normalizer::NFC ) ) );
				}
				$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[1], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFC ) );

				if ( $missing ) {
					$this->assertSame( Normalizer::normalize( $c[0], Normalizer::NFD ), TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFD ) );
				} else {
					$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFD ) );
				}
				$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[2], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFD ) );

				if ( $missing ) {
					$this->assertSame( Normalizer::normalize( $c[0], Normalizer::NFKC ), TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKC ) );
				} else {
					$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKC ) );
				}
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFKC ) );
				$this->assertSame( $c[3], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFKC ) );

				if ( $missing ) {
					$this->assertSame( Normalizer::normalize( $c[0], Normalizer::NFKD ), TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKD ) );
				} else {
					$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[0], TLN_Normalizer::NFKD ) );
				}
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[1], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[2], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[3], TLN_Normalizer::NFKD ) );
				$this->assertSame( $c[4], TLN_Normalizer::normalize( $c[4], TLN_Normalizer::NFKD ) );
            }
        }
    }

    private static function chr( $c ) {
        if ( 0x80 > $c %= 0x200000 ) {
            return chr( $c );
        }
        if ( 0x800 > $c ) {
            return chr( 0xC0 | $c >> 6 ).chr( 0x80 | $c & 0x3F );
        }
        if ( 0x10000 > $c ) {
            return chr( 0xE0 | $c >> 12 ).chr( 0x80 | $c >> 6 & 0x3F ).chr( 0x80 | $c & 0x3F );
        }

        return chr( 0xF0 | $c >> 18 ).chr( 0x80 | $c >> 12 & 0x3F ).chr( 0x80 | $c >> 6 & 0x3F ).chr( 0x80 | $c & 0x3F );
    }
}
