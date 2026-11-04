<?php
/**
 * Image sitemap at /image-sitemap.xml.
 *
 * WordPress's own sitemaps carry no image entries. This file lists every
 * published page and post with the images it shows: its featured image and
 * each photograph placed with the lailatulqadar/photo block, at the largest
 * size the page serves. robots.txt names it. While an SEO plugin is active
 * the file stands down, as Rank Math lists images in its own sitemap.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Image addresses one post shows.
 *
 * @param WP_Post $post Post or page.
 * @return string[]
 */
function lq_image_sitemap_images( $post ) {
	$images = array();
	$thumb  = get_post_thumbnail_id( $post );
	if ( $thumb ) {
		$images[] = (string) wp_get_attachment_url( $thumb );
	}
	foreach ( lq_photo_block_images( $post ) as $image ) {
		if ( file_exists( lq_image_file( $image, max( $image['widths'] ), LQ_DIR ) ) ) {
			$images[] = lq_image_file( $image, max( $image['widths'] ), LQ_URI );
		}
	}
	return array_values( array_unique( array_filter( $images ) ) );
}

/**
 * Image entries of the lailatulqadar/photo blocks in a post, nested ones
 * included, resolved as blocks/photo/render.php does: the block's key, or
 * the post's own registry key when the block's is empty.
 *
 * @param WP_Post $post Post or page.
 * @return array[] Entries from inc/data/images.json.
 */
function lq_photo_block_images( $post ) {
	$images = array();
	$own    = lq_page_key_of( $post->ID );
	foreach ( parse_blocks( (string) $post->post_content ) as $block ) {
		lq_photo_block_walk( $block, $own, $images );
	}
	return $images;
}

/**
 * Collect photo blocks, nested ones included.
 *
 * @param array   $block  Parsed block.
 * @param string  $own    Registry key of the post.
 * @param array[] $images Collected image entries.
 */
function lq_photo_block_walk( $block, $own, &$images ) {
	if ( 'lailatulqadar/photo' === $block['blockName'] ) {
		$key = isset( $block['attrs']['key'] ) ? sanitize_key( $block['attrs']['key'] ) : '';
		if ( '' === $key ) {
			$key = $own;
		}
		$image = '' !== $key ? lq_image( $key, max( 1, (int) ( $block['attrs']['n'] ?? 1 ) ) ) : null;
		if ( $image ) {
			$images[] = $image;
		}
	}
	foreach ( (array) $block['innerBlocks'] as $inner ) {
		lq_photo_block_walk( $inner, $own, $images );
	}
}

/**
 * The image sitemap as XML.
 *
 * @return string
 */
function lq_image_sitemap_xml() {
	$posts = array();
	$paged = 1;
	do {
		// Batches of 200, so every post is listed without one unbounded query.
		$batch = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'paged'          => $paged,
				'has_password'   => false,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				// Unlisted pages (inc/unlist.php), also while the plugin is active.
				'post__not_in'   => function_exists( 'lailatulqadar_unlist_sitemap_ids' ) ? lailatulqadar_unlist_sitemap_ids() : array(),
			)
		);
		$posts = array_merge( $posts, $batch );
		++$paged;
	} while ( count( $batch ) === 200 );
	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
		. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
	foreach ( $posts as $post ) {
		$images = lq_image_sitemap_images( $post );
		if ( ! $images ) {
			continue;
		}
		$xml .= "\t<url>\n\t\t<loc>" . esc_url( get_permalink( $post ) ) . "</loc>\n";
		foreach ( $images as $image ) {
			$xml .= "\t\t<image:image><image:loc>" . esc_url( $image ) . "</image:loc></image:image>\n";
		}
		$xml .= "\t</url>\n";
	}
	return $xml . '</urlset>' . "\n";
}

/**
 * Serve /image-sitemap.xml.
 *
 * @param WP $wp Request.
 */
function lq_image_sitemap_serve( $wp ) {
	if ( 'image-sitemap.xml' !== $wp->request || lq_seo_plugin() || ! get_option( 'blog_public' ) ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo lq_image_sitemap_xml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped above.
	exit;
}
add_action( 'parse_request', 'lq_image_sitemap_serve', 0 );

/**
 * robots.txt: name the image sitemap.
 *
 * @param string $output robots.txt.
 * @param bool   $public Whether the site is public.
 * @return string
 */
function lq_image_sitemap_robots_txt( $output, $public ) {
	if ( ! $public || lq_seo_plugin() || ! get_option( 'permalink_structure' ) ) {
		return $output;
	}
	return rtrim( $output ) . "\nSitemap: " . esc_url( home_url( '/image-sitemap.xml' ) ) . "\n";
}
add_filter( 'robots_txt', 'lq_image_sitemap_robots_txt', 20, 2 );
