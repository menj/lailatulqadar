<?php
/**
 * Two languages without a plugin.
 *
 * English pages live at the root of the site; the Malay home is the page
 * at /ms/ and every Malay page sits beneath it, so WordPress's own page
 * hierarchy gives the Malay addresses. The theme pairs each page with its
 * translation (post meta _lq_translation, and the page map), marks the page
 * language (_lq_lang), and here prints the language signals: the lang
 * attribute on <html>, hreflang links for search engines, the Malay site
 * name, and a language hint on search forms.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * hreflang code for a role, from the Languages tab.
 *
 * @param string $role primary or secondary.
 * @return string
 */
function lq_hreflang( $role ) {
	return 'secondary' === $role ? 'ms-MY' : 'en';
}

/**
 * <html lang>: ms-MY on Malay pages.
 *
 * @param string $output Attributes.
 * @return string
 */
function lq_language_attributes( $output ) {
	if ( is_admin() || 'secondary' !== lq_current_role() ) {
		return $output;
	}
	return preg_match( '/lang="[^"]*"/', $output ) ? preg_replace( '/lang="[^"]*"/', 'lang="ms-MY"', $output ) : $output . ' lang="ms-MY"';
}
add_filter( 'language_attributes', 'lq_language_attributes' );

/**
 * hreflang alternates for pages that exist in both languages, and for the
 * two home pages; x-default points to the English version.
 */
function lq_print_hreflang() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$id    = get_queried_object_id();
	$other = lq_translation_of( $id );
	if ( ! $other || 'publish' !== get_post_status( $other ) ) {
		return;
	}
	$role  = lq_role_of( $id );
	$links = array(
		$role                                              => get_permalink( $id ),
		( 'secondary' === $role ? 'primary' : 'secondary' ) => get_permalink( $other ),
	);
	if ( lq_is_home() && isset( $links['primary'] ) ) {
		$links['primary'] = home_url( '/' );
	}
	foreach ( array( 'primary', 'secondary' ) as $r ) {
		printf( '<link rel="alternate" hreflang="%1$s" href="%2$s">' . "\n", esc_attr( lq_hreflang( $r ) ), esc_url( $links[ $r ] ) );
	}
	printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( $links['primary'] ) );
}
add_action( 'wp_head', 'lq_print_hreflang', 2 );

/**
 * Site name on Malay pages: "Lailatulqadar" (Languages tab).
 *
 * @param mixed $name Site name.
 * @return mixed
 */
function lq_blogname( $name ) {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! did_action( 'wp' ) ) {
		return $name;
	}
	if ( 'secondary' === lq_current_role() && '' !== lq_setting( 'site_name_secondary' ) ) {
		return lq_setting( 'site_name_secondary' );
	}
	return $name;
}
add_filter( 'option_blogname', 'lq_blogname' );

/**
 * Search forms on Malay pages keep the visitor in Malay: ?lang=ms.
 *
 * @param string $html Block HTML.
 * @return string
 */
function lq_search_language( $html ) {
	if ( 'secondary' !== lq_current_role() || false === strpos( $html, '<form' ) ) {
		return $html;
	}
	return preg_replace( '/(<form[^>]*>)/', '$1<input type="hidden" name="lang" value="' . esc_attr( lq_setting( 'secondary_slug' ) ) . '">', $html, 1 );
}
add_filter( 'render_block_core/search', 'lq_search_language' );

/**
 * Body class for the current language.
 *
 * @param array $classes Classes.
 * @return array
 */
function lq_language_body_class( $classes ) {
	$classes[] = 'secondary' === lq_current_role() ? 'lq-lang-ms' : 'lq-lang-en';
	return $classes;
}
add_filter( 'body_class', 'lq_language_body_class' );

/**
 * Notice when Polylang is still active: the theme now handles both
 * languages, and Polylang's own language filtering would hide pages.
 */
function lq_polylang_notice() {
	if ( ! current_user_can( 'activate_plugins' ) || ! function_exists( 'pll_current_language' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'Lailatulqadar now handles English and Malay itself. Deactivate Polylang, then press Run setup under Appearance > Lailatulqadar > Tools.', 'lailatulqadar' ) . '</p></div>';
}
add_action( 'admin_notices', 'lq_polylang_notice' );

/**
 * Malay wording for the search screen's WordPress-supplied text.
 *
 * @param string $html Block HTML.
 * @return string
 */
function lq_search_words_ms( $html ) {
	if ( 'secondary' !== lq_current_role() ) {
		return $html;
	}
	return str_replace(
		array( 'Search results for:', 'Search Results for:', 'aria-label="Search"', '>Search<', 'placeholder="Search"' ),
		array( 'Hasil carian untuk:', 'Hasil carian untuk:', 'aria-label="Cari"', '>Cari<', 'placeholder="Cari"' ),
		$html
	);
}
add_filter( 'render_block_core/query-title', 'lq_search_words_ms' );
add_filter( 'render_block_core/search', 'lq_search_words_ms', 20 );

/**
 * Malay browser title on search results.
 *
 * @param array $parts Title parts.
 * @return array
 */
function lq_search_title_ms( $parts ) {
	if ( is_search() && 'secondary' === lq_current_role() ) {
		/* translators: %s: search terms. Malay wording used on Malay pages. */
		$parts['title'] = sprintf( 'Hasil carian untuk “%s”', get_search_query() );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'lq_search_title_ms' );
