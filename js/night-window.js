/**
 * Lailatulqadar night window.
 *
 * Shows, for a chosen city, when each of the last ten nights of Ramadan
 * begins (sunset), when its last third begins, and when it ends (dawn).
 * Prayer times come from the adhan library (MIT), loaded before this file.
 * Everything runs in the visitor's browser; no location leaves the device.
 */
( function () {
	'use strict';

	var A = window.adhan;
	var DAY = 86400000;
	var STORE = 'lq-night-window';
	var METHOD_LABELS = {
		UmmAlQura: 'Umm al-Qura',
		Dubai: 'Dubai',
		Qatar: 'Qatar',
		Kuwait: 'Kuwait',
		MuslimWorldLeague: 'Muslim World League',
		Egyptian: 'Egyptian General Authority of Survey',
		Turkey: 'Diyanet (Türkiye)',
		Tehran: 'Tehran',
		Karachi: 'University of Islamic Sciences, Karachi',
		Singapore: '20° / 18° (JAKIM, MUIS and Kemenag type)',
		NorthAmerica: 'ISNA'
	};

	if ( ! A ) {
		return;
	}

	document.querySelectorAll( '.lq-nw' ).forEach( init );

	function init( root ) {
		var cfg;
		try {
			cfg = JSON.parse( root.getAttribute( 'data-config' ) );
		} catch ( e ) {
			return;
		}
		var S = cfg.strings;
		var app = root.querySelector( '.lq-nw__app' );
		var saved = load();
		if ( ! cfg.years || ! cfg.years.length ) {
			return;
		}
		var state = {
			city: findCity( cfg, saved.city ) || findCity( cfg, S.defaultCity ) || cfg.cities[ 0 ],
			which: saved.which === 'alt' ? 'alt' : 'start',
			year: yearOf( cfg, cfg.current ) ? cfg.current : cfg.years[ 0 ].hijri,
			selected: null,
			nights: []
		};
		if ( saved.custom && saved.city === 'custom' ) {
			state.city = saved.custom;
		}

		app.innerHTML = shell( cfg, S );
		app.hidden = false;

		var el = {
			select: app.querySelector( '.lq-nw__city' ),
			locate: app.querySelector( '.lq-nw__locate' ),
			starts: app.querySelector( '.lq-nw__starts' ),
			pills: app.querySelector( '.lq-nw__pills' ),
			yearLabel: app.querySelector( '.lq-nw__year-label' ),
			prev: app.querySelector( '.lq-nw__year-prev' ),
			next: app.querySelector( '.lq-nw__year-next' ),
			status: app.querySelector( '.lq-nw__status' ),
			strip: app.querySelector( '.lq-nw__nights' ),
			detail: app.querySelector( '.lq-nw__detail' ),
			meta: app.querySelector( '.lq-nw__method' )
		};

		el.select.value = state.city.id === 'custom' ? '' : state.city.id;
		el.select.addEventListener( 'change', function () {
			var c = findCity( cfg, el.select.value );
			if ( c ) {
				state.city = c;
				state.selected = null;
				update();
			}
		} );

		el.locate.addEventListener( 'click', function () {
			if ( ! navigator.geolocation ) {
				el.status.textContent = S.locateFail;
				return;
			}
			el.locate.disabled = true;
			el.locate.querySelector( 'span' ).textContent = S.locating;
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				var lat = pos.coords.latitude;
				var lng = pos.coords.longitude;
				var near = nearest( cfg.cities, lat, lng );
				state.city = {
					id: 'custom',
					name: S.myLocation,
					country: '',
					lat: lat,
					lng: lng,
					tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
					method: near.distance < 1500 ? near.city.method : 'MuslimWorldLeague'
				};
				el.select.value = '';
				resetLocate();
				state.selected = null;
				update();
			}, function () {
				resetLocate();
				el.status.textContent = S.locateFail;
			}, { timeout: 10000, maximumAge: 3600000 } );
		} );

		function resetLocate() {
			el.locate.disabled = false;
			el.locate.querySelector( 'span' ).textContent = S.locate;
		}

		el.starts.addEventListener( 'change', function ( e ) {
			if ( e.target && e.target.name === 'lq-nw-start' ) {
				state.which = e.target.value;
				state.selected = null;
				update();
			}
		} );

		function stepYear( delta ) {
			var i = indexOfYear( cfg, state.year ) + delta;
			if ( i >= 0 && i < cfg.years.length ) {
				state.year = cfg.years[ i ].hijri;
				state.selected = null;
				update();
			}
		}
		el.prev.addEventListener( 'click', function () {
			stepYear( -1 );
		} );
		el.next.addEventListener( 'click', function () {
			stepYear( 1 );
		} );

		el.strip.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( '[data-night]' );
			if ( b ) {
				select( parseInt( b.getAttribute( 'data-night' ), 10 ), false );
			}
		} );

		el.strip.addEventListener( 'keydown', function ( e ) {
			var keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
			if ( keys[ e.key ] ) {
				e.preventDefault();
				var next = Math.min( 30, Math.max( 21, state.selected + keys[ e.key ] ) );
				select( next, true );
			} else if ( e.key === 'Home' || e.key === 'End' ) {
				e.preventDefault();
				select( e.key === 'Home' ? 21 : 30, true );
			}
		} );

		el.detail.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( '[data-ics]' );
			if ( ! b ) {
				return;
			}
			var which = b.getAttribute( 'data-ics' );
			var list = which === 'all' ? state.nights : state.nights.filter( function ( n ) {
				return n.n === state.selected;
			} );
			download( ics( list, S, cfg, state.year ), which === 'all' ? 'last-ten-nights.ics' : 'night-' + state.selected + '.ics' );
		} );

		function centreSelected() {
			var chip = el.strip.querySelector( '.is-selected' );
			if ( chip && el.strip.scrollWidth > el.strip.clientWidth ) {
				el.strip.scrollLeft = chip.offsetLeft - ( el.strip.clientWidth - chip.clientWidth ) / 2;
			}
		}

		function select( n, focus ) {
			state.selected = n;
			renderStrip();
			renderDetail();
			if ( focus ) {
				var b = el.strip.querySelector( '[data-night="' + n + '"]' );
				if ( b ) {
					b.focus();
					b.scrollIntoView( { block: 'nearest', inline: 'center' } );
				}
			}
		}

		function update() {
			var yr = yearOf( cfg, state.year );
			state.nights = compute( state.city, yr[ state.which ] );
			if ( ! state.selected ) {
				var t = tonight( state.nights );
				state.selected = t ? t.n : 27;
			}
			save( { city: state.city.id, which: state.which, custom: state.city.id === 'custom' ? state.city : null } );
			renderStarts();
			renderStrip();
			centreSelected();
			renderDetail();
			renderStatus();
			el.meta.textContent = fmt( S.method, ( S.methods && S.methods[ state.city.method ] ) || methodLabel( state.city.method ) ) + ' ' + S.note;
		}

		function renderStarts() {
			var yr = yearOf( cfg, state.year );
			var i = indexOfYear( cfg, state.year );
			el.pills.innerHTML = [ 'start', 'alt' ].map( function ( key ) {
				return '<label class="lq-nw__pill"><input type="radio" name="lq-nw-start" value="' + key + '"' + ( key === state.which ? ' checked' : '' ) + '><span>' +
					esc( fmt( S.firstDay, dayLabel( yr[ key ], S.locale ) ) ) + '</span></label>';
			} ).join( '' );
			el.yearLabel.textContent = fmt( S.year, yr.hijri + S.hijriSuffix ) + ' · ' + yr.start.slice( 0, 4 );
			el.prev.disabled = i <= 0;
			el.next.disabled = i >= cfg.years.length - 1;
		}

		function renderStrip() {
			var tz = state.city.tz;
			el.strip.innerHTML = state.nights.map( function ( n ) {
				var sel = n.n === state.selected;
				return '<button type="button" role="tab" class="lq-nw__chip' +
					( n.odd ? ' is-odd' : '' ) + ( n.n === 27 ? ' is-27' : '' ) + ( n.n === 30 ? ' is-maybe' : '' ) + ( sel ? ' is-selected' : '' ) + ( n.isTonight ? ' is-tonight' : '' ) +
					'" data-night="' + n.n + '" aria-selected="' + sel + '" tabindex="' + ( sel ? 0 : -1 ) + '" aria-controls="lq-nw-detail" aria-label="' + esc( fmt( S.hijriNight, n.n, state.year ) + ', ' + date( n.sunset, tz, S.locale, { weekday: 'long', day: 'numeric', month: 'long' } ) + ', ' + S.lastThird + ' ' + time( n.third, tz, S.locale ) ) + '"' +
					( n.n === 30 ? ' title="' + esc( S.maybe ) + '"' : '' ) + '>' +
					'<span class="lq-nw__chip-n">' + n.n + ( n.n === 27 ? '<span class="lq-nw__star" aria-hidden="true">✦</span>' : '' ) + '</span>' +
					'<span class="lq-nw__chip-d">' + esc( date( n.sunset, tz, S.locale, { weekday: 'short', day: 'numeric', month: 'short' } ) ) + '</span>' +
					'<span class="lq-nw__chip-t">' + esc( time( n.third, tz, S.locale ) ) + '</span>' +
					'</button>';
			} ).join( '' );
		}

		function renderDetail() {
			var n = find( state.nights, state.selected );
			if ( ! n ) {
				return;
			}
			var tz = state.city.tz;
			var len = n.dawn - n.sunset;
			var place = state.city.name + ( state.city.country ? ', ' + state.city.country : '' );
			el.detail.innerHTML =
				'<div class="lq-nw__detail-head">' +
					'<p class="lq-nw__kicker">' + esc( place ) + '</p>' +
					'<h3 class="lq-nw__night">' + esc( fmt( S.night, label( n.n, cfg ) ) ) + ' <span class="lq-nw__badge' + ( n.odd ? ' is-odd' : '' ) + '">' + esc( n.odd ? S.odd : S.even ) + '</span></h3>' +
					'<p class="lq-nw__hijri">' + esc( fmt( S.hijriNight, n.n, state.year ) ) + '</p>' +
					'<p class="lq-nw__when">' + esc( fmt( S.evening, date( n.sunset, tz, S.locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' } ) ) ) + '</p>' +
					( n.n === 30 ? '<p class="lq-nw__hint">' + esc( S.maybe ) + '</p>' : '' ) +
				'</div>' +
				arc( n, tz, S ) +
				'<dl class="lq-nw__stats">' +
					stat( S.begins, time( n.sunset, tz, S.locale ), '' ) +
					stat( S.lastThird, time( n.third, tz, S.locale ), 'is-key' ) +
					stat( S.ends, time( n.dawn, tz, S.locale ), '' ) +
					stat( S.length, duration( len, S ), '' ) +
				'</dl>' +
				'<div class="lq-nw__actions">' +
					'<button type="button" class="lq-nw__btn is-primary" data-ics="one">' + icon( 'cal' ) + esc( S.addOne ) + '</button>' +
					'<button type="button" class="lq-nw__btn" data-ics="all">' + icon( 'cal' ) + esc( S.addAll ) + '</button>' +
				'</div>';
		}

		function renderStatus() {
			var now = Date.now();
			var first = state.nights[ 0 ];
			var last = state.nights[ state.nights.length - 1 ];
			var t = tonight( state.nights );
			var text;
			if ( t ) {
				text = fmt( t.odd ? S.tonightOdd : S.tonight, fmt( S.hijriDate, t.n, state.year ) ) + ' ' +
					( now >= t.third ? S.inProgress : fmt( S.countdown, duration( t.third - now, S ) ) );
			} else if ( now < first.sunset ) {
				text = fmt( S.before, Math.ceil( ( first.sunset - now ) / DAY ) );
			} else if ( now > last.dawn ) {
				text = fmt( S.after, state.year + S.hijriSuffix );
			} else {
				var nextNight = state.nights.filter( function ( n ) {
					return n.sunset > now;
				} )[ 0 ];
				text = nextNight ? fmt( S.next, label( nextNight.n, cfg ), date( nextNight.sunset, state.city.tz, S.locale, { weekday: 'long', day: 'numeric', month: 'long' } ), time( nextNight.sunset, state.city.tz, S.locale ), fmt( S.hijriDate, nextNight.n, state.year ) ) : '';
			}
			el.status.textContent = text;
		}

		update();
		window.setInterval( function () {
			state.nights.forEach( markTonight );
			renderStatus();
			var sel = find( state.nights, state.selected );
			if ( sel && ( sel.isTonight || el.detail.querySelector( '.lq-nw__now' ) ) && ! el.detail.contains( document.activeElement ) ) {
				renderDetail();
			}
		}, 30000 );
	}

	/* ---------- Calculation ---------- */

	function params( method ) {
		if ( method.indexOf( 'custom:' ) === 0 ) {
			var parts = method.split( ':' );
			var p = A.CalculationMethod.Other();
			p.fajrAngle = parseFloat( parts[ 1 ] );
			p.ishaAngle = parseFloat( parts[ 2 ] );
			return p;
		}
		return ( A.CalculationMethod[ method ] || A.CalculationMethod.MuslimWorldLeague )();
	}

	function compute( city, start ) {
		var coords = new A.Coordinates( city.lat, city.lng );
		var p = params( city.method );
		var d = start.split( '-' );
		var first = new Date( +d[ 0 ], +d[ 1 ] - 1, +d[ 2 ] );
		var out = [];
		for ( var n = 21; n <= 30; n++ ) {
			var eve = new Date( first.getFullYear(), first.getMonth(), first.getDate() + n - 2 );
			var morn = new Date( first.getFullYear(), first.getMonth(), first.getDate() + n - 1 );
			var sunset = new A.PrayerTimes( coords, eve, p ).maghrib.getTime();
			var dawn = new A.PrayerTimes( coords, morn, p ).fajr.getTime();
			var night = { n: n, odd: n % 2 === 1, sunset: sunset, dawn: dawn, third: dawn - ( dawn - sunset ) / 3 };
			markTonight( night );
			out.push( night );
		}
		return out;
	}

	function markTonight( n ) {
		var now = Date.now();
		n.isTonight = now >= n.sunset && now < n.dawn;
	}

	function tonight( nights ) {
		return nights.filter( function ( n ) {
			return n.isTonight;
		} )[ 0 ];
	}

	function nearest( cities, lat, lng ) {
		var best = { city: cities[ 0 ], distance: Infinity };
		cities.forEach( function ( c ) {
			var dist = haversine( lat, lng, c.lat, c.lng );
			if ( dist < best.distance ) {
				best = { city: c, distance: dist };
			}
		} );
		return best;
	}

	function haversine( a1, o1, a2, o2 ) {
		var r = Math.PI / 180;
		var x = Math.sin( ( a2 - a1 ) * r / 2 );
		var y = Math.sin( ( o2 - o1 ) * r / 2 );
		var h = x * x + Math.cos( a1 * r ) * Math.cos( a2 * r ) * y * y;
		return 12742 * Math.asin( Math.sqrt( h ) );
	}

	/* ---------- Markup ---------- */

	function shell( cfg, S ) {
		var groups = {};
		cfg.cities.forEach( function ( c ) {
			( groups[ c.region ] = groups[ c.region ] || [] ).push( c );
		} );
		var options = Object.keys( S.regions ).map( function ( r ) {
			return groups[ r ] ? '<optgroup label="' + esc( S.regions[ r ] ) + '">' + groups[ r ].map( function ( c ) {
				return '<option value="' + c.id + '">' + esc( c.name + ', ' + c.country ) + '</option>';
			} ).join( '' ) + '</optgroup>' : '';
		} ).join( '' );

		return '' +
			'<div class="lq-nw__controls">' +
				'<div class="lq-nw__field"><label class="lq-nw__label" for="lq-nw-city">' + esc( S.city ) + '</label>' +
					'<div class="lq-nw__city-row"><select id="lq-nw-city" class="lq-nw__city"><option value="" hidden>' + esc( S.myLocation ) + '</option>' + options + '</select>' +
					'<button type="button" class="lq-nw__locate">' + icon( 'pin' ) + '<span>' + esc( S.locate ) + '</span></button></div></div>' +
				'<fieldset class="lq-nw__field lq-nw__starts"><legend class="lq-nw__label">' + esc( S.start ) + '</legend><div class="lq-nw__pills"></div>' +
					'<p class="lq-nw__hint">' + esc( S.startNote ) + '</p></fieldset>' +
			'</div>' +
			'<div class="lq-nw__year">' +
				'<button type="button" class="lq-nw__year-btn lq-nw__year-prev" aria-label="' + esc( S.prevYear ) + '"><span aria-hidden="true">‹</span></button>' +
				'<p class="lq-nw__year-label" aria-live="polite"></p>' +
				'<button type="button" class="lq-nw__year-btn lq-nw__year-next" aria-label="' + esc( S.nextYear ) + '"><span aria-hidden="true">›</span></button>' +
			'</div>' +
			'<p class="lq-nw__status" role="status" aria-live="polite"></p>' +
			'<div class="lq-nw__nights" role="tablist" aria-label="' + esc( S.title ) + '"></div>' +
			'<div class="lq-nw__detail" id="lq-nw-detail" role="tabpanel"></div>' +
			'<p class="lq-nw__method"></p>';
	}

	function arc( n, tz, S ) {
		var now = Date.now();
		var w = 600;
		var x3 = w * 2 / 3;
		var nowX = now > n.sunset && now < n.dawn ? ( now - n.sunset ) / ( n.dawn - n.sunset ) * w : null;
		return '<figure class="lq-nw__arc"><svg viewBox="0 0 ' + w + ' 66" role="img" aria-label="' + esc( S.sunset + ' ' + time( n.sunset, tz, S.locale ) + ', ' + S.lastThird + ' ' + time( n.third, tz, S.locale ) + ', ' + S.dawn + ' ' + time( n.dawn, tz, S.locale ) ) + '">' +
			'<defs><linearGradient id="lq-nw-g' + n.n + '" x1="0" x2="1"><stop offset="0" stop-color="var(--lq-nw-dusk)"/><stop offset=".5" stop-color="var(--lq-nw-deep)"/><stop offset="1" stop-color="var(--lq-nw-dusk)"/></linearGradient></defs>' +
			'<clipPath id="lq-nw-c' + n.n + '"><rect x="0" y="22" width="' + w + '" height="30" rx="15"/></clipPath>' +
			'<rect x="0" y="22" width="' + w + '" height="30" rx="15" fill="url(#lq-nw-g' + n.n + ')"/>' +
			'<rect x="' + x3 + '" y="22" width="' + ( w - x3 ) + '" height="30" class="lq-nw__third" clip-path="url(#lq-nw-c' + n.n + ')"/>' +
			'<line x1="' + ( w / 3 ) + '" x2="' + ( w / 3 ) + '" y1="22" y2="52" class="lq-nw__tick"/>' +
			'<line x1="' + x3 + '" x2="' + x3 + '" y1="16" y2="58" class="lq-nw__tick is-key"/>' +
			( nowX !== null ? '<g class="lq-nw__now"><line class="lq-nw__now-halo" x1="' + nowX + '" x2="' + nowX + '" y1="14" y2="62"/><line x1="' + nowX + '" x2="' + nowX + '" y1="12" y2="62"/><circle cx="' + nowX + '" cy="12" r="4"/></g>' : '' ) +
			'</svg>' +
			'<div class="lq-nw__lbls" aria-hidden="true">' +
				'<span>' + esc( S.sunset + ' ' + time( n.sunset, tz, S.locale ) ) + '</span>' +
				'<span class="is-key" style="left:66.667%">' + esc( time( n.third, tz, S.locale ) ) + '</span>' +
				'<span>' + esc( S.dawn + ' ' + time( n.dawn, tz, S.locale ) ) + '</span>' +
			'</div></figure>';
	}

	function stat( term, value, cls ) {
		return '<div class="lq-nw__stat ' + cls + '"><dt>' + esc( term ) + '</dt><dd>' + esc( value ) + '</dd></div>';
	}

	function icon( name ) {
		var paths = {
			pin: '<path d="M12 21s-6-5.3-6-10a6 6 0 1 1 12 0c0 4.7-6 10-6 10z"/><circle cx="12" cy="11" r="2.2"/>',
			cal: '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>'
		};
		return '<svg class="lq-nw__icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + paths[ name ] + '</svg>';
	}

	/* ---------- Formatting ---------- */

	function time( ms, tz, locale ) {
		return new Intl.DateTimeFormat( locale, { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: tz } ).format( new Date( ms ) );
	}

	function date( ms, tz, locale, opts ) {
		var o = Object.assign( { timeZone: tz }, opts );
		return new Intl.DateTimeFormat( locale, o ).format( new Date( ms ) );
	}

	function duration( ms, S ) {
		var m = Math.max( 0, Math.round( ms / 60000 ) );
		return fmt( S.hours, Math.floor( m / 60 ), m % 60 );
	}

	function label( n, cfg ) {
		if ( cfg.role === 'secondary' ) {
			return String( n );
		}
		var s = [ 'th', 'st', 'nd', 'rd' ];
		var v = n % 100;
		return n + ( s[ ( v - 20 ) % 10 ] || s[ v ] || s[ 0 ] );
	}

	function fmt( str ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var i = 0;
		return String( str ).replace( /%(?:(\d)\$)?(0?2?)([sd])/g, function ( m, pos, pad, type ) {
			var v = pos ? args[ +pos - 1 ] : args[ i++ ];
			v = type === 'd' ? String( parseInt( v, 10 ) ) : String( v );
			return pad === '02' && v.length < 2 ? '0' + v : v;
		} );
	}

	function methodLabel( m ) {
		if ( m.indexOf( 'custom:' ) === 0 ) {
			var p = m.split( ':' );
			return p[ 1 ] + '° / ' + p[ 2 ] + '°';
		}
		return METHOD_LABELS[ m ] || m;
	}

	function esc( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* ---------- Calendar export ---------- */

	function ics( list, S, cfg, hijriYear ) {
		var stamp = stampUtc( Date.now() );
		var lines = [ 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//lailatulqadar.guide//Night Window//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH' ];
		list.forEach( function ( n ) {
			lines.push(
				'BEGIN:VEVENT',
				'UID:night-' + n.n + '-' + n.sunset + '@lailatulqadar.guide',
				'DTSTAMP:' + stamp,
				'DTSTART:' + stampUtc( n.third ),
				'DTEND:' + stampUtc( n.dawn ),
				'SUMMARY:' + icsText( fmt( S.icsTitle, label( n.n, cfg ), fmt( S.hijriDate, n.n, hijriYear ) ) ),
				'DESCRIPTION:' + icsText( S.icsDesc ),
				'BEGIN:VALARM', 'TRIGGER:-PT15M', 'ACTION:DISPLAY', 'DESCRIPTION:' + icsText( fmt( S.icsTitle, label( n.n, cfg ), fmt( S.hijriDate, n.n, hijriYear ) ) ), 'END:VALARM',
				'END:VEVENT'
			);
		} );
		lines.push( 'END:VCALENDAR' );
		return lines.join( '\r\n' );
	}

	function stampUtc( ms ) {
		return new Date( ms ).toISOString().replace( /[-:]/g, '' ).replace( /\.\d{3}/, '' );
	}

	function icsText( s ) {
		return String( s ).replace( /\\/g, '\\\\' ).replace( /([,;])/g, '\\$1' ).replace( /\n/g, '\\n' );
	}

	function download( text, name ) {
		var blob = new Blob( [ text ], { type: 'text/calendar;charset=utf-8' } );
		var a = document.createElement( 'a' );
		a.href = URL.createObjectURL( blob );
		a.download = name;
		document.body.appendChild( a );
		a.click();
		window.setTimeout( function () {
			URL.revokeObjectURL( a.href );
			a.remove();
		}, 1000 );
	}

	/* ---------- Helpers ---------- */

	function yearOf( cfg, hijri ) {
		return cfg.years.filter( function ( y ) {
			return y.hijri === hijri;
		} )[ 0 ];
	}

	function indexOfYear( cfg, hijri ) {
		for ( var i = 0; i < cfg.years.length; i++ ) {
			if ( cfg.years[ i ].hijri === hijri ) {
				return i;
			}
		}
		return -1;
	}

	function dayLabel( ymd, locale ) {
		var d = ymd.split( '-' );
		return new Intl.DateTimeFormat( locale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' } ).format( new Date( Date.UTC( +d[ 0 ], +d[ 1 ] - 1, +d[ 2 ], 12 ) ) );
	}

	function findCity( cfg, id ) {
		return cfg.cities.filter( function ( c ) {
			return c.id === id;
		} )[ 0 ];
	}

	function find( nights, n ) {
		return nights.filter( function ( x ) {
			return x.n === n;
		} )[ 0 ];
	}

	function load() {
		try {
			return JSON.parse( window.localStorage.getItem( STORE ) ) || {};
		} catch ( e ) {
			return {};
		}
	}

	function save( data ) {
		try {
			window.localStorage.setItem( STORE, JSON.stringify( data ) );
		} catch ( e ) {}
	}
}() );
