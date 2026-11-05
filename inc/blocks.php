<?php
/**
 * Dynamic theme blocks (guide menu, night window, odd nights, breadcrumbs,
 * scheme toggle, logo mark, language switcher, photo, image credits, and the
 * guide page parts: page details, in brief, sources, previous and next) and
 * the Arabic paragraph styles.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the editor script, the night window assets and the dynamic blocks.
 */
function lq_register_blocks() {
	wp_register_script(
		'lailatulqadar-editor-blocks',
		LQ_URI . '/js/editor-blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
		LQ_VERSION,
		true
	);
	wp_set_script_translations( 'lailatulqadar-editor-blocks', 'lailatulqadar', LQ_DIR . '/languages' );

	// adhan.js is the unminified source of adhan.min.js (the UMD bundle of adhan 4.4.6).
	$adhan = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'adhan.js' : 'adhan.min.js';
	wp_register_script( 'lailatulqadar-adhan', LQ_URI . '/js/vendor/' . $adhan, array(), '4.4.6', true );
	wp_register_script( 'lailatulqadar-night-window', LQ_URI . '/js/night-window.js', array( 'lailatulqadar-adhan' ), LQ_VERSION, true );
	wp_register_style( 'lailatulqadar-night-window', LQ_URI . '/css/night-window.css', array(), LQ_VERSION );

	register_block_type( LQ_DIR . '/blocks/guide-menu' );
	register_block_type( LQ_DIR . '/blocks/night-window' );
	register_block_type( LQ_DIR . '/blocks/odd-nights' );
	register_block_type( LQ_DIR . '/blocks/breadcrumbs' );
	register_block_type( LQ_DIR . '/blocks/scheme-toggle' );
	register_block_type( LQ_DIR . '/blocks/logo-mark' );
	register_block_type( LQ_DIR . '/blocks/language-switcher' );
	register_block_type( LQ_DIR . '/blocks/photo' );
	register_block_type( LQ_DIR . '/blocks/image-credits' );
	register_block_type( LQ_DIR . '/blocks/page-details' );
	register_block_type( LQ_DIR . '/blocks/in-brief' );
	register_block_type( LQ_DIR . '/blocks/sources' );
	register_block_type( LQ_DIR . '/blocks/path-nav' );
}
add_action( 'init', 'lq_register_blocks' );

/**
 * Arabic paragraph styles: "Qurʾān verse" (KFGQPC Hafs), "Hadith or
 * supplication" (Arslan Wessam A) and "Arabic quotation" (Arslan Wessam B).
 */
function lq_register_block_styles() {
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'lq-quran',
			'label' => __( 'Qurʾān verse', 'lailatulqadar' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'lq-hadith',
			'label' => __( 'Hadith or supplication', 'lailatulqadar' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'lq-arabic-quote',
			'label' => __( 'Arabic quotation (display)', 'lailatulqadar' ),
		)
	);
}
add_action( 'init', 'lq_register_block_styles' );

/**
 * Whether a parsed block is the footer template part.
 *
 * @param array $block Parsed block.
 * @return bool
 */
function lq_is_footer_part( $block ) {
	if ( 'core/template-part' !== ( $block['blockName'] ?? '' ) ) {
		return false;
	}
	$attrs = (array) ( $block['attrs'] ?? array() );
	return 'footer' === ( $attrs['slug'] ?? '' ) || 'footer' === ( $attrs['area'] ?? '' ) || 'footer' === ( $attrs['tagName'] ?? '' );
}

/**
 * Counts the footer template parts being rendered, so a block inside one
 * (the language switcher) knows it sits in the footer, whatever its own
 * attributes say. render_block_data runs before a block and its inner
 * blocks render, render_block after.
 *
 * @param array $block Parsed block.
 * @return array
 */
function lq_enter_footer_part( $block ) {
	if ( lq_is_footer_part( $block ) ) {
		$GLOBALS['lq_footer_depth'] = (int) ( $GLOBALS['lq_footer_depth'] ?? 0 ) + 1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	return $block;
}
add_filter( 'render_block_data', 'lq_enter_footer_part' );

/**
 * The other half of lq_enter_footer_part().
 *
 * @param string $html  Rendered block.
 * @param array  $block Parsed block.
 * @return string
 */
function lq_leave_footer_part( $html, $block ) {
	if ( lq_is_footer_part( $block ) ) {
		$GLOBALS['lq_footer_depth'] = max( 0, (int) ( $GLOBALS['lq_footer_depth'] ?? 0 ) - 1 ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	return $html;
}
add_filter( 'render_block', 'lq_leave_footer_part', 10, 2 );
