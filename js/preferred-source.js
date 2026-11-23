/**
 * Google preferred sources, button mode (inc/preferred-source.php). Each
 * theme button is a link to Google's source preferences tool; once Google's
 * library is ready, a click opens its flow in place instead, in the page's
 * colour scheme and language. If the library never arrives, the link works.
 *
 * @package Lailatulqadar
 * @since   1.23.0
 */
( function () {
	'use strict';
	var links = document.querySelectorAll( '[data-lq-prefsrc]' );
	if ( ! links.length ) {
		return;
	}
	function scheme() {
		var s = document.documentElement.getAttribute( 'data-scheme' );
		if ( 'dawn' === s ) {
			return 'light';
		}
		if ( 'auto' === s && window.matchMedia && window.matchMedia( '(prefers-color-scheme: light)' ).matches ) {
			return 'light';
		}
		return 'dark';
	}
	var lang = 0 === ( document.documentElement.lang || '' ).toLowerCase().indexOf( 'ms' ) ? 'ms' : 'en';
	( self.PREFERRED_SOURCE = self.PREFERRED_SOURCE || [] ).push( function ( preferredSource ) {
		try {
			preferredSource.init( { theme: scheme(), lang: lang } );
		} catch ( e ) {
			return;
		}
		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				try {
					preferredSource.addPreferredSource();
					event.preventDefault();
				} catch ( e ) {
					// Leave the link to Google's tool.
				}
			} );
		} );
	} );
}() );
