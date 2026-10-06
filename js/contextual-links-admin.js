( function () {
	'use strict';
	var root = document.querySelector( '.cl-admin' );
	if ( ! root ) { return; }
	var tabs = root.querySelectorAll( '.cl-admin__tab' );
	var panels = root.querySelectorAll( '.cl-admin__panel' );
	function show( name ) {
		tabs.forEach( function ( t ) { t.setAttribute( 'aria-selected', t.dataset.tab === name ? 'true' : 'false' ); } );
		panels.forEach( function ( p ) { p.hidden = p.dataset.panel !== name; } );
		try { window.localStorage.setItem( 'clTab', name ); } catch ( e ) {}
	}
	tabs.forEach( function ( t ) { t.addEventListener( 'click', function () { show( t.dataset.tab ); } ); } );
	var saved = 'domains';
	try { saved = window.localStorage.getItem( 'clTab' ) || saved; } catch ( e ) {}
	show( root.querySelector( '[data-tab="' + saved + '"]' ) ? saved : 'domains' );
}() );
