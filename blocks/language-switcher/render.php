<?php
/**
 * Language switcher block render: links to the same page in the other
 * language, or to that language's home when no translation is published.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_map  = lq_page_map();
$lq_role = lq_current_role();
if ( empty( $lq_map['secondary']['home'] ) || 'publish' !== get_post_status( (int) $lq_map['secondary']['home'] ) ) {
	return;
}

$lq_urls = array(
	'primary'   => home_url( '/' ),
	'secondary' => get_permalink( (int) $lq_map['secondary']['home'] ),
);
if ( is_singular( 'page' ) ) {
	$lq_other = lq_translation_of( get_queried_object_id() );
	if ( $lq_other && 'publish' === get_post_status( $lq_other ) && ! lq_is_home() ) {
		$lq_urls[ 'secondary' === $lq_role ? 'primary' : 'secondary' ] = get_permalink( $lq_other );
	}
}
$lq_labels = array(
	'primary'   => lq_setting( 'primary_label' ),
	'secondary' => lq_setting( 'secondary_label' ),
);
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-lang' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( 'secondary' === $lq_role ? 'Bahasa' : 'Language' ); ?>">
	<ul class="lq-lang__list">
		<?php foreach ( array( 'primary', 'secondary' ) as $lq_r ) : ?>
			<li>
				<?php if ( $lq_r === $lq_role ) : ?>
					<span aria-current="true"><?php echo esc_html( $lq_labels[ $lq_r ] ); ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $lq_urls[ $lq_r ] ); ?>" hreflang="<?php echo esc_attr( lq_hreflang( $lq_r ) ); ?>" lang="<?php echo esc_attr( lq_hreflang( $lq_r ) ); ?>"><?php echo esc_html( $lq_labels[ $lq_r ] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
