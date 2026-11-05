<?php
/**
 * In brief block render: the Guide page box's points, for a guide page
 * whose text has no In brief box of its own.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_id = lailatulqadar_current_guide_page();
$lq_points = $lq_id ? lailatulqadar_template_brief( $lq_id ) : array();
if ( ! $lq_points ) {
	return;
}
echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'lq-brief-wrap' ) ) . '>' . lailatulqadar_brief_html( $lq_points, lq_role_of( $lq_id ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
