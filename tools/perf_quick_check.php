<?php

$basename = basename( __FILE__ );
$dirname = dirname( __FILE__ );
$dirdirname = dirname( $dirname );

error_log( "(===begin " . $basename );

require $dirname . '/functions.php';

	function tln_null( $string ) {
		return ! empty( $string );
	}

	function tln_is_subset_NFC_u( $string ) {
		return 0 === preg_match( '/[^\x00-\x{2FF}]/u', $string );
	}

	function tln_is_subset_NFC( $string ) {
		return 1 === preg_match(
			'/\A(?:
			  [\x00-\x7F]                                             # ASCII
			| [\xC2-\xCB][\x80-\xBF]                                  # 110xxxxx 10xxxxxx up to U+02FF
			)*+\z/x',
			$string
		);
	}

	require $dirdirname . '/Symfony/tln_ins.php';

global $tln_nfc_maybes_or_reorders;

$valid_strs = array();

for ( $i = 0; $i < 100; $i++ ) {
	$valid_strs[] = tln_utf8_rand_str( rand( 100, 100000 ), 0x2ff );
}

$one_thou_strs = array();

for ( $i = 0; $i < 100; $i++ ) {
	$one_thou_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.001, $tln_nfc_maybes_or_reorders );
}

$one_cent_strs = array();

for ( $i = 0; $i < 100; $i++ ) {
	$one_cent_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.01, $tln_nfc_maybes_or_reorders );
}

$ten_cent_strs = array();

for ( $i = 0; $i < 100; $i++ ) {
	$ten_cent_strs[] = tln_utf8_rand_ratio_str( rand( 100, 100000 ), 0.1, $tln_nfc_maybes_or_reorders );
}

for ( $i = 0; $i < 100; $i++ ) {
	if ( ! tln_is_subset_NFC( $valid_strs[ $i ] ) ) {
		error_log( "bad result valid_strs[ $i ]" );
		return;
	}
	if ( tln_is_subset_NFC( $valid_strs[ $i ] ) !== tln_is_subset_NFC_u( $valid_strs[ $i ] ) ) {
		error_log( "bad match valid_strs[ $i ]" );
		return;
	}
	if ( tln_is_subset_NFC( $one_thou_strs[ $i ] ) !== tln_is_subset_NFC_u( $one_thou_strs[ $i ] ) ) {
		error_log( "bad match one_thou_strs[ $i ]" );
		return;
	}
	if ( tln_is_subset_NFC( $one_cent_strs[ $i ] ) !== tln_is_subset_NFC_u( $one_cent_strs[ $i ] ) ) {
		error_log( "bad match one_cent_strs[ $i ]" );
		return;
	}
	if ( tln_is_subset_NFC( $ten_cent_strs[ $i ] ) !== tln_is_subset_NFC_u( $ten_cent_strs[ $i ] ) ) {
		error_log( "bad match ten_cent_strs[ $i ]" );
		return;
	}
}

$is_subse_tot_m = $is_subse_tot_u = $is_subse_tot = 0;
$one_thou_tot_m = $one_thou_tot_u = $one_thou_tot = 0;
$one_cent_tot_m = $one_cent_tot_u = $one_cent_tot = 0;
$ten_cent_tot_m = $ten_cent_tot_u = $ten_cent_tot = 0;

// Valid

$i = 0;
while ( ++$i < 100 ) {
	tln_null( $valid_strs[ $i ] );
}

$i = 0;
$is_subse_tot = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC( $valid_strs[ $i ] );
}
$is_subse_tot += microtime( true );

$i = 0;
$is_subse_tot_u = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC_u( $valid_strs[ $i ] );
}
$is_subse_tot_u += microtime( true );

$i = 0;
$is_subse_tot_m = -microtime( true );
while ( ++$i < 100 ) {
	if ( ! tln_in_nfc_noes( $valid_strs[ $i ] ) ) {
		tln_in_nfc_maybes_or_reorders( $valid_strs[ $i ] );
	}
}
$is_subse_tot_m += microtime( true );

// One thou

$i = 0;
while ( ++$i < 100 ) {
	tln_null( $one_thou_strs[ $i ] );
}

$i = 0;
$one_thou_tot = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC( $one_thou_strs[ $i ] );
}
$one_thou_tot += microtime( true );

$i = 0;
$one_thou_tot_u = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC_u( $one_thou_strs[ $i ] );
}
$one_thou_tot_u += microtime( true );

$i = 0;
$one_thou_tot_m = -microtime( true );
while ( ++$i < 100 ) {
	if ( ! tln_in_nfc_noes( $one_thou_strs[ $i ] ) ) {
		tln_in_nfc_maybes_or_reorders( $one_thou_strs[ $i ] );
	}
}
$one_thou_tot_m += microtime( true );

// One cent

$i = 0;
while ( ++$i < 100 ) {
	tln_null( $one_cent_strs[ $i ] );
}

$i = 0;
$one_cent_tot = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC( $one_cent_strs[ $i ] );
}
$one_cent_tot += microtime( true );

$i = 0;
$one_cent_tot_u = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC_u( $one_cent_strs[ $i ] );
}
$one_cent_tot_u += microtime( true );

$i = 0;
$one_cent_tot_m = -microtime( true );
while ( ++$i < 100 ) {
	if ( ! tln_in_nfc_noes( $one_cent_strs[ $i ] ) ) {
		tln_in_nfc_maybes_or_reorders( $one_cent_strs[ $i ] );
	}
}
$one_cent_tot_m += microtime( true );

// Ten cent

$i = 0;
while ( ++$i < 100 ) {
	tln_null( $ten_cent_strs[ $i ] );
}

$i = 0;
$ten_cent_tot = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC( $ten_cent_strs[ $i ] );
}
$ten_cent_tot += microtime( true );

$i = 0;
$ten_cent_tot_u = -microtime( true );
while ( ++$i < 100 ) {
	tln_is_subset_NFC_u( $ten_cent_strs[ $i ] );
}
$ten_cent_tot_u += microtime( true );

$i = 0;
$ten_cent_tot_m = -microtime( true );
while ( ++$i < 100 ) {
	if ( ! tln_in_nfc_noes( $ten_cent_strs[ $i ] ) ) {
		tln_in_nfc_maybes_or_reorders( $ten_cent_strs[ $i ] );
	}
}
$ten_cent_tot_m += microtime( true );

error_log( "is_subse_tot  =" . sprintf( '%.10f', $is_subse_tot ) . ", one_thou_tot  =" . sprintf( '%.10f', $one_thou_tot )
	. ", one_cent_tot  =" . sprintf( '%.10f', $one_cent_tot ) . ", ten_cent_tot  =" . sprintf( '%.10f', $ten_cent_tot ) );
error_log( "is_subse_tot_u=" . sprintf( '%.10f', $is_subse_tot_u ) . ", one_thou_tot_u=" . sprintf( '%.10f', $one_thou_tot_u )
	. ", one_cent_tot_u=" . sprintf( '%.10f', $one_cent_tot_u ) . ", ten_cent_tot_u=" . sprintf( '%.10f', $ten_cent_tot_u ) );
error_log( "is_subse_tot_m=" . sprintf( '%.10f', $is_subse_tot_m ) . ", one_thou_tot_m=" . sprintf( '%.10f', $one_thou_tot_m )
	. ", one_cent_tot_m=" . sprintf( '%.10f', $one_cent_tot_m ) . ", ten_cent_tot_m=" . sprintf( '%.10f', $ten_cent_tot_m ) );

error_log( ")===end " . $basename );
