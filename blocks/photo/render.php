<?php
/**
 * Commons image block render: one image of a guide page with its credit.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_key = isset( $attributes['key'] ) ? sanitize_key( $attributes['key'] ) : '';
if ( '' === $lq_key && get_the_ID() ) {
	$lq_key = lq_page_key_of( get_the_ID() );
}
$lq_image = '' !== $lq_key ? lq_image( $lq_key, max( 1, (int) ( $attributes['n'] ?? 1 ) ) ) : null;

if ( $lq_image ) {
	echo lq_image_figure( $lq_image ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in lq_image_figure().
}
