<?php
/**
 * NOTE: This is now obsolete. See "tools/gen_unidata.php".
 *
 * Output "Symfony/Resources/unidata/combiningClass.php" from 
 * the UCD derived combining class file "DerivedCombiningClass.txt".
 *
 * This is reverse engineered to update the Symfony polyfill to UCD 9.0.0
 * conformance since as of April 2016 it was at UCD 7.0.0.
 * Actually didn't need to do this as tools available at
 * https://github.com/nicolas-grekas/Patchwork-UTF8/blob/master/src/Patchwork/Utf8/Compiler.php
 *
 * See http://www.unicode.org/Public/9.0.0/ucd/extracted/DerivedCombiningClass.txt
 */

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );
$subdirname = basename( $dirname );

require $dirname . '/functions.php';

if ( ! function_exists( '__' ) ) {
	function __( $str, $td ) { return $str; }
}

// Open the file.

$filename ='/tests/UCD-9.0.0/extracted/DerivedCombiningClass.txt';
$file = dirname( $dirname ) . $filename;
error_log( "$basename: reading file=$file" );

// Read the file.

if ( false === ( $get = file_get_contents( $file ) ) ) {
	/* translators: %s: file name */
	$error = sprintf( __( 'Could not read derived combining class file "%s"', 'normalizer' ), $file );
	error_log( $error );
	return $error;
}

$lines = array_map( 'tln_get_cb', explode( "\n", $get ) ); // Strip newlines.

// Parse the file, creating array of codepoint => class.

$code_classes = array();
$in = false;
$line_num = 0;
foreach ( $lines as $line ) {
	$line_num++;
	$line = trim( $line );
	if ( '' === $line ) {
		continue;
	}
	if ( ! $in ) {
		if ( 0 !== strpos( $line, '# Canonical_Combining_Class=' ) ) {
			continue;
		}
		if ( '# Canonical_Combining_Class=Not_Reordered' !== $line ) {
			$in = true;
		}
	} else {
		if ( '#' === $line[0] ) {
			continue;
		}
		$parts = explode( ';', $line );
		if ( 2 !== count( $parts ) ) {
			continue;
		}
		$code = trim( $parts[0] );
		if ( 0 < ( $pos = strpos( $parts[1], '#' ) ) ) {
			$parts[1] = substr( $parts[1], 0, $pos - 1 );
		}
		$class = trim( $parts[1] );

		$codes = explode( '..', $code );
		if ( count( $codes ) > 1 ) {
			$begin = hexdec( $codes[0] );
			$end = hexdec( $codes[1] );
			for ( $i = $begin; $i <= $end; $i++ ) {
				$code_classes[ $i ] = $class;
			}
		} else {
			$code_classes[ hexdec( $code ) ] = $class;
		}
	}
}
ksort( $code_classes );
//error_log( "code_classes=" . print_r( $code_classes, true ) );

// Output.

$out = array();
$out[] =  '<?php';
$out[] = '';
$out[] = 'static $data = array (';

foreach ( $code_classes as $code => $class ) {
	$out[] = '  \'' . tln_utf8_chr( $code ) . '\' => ' . $class . ',';
}
$out[] = ');';
$out[] = '';
$out[] = '$result =& $data;';
$out[] = 'unset($data);';
$out[] = '';
$out[] = 'return $result;';
$out[] = '';

$out = implode( "\n", $out );

echo $out;
