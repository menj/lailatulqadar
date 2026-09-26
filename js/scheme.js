/**
 * Lailatulqadar light/dark switch.
 *
 * Loaded in the head so a visitor's saved choice applies before the page
 * paints. The site default (night, dawn or auto) comes from the
 * data-scheme attribute printed by the theme; a visitor's choice, stored in
 * this browser only, overrides it.
 */
( function () {
	'use strict';

	var KEY = 'lq-scheme';
	var root = document.documentElement;

	function saved() {
		try {
			var v = window.localStorage.getItem( KEY );
			return v === 'night' || v === 'dawn' ? v : null;
		} catch ( e ) {
			return null;
		}
	}

	function effective() {
		var s = root.getAttribute( 'data-scheme' );
		if ( s === 'auto' ) {
			return window.matchMedia && window.matchMedia( '(prefers-color-scheme: light)' ).matches ? 'dawn' : 'night';
		}
		return s === 'dawn' ? 'dawn' : 'night';
	}

	var choice = saved();
	if ( choice ) {
		root.setAttribute( 'data-scheme', choice );
	}

	function sync( button ) {
		var now = effective();
		var next = now === 'dawn' ? 'night' : 'dawn';
		button.setAttribute( 'aria-label', button.getAttribute( next === 'dawn' ? 'data-label-light' : 'data-label-dark' ) );
		button.setAttribute( 'title', button.getAttribute( 'aria-label' ) );
		button.setAttribute( 'data-current', now );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var buttons = document.querySelectorAll( '.lq-scheme-toggle' );
		buttons.forEach( function ( button ) {
			sync( button );
			button.addEventListener( 'click', function () {
				var next = effective() === 'dawn' ? 'night' : 'dawn';
				root.setAttribute( 'data-scheme', next );
				try {
					window.localStorage.setItem( KEY, next );
				} catch ( e ) {}
				buttons.forEach( sync );
			} );
		} );
	} );
}() );
