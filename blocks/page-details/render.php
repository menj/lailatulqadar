<?php
/**
 * Page details block render: published and updated dates, the last review
 * and the reading time, under the title of a guide page; then a Print / Save
 * as PDF button (shown once js/print.js runs) and the page's address, which
 * only the printed copy shows (css/print.css).
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_id = lailatulqadar_current_guide_page();
if ( ! $lq_id ) {
	return;
}
$lq_items = lailatulqadar_page_details( $lq_id );
$lq_role  = lq_role_of( $lq_id );
$lq_print = lailatulqadar_t( 'Print or save as PDF', 'Cetak atau simpan sebagai PDF', $lq_role );
?>
<ul <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-details' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php foreach ( $lq_items as $lq_kind => $lq_item ) : ?>
	<li class="lq-details__item lq-details__item--<?php echo esc_attr( $lq_kind ); ?>"><span class="lq-details__label"><?php echo esc_html( $lq_item[0] ); ?></span> <?php if ( '' !== $lq_item[2] ) : ?><time datetime="<?php echo esc_attr( $lq_item[2] ); ?>"><?php echo esc_html( $lq_item[1] ); ?></time><?php else : ?><?php echo esc_html( $lq_item[1] ); ?><?php endif; ?></li>
	<?php endforeach; ?>
	<li class="lq-details__item lq-details__item--print" hidden><button type="button" class="lq-print" data-lq-print><svg class="lq-print__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M6 9V3h12v6M6 18H3v-8h18v8h-3M6 14h12v7H6z"/></svg><?php echo esc_html( $lq_print ); ?></button></li>
	<?php $lq_prefsrc = lailatulqadar_prefsrc_html( 'guide' ); ?>
	<?php if ( '' !== $lq_prefsrc ) : ?>
	<li class="lq-details__item lq-details__item--prefsrc"><?php echo $lq_prefsrc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in lailatulqadar_prefsrc_html(). ?></li>
	<?php endif; ?>
	<li class="lq-details__item lq-details__item--source"><span class="lq-details__label"><?php echo esc_html( lailatulqadar_t( 'Online at', 'Dalam talian di', $lq_role ) ); ?></span> <?php echo esc_html( (string) get_permalink( $lq_id ) ); ?></li>
</ul>
