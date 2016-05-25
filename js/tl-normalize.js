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
				var before = event.content;
				event.content = event.content.normalize();
				console.log('normalize before=%s, after=%s, same=%d', before, event.content, before === event.content );
			}
		} );
	} );

	/**
	 * Normalize text pasted into any standard admin input/textarea.
	 */
	 $( document ).ready( function () {
		var $wpcontent = $( '#wpcontent' );
		if ( $wpcontent.length ) {
			$( 'input, textarea', $wpcontent ).on( 'paste', function ( event ) {
				var $el = $(this);
				if ( $el.val().normalize ) {
					// http://stackoverflow.com/a/1503425/664741
					setTimeout( function () {
						var before = $el.val(), dmp = '', i;
						for ( i = 0; i < before.length; i++ ) {
							dmp += ( '0000' + before.charCodeAt( i ).toString( 16 ) ).slice( -4 ) + ' ';
						}
						$el.val( $el.val().normalize() );
						console.log('normalize before=%s, after=%s, same=%d, dmp=%s', before, $el.val(), before === $el.val(), dmp );
					} );
				}
			} );
		}

	 	if ( wp && wp.media ) {
			console.log('wp.media=%o', wp.media);
			wp.media.bind( 'open', function () {
				console.log( 'media open' );
			} );
			if ( wp.media.view ) {
				console.log('wp.media.view=%o', wp.media.view);
				if ( wp.media.view.frame ) {
					console.log('wp.media.view.frame=%o', wp.media.view.frame);
				}
			}
			/*
			if ( wp.Uploader ) {
				console.log('wp.Uploader=%o', wp.Uploader);
				if (wp.Uploader.queue) {
					console.log('wp.Uploader.queue=%o', wp.Uploader.queue);
				}
			}
			if ( wp.media.frame ) {
				console.log('wp.media.frame=%o', wp.media.frame);
			}
			*/
			if ( wp.media.controller ) {
				/*
				console.log('have controller=%o', wp.media.controller);
				console.log('Library=%o', wp.media.controller.Library);
				*/
				/*
				var bind = wp.media.controller.Library.bind;
				console.log('bind=%o', bind);
				wp.media.controller.Library.bind( 'all', function (d) {
					console.log('all this=%o', this);
				} );
				*/
				/*
				console.log('wp.media.controller=%o', wp.media.controller);
				if (wp.media.controller.on) {
					console.log('have controller on');
					wp.media.controller.on( 'reset', function () {
						console.log('controller reset');
					} );
				}
				console.log('wp.media.controller.State', wp.media.controller.State);
				console.log('wp.media.controller.State.bind=%o', wp.media.controller.State.bind);
				if (wp.media.controller.State.bind) {
					wp.media.controller.State.bind( 'reset', function (d) {
						console.log('State all this=%o', this);
					} );
				}
				console.log('wp.media.controller.StateMachine=%o', wp.media.controller.StateMachine);
				if (wp.media.controller.StateMachine.on) {
					wp.media.controller.StateMachine.on( 'ready', function (d) {
						console.log('State all this=%o', this);
					} );
				}
				*/
			}
			var reach = wp.media.frame;
			console.log('reach=%o', wp.media.frame);
		}
	 	if ( wp && wp.media && wp.media.view ) {
			/*
			console.log('wp.media.view.Attachment=%o', wp.media.view.Attachment);
			if (wp.media.view.controller) {
			}
			if (wp.media.view.Attachment.Details ) {
				if (wp.media.view.Attachment.Details.prototype) {
					console.log('have attachment details prototype=%o', wp.media.view.Attachment.Details.prototype );
				}
				if (wp.media.view.Attachment.model) {
					console.log('have  model');
				}
			}
			*/
			/*
			if (wp.media.view.Attachment && wp.media.view.Attachment.Details) {
				console.log('have attachment details=%o', wp.media.view.Attachment.Details);
				if (wp.media.view.Attachment.Details.on) {
					console.log('have details on' );
				} else {
					console.log('ndetails o on' );
				}
				wp.media.view.ImageDetails.on( 'all', function ( d ) {
					console.log('imagedetails all');
				} );
				wp.media.view.ImageDetails.on( 'change', function ( d ) {
					console.log('imagedetails change');
				} );
			}
			*/
		}
	 } );

} )( jQuery, window );
