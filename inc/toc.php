<?php
/**
 * WP TOC (wp-toc/, from https://github.com/menj/wp-toc) as part of the
 * theme. The folder is bundled unchanged and loaded from functions.php;
 * this file only fits it to the site through WP TOC's own filters:
 *
 * - The automatic table of contents goes on guide pages only (the pages of
 *   the reading path), lists their H2 headings and needs at least
 *   `lailatulqadar_contents_min` of them (default 3), as the theme's own
 *   contents list did before 1.22.0.
 * - Its title reads "On this page" or "Kandungan halaman", by the page's
 *   language.
 * - Its stylesheet is replaced by the theme's (css/page-parts.css), so it
 *   follows the palette and both colour schemes.
 * - Its JSON-LD is not printed on its own: the sections go into the theme's
 *   one graph instead (SSOT.md, Structured data), on the WebPage node whose
 *   @id WP TOC's microdata already points to (the permalink plus #webpage,
 *   or Rank Math's canonical plus #webpage).
 *
 * WP TOC needs PHP 8.4; on an older PHP it does not load, and every
 * function here does nothing.
 *
 * @package Lailatulqadar
 * @since   1.22.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether WP TOC is loaded.
 *
 * @return bool
 */
function lailatulqadar_toc_active() {
	return class_exists( 'WP_TOC' );
}

/**
 * The automatic table of contents: guide pages only, their H2 headings,
 * three at least. The theme's heading ids (inc/page-parts.php) stay as they
 * are, since WP TOC keeps an id a heading already has.
 *
 * @param array $settings Settings from WP TOC.
 * @param int   $post_id  Post ID.
 * @return array
 */
function lailatulqadar_toc_auto_insert( $settings, $post_id ) {
	$settings               = is_array( $settings ) ? $settings : array();
	$settings['enabled']    = $post_id > 0 && lailatulqadar_is_guide_page( (int) $post_id );
	$settings['post_types'] = array( 'page' );
	$settings['position']   = 'before_first_heading';
	$settings['atts']       = array_merge(
		isset( $settings['atts'] ) ? (array) $settings['atts'] : array(),
		array(
			'tags' => 'h2',
			'min'  => (string) max( 1, (int) apply_filters( 'lailatulqadar_contents_min', 3 ) ),
		)
	);
	return $settings;
}
add_filter( 'wp_toc_auto_insert', 'lailatulqadar_toc_auto_insert', 10, 2 );

/**
 * The default title, in the page's language.
 *
 * @param array $defaults Default shortcode attributes.
 * @return array
 */
function lailatulqadar_toc_default_atts( $defaults ) {
	$defaults          = is_array( $defaults ) ? $defaults : array();
	$id                = (int) get_the_ID();
	$defaults['title'] = lailatulqadar_t( 'On this page', 'Kandungan halaman', $id ? lq_role_of( $id ) : null );
	return $defaults;
}
add_filter( 'wp_toc_default_atts', 'lailatulqadar_toc_default_atts' );

// The theme styles the table of contents itself (css/page-parts.css).
add_filter( 'wp_toc_load_css', '__return_false' );

/**
 * The sections WP TOC would have printed as its own JSON-LD, kept for the
 * theme's graph, keyed by the WebPage @id they belong to.
 *
 * @param array|null $data New data to keep, or null to read.
 * @return array
 */
function lailatulqadar_toc_sections( $data = null ) {
	static $kept = array();
	if ( is_array( $data ) && ! empty( $data['@id'] ) && ! empty( $data['hasPart'] ) ) {
		$kept[ (string) $data['@id'] ] = (array) $data['hasPart'];
	}
	return $kept;
}

/**
 * Keep WP TOC's JSON-LD for the theme's graph and print none of its own,
 * so the page has one graph. In a block theme the content renders before
 * wp_head, so the sections are known when the graph prints.
 *
 * @param array $data JSON-LD from WP TOC.
 * @return array Empty, so WP TOC prints nothing.
 */
function lailatulqadar_toc_schema( $data ) {
	lailatulqadar_toc_sections( $data );
	return array();
}
add_filter( 'wp_toc_schema', 'lailatulqadar_toc_schema' );

/**
 * Add the kept sections to the WebPage node with the same @id, as
 * WebPageElement nodes in its hasPart. The @id of each section (permalink,
 * #, heading id) is the itemid WP TOC's microdata gives the same entry, so
 * the two descriptions are one.
 *
 * @param array $graph The theme's graph (a list of nodes).
 * @return array
 */
function lailatulqadar_toc_graph( $graph ) {
	$kept = lailatulqadar_toc_sections();
	if ( ! $kept || ! is_array( $graph ) ) {
		return $graph;
	}
	foreach ( $graph as $i => $node ) {
		$id = is_array( $node ) && isset( $node['@id'] ) ? (string) $node['@id'] : '';
		if ( '' === $id || ! isset( $kept[ $id ] ) ) {
			continue;
		}
		$parts                  = isset( $node['hasPart'] ) ? (array) $node['hasPart'] : array();
		$parts                  = isset( $parts['@id'] ) || isset( $parts['@type'] ) ? array( $parts ) : $parts;
		$graph[ $i ]['hasPart'] = array_merge( $parts, $kept[ $id ] );
	}
	return $graph;
}
add_filter( 'lq_schema_graph', 'lailatulqadar_toc_graph', 20 );

/**
 * Whether a post's text places a table of contents by hand: the [wp_toc]
 * or [auto_toc] shortcode, or the Contents block.
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function lailatulqadar_toc_placed( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	return has_shortcode( $post->post_content, 'wp_toc' ) || has_shortcode( $post->post_content, 'auto_toc' ) || has_block( 'lailatulqadar/contents', $post );
}

/**
 * The theme's table of contents style on any page or post that places one;
 * guide pages load it already (inc/page-parts.php).
 */
function lailatulqadar_toc_assets() {
	if ( lailatulqadar_toc_active() && is_singular() && lailatulqadar_toc_placed( get_queried_object_id() ) ) {
		wp_enqueue_style( 'lailatulqadar-page-parts', LQ_URI . '/css/page-parts.css', array( 'lailatulqadar-front' ), LQ_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_toc_assets', 20 );

/**
 * A table of contents without a visible title is named "Table of Contents"
 * by WP TOC, for screen readers and the schema name, in English. Give it
 * the page's language instead.
 *
 * @param string $html TOC markup.
 * @return string
 */
function lailatulqadar_toc_name( $html ) {
	$english = esc_attr__( 'Table of Contents', 'wp-toc' );
	$id      = (int) get_the_ID();
	$name    = esc_attr( lailatulqadar_t( 'On this page', 'Kandungan halaman', $id ? lq_role_of( $id ) : null ) );
	return str_replace(
		array( 'aria-label="' . $english . '"', 'itemprop="name" content="' . $english . '"' ),
		array( 'aria-label="' . $name . '"', 'itemprop="name" content="' . $name . '"' ),
		(string) $html
	);
}
add_filter( 'wp_toc_html', 'lailatulqadar_toc_name' );
