/**
 * Lailatulqadar settings screen: live accent preview and confirmations.
 */
( function () {
	'use strict';

	document.querySelectorAll( 'input[data-lq-preview]' ).forEach( function ( input ) {
		var target = document.querySelector( '[data-lq-preview-target="' + input.dataset.lqPreview + '"]' );
		if ( ! target ) {
			return;
		}
		var apply = function () {
			target.style.setProperty( '--lq-preview-accent', input.value );
		};
		input.addEventListener( 'input', apply );
		apply();
	} );

	document.querySelectorAll( '[data-lq-confirm]' ).forEach( function ( button ) {
		button.addEventListener( 'click', function ( event ) {
			if ( ! window.confirm( button.dataset.lqConfirm ) ) {
				event.preventDefault();
			}
		} );
	} );

	document.querySelectorAll( '[data-lq-media]' ).forEach( function ( box ) {
		var id = box.querySelector( '[data-lq-media-id]' );
		var preview = box.querySelector( '[data-lq-media-preview]' );
		var remove = box.querySelector( '[data-lq-media-remove]' );
		var choose = box.querySelector( '[data-lq-media-choose]' );
		var frame;

		choose.addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			if ( ! frame ) {
				frame = window.wp.media( {
					title: choose.dataset.title,
					button: { text: choose.dataset.button },
					library: { type: 'image' },
					multiple: false
				} );
				frame.on( 'select', function () {
					var file = frame.state().get( 'selection' ).first().toJSON();
					id.value = file.id;
					preview.querySelector( 'img' ).src = file.url;
					preview.hidden = false;
					remove.hidden = false;
				} );
			}
			frame.open();
		} );

		remove.addEventListener( 'click', function () {
			id.value = '';
			preview.hidden = true;
			remove.hidden = true;
		} );
	} );
}() );
