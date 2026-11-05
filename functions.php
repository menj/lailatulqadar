<?php
/**
 * Lailatulqadar child theme bootstrap.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

define( 'LQ_VERSION', '1.18.3' );
define( 'LQ_DIR', get_stylesheet_directory() );
define( 'LQ_URI', get_stylesheet_directory_uri() );

require_once LQ_DIR . '/inc/helpers.php';
require_once LQ_DIR . '/inc/parent.php';
require_once LQ_DIR . '/inc/languages.php';
require_once LQ_DIR . '/inc/assets.php';
require_once LQ_DIR . '/inc/blocks.php';
require_once LQ_DIR . '/inc/images.php';
require_once LQ_DIR . '/inc/ramadan.php';
require_once LQ_DIR . '/inc/night-window.php';
require_once LQ_DIR . '/inc/seo.php';
require_once LQ_DIR . '/inc/contextual-links.php';
require_once LQ_DIR . '/inc/draft-links.php';
require_once LQ_DIR . '/inc/schema-content.php';
require_once LQ_DIR . '/inc/schema.php';
require_once LQ_DIR . '/inc/image-sitemap.php';
require_once LQ_DIR . '/inc/no-blog.php';
require_once LQ_DIR . '/inc/login.php';
require_once LQ_DIR . '/inc/security.php';
require_once LQ_DIR . '/inc/setup.php';
require_once LQ_DIR . '/inc/slugs.php';
require_once LQ_DIR . '/inc/social-icons.php';
require_once LQ_DIR . '/inc/reading-progress.php';
require_once LQ_DIR . '/inc/unlist.php';
require_once LQ_DIR . '/inc/search-permalinks.php';
// Loaded everywhere: the drop-ins are written on after_switch_theme, which
// can fire on a front-end request, and removed on switch_theme.
require_once LQ_DIR . '/inc/error-pages.php';

if ( is_admin() ) {
	require_once LQ_DIR . '/inc/settings.php';
}

/**
 * Load translations, declare theme supports and add the editor styles.
 * Block themes get title-tag and automatic-feed-links from core; the theme
 * declares both itself as well. The retired blog still removes the feed
 * links from the head (inc/no-blog.php).
 */
function lq_theme_setup() {
	load_child_theme_textdomain( 'lailatulqadar', LQ_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_editor_style( array( 'css/front.css', 'css/editor.css' ) );
}
add_action( 'after_setup_theme', 'lq_theme_setup' );
