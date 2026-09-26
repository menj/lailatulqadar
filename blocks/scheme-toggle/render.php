<?php
/**
 * Light and dark switch: a button the head script wires up.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

if ( 'hide' === lq_setting( 'scheme_toggle' ) ) {
	return;
}

$lq_ms    = 'secondary' === lq_current_role();
$lq_light = $lq_ms ? 'Tukar ke mod cerah' : 'Switch to light mode';
$lq_dark  = $lq_ms ? 'Tukar ke mod gelap' : 'Switch to dark mode';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-scheme-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<button type="button" class="lq-scheme-toggle" data-label-light="<?php echo esc_attr( $lq_light ); ?>" data-label-dark="<?php echo esc_attr( $lq_dark ); ?>" aria-label="<?php echo esc_attr( $lq_light ); ?>">
		<svg class="lq-scheme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
		<svg class="lq-scheme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
	</button>
</div>
