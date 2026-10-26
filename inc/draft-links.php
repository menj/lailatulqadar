<?php
/**
 * Links to guide pages that are not yet published.
 *
 * Setup writes internal links into seeded articles ({{url:key}}), but leaves
 * most guide pages as drafts. A visitor following such a link would reach a
 * page not found, so until the target is published the link is shown as
 * plain text. Editors still see the link, so they can follow it to the draft.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Addresses of the guide pages that are not published, in every form a
 * seeded link may take: the pretty address, the draft address WordPress
 * gives, and ?page_id=.
 *
 * @return string[] Address => true, without a trailing slash.
 */
function lq_unpublished_page_urls() {
	static $urls = null;
	if ( null !== $urls ) {
		return $urls;
	}
	$urls = array();
	$defs = lq_page_definitions();
	foreach ( lq_page_map() as $role => $ids ) {
		foreach ( (array) $ids as $key => $id ) {
			$id = (int) $id;
			if ( ! $id || 'publish' === get_post_status( $id ) ) {
				continue;
			}
			$forms = array( get_permalink( $id ), home_url( '/?page_id=' . $id ) );
			if ( ! empty( $defs[ $key ][ $role ]['slug'] ) && 'home' !== $key ) {
				$prefix  = 'secondary' === $role ? lq_setting( 'secondary_slug' ) . '/' : '';
				$forms[] = home_url( '/' . $prefix . $defs[ $key ][ $role ]['slug'] . '/' );
			}
			foreach ( $forms as $url ) {
				if ( $url ) {
					$urls[ untrailingslashit( $url ) ] = true;
				}
			}
		}
	}
	return $urls;
}

/**
 * Show links to unpublished guide pages as plain text for visitors.
 *
 * @param string $content Post content.
 * @return string
 */
function lq_unlink_unpublished_pages( $content ) {
	if ( is_admin() || current_user_can( 'edit_pages' ) || false === strpos( (string) $content, '<a ' ) ) {
		return $content;
	}
	$urls = lq_unpublished_page_urls();
	if ( ! $urls ) {
		return $content;
	}
	$out = preg_replace_callback(
		'#<a\s[^>]*\bhref=(["\'])([^"\']*)\1[^>]*>(.*?)</a>#is',
		static function ( $m ) use ( $urls ) {
			$href = untrailingslashit( html_entity_decode( strtok( $m[2], '#' ), ENT_QUOTES, 'UTF-8' ) );
			return isset( $urls[ $href ] ) ? $m[3] : $m[0];
		},
		$content
	);
	return null === $out ? $content : $out;
}
add_filter( 'the_content', 'lq_unlink_unpublished_pages', 20 );
