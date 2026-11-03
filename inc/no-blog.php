<?php
/**
 * The site has no blog (owner's decision of 3 November 2026).
 *
 * Posts are retired: the blog templates are withdrawn from the editor and
 * the template hierarchy, every post view (the blog home, single posts and
 * the date, author, category and tag archives) and every feed answers 301
 * to the language home, and posts leave the XML sitemaps, core and Rank
 * Math alike. A one-time migration, run by setup, clears the posts page
 * setting and any blog template a page was given. The admin is untouched.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

define( 'LQ_BLOG_RETIRED_OPTION', 'lailatulqadar_blog_retired' );
define( 'LQ_BLOG_RETIRED_VERSION', '1.15.0' );

/**
 * Template slugs that served posts and are no longer offered.
 *
 * @return string[]
 */
function lq_retired_template_slugs() {
	return array( 'home', 'archive', 'single', 'single-post', 'date', 'author', 'category', 'tag' );
}

/**
 * Drop the retired templates from every template list, the editor's and
 * the hierarchy's, including copies of them saved in the Site Editor.
 *
 * @param array  $templates     Block templates.
 * @param array  $query         Query.
 * @param string $template_type wp_template or wp_template_part.
 * @return array
 */
function lq_drop_retired_templates( $templates, $query = array(), $template_type = 'wp_template' ) {
	if ( 'wp_template' !== $template_type || ! is_array( $templates ) ) {
		return $templates;
	}
	$retired = lq_retired_template_slugs();
	return array_values(
		array_filter(
			$templates,
			static function ( $t ) use ( $retired ) {
				return ! ( is_object( $t ) && isset( $t->slug ) && in_array( $t->slug, $retired, true ) );
			}
		)
	);
}
add_filter( 'get_block_templates', 'lq_drop_retired_templates', 10, 3 );

/**
 * Keep the retired template types out of the Site Editor's "Add template".
 *
 * @param array $types Template types keyed by slug.
 * @return array
 */
function lq_drop_retired_template_types( $types ) {
	foreach ( lq_retired_template_slugs() as $slug ) {
		unset( $types[ $slug ] );
	}
	return $types;
}
add_filter( 'default_template_types', 'lq_drop_retired_template_types' );

/**
 * Whether the current request is a retired post view or a feed.
 *
 * @return bool
 */
function lq_is_retired_view() {
	if ( is_feed() ) {
		return true;
	}
	if ( is_home() && ! is_front_page() ) {
		return true;
	}
	return is_singular( 'post' ) || is_date() || is_author() || is_category() || is_tag() || is_post_type_archive( 'post' );
}

/**
 * The home page of a language: the Malay home at /ms/ for the secondary
 * role, when it is published, and the front page otherwise.
 *
 * @param string $role primary or secondary.
 * @return string
 */
function lq_language_home_url( $role ) {
	$map = lq_page_map();
	if ( 'secondary' === $role && ! empty( $map['secondary']['home'] ) && 'publish' === get_post_status( (int) $map['secondary']['home'] ) ) {
		return get_permalink( (int) $map['secondary']['home'] );
	}
	return home_url( '/' );
}

/**
 * Where a retired view goes: the language home of the post or address.
 *
 * @return string
 */
function lq_retired_target() {
	$role = is_singular( 'post' ) ? lq_role_of( get_queried_object_id() ) : lq_current_role();
	return lq_language_home_url( $role );
}

/**
 * Send retired views to the language home with a 301. Spam the security
 * tool trashed is a 404 and keeps its 410 (lq_sec_send_gone()).
 */
function lq_redirect_retired_views() {
	if ( is_admin() || is_404() || ! lq_is_retired_view() ) {
		return;
	}
	wp_safe_redirect( lq_retired_target(), 301, 'Lailatulqadar' );
	exit;
}
add_action( 'template_redirect', 'lq_redirect_retired_views', 1 );

// No feed links in the head.
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

/**
 * Remove posts from the core XML sitemap.
 *
 * @param array $types Post types keyed by name.
 * @return array
 */
function lq_sitemap_drop_posts( $types ) {
	unset( $types['post'] );
	return $types;
}
add_filter( 'wp_sitemaps_post_types', 'lq_sitemap_drop_posts' );

/**
 * Remove posts from the Rank Math sitemap.
 *
 * @param bool   $exclude Whether to exclude.
 * @param string $type    Post type.
 * @return bool
 */
function lq_rank_math_drop_posts( $exclude, $type ) {
	return 'post' === $type ? true : $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'lq_rank_math_drop_posts', 10, 2 );

/**
 * Remove post categories and tags from the Rank Math sitemap.
 *
 * @param bool   $exclude  Whether to exclude.
 * @param string $taxonomy Taxonomy.
 * @return bool
 */
function lq_rank_math_drop_post_terms( $exclude, $taxonomy ) {
	return in_array( $taxonomy, array( 'category', 'post_tag', 'post_format' ), true ) ? true : $exclude;
}
add_filter( 'rank_math/sitemap/exclude_taxonomy', 'lq_rank_math_drop_post_terms', 10, 2 );

/**
 * One-time migration: clear the posts page setting and any retired
 * template a page was given. Runs from setup once per site; a second run
 * finds the version stamp and does nothing.
 *
 * @return bool True when the migration ran.
 */
function lq_migrate_retire_blog() {
	if ( version_compare( (string) get_option( LQ_BLOG_RETIRED_OPTION, '0' ), LQ_BLOG_RETIRED_VERSION, '>=' ) ) {
		return false;
	}
	if ( (int) get_option( 'page_for_posts' ) ) {
		update_option( 'page_for_posts', 0 );
	}
	if ( 'posts' === get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
	}
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => array_merge( lq_retired_template_slugs(), array( 'index' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare'   => 'IN',
		)
	);
	foreach ( (array) $pages as $id ) {
		delete_post_meta( (int) $id, '_wp_page_template' );
	}
	update_option( LQ_BLOG_RETIRED_OPTION, LQ_BLOG_RETIRED_VERSION, false );
	return true;
}
