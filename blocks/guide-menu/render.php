<?php
/**
 * Guide menu block render.
 *
 * @package Lailatulqadar
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$lq_set = isset( $attributes['set'] ) && in_array( $attributes['set'], array( 'header', 'path', 'footer', 'sitemap' ), true ) ? $attributes['set'] : 'header';
// The reading path also lists pages still in preparation, as plain text,
// so the full plan shows from the start (filter lq_path_show_upcoming).
$lq_upcoming = 'path' === $lq_set && apply_filters( 'lq_path_show_upcoming', true );
$lq_items    = lq_menu_items( $lq_set, $lq_upcoming );
$lq_soon     = 'secondary' === lq_current_role() ? 'Akan datang' : 'Coming soon';
$lq_wrap     = get_block_wrapper_attributes( array( 'class' => 'lq-menu lq-menu--' . $lq_set ) );

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
	// On the front page the path is split into chapters, so the guide reads
	// as one story; the numbering runs on through them.
	$lq_groups = empty( $attributes['chapters'] ) ? array() : lq_path_groups( $lq_items );
	if ( $lq_groups ) :
		$lq_ms      = 'secondary' === lq_current_role();
		$lq_wrap    = get_block_wrapper_attributes( array( 'class' => 'lq-menu lq-menu--chapters alignwide' ) );
		$lq_star    = lq_star_svg();
		$lq_ch_word = $lq_ms ? 'Bab' : 'Chapter';
		$lq_minutes = $lq_ms ? '%d minit bacaan' : '%d min read';
		$lq_tool    = $lq_ms ? 'Jadual interaktif' : 'Interactive timetable';
		?>
	<div <?php echo $lq_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		foreach ( $lq_groups as $lq_n => $lq_group ) :
			$lq_ch_id = wp_unique_id( 'lq-chapter-' );
			?>
		<section class="lq-chapter" aria-labelledby="<?php echo esc_attr( $lq_ch_id ); ?>">
			<header class="lq-chapter__head">
				<span class="lq-chapter__badge"><?php echo $lq_star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?><span class="lq-chapter__no"><?php echo (int) $lq_n + 1; ?></span></span>
				<span class="lq-chapter__label"><?php echo esc_html( $lq_ch_word . ' ' . ( $lq_n + 1 ) ); ?></span>
				<h3 class="lq-chapter__title" id="<?php echo esc_attr( $lq_ch_id ); ?>"><?php echo esc_html( $lq_group['heading'] ); ?></h3>
			</header>
			<ol class="lq-cards" start="<?php echo (int) $lq_group['start']; ?>">
				<?php
				foreach ( $lq_group['items'] as $lq_i => $lq_item ) :
					$lq_num     = sprintf( '%02d', (int) $lq_group['start'] + $lq_i );
					$lq_summary = lq_page_summary( $lq_item['key'] );
					$lq_icon    = lq_page_icon( $lq_item['key'] );
					?>
					<?php if ( ! empty( $lq_item['upcoming'] ) ) : ?>
				<li class="lq-gcard is-upcoming">
					<div class="lq-gcard__body">
						<span class="lq-gcard__top"><span class="lq-gcard__num"><?php echo esc_html( $lq_num ); ?></span><?php echo $lq_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?></span>
						<span class="lq-gcard__title"><?php echo esc_html( $lq_item['title'] ); ?></span>
						<span class="lq-gcard__text"><?php echo esc_html( $lq_summary ); ?></span>
						<span class="lq-gcard__foot"><span class="lq-gcard__chip"><?php echo esc_html( $lq_soon ); ?></span></span>
					</div>
				</li>
					<?php else : ?>
				<li class="lq-gcard">
					<a class="lq-gcard__body" href="<?php echo esc_url( $lq_item['url'] ); ?>">
						<span class="lq-gcard__top"><span class="lq-gcard__num"><?php echo esc_html( $lq_num ); ?></span><?php echo $lq_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG. ?></span>
						<span class="lq-gcard__title"><?php echo esc_html( $lq_item['title'] ); ?></span>
						<span class="lq-gcard__text"><?php echo esc_html( $lq_summary ); ?></span>
						<span class="lq-gcard__foot">
							<span class="lq-gcard__chip"><?php echo esc_html( 'planner' === $lq_item['key'] ? $lq_tool : sprintf( $lq_minutes, lq_reading_minutes_cached( $lq_item['id'] ) ) ); ?></span>
							<span class="lq-gcard__arrow" aria-hidden="true">&rarr;</span>
						</span>
					</a>
				</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php endforeach; ?>
	</div>
		<?php
		return;
	endif;
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
