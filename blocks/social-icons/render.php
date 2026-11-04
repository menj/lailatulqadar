<?php
/**
 * Social icons block. In the footer part it carries context "footer" and
 * follows the footer switch in Theme Options.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_context = isset( $attributes['context'] ) ? sanitize_key( $attributes['context'] ) : '';
if ( 'footer' === $lq_context && '1' !== lq_setting( 'social_footer' ) ) {
	return;
}
$lq_html = lailatulqadar_social_icons_html( $lq_context );
if ( '' === $lq_html ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-social-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $lq_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in lailatulqadar_social_icons_html(). ?>
</div>
