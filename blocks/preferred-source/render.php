<?php
/**
 * Preferred source block. In the footer part it carries context "footer"
 * and follows the footer switch in Theme Options.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_context = isset( $attributes['context'] ) && 'footer' === $attributes['context'] ? 'footer' : 'inline';
$lq_html    = lailatulqadar_prefsrc_html( $lq_context );
if ( '' === $lq_html ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-prefsrc-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $lq_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in lailatulqadar_prefsrc_html(). ?>
</div>
