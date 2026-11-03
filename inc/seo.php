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
const LQ_TITLE_LIMIT      = 59; // Whole title tag, site name included: under 60 characters.
const LQ_TITLE_SITE       = 'Lailatulqadar';
const LQ_TITLE_SEP        = ' | ';
const LQ_META_LIMIT       = 130;

/**
 * Most characters the page part of a title tag may take, leaving room for
 * " | Lailatulqadar" inside LQ_TITLE_LIMIT.
 *
 * @return int
 */
function lq_title_part_limit() {
	return LQ_TITLE_LIMIT - mb_strlen( LQ_TITLE_SEP . LQ_TITLE_SITE );
}

/**
 * The page part of a title tag: markup removed, any " | Lailatulqadar"
 * already typed at the end dropped, and, when $shorten is set, cut at a word
 * boundary with an ellipsis to fit lq_title_part_limit(). Shortcodes are
 * expanded only when $expand is set, for title tags editors store (where
 * [lq_ramadan] gives the year); never for post titles or search terms, which
 * would let anyone who can write a title, or any visitor who searches, run a
 * shortcode.
 *
 * @param string $text    Page title, keyword or description.
 * @param bool   $shorten Whether to cut it to fit.
 * @param bool   $expand  Whether to expand shortcodes.
 * @return string
 */
function lq_title_part( $text, $shorten = true, $expand = true ) {
	$text   = (string) $text;
	$text   = trim( wp_strip_all_tags( $expand ? do_shortcode( $text ) : $text ) );
	$suffix = trim( LQ_TITLE_SEP ) . ' ' . LQ_TITLE_SITE;
	if ( mb_strlen( $text ) > mb_strlen( $suffix ) && 0 === strcasecmp( mb_substr( $text, -mb_strlen( $suffix ) ), $suffix ) ) {
		$text = trim( mb_substr( $text, 0, -mb_strlen( $suffix ) ) );
	}
	$max = lq_title_part_limit();
	if ( ! $shorten || mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut   = mb_substr( $text, 0, $max - 1 );
	$space = mb_strrpos( $cut, ' ' );
	if ( false !== $space && $space > $max / 2 ) {
		$cut = mb_substr( $cut, 0, $space );
	}
	return rtrim( $cut, " ,;:–-" ) . '…';
}

/**
 * A whole title tag: "<page title, keyword or description> | Lailatulqadar",
 * under 60 characters, or the site name alone when there is no page part.
 *
 * @param string $text    Page part.
 * @param bool   $shorten Whether to cut the page part to fit.
 * @return string
 */
function lq_compose_title( $text, $shorten = true ) {
	$part = lq_title_part( $text, $shorten );
	return '' === $part ? LQ_TITLE_SITE : $part . LQ_TITLE_SEP . LQ_TITLE_SITE;
}

/**
 * Name of an active SEO plugin, or an empty string.
 *
 * @return string
 */
function lq_seo_plugin() {
	$plugins = array(
		'WPSEO_VERSION'             => 'Yoast SEO',
		'RANK_MATH_VERSION'         => 'Rank Math',
		'SEOPRESS_VERSION'          => 'SEOPress',
		'AIOSEO_VERSION'            => 'All in One SEO',
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
 * Title tag for pages that have one: "<title tag> | Lailatulqadar". Other
 * views take the same form through lq_document_title_parts().
 *
 * @param string $title Title WordPress would print.
 * @return string
 */
function lq_document_title( $title ) {
	if ( lq_seo_plugin() || ! is_page() ) {
		return $title;
	}
	$custom = (string) get_post_meta( get_queried_object_id(), LQ_META_TITLE, true );
	return '' !== $custom ? esc_html( lq_compose_title( $custom ) ) : $title;
}
add_filter( 'pre_get_document_title', 'lq_document_title' );

/**
 * Every other title tag (posts, pages without a title tag of their own,
 * search, archives, not found) in the same form: the page part, cut to fit
 * when it is long, then " | Lailatulqadar". Runs after the Malay search
 * wording (lq_search_title_ms).
 *
 * @param array $parts Title parts: title, page, tagline, site.
 * @return array
 */
function lq_document_title_parts( $parts ) {
	if ( lq_seo_plugin() ) {
		return $parts;
	}
	$title = isset( $parts['title'] ) ? (string) $parts['title'] : '';
	if ( is_front_page() || $title === get_bloginfo( 'name' ) ) {
		$title = '';
	}
	if ( ! empty( $parts['page'] ) && '' !== $title ) {
		$title .= ' (' . $parts['page'] . ')';
	}
	$part = lq_title_part( $title, true, false );
	return '' === $part ? array( 'title' => LQ_TITLE_SITE ) : array(
		'title' => $part,
		'site'  => LQ_TITLE_SITE,
	);
}
add_filter( 'document_title_parts', 'lq_document_title_parts', 99 );

/**
 * The separator WordPress puts between title parts: a vertical bar.
 *
 * @param string $sep Separator.
 * @return string
 */
function lq_document_title_separator( $sep ) {
	return lq_seo_plugin() ? $sep : trim( LQ_TITLE_SEP );
}
add_filter( 'document_title_separator', 'lq_document_title_separator' );

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
		LQ_META_TITLE       => array( __( 'Title tag (the theme adds “ | Lailatulqadar”)', 'lailatulqadar' ), lq_title_part_limit(), 'input' ),
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
			esc_html( sprintf( /* translators: 1: characters used, 2: limit */ __( '%1$d of %2$d characters', 'lailatulqadar' ), mb_strlen( lq_title_part( $value, false ) ), (int) $field[1] ) )
		);
		echo '</div>';
	}
	echo '<p class="lq-seo__help">' . esc_html__( 'Leave the title tag empty to use the page title. Every title tag reads “title | Lailatulqadar” and stays under 60 characters; a longer one is cut at a word. The meta description should end with a call to action.', 'lailatulqadar' ) . '</p>';
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

/**
 * Title tags under Rank Math: the same "<page part> | Lailatulqadar" form,
 * under 60 characters. Rank Math's own text is kept as the page part; any
 * site name it adds at the end, with whatever separator, is replaced by the
 * theme's, and a long page part is cut at a word. Runs after
 * lq_expand_seo_shortcodes(), so [lq_ramadan] is already a year.
 *
 * @param string $title Title from Rank Math.
 * @return string
 */
function lq_rank_math_title( $title ) {
	if ( ! is_string( $title ) ) {
		return $title;
	}
	$text  = trim( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$names = array_unique( array( LQ_TITLE_SITE, trim( html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) );
	$seps  = array( '-', '–', '—', '|', '·', ':', '»', '•', '~', '/' );
	$own   = lq_rank_math_separator();
	if ( '' !== $own ) {
		$seps[] = $own;
	}
	$seps = implode( '|', array_map( 'lq_preg_quote_slash', array_unique( $seps ) ) );
	foreach ( $names as $name ) {
		if ( '' === $name ) {
			continue;
		}
		if ( 0 === strcasecmp( $text, $name ) ) {
			return LQ_TITLE_SITE;
		}
		$text = trim( (string) preg_replace( '/\s*(?:' . $seps . ')\s*' . preg_quote( $name, '/' ) . '\s*$/iu', '', $text ) );
	}
	return lq_compose_title( $text, true );
}
add_filter( 'rank_math/frontend/title', 'lq_rank_math_title', 30 );

/**
 * The title separator set in Rank Math (Titles & Meta), decoded, or an empty
 * string when Rank Math has none.
 *
 * @return string
 */
function lq_rank_math_separator() {
	$sep = '';
	if ( class_exists( 'RankMath\\Helper' ) && method_exists( 'RankMath\\Helper', 'get_settings' ) ) {
		$sep = (string) \RankMath\Helper::get_settings( 'titles.title_separator' );
	} else {
		$opts = get_option( 'rank-math-options-titles' );
		$sep  = is_array( $opts ) && isset( $opts['title_separator'] ) ? (string) $opts['title_separator'] : '';
	}
	return trim( html_entity_decode( $sep, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

/**
 * preg_quote() with "/" as the delimiter, for array_map().
 *
 * @param string $text Text.
 * @return string
 */
function lq_preg_quote_slash( $text ) {
	return preg_quote( $text, '/' );
}

/**
 * Text cut to the meta description limit at a word boundary.
 *
 * @param string $text Text.
 * @return string
 */
function lq_meta_trim( $text ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	if ( mb_strlen( $text ) <= LQ_META_LIMIT ) {
		return $text;
	}
	$cut   = mb_substr( $text, 0, LQ_META_LIMIT - 1 );
	$space = mb_strrpos( $cut, ' ' );
	if ( false !== $space ) {
		$cut = mb_substr( $cut, 0, $space );
	}
	return rtrim( $cut, " ,;:–-" ) . '…';
}

/**
 * Meta description for views with no stored one: a post's excerpt, an
 * archive's term description, or the posts page's own description.
 *
 * @return string
 */
function lq_fallback_description() {
	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		$text = '' !== trim( (string) $post->post_excerpt ) ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 40, '' );
		return lq_meta_trim( $text );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return lq_meta_trim( term_description() );
	}
	if ( is_home() && (int) get_option( 'page_for_posts' ) ) {
		return lq_meta_trim( do_shortcode( (string) get_post_meta( (int) get_option( 'page_for_posts' ), LQ_META_DESCRIPTION, true ) ) );
	}
	return '';
}

/**
 * Print the fallback description on posts and archives.
 */
function lq_meta_description_fallback() {
	if ( lq_seo_plugin() || is_page() ) {
		return;
	}
	$description = lq_fallback_description();
	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}
}
add_action( 'wp_head', 'lq_meta_description_fallback', 1 );

/**
 * Canonical address of an archive view, page number included. WordPress
 * prints one for single posts and pages only.
 *
 * @return string Empty when the view has none.
 */
function lq_archive_canonical() {
	if ( is_category() || is_tag() || is_tax() ) {
		$base = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$base = get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	} elseif ( is_home() && ! is_front_page() ) {
		$blog = (int) get_option( 'page_for_posts' );
		$base = $blog ? get_permalink( $blog ) : home_url( '/' );
	} else {
		return '';
	}
	if ( ! $base || is_wp_error( $base ) ) {
		return '';
	}
	$paged = (int) get_query_var( 'paged' );
	if ( $paged > 1 ) {
		$base = get_option( 'permalink_structure' )
			? user_trailingslashit( trailingslashit( $base ) . 'page/' . $paged )
			: add_query_arg( 'paged', $paged, $base );
	}
	return $base;
}

/**
 * Print the archive canonical link.
 */
function lq_print_archive_canonical() {
	if ( lq_seo_plugin() || is_singular() ) {
		return;
	}
	$url = lq_archive_canonical();
	if ( '' !== $url ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}
}
add_action( 'wp_head', 'lq_print_archive_canonical', 2 );
