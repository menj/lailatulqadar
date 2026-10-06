<?php
/**
 * Lailatulqadar child theme bootstrap.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

define( 'LQ_VERSION', '1.9.0' );
define( 'LQ_DIR', get_stylesheet_directory() );
define( 'LQ_URI', get_stylesheet_directory_uri() );

require_once LQ_DIR . '/inc/helpers.php';
require_once LQ_DIR . '/inc/parent.php';
require_once LQ_DIR . '/inc/languages.php';
require_once LQ_DIR . '/inc/assets.php';
require_once LQ_DIR . '/inc/blocks.php';
require_once LQ_DIR . '/inc/ramadan.php';
require_once LQ_DIR . '/inc/night-window.php';
require_once LQ_DIR . '/inc/seo.php';
require_once LQ_DIR . '/inc/schema.php';
require_once LQ_DIR . '/inc/login.php';
require_once LQ_DIR . '/inc/setup.php';

if ( is_admin() ) {
	require_once LQ_DIR . '/inc/settings.php';
}

/**
 * Load translations and editor styles.
 */
function lq_theme_setup() {
	load_child_theme_textdomain( 'lailatulqadar', LQ_DIR . '/languages' );
	add_editor_style( array( 'css/front.css', 'css/editor.css' ) );
}
add_action( 'after_setup_theme', 'lq_theme_setup' );

/* Contextual in-content links. */
require_once get_stylesheet_directory() . '/inc/contextual-links.php';
