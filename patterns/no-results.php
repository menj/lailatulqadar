<?php
/**
 * Title: No results
 * Slug: lailatulqadar/no-results
 * Inserter: no
 *
 * Message and next steps when a search or archive finds nothing, in the
 * language of the page.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_ms   = 'secondary' === lq_current_role();
$lq_text = $lq_ms
	? 'Tiada padanan ditemui. Cuba perkataan lain, atau mulakan dengan panduan di bawah.'
	: 'Nothing matched. Try other words, or start with the guide below.';
?>
<!-- wp:paragraph {"className":"lq-no-results"} -->
<p class="lq-no-results"><?php echo esc_html( $lq_text ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:lailatulqadar/guide-menu {"set":"path"} /-->
