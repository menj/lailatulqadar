<?php
/**
 * Contents block: a table of contents placed by hand, drawn by WP TOC
 * (wp-toc/, inc/toc.php). It prints WP TOC's [wp_toc] placeholder, which
 * WP TOC turns into the list once the whole text is rendered, so the block
 * works inside a page's or post's text. Nothing shows when WP TOC is not
 * loaded (PHP older than 8.4).
 *
 * @package Lailatulqadar
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! lailatulqadar_toc_active() ) {
	return;
}

$lq_atts = array( 'tags' => isset( $attributes['tags'] ) ? (string) $attributes['tags'] : 'h2,h3' );
if ( isset( $attributes['showTitle'] ) && ! $attributes['showTitle'] ) {
	$lq_atts['title'] = '';
} elseif ( isset( $attributes['title'] ) && '' !== trim( (string) $attributes['title'] ) ) {
	$lq_atts['title'] = trim( (string) $attributes['title'] );
}

// An HTML comment holding hex-encoded settings; WP TOC escapes the list it becomes.
echo WP_TOC::shortcode( $lq_atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
