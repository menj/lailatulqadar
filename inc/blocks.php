<?php
/**
 * Dynamic theme blocks: guide menu and language switcher.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the editor script and the two dynamic blocks.
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

	wp_register_script( 'lailatulqadar-adhan', LQ_URI . '/js/vendor/adhan.min.js', array(), '4.4.6', true );
	wp_register_script( 'lailatulqadar-night-window', LQ_URI . '/js/night-window.js', array( 'lailatulqadar-adhan' ), LQ_VERSION, true );
	wp_register_style( 'lailatulqadar-night-window', LQ_URI . '/css/night-window.css', array(), LQ_VERSION );

	register_block_type( LQ_DIR . '/blocks/guide-menu' );
	register_block_type( LQ_DIR . '/blocks/night-window' );
	register_block_type( LQ_DIR . '/blocks/odd-nights' );
	register_block_type( LQ_DIR . '/blocks/breadcrumbs' );
	register_block_type( LQ_DIR . '/blocks/scheme-toggle' );
	register_block_type( LQ_DIR . '/blocks/logo-mark' );
	register_block_type( LQ_DIR . '/blocks/language-switcher' );
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
