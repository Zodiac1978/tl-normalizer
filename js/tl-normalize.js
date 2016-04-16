/**
 * Javascript for Normalizer WP plugin.
 */
/*jslint ass: true, nomen: true, plusplus: true, regexp: true, vars: true, white: true, indent: 4 */
/*global jQuery */
/*exported tl_normalize */

var tl_normalize = tl_normalize || {};

( function ( $, window ) {
	'use strict';

	/**
	 * Normalize text pasted into tinymce.
	 */
	$( document ).on( 'tinymce-editor-init', function( event, editor ) {
		// Using PastePreProcess, which is fired with the paste as a HTML string set in event.content.
		// Easy option, may not be the best.
		editor.on( 'PastePreProcess', function( event ) {
			if ( event.content && event.content.length && event.content.normalize ) {
				event.content = event.content.normalize();
			}
		} );
	} );
} )( jQuery, window );
