<?php
/**
 * Addresses without "a", "an" or "the".
 *
 * When WordPress builds a post or page address from its title, the English
 * articles a, an and the are left out: "The Night of Decree" becomes
 * night-of-decree, not the-night-of-decree. Other small words (is, of, dan)
 * stay. Only an address WordPress generates is changed. One an editor types
 * in the slug field, one setup gives a guide page, and the address of any
 * page that already has one are left as they are, so no existing address
 * changes and no redirect is needed.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Words left out of generated addresses.
 *
 * @return string[]
 */
function lq_slug_stopwords() {
	return (array) apply_filters( 'lq_slug_stopwords', array( 'a', 'an', 'the' ) );
}

/**
 * A slug without the stopwords, or the slug unchanged if nothing would be
 * left of it.
 *
 * @param string $slug Sanitised slug.
 * @return string
 */
function lq_slug_without_stopwords( $slug ) {
	$words = explode( '-', (string) $slug );
	$kept  = array_values( array_diff( $words, lq_slug_stopwords() ) );
	return $kept ? implode( '-', $kept ) : (string) $slug;
}

/**
 * Drop the stopwords from an address WordPress has just built from the
 * title. WordPress builds one when a post is first given an address (on
 * publish, or on an explicit save of a draft) and the request carries no
 * slug of its own.
 *
 * @param array $data                Sanitised post data about to be saved.
 * @param array $postarr             Sanitised post data as passed in.
 * @param array $unsanitized_postarr Post data as passed in, before sanitising.
 * @return array
 */
function lq_generated_slug( $data, $postarr, $unsanitized_postarr ) {
	$given = isset( $unsanitized_postarr['post_name'] ) ? (string) $unsanitized_postarr['post_name'] : '';
	if ( '' !== $given || empty( $data['post_name'] ) || ! is_post_type_viewable( $data['post_type'] ) ) {
		return $data;
	}
	$slug = lq_slug_without_stopwords( sanitize_title( $data['post_title'] ) );
	if ( '' === $slug || $slug === $data['post_name'] ) {
		return $data;
	}
	$id                = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
	$data['post_name'] = wp_unique_post_slug( $slug, $id, $data['post_status'], $data['post_type'], (int) $data['post_parent'] );
	return $data;
}
add_filter( 'wp_insert_post_data', 'lq_generated_slug', 10, 3 );
