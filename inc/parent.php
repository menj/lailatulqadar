<?php
/**
 * Living with the parent theme, Twenty Twenty-Five.
 *
 * - Loads the parent stylesheet with the parent's own version number (the
 *   parent function uses the active theme's version, which in a child theme
 *   is the child's).
 * - Keeps the parent's full style variations and colour and typography
 *   presets out of the Site Editor: they replace this theme's palette and
 *   fonts, and the typography presets point at font files the child does
 *   not load. The parent's block and section styles (Display, Subtitle,
 *   Annotation, Section 1 to 5) stay available, since they use presets the
 *   child defines. The filter lq_allow_parent_variations restores them.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parent stylesheet, versioned by the parent theme. Replaces the pluggable
 * twentytwentyfive_enqueue_styles(), which the parent only defines when no
 * function of that name exists; the child's functions.php loads first.
 */
function twentytwentyfive_enqueue_styles() {
	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$src    = 'style' . $suffix . '.css';
	wp_enqueue_style(
		'twentytwentyfive-style',
		get_parent_theme_file_uri( $src ),
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);
	wp_style_add_data( 'twentytwentyfive-style', 'path', get_parent_theme_file_path( $src ) );
}

/**
 * Style variations offered in the Site Editor: only the child's own (none
 * at present), unless the filter allows the parent's.
 *
 * @param WP_HTTP_Response $response Response.
 * @param WP_REST_Server   $server   Server.
 * @param WP_REST_Request  $request  Request.
 * @return WP_HTTP_Response
 */
function lq_hide_parent_variations( $response, $server, $request ) {
	if ( ! preg_match( '#^/wp/v2/global-styles/themes/([^/]+)/variations$#', $request->get_route(), $m ) ) {
		return $response;
	}
	if ( urldecode( $m[1] ) !== get_stylesheet() || apply_filters( 'lq_allow_parent_variations', false ) ) {
		return $response;
	}
	$child = array();
	$dir   = get_stylesheet_directory() . '/styles';
	if ( is_dir( $dir ) ) {
		$titles = array();
		foreach ( glob( $dir . '/*.json' ) as $file ) {
			$data = wp_json_file_decode( $file, array( 'associative' => true ) );
			if ( is_array( $data ) ) {
				$titles[] = isset( $data['title'] ) ? $data['title'] : basename( $file, '.json' );
			}
		}
		foreach ( (array) $response->get_data() as $variation ) {
			if ( isset( $variation['title'] ) && in_array( $variation['title'], $titles, true ) ) {
				$child[] = $variation;
			}
		}
	}
	$response->set_data( $child );
	return $response;
}
add_filter( 'rest_post_dispatch', 'lq_hide_parent_variations', 10, 3 );
