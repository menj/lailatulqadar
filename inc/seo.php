<?php
/**
 * Search appearance: title tag and meta description per page.
 *
 * Values live in post meta, are prefilled by setup from the page registry,
 * and are editable in the "Search appearance" box on each page. When a
 * dedicated SEO plugin is active the theme stops printing its own tags,
 * so the plugin stays in charge and nothing is duplicated.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

const LQ_META_TITLE       = '_lq_seo_title';
const LQ_META_DESCRIPTION = '_lq_meta_description';
const LQ_TITLE_LIMIT      = 60;
const LQ_META_LIMIT       = 130;

/**
 * Name of an active SEO plugin, or an empty string.
 *
 * @return string
 */
function lq_seo_plugin() {
	$plugins = array(
		'WPSEO_VERSION'      => 'Yoast SEO',
		'RANK_MATH_VERSION'  => 'Rank Math',
		'SEOPRESS_VERSION'   => 'SEOPress',
		'AIOSEO_VERSION'     => 'All in One SEO',
		'THE_SEO_FRAMEWORK_VERSION' => 'The SEO Framework',
	);
	foreach ( $plugins as $constant => $name ) {
		if ( defined( $constant ) ) {
			return $name;
		}
	}
	return '';
}

/**
 * Register both fields so they are protected and available over REST.
 */
function lq_register_seo_meta() {
	foreach ( array( LQ_META_TITLE, LQ_META_DESCRIPTION ) as $key ) {
		register_post_meta(
			'page',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_pages' );
				},
			)
		);
	}
}
add_action( 'init', 'lq_register_seo_meta' );

/**
 * Title tag override for pages that have one.
 *
 * @param string $title Title WordPress would print.
 * @return string
 */
function lq_document_title( $title ) {
	if ( lq_seo_plugin() || ! is_page() ) {
		return $title;
	}
	$custom = (string) get_post_meta( get_queried_object_id(), LQ_META_TITLE, true );
	return '' !== $custom ? wp_strip_all_tags( do_shortcode( $custom ) ) : $title;
}
add_filter( 'pre_get_document_title', 'lq_document_title' );

/**
 * Print the meta description on pages.
 */
function lq_meta_description() {
	if ( lq_seo_plugin() || ! is_page() ) {
		return;
	}
	$description = wp_strip_all_tags( do_shortcode( (string) get_post_meta( get_queried_object_id(), LQ_META_DESCRIPTION, true ) ) );
	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}
add_action( 'wp_head', 'lq_meta_description', 1 );

/**
 * "Search appearance" box on the page editor.
 */
function lq_add_seo_box() {
	if ( lq_seo_plugin() ) {
		return;
	}
	add_meta_box( 'lq-seo', __( 'Search appearance', 'lailatulqadar' ), 'lq_render_seo_box', 'page', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'lq_add_seo_box' );

/**
 * Meta box assets.
 *
 * @param string $hook Current admin page.
 */
function lq_seo_box_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'page' !== get_post_type() || lq_seo_plugin() ) {
		return;
	}
	wp_enqueue_style( 'lailatulqadar-meta-box', LQ_URI . '/css/meta-box.css', array(), LQ_VERSION );
	wp_enqueue_script( 'lailatulqadar-meta-box', LQ_URI . '/js/meta-box.js', array(), LQ_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'lq_seo_box_assets' );

/**
 * Render the box.
 *
 * @param WP_Post $post Page being edited.
 */
function lq_render_seo_box( $post ) {
	wp_nonce_field( 'lq_seo_save', 'lq_seo_nonce' );
	$fields = array(
		LQ_META_TITLE       => array( __( 'Title tag', 'lailatulqadar' ), LQ_TITLE_LIMIT, 'input' ),
		LQ_META_DESCRIPTION => array( __( 'Meta description', 'lailatulqadar' ), LQ_META_LIMIT, 'textarea' ),
	);
	echo '<div class="lq-seo">';
	foreach ( $fields as $key => $field ) {
		$value = (string) get_post_meta( $post->ID, $key, true );
		$id    = 'lq-seo' . str_replace( '_', '-', $key );
		echo '<div class="lq-seo__field">';
		printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $field[0] ) );
		if ( 'input' === $field[2] ) {
			printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" data-lq-limit="%4$d">', esc_attr( $id ), esc_attr( $key ), esc_attr( $value ), (int) $field[1] );
		} else {
			printf( '<textarea id="%1$s" name="%2$s" rows="3" data-lq-limit="%4$d">%3$s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( $value ), (int) $field[1] );
		}
		printf(
			'<p class="lq-seo__count" data-lq-count-for="%1$s">%2$s</p>',
			esc_attr( $id ),
			esc_html( sprintf( /* translators: 1: characters used, 2: limit */ __( '%1$d of %2$d characters', 'lailatulqadar' ), mb_strlen( $value ), (int) $field[1] ) )
		);
		echo '</div>';
	}
	echo '<p class="lq-seo__help">' . esc_html__( 'Leave the title tag empty to use the page title. The meta description should end with a call to action.', 'lailatulqadar' ) . '</p>';
	echo '</div>';
}

/**
 * Save the box.
 *
 * @param int $post_id Page ID.
 */
function lq_save_seo_box( $post_id ) {
	if ( ! isset( $_POST['lq_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lq_seo_nonce'] ) ), 'lq_seo_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}
	foreach ( array( LQ_META_TITLE, LQ_META_DESCRIPTION ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
}
add_action( 'save_post_page', 'lq_save_seo_box' );

/**
 * Expand [lq_ramadan] in titles and descriptions managed by SEO plugins, so
 * dated titles roll over to the next Ramadan by themselves.
 *
 * @param string $text Title or description.
 * @return string
 */
function lq_expand_seo_shortcodes( $text ) {
	return is_string( $text ) && false !== strpos( $text, '[lq_ramadan' ) ? wp_strip_all_tags( do_shortcode( $text ) ) : $text;
}
foreach ( array( 'rank_math/frontend/title', 'rank_math/frontend/description', 'wpseo_title', 'wpseo_metadesc', 'wpseo_opengraph_title', 'wpseo_opengraph_desc' ) as $lq_hook ) {
	add_filter( $lq_hook, 'lq_expand_seo_shortcodes', 20 );
}
unset( $lq_hook );

/**
 * Rank Math variables for the current or next Ramadan, so dated titles and
 * descriptions stay inside Rank Math's own template system:
 * %lq_ramadan_year% (Gregorian year, e.g. 2027) and
 * %lq_ramadan_hijri% (Hijri year, e.g. 1448).
 */
function lq_register_rank_math_vars() {
	if ( ! function_exists( 'rank_math_register_var_replacement' ) ) {
		return;
	}
	rank_math_register_var_replacement(
		'lq_ramadan_year',
		array(
			'name'        => __( 'Ramadan year (Gregorian)', 'lailatulqadar' ),
			'description' => __( 'Gregorian year of the current or next Ramadan, from the Lailatulqadar theme.', 'lailatulqadar' ),
			'variable'    => 'lq_ramadan_year',
			'example'     => lq_ramadan() ? gmdate( 'Y', strtotime( lq_ramadan()['start'] ) ) : '',
		),
		function () {
			$r = lq_ramadan();
			return $r ? gmdate( 'Y', strtotime( $r['start'] ) ) : '';
		}
	);
	rank_math_register_var_replacement(
		'lq_ramadan_hijri',
		array(
			'name'        => __( 'Ramadan year (Hijri)', 'lailatulqadar' ),
			'description' => __( 'Hijri year of the current or next Ramadan, from the Lailatulqadar theme.', 'lailatulqadar' ),
			'variable'    => 'lq_ramadan_hijri',
			'example'     => lq_ramadan() ? (string) lq_ramadan()['hijri'] : '',
		),
		function () {
			$r = lq_ramadan();
			return $r ? (string) $r['hijri'] : '';
		}
	);
}
add_action( 'rank_math/vars/register_extra_replacements', 'lq_register_rank_math_vars' );

/**
 * Guide pages have no hand-written excerpt; in search results and lists,
 * use the page's meta description, which is written as a summary.
 *
 * @param string  $excerpt Excerpt.
 * @param WP_Post $post    Post.
 * @return string
 */
function lq_page_excerpt( $excerpt, $post ) {
	if ( $post instanceof WP_Post && 'page' === $post->post_type && '' === trim( (string) $post->post_excerpt ) ) {
		$desc = wp_strip_all_tags( do_shortcode( (string) get_post_meta( $post->ID, LQ_META_DESCRIPTION, true ) ) );
		if ( '' !== $desc ) {
			return $desc;
		}
	}
	return $excerpt;
}
add_filter( 'get_the_excerpt', 'lq_page_excerpt', 20, 2 );
