<?php
/**
 * Sources block render: the verses and hadith a guide page quotes, and the
 * sources its notes name, each linked to its note. The ids match the
 * structured data (#quote-N, #note-N), which cites the same list.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_id = lailatulqadar_current_guide_page();
if ( ! $lq_id || 'sources' === lq_page_key_of( $lq_id ) ) {
	return;
}
$lq_sources = lailatulqadar_page_sources( $lq_id );
if ( ! $lq_sources['dalil'] && ! $lq_sources['refs'] ) {
	return;
}
$lq_role = lq_role_of( $lq_id );
$lq_seen = array();
$lq_title = wp_unique_id( 'lq-sources-title-' );
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-sources', 'aria-labelledby' => $lq_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<h2 class="lq-sources__title" id="<?php echo esc_attr( $lq_title ); ?>"><?php echo esc_html( lailatulqadar_t( 'Evidence and sources', 'Dalil dan rujukan', $lq_role ) ); ?></h2>
	<?php if ( $lq_sources['dalil'] ) : ?>
	<h3 class="lq-sources__heading"><?php echo esc_html( lailatulqadar_t( 'Verses and hadith quoted', 'Ayat dan hadis yang dipetik', $lq_role ) ); ?></h3>
	<ul class="lq-sources__list">
		<?php foreach ( $lq_sources['dalil'] as $lq_q ) : ?>
			<?php
			if ( isset( $lq_seen[ 'q' . $lq_q['cite'] ] ) ) {
				continue;
			}
			$lq_seen[ 'q' . $lq_q['cite'] ] = true;
			?>
		<li id="quote-<?php echo (int) $lq_q['n']; ?>"><span class="lq-sources__kind"><?php echo esc_html( 'quran' === $lq_q['kind'] ? lailatulqadar_t( 'Qurʾān', 'Al-Quran', $lq_role ) : lailatulqadar_t( 'Hadith', 'Hadis', $lq_role ) ); ?></span> <?php echo esc_html( $lq_q['cite'] ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
	<?php if ( $lq_sources['refs'] ) : ?>
	<h3 class="lq-sources__heading"><?php echo esc_html( lailatulqadar_t( 'References in the notes', 'Rujukan dalam nota', $lq_role ) ); ?></h3>
	<ul class="lq-sources__list">
		<?php foreach ( $lq_sources['refs'] as $lq_r ) : ?>
			<?php
			if ( isset( $lq_seen[ 'r' . $lq_r['name'] ] ) ) {
				continue;
			}
			$lq_seen[ 'r' . $lq_r['name'] ] = true;
			?>
		<li id="note-<?php echo (int) $lq_r['n']; ?>"><a href="#<?php echo esc_attr( $lq_r['note'] ); ?>"><?php echo esc_html( $lq_r['name'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</section>
