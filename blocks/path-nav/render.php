<?php
/**
 * Previous and next block render: the neighbouring published pages of the
 * reading path (the guide menu's path set), at the foot of a guide page.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_id = lailatulqadar_current_guide_page();
if ( ! $lq_id ) {
	return;
}
$lq_near = lailatulqadar_path_neighbours( $lq_id );
if ( ! $lq_near['prev'] && ! $lq_near['next'] ) {
	return;
}
$lq_role = lq_role_of( $lq_id );
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-pathnav' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( lailatulqadar_t( 'Reading path', 'Laluan bacaan', $lq_role ) ); ?>">
	<?php if ( $lq_near['prev'] ) : ?>
	<a class="lq-pathnav__link lq-pathnav__link--prev" href="<?php echo esc_url( $lq_near['prev']['url'] ); ?>" rel="prev"><span class="lq-pathnav__label"><?php echo esc_html( lailatulqadar_t( 'Previous', 'Sebelumnya', $lq_role ) ); ?></span> <span class="lq-pathnav__title"><?php echo esc_html( $lq_near['prev']['title'] ); ?></span></a>
	<?php endif; ?>
	<?php if ( $lq_near['next'] ) : ?>
	<a class="lq-pathnav__link lq-pathnav__link--next" href="<?php echo esc_url( $lq_near['next']['url'] ); ?>" rel="next"><span class="lq-pathnav__label"><?php echo esc_html( lailatulqadar_t( 'Next', 'Seterusnya', $lq_role ) ); ?></span> <span class="lq-pathnav__title"><?php echo esc_html( $lq_near['next']['title'] ); ?></span></a>
	<?php endif; ?>
</nav>
