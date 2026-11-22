/**
 * Print / Save as PDF button on guide pages (blocks/page-details). Hidden
 * until this runs, so a visitor without scripts sees no dead button; the
 * browser's print dialog offers "Save as PDF". The layout on paper is
 * css/print.css.
 *
 * @package Lailatulqadar
 * @since   1.23.0
 */
( function () {
	'use strict';
	if ( typeof window.print !== 'function' ) {
		return;
	}
	document.querySelectorAll( '[data-lq-print]' ).forEach( function ( button ) {
		var item = button.closest( '[hidden]' );
		if ( item ) {
			item.hidden = false;
		}
		button.addEventListener( 'click', function () {
			window.print();
		} );
	} );
}() );
