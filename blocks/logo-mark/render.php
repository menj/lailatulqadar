<?php
/**
 * Logo mark: the lantern (without the crescent and star, which stay with
 * the larger uses) and the Arabic calligraphy of
 * ليلة القدر, followed by a thin rule before the site title. Decorative for
 * assistive technology; the site title beside it carries the name and link.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;
?>
<span <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-brand' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<a class="lq-brand__link" href="<?php echo esc_url( lq_home_url() ); ?>" tabindex="-1" aria-hidden="true">
		<span class="lq-mark"><?php echo lq_mark_svg( 'icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php echo lq_calligraphy_html( 'lq-calli--header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
	<span class="lq-brand__rule" aria-hidden="true"></span>
</span>
