/**
 * Lailatulqadar settings screen: tabs without a reload, live slider values,
 * colour pickers with an accent preview, the media chooser and confirmations.
 * Uses core scripts only (jQuery and wp-color-picker) and sets no globals.
 */
( function ( $ ) {
	'use strict';

	var root = document.querySelector( '.lq-admin' );
	if ( ! root ) {
		return;
	}

	/* Tabs: ARIA tablist with roving tabindex and arrow, Home and End keys. */
	var tablist = root.querySelector( '[role="tablist"]' );
	var tabs = tablist ? Array.prototype.slice.call( tablist.querySelectorAll( '[role="tab"]' ) ) : [];
	var transient = [ 'settings-updated', 'lq-reset', 'lq-sec', 'lq-count' ];

	function activate( tab, moveFocus ) {
		tabs.forEach( function ( item ) {
			var on = item === tab;
			var panel = document.getElementById( item.getAttribute( 'aria-controls' ) );
			item.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			item.tabIndex = on ? 0 : -1;
			item.classList.toggle( 'is-active', on );
			if ( panel ) {
				panel.hidden = ! on;
			}
		} );
		if ( moveFocus ) {
			tab.focus();
		}
		if ( tab.scrollIntoView ) {
			tab.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
		}
		if ( window.history && window.history.replaceState && window.URL ) {
			var url = new window.URL( window.location.href );
			url.searchParams.set( 'tab', tab.getAttribute( 'data-tab' ) );
			transient.forEach( function ( key ) {
				url.searchParams.delete( key );
			} );
			window.history.replaceState( null, '', url.toString() );
		}
	}

	tabs.forEach( function ( tab, index ) {
		tab.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			activate( tab, false );
		} );
		tab.addEventListener( 'keydown', function ( event ) {
			var next = null;
			switch ( event.key ) {
				case 'ArrowRight':
					next = tabs[ ( index + 1 ) % tabs.length ];
					break;
				case 'ArrowLeft':
					next = tabs[ ( index - 1 + tabs.length ) % tabs.length ];
					break;
				case 'Home':
					next = tabs[ 0 ];
					break;
				case 'End':
					next = tabs[ tabs.length - 1 ];
					break;
				case ' ':
				case 'Enter':
					next = tab;
					break;
			}
			if ( next ) {
				event.preventDefault();
				activate( next, true );
			}
		} );
	} );

	/* Range sliders: show the value beside each one as it moves. */
	root.querySelectorAll( 'input[type="range"][data-lq-output]' ).forEach( function ( input ) {
		var output = document.getElementById( input.getAttribute( 'data-lq-output' ) );
		if ( ! output ) {
			return;
		}
		input.addEventListener( 'input', function () {
			output.textContent = input.value;
		} );
	} );

	/* Colour pickers (wp-color-picker) and the accent preview. */
	function preview( input, value ) {
		var target = root.querySelector( '[data-lq-preview-target="' + input.getAttribute( 'data-lq-preview' ) + '"]' );
		if ( target && value ) {
			target.style.setProperty( '--lq-preview-accent', value );
		}
	}

	root.querySelectorAll( 'input[data-lq-preview]' ).forEach( function ( input ) {
		preview( input, input.value );
		if ( $ && $.fn && $.fn.wpColorPicker ) {
			$( input ).wpColorPicker( {
				change: function ( event, ui ) {
					preview( input, ui.color.toString() );
				},
				clear: function () {
					preview( input, input.getAttribute( 'data-default-color' ) );
				}
			} );
		} else {
			input.addEventListener( 'input', function () {
				preview( input, input.value );
			} );
		}
	} );

	/* Confirmation before destructive actions. */
	root.querySelectorAll( '[data-lq-confirm]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( button.getAttribute( 'data-lq-confirm' ) ) ) {
				event.preventDefault();
			}
		} );
	} );

	/* Media chooser for the logo calligraphy. */
	root.querySelectorAll( '[data-lq-media]' ).forEach( function ( box ) {
		var id = box.querySelector( '[data-lq-media-id]' );
		var image = box.querySelector( '[data-lq-media-preview]' );
		var remove = box.querySelector( '[data-lq-media-remove]' );
		var choose = box.querySelector( '[data-lq-media-choose]' );
		var frame;

		if ( ! id || ! image || ! remove || ! choose ) {
			return;
		}

		choose.addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			if ( ! frame ) {
				frame = window.wp.media( {
					title: choose.getAttribute( 'data-title' ),
					button: { text: choose.getAttribute( 'data-button' ) },
					library: { type: 'image' },
					multiple: false
				} );
				frame.on( 'select', function () {
					var file = frame.state().get( 'selection' ).first().toJSON();
					id.value = file.id;
					image.querySelector( 'img' ).src = file.url;
					image.hidden = false;
					remove.hidden = false;
					choose.focus();
				} );
			}
			frame.open();
		} );

		remove.addEventListener( 'click', function () {
			id.value = '';
			image.hidden = true;
			remove.hidden = true;
			choose.focus();
		} );
	} );
}( window.jQuery ) );
