<?php
/**
 * Contents block: leaves a marker that inc/contents.php turns into the list
 * once the whole text is rendered. Works inside a page's or post's text.
 *
 * @package Lailatulqadar
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$lq_title = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';
if ( isset( $attributes['showTitle'] ) && ! $attributes['showTitle'] ) {
	$lq_title = '';
} elseif ( '' === $lq_title ) {
	$lq_title = null;
}
echo lailatulqadar_toc_mark( lailatulqadar_toc_levels( $attributes['tags'] ?? 'h2,h3' ), $lq_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- an HTML comment holding base64 text.
