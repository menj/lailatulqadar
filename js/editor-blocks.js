/**
 * Lailatulqadar editor registration for the theme's dynamic blocks.
 * They render on the server; the editor shows a live server render or,
 * where that would mislead (scheme toggle, breadcrumbs, night window), a
 * placeholder.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'lailatulqadar/guide-menu', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Menu', 'lailatulqadar' ) },
						el( SelectControl, {
							label: __( 'Show', 'lailatulqadar' ),
							value: props.attributes.set,
							options: [
								{ label: __( 'Header links', 'lailatulqadar' ), value: 'header' },
								{ label: __( 'Reading path', 'lailatulqadar' ), value: 'path' },
								{ label: __( 'Footer', 'lailatulqadar' ), value: 'footer' },
								{ label: __( 'Sitemap (every page)', 'lailatulqadar' ), value: 'sitemap' }
							],
							onChange: function ( value ) {
								props.setAttributes( { set: value } );
							}
						} ),
						'path' === props.attributes.set && el( ToggleControl, {
							label: __( 'Split into chapters', 'lailatulqadar' ),
							help: __( 'Headings from inc/data/path-chapters.json; the numbers run on through them.', 'lailatulqadar' ),
							checked: !! props.attributes.chapters,
							onChange: function ( value ) {
								props.setAttributes( { chapters: value } );
							}
						} )
					)
				),
				el( ServerSideRender, { block: 'lailatulqadar/guide-menu', attributes: props.attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/language-switcher', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, {
					block: 'lailatulqadar/language-switcher',
					attributes: props.attributes,
					EmptyResponsePlaceholder: function () {
						return el( 'span', null, __( 'Language switcher', 'lailatulqadar' ) );
					}
				} )
			);
		},
		save: function () {
			return null;
		}
	} );
	registerBlockType( 'lailatulqadar/logo-mark', {
		edit: function () {
			return el( 'div', useBlockProps(), el( ServerSideRender, { block: 'lailatulqadar/logo-mark' } ) );
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/scheme-toggle', {
		edit: function () {
			return el( 'div', useBlockProps(), el( 'span', { className: 'lq-scheme-placeholder' }, '☾ / ☀' ) );
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/social-icons', {
		edit: function ( props ) {
			return el( 'div', useBlockProps(), el( ServerSideRender, { block: 'lailatulqadar/social-icons', attributes: props.attributes } ) );
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/photo', {
		edit: function ( props ) {
			return el( 'div', useBlockProps(), el( ServerSideRender, { block: 'lailatulqadar/photo', attributes: props.attributes } ) );
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/image-credits', {
		edit: function () {
			return el( 'div', useBlockProps(), el( ServerSideRender, { block: 'lailatulqadar/image-credits' } ) );
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/breadcrumbs', {
		edit: function () {
			return el( 'div', useBlockProps(), el( 'span', { className: 'lq-crumbs' }, __( 'Home › Page title', 'lailatulqadar' ) ) );
		},
		save: function () {
			return null;
		}
	} );

	// The guide page parts read the page being viewed, so the editor shows a label.
	[
		[ 'lailatulqadar/page-details', __( 'Published, updated, last reviewed, reading time', 'lailatulqadar' ) ],
		[ 'lailatulqadar/in-brief', __( 'In brief (from the Guide page box)', 'lailatulqadar' ) ],
		[ 'lailatulqadar/sources', __( 'Evidence and sources', 'lailatulqadar' ) ],
		[ 'lailatulqadar/path-nav', __( 'Previous and next page of the reading path', 'lailatulqadar' ) ]
	].forEach( function ( pair ) {
		registerBlockType( pair[ 0 ], {
			edit: function () {
				return el( 'p', useBlockProps(), pair[ 1 ] );
			},
			save: function () {
				return null;
			}
		} );
	} );

	registerBlockType( 'lailatulqadar/odd-nights', {
		edit: function ( props ) {
			var ToggleControl = wp.components.ToggleControl;
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Rows', 'lailatulqadar' ) },
						el( ToggleControl, {
							label: __( 'Add the expected Eid al-Fitr', 'lailatulqadar' ),
							checked: !! props.attributes.eid,
							onChange: function ( v ) {
								props.setAttributes( { eid: v } );
							}
						} )
					)
				),
				el( ServerSideRender, { block: 'lailatulqadar/odd-nights', attributes: props.attributes } )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/night-window', {
		edit: function () {
			return el(
				'div',
				useBlockProps( { className: 'lq-nw-placeholder' } ),
				el( 'strong', null, __( 'Night window', 'lailatulqadar' ) ),
				el( 'p', null, __( 'Interactive timetable of the last ten nights. It appears on the published page.', 'lailatulqadar' ) )
			);
		},
		save: function () {
			return null;
		}
	} );

	registerBlockType( 'lailatulqadar/contents', {
		edit: function ( props ) {
			var a = props.attributes;
			return el(
				'div',
				useBlockProps( { className: 'lq-contents lq-contents--placeholder' } ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Contents', 'lailatulqadar' ) },
						el( SelectControl, {
							label: __( 'Headings to list', 'lailatulqadar' ),
							value: a.tags,
							options: [
								{ label: __( 'H2 only', 'lailatulqadar' ), value: 'h2' },
								{ label: __( 'H2 and H3', 'lailatulqadar' ), value: 'h2,h3' },
								{ label: __( 'H2 to H4', 'lailatulqadar' ), value: 'h2,h3,h4' },
								{ label: __( 'H2 to H6', 'lailatulqadar' ), value: 'h2,h3,h4,h5,h6' }
							],
							onChange: function ( value ) {
								props.setAttributes( { tags: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Show a title', 'lailatulqadar' ),
							checked: !! a.showTitle,
							onChange: function ( value ) {
								props.setAttributes( { showTitle: value } );
							}
						} ),
						a.showTitle && el( TextControl, {
							label: __( 'Title', 'lailatulqadar' ),
							help: __( 'Leave empty for "On this page" (Malay: "Kandungan halaman").', 'lailatulqadar' ),
							value: a.title,
							onChange: function ( value ) {
								props.setAttributes( { title: value } );
							}
						} )
					)
				),
				el( 'p', { className: 'lq-contents__title' }, a.showTitle && a.title ? a.title : __( 'On this page', 'lailatulqadar' ) ),
				el( 'p', null, __( 'The list of headings appears on the published page.', 'lailatulqadar' ) )
			);
		},
		save: function () {
			return null;
		}
	} );
}( window.wp ) );
