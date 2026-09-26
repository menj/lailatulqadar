/**
 * Lailatulqadar editor registration for the two dynamic blocks.
 * Both render on the server; the editor shows a live server render.
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
		edit: function () {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, {
					block: 'lailatulqadar/language-switcher',
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

	registerBlockType( 'lailatulqadar/breadcrumbs', {
		edit: function () {
			return el( 'div', useBlockProps(), el( 'span', { className: 'lq-crumbs' }, __( 'Home › Page title', 'lailatulqadar' ) ) );
		},
		save: function () {
			return null;
		}
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
}( window.wp ) );
