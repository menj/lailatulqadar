<?php
/**
 * Page details block render: published and updated dates, the last review
 * and the reading time, under the title of a guide page.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_id = lailatulqadar_current_guide_page();
if ( ! $lq_id ) {
	return;
}
$lq_items = lailatulqadar_page_details( $lq_id );
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-details' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $lq_items as $lq_kind => $lq_item ) : ?>
	<li class="lq-details__item lq-details__item--<?php echo esc_attr( $lq_kind ); ?>"><span class="lq-details__label"><?php echo esc_html( $lq_item[0] ); ?></span> <?php if ( '' !== $lq_item[2] ) : ?><time datetime="<?php echo esc_attr( $lq_item[2] ); ?>"><?php echo esc_html( $lq_item[1] ); ?></time><?php else : ?><?php echo esc_html( $lq_item[1] ); ?><?php endif; ?></li>
	<?php endforeach; ?>
</ul>
