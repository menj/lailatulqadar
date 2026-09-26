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
		var update = function () {
			var used = Array.from( field.value ).length;
			count.textContent = used + ' / ' + limit;
			count.classList.toggle( 'is-over', used > limit );
		};
		field.addEventListener( 'input', update );
		update();
	} );
}() );
