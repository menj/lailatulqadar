/**
 * Reading progress bar (inc/reading-progress.php). One passive scroll
 * listener; the width is written once per animation frame.
 */
( function () {
	'use strict';
	var fill = document.querySelector( '.lq-rpb__fill' );
	if ( ! fill ) {
		return;
	}
	var target = document.querySelector( '.wp-block-post-content, .entry-content, main' );
	var queued = false;

	function pct() {
		var viewH = window.innerHeight;
		if ( target ) {
			// 0 while the top of the content is at or below the top of the
			// window, 100 once its end reaches the bottom of the window.
			var rect = target.getBoundingClientRect();
			var readable = target.offsetHeight - viewH;
			if ( readable <= 0 ) {
				return rect.bottom <= viewH ? 100 : 0;
			}
			return Math.min( 100, Math.max( 0, ( -rect.top / readable ) * 100 ) );
		}
		var max = document.documentElement.scrollHeight - viewH;
		return max > 0 ? Math.min( 100, ( window.scrollY / max ) * 100 ) : 100;
	}

	function paint() {
		queued = false;
		fill.style.transform = 'scaleX(' + ( pct() / 100 ).toFixed( 4 ) + ')';
	}

	function request() {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( paint );
		}
	}

	window.addEventListener( 'scroll', request, { passive: true } );
	window.addEventListener( 'resize', request, { passive: true } );
	// Images, fonts and late content change the page height: recompute then too.
	if ( typeof ResizeObserver === 'function' ) {
		new ResizeObserver( request ).observe( document.body );
		if ( target && target !== document.body ) {
			new ResizeObserver( request ).observe( target );
		}
	}
	paint();
}() );
