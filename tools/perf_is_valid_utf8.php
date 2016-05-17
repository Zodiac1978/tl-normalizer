<?php

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );

error_log( "(===begin " . $basename );

require $dirname . '/functions.php';

	function tln_null( $string ) {
		return ! empty( $string );
	}

	function tln_is_valid_utf8_u( $string ) {
		return 1 === preg_match( '//u', $string ); // Original Normalizer validity check.
	}

	function tln_is_valid_utf8( $string ) {

		// See https://www.w3.org/International/questions/qa-forms-utf-8
		return 1 === preg_match(
			'/\A(?:
			  [\x00-\x7F]                                     # ASCII
			| [\xC2-\xDF][\x80-\xBF]                          # non-overlong 2-byte
			| \xE0[\xA0-\xBF][\x80-\xBF]                      # excluding overlongs
			| [\xE1-\xEC\xEE\xEF][\x80-\xBF][\x80-\xBF]       # straight 3-byte
			| \xED[\x80-\x9F][\x80-\xBF]                      # excluding surrogates
			| \xF0[\x90-\xBF][\x80-\xBF][\x80-\xBF]           # planes 1-3
			| [\xF1-\xF3][\x80-\xBF][\x80-\xBF][\x80-\xBF]    # planes 4-15
			| \xF4[\x80-\x8F][\x80-\xBF][\x80-\xBF]           # plane 16
			)*+\z/x',
			$string
		);
	}

$valid_strs = array();

for ( $i = 0; $i < 200; $i++ ) {
	$valid_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0 );
}

$one_thou_strs = array();

for ( $i = 0; $i < 200; $i++ ) {
	$one_thou_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.001 );
}

$one_cent_strs = array();

for ( $i = 0; $i < 200; $i++ ) {
	$one_cent_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.01 );
}

$ten_cent_strs = array();

for ( $i = 0; $i < 200; $i++ ) {
	$ten_cent_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.1 );
}

for ( $i = 0; $i < 200; $i++ ) {
	if ( ! tln_is_valid_utf8( $valid_strs[ $i ] ) ) {
		error_log( "bad result valid_strs[ $i ]" );
		return;
	}
	if ( tln_is_valid_utf8( $valid_strs[ $i ] ) !== tln_is_valid_utf8_u( $valid_strs[ $i ] ) ) {
		error_log( "bad match valid_strs[ $i ]" );
		return;
	}
	if ( tln_is_valid_utf8( $one_thou_strs[ $i ] ) !== tln_is_valid_utf8_u( $one_thou_strs[ $i ] ) ) {
		error_log( "bad match one_thou_strs[ $i ]" );
		return;
	}
	if ( tln_is_valid_utf8( $one_cent_strs[ $i ] ) !== tln_is_valid_utf8_u( $one_cent_strs[ $i ] ) ) {
		error_log( "bad match one_cent_strs[ $i ]" );
		return;
	}
	if ( tln_is_valid_utf8( $ten_cent_strs[ $i ] ) !== tln_is_valid_utf8_u( $ten_cent_strs[ $i ] ) ) {
		error_log( "bad match ten_cent_strs[ $i ]" );
		return;
	}
}

$is_valid_tot_u = $is_valid_tot = 0;
$one_thou_tot_u = $one_thou_tot = 0;
$one_cent_tot_u = $one_cent_tot = 0;
$ten_cent_tot_u = $ten_cent_tot = 0;

// Valid

$i = 0;
while ( ++$i < 200 ) {
	tln_null( $valid_strs[ $i ] );
}

$i = 0;
$is_valid_tot = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8( $valid_strs[ $i ] );
}
$is_valid_tot += microtime( true );

$i = 0;
$is_valid_tot_u = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8_u( $valid_strs[ $i ] );
}
$is_valid_tot_u += microtime( true );

// One thou

$i = 0;
while ( ++$i < 200 ) {
	tln_null( $one_thou_strs[ $i ] );
}

$i = 0;
$one_thou_tot = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8( $one_thou_strs[ $i ] );
}
$one_thou_tot += microtime( true );

$i = 0;
$one_thou_tot_u = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8_u( $one_thou_strs[ $i ] );
}
$one_thou_tot_u += microtime( true );

// One cent

$i = 0;
while ( ++$i < 200 ) {
	tln_null( $one_cent_strs[ $i ] );
}

$i = 0;
$one_cent_tot = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8( $one_cent_strs[ $i ] );
}
$one_cent_tot += microtime( true );

$i = 0;
$one_cent_tot_u = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8_u( $one_cent_strs[ $i ] );
}
$one_cent_tot_u += microtime( true );

// Ten cent

$i = 0;
while ( ++$i < 200 ) {
	tln_null( $ten_cent_strs[ $i ] );
}

$i = 0;
$ten_cent_tot = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8( $ten_cent_strs[ $i ] );
}
$ten_cent_tot += microtime( true );

$i = 0;
$ten_cent_tot_u = -microtime( true );
while ( ++$i < 200 ) {
	tln_is_valid_utf8_u( $ten_cent_strs[ $i ] );
}
$ten_cent_tot_u += microtime( true );

error_log( "is_valid_tot  =" . sprintf( '%.10f', $is_valid_tot ) . ", one_thou_tot  =" . sprintf( '%.10f', $one_thou_tot )
	. ", one_cent_tot  =" . sprintf( '%.10f', $one_cent_tot ) . ", ten_cent_tot  =" . sprintf( '%.10f', $ten_cent_tot ) );
error_log( "is_valid_tot_u=" . sprintf( '%.10f', $is_valid_tot_u ) . ", one_thou_tot_u=" . sprintf( '%.10f', $is_valid_tot_u )
	. ", one_cent_tot_u=" . sprintf( '%.10f', $one_cent_tot_u ) . ", ten_cent_tot_u=" . sprintf( '%.10f', $ten_cent_tot_u ) );

error_log( ")===end " . $basename );
