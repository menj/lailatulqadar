<?php
/**
 * Title: Blog index title
 * Slug: lailatulqadar/blog-title
 * Inserter: no
 *
 * The heading of the posts index: the title of the page chosen as the posts
 * page, or "Posts" (Malay "Catatan") when none is chosen.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

$lq_blog  = (int) get_option( 'page_for_posts' );
$lq_title = $lq_blog ? get_the_title( $lq_blog ) : ( 'secondary' === lq_current_role() ? 'Catatan' : 'Posts' );
?>
<!-- wp:heading {"level":1,"className":"lq-page-title"} -->
<h1 class="wp-block-heading lq-page-title"><?php echo esc_html( $lq_title ); ?></h1>
<!-- /wp:heading -->
