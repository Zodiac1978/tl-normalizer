<?php

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );
$dirdirname = dirname( $dirname );

error_log( "(===begin " . $basename );

require $dirname . '/functions.php';
require $dirdirname . '/Symfony/Normalizer.php';
require $dirdirname . '/Symfony/Sym_Normalizer.php';

$new_8_0_0 = array( 0x8e3, 0xa69e, /*0xa69f,*/ 0xfe2e, 0xfe2f, 0x111ca, 0x1172b, ); // Combining class additions UCD 8.0.0 re 7.0.0
$new_8_0_0_regex = '/' . implode( '|', array_map( 'tln_utf8_chr', $new_8_0_0 ) ) . '/';

$strs = array();

for ( $i = 0; $i < 2000; $i++ ) {
	$str = tln_utf8_rand_str( rand( 10, 1000 ), 0x550 );
	if ( $new_8_0_0_regex ) {
		$str = preg_replace( $new_8_0_0_regex, '', $str );
	}
	$strs[] = $str;
}
$cnt = count( $strs );

$is_normalized_tln = $is_normalized_sym = $is_normalized_php = 0;
$true_tln = $true_sym = $true_php = 0;
$i = $j = 0;
$loop_cnt = 50;

for ( $j = 0; $j < $loop_cnt; $j++ ) {
	for ( $i = 0; $i < $cnt; $i++ ) {
		$is_normalized_tln += -microtime( true );
		if ( TLN_Normalizer::isNormalized( $strs[ $i ] ) ) $true_tln++;
		$is_normalized_tln += microtime( true );

		$is_normalized_sym += -microtime( true );
		if ( Sym_Normalizer::isNormalized( $strs[ $i ] ) ) $true_sym++;
		$is_normalized_sym += microtime( true );

		$is_normalized_php += -microtime( true );
		if ( Normalizer::isNormalized( $strs[ $i ] ) ) $true_php++;
		$is_normalized_php += microtime( true );
	}
}

error_log( "is_normalized_tln  =" . sprintf( '%.10f', $is_normalized_tln ) . ", true_tln=$true_tln" );
error_log( "is_normalized_sym  =" . sprintf( '%.10f', $is_normalized_sym ) . ", true_sym=$true_sym" );
error_log( "is_normalized_php  =" . sprintf( '%.10f', $is_normalized_php ) . ", true_php=$true_php" );

error_log( ")===end " . $basename );
