<?php
/**
 * The few WordPress functions the schema code calls, backed by a fake
 * site built from the shipped articles. For tests/schema-graph-test.php
 * only; never loaded by the theme.
 *
 * @package Lailatulqadar
 */

$GLOBALS['lq_stub'] = array(
	'posts' => array(),
	'meta'  => array(),
	'opts'  => array(),
	'view'  => array(),
);

/**
 * Build the site: every guide page in both languages, with the shipped
 * article as its content and footnotes.
 *
 * @param string $root Theme folder.
 */
function lq_stub_site( $root ) {
	$defs = json_decode( (string) file_get_contents( $root . '/inc/data/pages.json' ), true );
	$map  = array();
	$i    = 0;
	foreach ( array( 'primary' => 'en', 'secondary' => 'ms' ) as $role => $lang ) {
		foreach ( $defs as $key => $def ) {
			$id   = ( 'primary' === $role ? 100 : 200 ) + ( $i++ % 100 );
			$file = $root . '/content/' . $lang . '/' . $key . '.json';
			$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array();
			$slug = 'home' === $key ? '' : $def[ $role ]['slug'] . '/';
			$url  = 'https://lailatulqadar.test/' . ( 'secondary' === $role ? 'ms/' : '' ) . $slug;
			$GLOBALS['lq_stub']['posts'][ $id ] = (object) array(
				'ID'            => $id,
				'post_type'     => 'page',
				'post_status'   => 'publish',
				'post_title'    => $def[ $role ]['title'],
				'post_content'  => isset( $data['content'] ) ? $data['content'] : '',
				'post_author'   => 1,
				'post_date_gmt' => '2026-10-26 10:00:00',
				'post_modified_gmt' => '2026-11-02 10:00:00',
				'url'           => $url,
			);
			$GLOBALS['lq_stub']['meta'][ $id ] = array(
				'footnotes'            => isset( $data['footnotes'] ) ? json_encode( $data['footnotes'] ) : '',
				'_lq_meta_description' => $def[ $role ]['meta'],
				'_lq_lang'             => $lang,
			);
			$map[ $role ][ $key ] = $id;
		}
		$i = 0;
	}
	$GLOBALS['lq_stub']['opts']['lailatulqadar_pages'] = $map;
}

/**
 * Point the request at one page.
 *
 * @param string $key  Page key.
 * @param string $role primary or secondary.
 */
function lq_stub_view( $key, $role ) {
	$id                         = $GLOBALS['lq_stub']['opts']['lailatulqadar_pages'][ $role ][ $key ];
	$GLOBALS['lq_stub']['view'] = array(
		'id'    => $id,
		'url'   => $GLOBALS['lq_stub']['posts'][ $id ]->url,
		'front' => 'home' === $key && 'primary' === $role,
	);
}

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function apply_filters( $hook, $value ) {
	return $value;
}
function did_action() {
	return 1;
}
function is_singular( $types = '' ) {
	return '' === $types || in_array( 'page', (array) $types, true );
}
function is_page() {
	return true;
}
function is_front_page() {
	return $GLOBALS['lq_stub']['view']['front'];
}
function is_404() {
	return false;
}
function is_search() {
	return false;
}
function is_home() {
	return false;
}
function is_archive() {
	return false;
}
function is_admin() {
	return false;
}
function get_queried_object_id() {
	return $GLOBALS['lq_stub']['view']['id'];
}
function get_the_ID() {
	return $GLOBALS['lq_stub']['view']['id'];
}
function get_post( $id ) {
	return isset( $GLOBALS['lq_stub']['posts'][ (int) $id ] ) ? $GLOBALS['lq_stub']['posts'][ (int) $id ] : null;
}
function get_permalink( $id ) {
	$p = get_post( $id );
	return $p ? $p->url : '';
}
function get_the_title( $id ) {
	$p = get_post( $id );
	return $p ? $p->post_title : '';
}
function get_post_field( $field, $id ) {
	$p = get_post( $id );
	return $p ? $p->$field : '';
}
function get_post_status( $id ) {
	return get_post( $id ) ? 'publish' : false;
}
function get_post_meta( $id, $key = '', $single = false ) {
	return isset( $GLOBALS['lq_stub']['meta'][ $id ][ $key ] ) ? $GLOBALS['lq_stub']['meta'][ $id ][ $key ] : '';
}
function get_post_ancestors() {
	return array();
}
function get_page_template_slug() {
	return '';
}
function get_post_time( $format, $gmt, $post ) {
	return gmdate( 'c', strtotime( $post->post_date_gmt . ' UTC' ) );
}
function get_post_modified_time( $format, $gmt, $post ) {
	return gmdate( 'c', strtotime( $post->post_modified_gmt . ' UTC' ) );
}
function get_the_author_meta() {
	return 'Editor';
}
function has_post_thumbnail() {
	return false;
}
function get_site_icon_url() {
	return '';
}
function get_option( $name, $default = false ) {
	return isset( $GLOBALS['lq_stub']['opts'][ $name ] ) ? $GLOBALS['lq_stub']['opts'][ $name ] : $default;
}
function get_bloginfo() {
	return 'Lailatulqadar';
}
function home_url( $path = '' ) {
	return 'https://lailatulqadar.test' . ( '' === $path ? '' : '/' . ltrim( $path, '/' ) );
}
function trailingslashit( $s ) {
	return rtrim( $s, '/' ) . '/';
}
function wp_parse_args( $args, $defaults ) {
	return array_merge( $defaults, (array) $args );
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function wp_unslash( $v ) {
	return $v;
}
function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) );
}
function do_shortcode( $s ) {
	return (string) $s;
}
function wp_date( $format ) {
	return gmdate( $format, strtotime( '2026-11-03' ) );
}
function current_time( $format ) {
	return gmdate( $format, strtotime( '2026-11-03' ) );
}
function sanitize_key( $s ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) );
}
function sanitize_title( $s ) {
	$s = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $s );
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( $s ) ), '-' );
}
function wp_list_pluck( $list, $field ) {
	$out = array();
	foreach ( $list as $k => $item ) {
		$out[ $k ] = is_object( $item ) ? $item->$field : $item[ $field ];
	}
	return $out;
}
function is_wp_error() {
	return false;
}
function __( $s ) {
	return $s;
}
function get_the_category() {
	return array();
}
function get_the_tags() {
	return array();
}
function get_search_link() {
	return home_url( '/?s=' );
}
function absint( $n ) {
	return abs( (int) $n );
}
define( 'DAY_IN_SECONDS', 86400 );
