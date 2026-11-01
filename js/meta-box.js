/**
 * Lailatulqadar "Search appearance" box: live character counts.
 */
( function () {
	'use strict';

	document.querySelectorAll( '.lq-seo [data-lq-limit]' ).forEach( function ( field ) {
		var count = document.querySelector( '[data-lq-count-for="' + field.id + '"]' );
		if ( ! count ) {
			return;
		}
		var limit = parseInt( field.dataset.lqLimit, 10 );
		// The server prints the count in the editor's language ("12 of 60
		// characters"); only its first number changes as the editor types.
		var template = count.textContent;
		var update = function () {
			// [lq_ramadan] shortcodes print as a four-digit year, so they
			// count as four characters, as the server counts them.
			var used = Array.from( field.value.replace( /\[lq_ramadan[^\]]*\]/g, '0000' ) ).length;
			count.textContent = template.replace( /\d+/, String( used ) );
			count.classList.toggle( 'is-over', used > limit );
		};
		field.addEventListener( 'input', update );
		update();
	} );
}() );
