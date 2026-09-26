<?php
/**
 * Guide menu block render.
 *
 * @package Lailatulqadar
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$lq_set   = isset( $attributes['set'] ) && in_array( $attributes['set'], array( 'header', 'path', 'footer', 'sitemap' ), true ) ? $attributes['set'] : 'header';
// The reading path also lists pages still in preparation, as plain text,
// so the full plan shows from the start (filter lq_path_show_upcoming).
$lq_upcoming = 'path' === $lq_set && apply_filters( 'lq_path_show_upcoming', true );
$lq_items    = lq_menu_items( $lq_set, $lq_upcoming );
$lq_soon     = 'secondary' === lq_current_role() ? 'Akan datang' : 'Coming soon';
$lq_wrap  = get_block_wrapper_attributes( array( 'class' => 'lq-menu lq-menu--' . $lq_set ) );

if ( 'footer' === $lq_set ) {
	$lq_note = lq_setting( 'secondary' === lq_current_role() ? 'footer_note_secondary' : 'footer_note_primary' );
	?>
	<div <?php echo $lq_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $lq_items ) : ?>
			<nav aria-label="<?php esc_attr_e( 'Site information', 'lailatulqadar' ); ?>">
				<ul class="lq-menu__list">
					<?php foreach ( $lq_items as $lq_item ) : ?>
						<li><a href="<?php echo esc_url( $lq_item['url'] ); ?>"><?php echo esc_html( $lq_item['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
		<?php if ( $lq_note ) : ?>
			<p class="lq-menu__note"><?php echo esc_html( $lq_note ); ?></p>
		<?php endif; ?>
		<p class="lq-menu__credit">&copy; <?php echo esc_html( wp_date( 'Y' ) . ' ' . get_bloginfo( 'name' ) ); ?></p>
	</div>
	<?php
	return;
}

if ( ! $lq_items ) {
	if ( current_user_can( 'edit_pages' ) && 'path' === $lq_set ) {
		printf(
			'<p %1$s>%2$s</p>',
			$lq_wrap, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Publish the guide pages to list them here. Only editors see this notice.', 'lailatulqadar' )
		);
	}
	return;
}

$lq_current = is_singular() ? get_queried_object_id() : 0;

if ( 'sitemap' === $lq_set ) :
	?>
	<ul <?php echo $lq_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php foreach ( $lq_items as $lq_item ) : ?>
			<li><a href="<?php echo esc_url( $lq_item['url'] ); ?>"><?php echo esc_html( $lq_item['title'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php
	return;
endif;

if ( 'path' === $lq_set ) :
	?>
	<ol <?php echo $lq_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php foreach ( $lq_items as $lq_item ) : ?>
			<?php if ( ! empty( $lq_item['upcoming'] ) ) : ?>
				<li class="is-upcoming"><span class="lq-menu__upcoming"><span class="lq-menu__title"><?php echo esc_html( $lq_item['title'] ); ?></span><span class="lq-menu__soon"><?php echo esc_html( $lq_soon ); ?></span></span></li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( $lq_item['url'] ); ?>"><?php echo esc_html( $lq_item['title'] ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
	<?php
	return;
endif;
?>
<nav <?php echo $lq_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Guide', 'lailatulqadar' ); ?>">
	<ul class="lq-menu__list">
		<?php foreach ( $lq_items as $lq_item ) : ?>
			<li><a href="<?php echo esc_url( $lq_item['url'] ); ?>"<?php echo $lq_current === $lq_item['id'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $lq_item['short'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</nav>
