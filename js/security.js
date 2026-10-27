/**
 * Lailatulqadar: Security tab.
 * Runs the spam scan in batches of 100,
 * moves the selected posts to the trash in batches, and confirms the
 * admin-post buttons.
 */
( function () {
	'use strict';

	var cfg = window.lqSecurity || {};
	var i18n = cfg.i18n || {};
	var tools = document.getElementById( 'lq-security-tools' );

	if ( ! tools ) {
		return;
	}

	function format( text ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		return String( text ).replace( /%(\d)\$d/g, function ( m, n ) {
			return String( args[ n - 1 ] );
		} );
	}

	function post( action, data ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );
		Object.keys( data ).forEach( function ( key ) {
			var value = data[ key ];
			if ( Array.isArray( value ) ) {
				value.forEach( function ( v ) {
					body.append( key + '[]', v );
				} );
			} else {
				body.append( key, value );
			}
		} );
		return fetch( cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					throw new Error( ( json && json.data && json.data.message ) || i18n.error );
				}
				return json.data;
			} );
	}

	var scanBtn = document.getElementById( 'lq-sec-scan' );
	var trashBtn = document.getElementById( 'lq-sec-trash' );
	var status = document.getElementById( 'lq-sec-status' );
	var table = document.getElementById( 'lq-sec-results' );
	var all = document.getElementById( 'lq-sec-all' );
	var tbody = table ? table.querySelector( 'tbody' ) : null;

	function boxes() {
		return tbody ? Array.prototype.slice.call( tbody.querySelectorAll( 'input[type="checkbox"]' ) ) : [];
	}

	function refresh() {
		var picked = boxes().filter( function ( b ) {
			return b.checked;
		} );
		trashBtn.disabled = ! picked.length;
		if ( all ) {
			all.checked = picked.length > 0 && picked.length === boxes().length;
		}
	}

	function cell( row, text ) {
		var td = document.createElement( 'td' );
		td.textContent = text;
		row.appendChild( td );
		return td;
	}

	function addRow( item ) {
		var tr = document.createElement( 'tr' );
		tr.dataset.id = item.id;
		var check = document.createElement( 'th' );
		check.className = 'check-column';
		check.scope = 'row';
		var box = document.createElement( 'input' );
		box.type = 'checkbox';
		box.value = item.id;
		box.setAttribute( 'aria-label', i18n.select + ': ' + item.title );
		check.appendChild( box );
		tr.appendChild( check );

		var title = cell( tr, '' );
		var strong = document.createElement( 'strong' );
		strong.textContent = item.title;
		title.appendChild( strong );
		var links = document.createElement( 'div' );
		links.className = 'lq-sec-links';
		[ [ item.edit, i18n.edit ], [ item.view, i18n.view ] ].forEach( function ( pair ) {
			if ( pair[ 0 ] ) {
				var a = document.createElement( 'a' );
				a.href = pair[ 0 ];
				a.textContent = pair[ 1 ];
				a.target = '_blank';
				a.rel = 'noopener';
				links.appendChild( a );
			}
		} );
		title.appendChild( links );

		cell( tr, item.date );
		cell( tr, item.status );
		cell( tr, item.author );
		var reasons = cell( tr, '' );
		( item.reasons || [] ).forEach( function ( r ) {
			var tag = document.createElement( 'span' );
			tag.className = 'lq-sec-reason';
			tag.textContent = r;
			reasons.appendChild( tag );
		} );
		tbody.appendChild( tr );
	}

	if ( scanBtn ) {
		scanBtn.addEventListener( 'click', function () {
			var found = 0;
			tbody.textContent = '';
			table.hidden = true;
			scanBtn.disabled = true;
			trashBtn.disabled = true;

			function step( offset ) {
				post( 'lq_sec_scan', { offset: offset } )
					.then( function ( data ) {
						data.rows.forEach( addRow );
						found += data.rows.length;
						if ( found ) {
							table.hidden = false;
						}
						if ( data.done ) {
							status.textContent = found ? format( i18n.done, data.next, found ) : i18n.none;
							scanBtn.disabled = false;
							refresh();
							return;
						}
						status.textContent = format( i18n.scanning, data.next, data.total, found );
						step( data.next );
					} )
					.catch( function ( err ) {
						status.textContent = err.message;
						scanBtn.disabled = false;
					} );
			}
			step( 0 );
		} );
	}

	if ( tbody ) {
		tbody.addEventListener( 'change', refresh );
	}
	if ( all ) {
		all.addEventListener( 'change', function () {
			boxes().forEach( function ( b ) {
				b.checked = all.checked;
			} );
			refresh();
		} );
	}

	if ( trashBtn ) {
		trashBtn.addEventListener( 'click', function () {
			var ids = boxes()
				.filter( function ( b ) {
					return b.checked;
				} )
				.map( function ( b ) {
					return b.value;
				} );
			if ( ! ids.length || ! window.confirm( i18n.confirmTrash ) ) {
				return;
			}
			var total = ids.length;
			var moved = 0;
			var skipped = 0;
			trashBtn.disabled = true;
			scanBtn.disabled = true;

			function step() {
				var chunk = ids.splice( 0, 100 );
				if ( ! chunk.length ) {
					status.textContent = format( i18n.trashed, moved, skipped );
					scanBtn.disabled = false;
					refresh();
					return;
				}
				post( 'lq_sec_trash', { ids: chunk } )
					.then( function ( data ) {
						data.trashed.forEach( function ( id ) {
							var row = tbody.querySelector( 'tr[data-id="' + id + '"]' );
							if ( row ) {
								row.remove();
							}
						} );
						moved += data.trashed.length;
						skipped += data.skipped.length;
						status.textContent = format( i18n.trashing, moved + skipped, total );
						step();
					} )
					.catch( function ( err ) {
						status.textContent = err.message;
						scanBtn.disabled = false;
						refresh();
					} );
			}
			step();
		} );
	}

	// Confirm the admin-post buttons (revoke, log out, delete categories, clear log).
	Array.prototype.forEach.call( tools.querySelectorAll( 'form[data-lq-sec-confirm]' ), function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			if ( ! window.confirm( form.getAttribute( 'data-lq-sec-confirm' ) ) ) {
				e.preventDefault();
			}
		} );
	} );
}() );
