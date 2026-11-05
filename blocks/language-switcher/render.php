<?php
/**
 * Language switcher block render: links to the same page in the other
 * language, or to that language's home when no translation is published.
 * The header and the footer each hold one, and each landmark gets a name of
 * its own (see below).
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
// Each navigation landmark on a page needs a name of its own. The footer's
// is known by its context attribute or, for a footer part saved in the Site
// Editor before that attribute existed, by sitting in the footer part
// (lq_enter_footer_part()). Any other switcher after the first, one placed
// in a page's content, is numbered.
$lq_footer = ( isset( $attributes['context'] ) && 'footer' === sanitize_key( $attributes['context'] ) )
	|| ! empty( $GLOBALS['lq_footer_depth'] );
if ( $lq_footer ) {
	$lq_name = 'secondary' === $lq_role ? 'Pautan bahasa di kaki halaman' : 'Footer language links';
} else {
	$lq_seen                     = (int) ( $GLOBALS['lq_lang_switchers'] ?? 0 ) + 1;
	$GLOBALS['lq_lang_switchers'] = $lq_seen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( 1 === $lq_seen ) {
		$lq_name = 'secondary' === $lq_role ? 'Bahasa' : 'Language';
	} else {
		/* Numbered from 2: "Pautan bahasa 2", "Language links 2". */
		$lq_name = ( 'secondary' === $lq_role ? 'Pautan bahasa ' : 'Language links ' ) . $lq_seen;
	}
}
$lq_labels = array(
	'primary'   => lq_setting( 'primary_label' ),
	'secondary' => lq_setting( 'secondary_label' ),
);
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-lang' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php echo esc_attr( $lq_name ); ?>">
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
