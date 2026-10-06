( function () {
	'use strict';
	var root = document.querySelector( '.nl-admin' );
	if ( ! root ) { return; }
	var tabs = root.querySelectorAll( '.nl-admin__tab' );
	var panels = root.querySelectorAll( '.nl-admin__panel' );
	function show( name ) {
		tabs.forEach( function ( t ) { t.setAttribute( 'aria-selected', t.dataset.tab === name ? 'true' : 'false' ); } );
		panels.forEach( function ( p ) { p.hidden = p.dataset.panel !== name; } );
		try { window.localStorage.setItem( 'nlTab', name ); } catch ( e ) {}
	}
	tabs.forEach( function ( t ) { t.addEventListener( 'click', function () { show( t.dataset.tab ); } ); } );
	var saved = 'sites';
	try { saved = window.localStorage.getItem( 'nlTab' ) || saved; } catch ( e ) {}
	show( root.querySelector( '[data-tab="' + saved + '"]' ) ? saved : 'sites' );
}() );
