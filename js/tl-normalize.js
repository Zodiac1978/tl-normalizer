/**
 * Javascript for Normalizer WP plugin.
 */
/*jslint ass: true, nomen: true, plusplus: true, regexp: true, vars: true, white: true, indent: 4 */
/*global jQuery, wp, tln_params */
/*exported tl_normalize */

var tl_normalize = tl_normalize || {}; // Our namespace.

( function ( $ ) {
	'use strict';

	/**
	 * Helper to normalize text pasted into text-like inputs and textareas.
	 */
	tl_normalize.input_textarea_normalize_on_paste = function ( context ) {
		// TODO: Other types: "email", "password", "url" ??
		$( 'input[type="text"], input[type="search"], textarea', context ).on( 'paste', function ( event ) {
			var $el = $( this );
			if ( $el.val().normalize ) {
				// http://stackoverflow.com/a/1503425/664741
				setTimeout( function () {
					var before = $el.val(), after = before.normalize(), selection;
					if ( before !== after ) {
						if ( ! ( tln_params && tln_params.p.dont_paste ) ) {
							selection = $el.getSelection();
							$el.val( after );
							$el.setSelection( selection.start + ( after.length - before.length ), selection.end + ( after.length - before.length ) );
						}
						$el.change(); // Trigger change.
					}
					if ( tln_params && tln_params.p.script_debug ) {
						tl_normalize.dmp_before_and_after( before, after );
					}
				} );
			}
		} );
	};

	/**
	 * Normalize text pasted into tinymce.
	 */
	tl_normalize.tinymce_editor_init = function () {
		$( document ).on( 'tinymce-editor-init', function ( event, editor ) {
			// Using PastePreProcess, which is fired with the paste as a HTML string set in event.content.
			// Easy option, may not be the best.
			editor.on( 'PastePreProcess', function( event ) {
				var before; // Keep jshint happy.
				if ( event.content && event.content.length && event.content.normalize ) {
					if ( tln_params && tln_params.p.script_debug ) {
						before = event.content;
					}
					event.content = event.content.normalize();
					if ( tln_params && tln_params.p.script_debug ) {
						tl_normalize.dmp_before_and_after( before, event.content );
					}
				}
			} );
		} );
	};

	/**
	 * Call in admin on jQuery ready.
	 * Standard admin inputs and wp.media stuff.
	 */
	tl_normalize.admin_ready = function () {

		var $wpcontent = $( '#wpcontent' ), old_details_render, old_display_render;

		// Any standard admin text input or textarea. May need refining.
		if ( $wpcontent.length ) {
			tl_normalize.input_textarea_normalize_on_paste( $wpcontent );
		}

		// Media.
		if ( wp && wp.media && wp.media.view ) {
			if ( wp.media.view.Attachment && wp.media.view.Attachment.Details ) {
				// Override render. Probably not the best option.
				old_details_render = wp.media.view.Attachment.Details.prototype.render;
				wp.media.view.Attachment.Details.prototype.render = function () {
					old_details_render.apply( this, arguments );
					tl_normalize.input_textarea_normalize_on_paste( this.$el );
				};
			}
			if ( wp.media.view.Settings && wp.media.view.Settings.AttachmentDisplay ) {
				// Override render. Again, probably not the best option.
				old_display_render = wp.media.view.Settings.AttachmentDisplay.prototype.render;
				wp.media.view.Settings.AttachmentDisplay.prototype.render = function () {
					old_display_render.apply( this, arguments );
					tl_normalize.input_textarea_normalize_on_paste( this.$el );
				};
			}
			// TODO: Other media stuff.
		}
		// TODO: Other stuff.
	};

	/**
	 * Call in front end on jQuery ready.
	 * Standard inputs.
	 */
	tl_normalize.front_end_ready = function () {

		// Any standard text input or textarea. May need refining.
		tl_normalize.input_textarea_normalize_on_paste();

	};

	/**
	 * Call in admin.
	 * Customizer stuff.
	 */
	tl_normalize.customizer_ready = function () {
		// Customizer - do outside jQuery ready otherwise will miss 'ready' event.
		if ( wp && wp.customize ) {
			wp.customize.bind( 'ready', function () {
				tl_normalize.input_textarea_normalize_on_paste();
			} );
		}
	};

	/**
	 * Debug helper - dump before and after.
	 */
	tl_normalize.dmp_before_and_after = function ( before, after ) {
		if ( console && console.log ) {
			var i, before_dmp = '', after_dmp = '';
			if ( before === after ) {
				console.log( 'normalize same' );
			} else {
				for ( i = 0; i < before.length; i++ ) {
					before_dmp += ( '0000' + before.charCodeAt( i ).toString( 16 ) ).slice( -4 ) + ' ';
				}
				for ( i = 0; i < after.length; i++ ) {
					after_dmp += ( '0000' + after.charCodeAt( i ).toString( 16 ) ).slice( -4 ) + ' ';
				}
				console.log( 'normalize different\nbefore_dmp=%s\n after_dmp=%s', before_dmp, after_dmp );
			}
		}
	};

} )( jQuery );
