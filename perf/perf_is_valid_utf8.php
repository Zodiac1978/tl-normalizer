<?php

ini_set( 'ignore_repeated_errors', true );

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );
$dirdirname = dirname( $dirname );

error_log( "(===begin " . $basename );

require $dirdirname . '/tools/functions.php';
require $dirdirname . '/Symfony/Normalizer.php';

function tln_null( $str ) {
	return ! empty( $str );
}

$check = true;
$strs_num = 100;
$loop_num = 1000;
$str_min = 0;
$str_max = 100000;

$strs = array(
	'zer_oooo' => array(), 'one_thou' => array(), 'one_cent' => array(), //'fiv_cent' => array(), 'ten_cent' => array(),
	//'twe_cent' => array(), 'thi_cent' => array(), 'fif_cent' => array(), 'eig_cent' => array(), 'hun_cent' => array(),
);

for ( $i = 0; $i < $strs_num; $i++ ) {
	$strs['zer_oooo'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0 );
	$strs['one_thou'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.001 );
	$strs['one_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.01 );
	/*
	$strs['fiv_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.05 );
	$strs['ten_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.1 );
	$strs['twe_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.2 );
	$strs['thi_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.3 );
	$strs['fif_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.5 );
	$strs['eig_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 0.8 );
	$strs['hun_cent'][] = tln_utf8_rand_ratio_str( rand( $str_min, $str_max ), 1.0 );
	*/
}
error_log( "done strs" );

if ( $check ) {
	foreach ( array_keys( $strs ) as $idx ) {
		foreach ( $strs[ $idx ] as $i => $str ) {
			if ( 'zer_oooo' === $idx ) {
				if ( 1 === preg_match( TLN_REGEX_IS_INVALID_UTF8, $str ) ) {
					error_log( "bad result $idx [ $i ]" );
					return;
				}
			}
			if ( ( 1 !== preg_match( TLN_REGEX_IS_INVALID_UTF8, $str ) ) !== ( 1 === preg_match( '//u', $str ) ) ) {
				error_log( "bad match $idx [ $i ]" );
				error_log( "str=" . bin2hex( $str ) );
				return;
			}
			if ( ( 1 === preg_match( TLN_REGEX_IS_VALID_UTF8, $str ) ) !== ( 1 !== preg_match( TLN_REGEX_IS_INVALID_UTF8, $str ) ) ) {
				error_log( "bad match $idx [ $i ]" );
				return;
			}
		}
	}
}
error_log( "done check" );

$tots_u = array( 'zer_oooo' => 0, 'one_thou' => 0, 'one_cent' => 0, /*'fiv_cent' => 0, 'ten_cent' => 0, /*'twe_cent' => 0, 'thi_cent' => 0, 'fif_cent' => 0, 'eig_cent' => 0, 'hun_cent' => 0,*/ );
$tots_t = array( 'zer_oooo' => 0, 'one_thou' => 0, 'one_cent' => 0, /*'fiv_cent' => 0, 'ten_cent' => 0, /*'twe_cent' => 0, 'thi_cent' => 0, 'fif_cent' => 0, 'eig_cent' => 0, 'hun_cent' => 0,*/ );
$tots_r = array( 'zer_oooo' => 0, 'one_thou' => 0, 'one_cent' => 0, /*'fiv_cent' => 0, 'ten_cent' => 0, /*'twe_cent' => 0, 'thi_cent' => 0, 'fif_cent' => 0, 'eig_cent' => 0, 'hun_cent' => 0,*/ );

for ( $i = 0; $i < $loop_num; $i++ ) {
	foreach ( array_keys( $strs ) as $idx ) {
		foreach ( $strs[ $idx ] as $str ) {

			tln_null( $str );

			$tots_u[ $idx ] += -microtime( true );
			1 === preg_match( '//u', $str ); // Original Normalizer validity check.
			$tots_u[ $idx ] += microtime( true );

			$tots_t[ $idx ] += -microtime( true );
			1 === preg_match( TLN_REGEX_IS_VALID_UTF8, $str );
			$tots_t[ $idx ] += microtime( true );

			$tots_r[ $idx ] += -microtime( true );
			1 !== preg_match( TLN_REGEX_IS_INVALID_UTF8, $str );
			$tots_r[ $idx ] += microtime( true );
		}
	}
}

$tots = array( 'tots_u' => array(), 'tots_t' => array(), 'tots_r' => array() );
foreach ( array_keys( $strs ) as $idx ) {
	$tots['tots_u'][ $idx ] = " u=" . sprintf( '%.10f', $tots_u[ $idx ] );
	$tots['tots_t'][ $idx ] = " t=" . sprintf( '%.10f', $tots_t[ $idx ] );
	$tots['tots_r'][ $idx ] = " r=" . sprintf( '%.10f', $tots_r[ $idx ] );
}
//$ret = "\n" . ' zer_oooo        one_thou        one_cent        fiv_cent        ten_cent        twe_cent        thi_cent        fif_cent        eig_cent        hun_cent';
$ret = "\n" . ' zer_oooo        one_thou        one_cent';
foreach ( $tots as $key => $val ) {
	$ret .= "\n" . implode( ' ', $val );
}
error_log( $ret );

error_log( ")===end " . $basename );
