<?php
/**
 * Breadcrumbs block render.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

// When Rank Math's breadcrumbs are switched on, Rank Math owns the trail
// and its BreadcrumbList data; the block shows Rank Math's trail instead.
if ( function_exists( 'rank_math_get_breadcrumbs' ) && class_exists( '\\RankMath\\Helper' ) && \RankMath\Helper::is_breadcrumbs_enabled() ) {
	if ( lq_is_home() ) {
		return;
	}
	echo '<div ' . get_block_wrapper_attributes( array( 'class' => 'lq-crumbs lq-crumbs--rank-math' ) ) . '>' . rank_math_get_breadcrumbs() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$lq_trail = lq_breadcrumb_trail();
if ( ! $lq_trail ) {
	return;
}
$lq_last = count( $lq_trail ) - 1;
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-crumbs' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( 'secondary' === lq_current_role() ? 'Laluan navigasi' : 'Breadcrumb' ); ?>">
	<ol>
		<?php foreach ( $lq_trail as $lq_i => $lq_c ) : ?>
			<li>
				<?php if ( $lq_i === $lq_last || '' === (string) $lq_c[1] ) : ?>
					<span aria-current="page"><?php echo esc_html( $lq_c[0] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $lq_c[1] ); ?>"><?php echo esc_html( $lq_c[0] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
