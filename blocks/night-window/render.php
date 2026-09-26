<?php
/**
 * Night window block render: a shell the browser script fills in.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_config = lq_nw_config();
$lq_s      = $lq_config['strings'];
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'lq-nw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-config="<?php echo esc_attr( wp_json_encode( $lq_config ) ); ?>" aria-labelledby="lq-nw-title">
	<div class="lq-nw__sky" aria-hidden="true"></div>
	<header class="lq-nw__head">
		<h2 id="lq-nw-title" class="lq-nw__title"><?php echo esc_html( $lq_s['title'] ); ?></h2>
		<p class="lq-nw__lede"><?php echo esc_html( $lq_s['lede'] ); ?></p>
	</header>
	<div class="lq-nw__app" hidden></div>
	<noscript><p class="lq-nw__noscript"><?php echo esc_html( $lq_s['note'] ); ?></p></noscript>
</section>
