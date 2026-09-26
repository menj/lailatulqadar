<?php
/**
 * Structured data (JSON-LD) and breadcrumbs.
 *
 * Emits only types Google Search lists as supported (Article, Breadcrumb,
 * Organization) plus the WebSite and WebPage nodes that tie them together.
 * FAQPage and HowTo are deliberately absent: Google no longer lists them
 * among supported rich results. When an SEO plugin is active it owns the
 * structured data, and the theme prints none of its own.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registry key of a page ID in either language.
 *
 * @param int $id Page ID.
 * @return string Key, or empty string.
 */
function lq_page_key_for_id( $id ) {
	foreach ( lq_page_map() as $ids ) {
		$key = array_search( (int) $id, array_map( 'intval', (array) $ids ), true );
		if ( false !== $key ) {
			return (string) $key;
		}
	}
	return '';
}

/**
 * Front page URL in the current language.
 *
 * @return string
 */
function lq_home_url() {
	$map = lq_page_map();
	if ( 'secondary' === lq_current_role() && ! empty( $map['secondary']['home'] ) && get_post( (int) $map['secondary']['home'] ) ) {
		return get_permalink( (int) $map['secondary']['home'] );
	}
	return home_url( '/' );
}

/**
 * Breadcrumb trail for the current page: [ [ name, url ], ... ].
 *
 * @return array
 */
function lq_breadcrumb_trail() {
	if ( lq_is_home() || is_404() ) {
		return array();
	}
	$role  = lq_current_role();
	$map   = lq_page_map();
	$home  = isset( $map[ $role ]['home'] ) ? get_the_title( (int) $map[ $role ]['home'] ) : '';
	$trail = array( array( $home ? $home : get_bloginfo( 'name' ), lq_home_url() ) );

	if ( is_singular() ) {
		$id = get_queried_object_id();
		if ( is_singular( 'post' ) ) {
			$cats = get_the_category( $id );
			if ( $cats ) {
				$trail[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
			}
		}
		$trail[] = array( get_the_title( $id ), get_permalink( $id ) );
	} elseif ( is_search() ) {
		$trail[] = array( 'secondary' === $role ? 'Hasil carian' : 'Search results', get_search_link() );
	} elseif ( is_home() ) {
		$blog    = (int) get_option( 'page_for_posts' );
		$trail[] = array( $blog ? get_the_title( $blog ) : ( 'secondary' === $role ? 'Catatan' : 'Posts' ), $blog ? get_permalink( $blog ) : home_url( '/' ) );
	} elseif ( is_archive() ) {
		$is_term = is_category() || is_tag() || is_tax();
		$trail[] = array( $is_term ? single_term_title( '', false ) : wp_strip_all_tags( get_the_archive_title() ), $is_term ? get_term_link( get_queried_object() ) : '' );
	} else {
		return array();
	}
	return $trail;
}

/**
 * Article pages: the guide pages that carry written content.
 *
 * @return string[]
 */
function lq_article_keys() {
	return array( 'start', 'what', 'surah', 'when', 'last10', 'signs', 'worship', 'dua', 'faq', 'sources' );
}

/**
 * Build the JSON-LD graph for the current request.
 *
 * @return array
 */
function lq_schema_graph() {
	$role    = lq_current_role();
	$lang    = 'secondary' === $role ? 'ms-MY' : 'en';
	$home    = lq_home_url();
	$site_id = trailingslashit( home_url( '/' ) ) . '#website';
	$org_id  = trailingslashit( home_url( '/' ) ) . '#organization';
	$name    = get_bloginfo( 'name' );

	$org = array(
		'@type' => 'Organization',
		'@id'   => $org_id,
		'name'  => $name,
		'url'   => home_url( '/' ),
	);
	$icon = get_site_icon_url( 512 );
	$icon = $icon ? $icon : LQ_URI . '/images/lailatulqadar-mark-512.png';
	if ( $icon ) {
		$org['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $icon,
		);
	}

	$graph = array(
		$org,
		array(
			'@type'      => 'WebSite',
			'@id'        => $site_id,
			'url'        => $home,
			'name'       => $name,
			'inLanguage' => $lang,
			'publisher'  => array( '@id' => $org_id ),
		),
	);

	if ( ! is_singular( array( 'page', 'post' ) ) ) {
		return $graph;
	}

	$id        = get_queried_object_id();
	$url       = get_permalink( $id );
	$page_id   = $url . '#webpage';
	$key       = lq_page_key_for_id( $id );
	$desc      = wp_strip_all_tags( do_shortcode( (string) get_post_meta( $id, LQ_META_DESCRIPTION, true ) ) );
	$webpage   = array(
		'@type'      => 'WebPage',
		'@id'        => $page_id,
		'url'        => $url,
		'name'       => wp_strip_all_tags( get_the_title( $id ) ),
		'isPartOf'   => array( '@id' => $site_id ),
		'inLanguage' => $lang,
	);
	if ( $desc ) {
		$webpage['description'] = $desc;
	}

	$trail = lq_breadcrumb_trail();
	if ( $trail ) {
		$items = array();
		foreach ( $trail as $i => $crumb ) {
			$items[] = array_filter(
				array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => wp_strip_all_tags( $crumb[0] ),
					'item'     => $crumb[1],
				)
			);
		}
		$graph[]               = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $url . '#breadcrumb',
			'itemListElement' => $items,
		);
		$webpage['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}
	$graph[] = $webpage;

	// About page: ProfilePage about the author when one is named, otherwise
	// about the publishing organisation.
	if ( 'about' === $key ) {
		$post          = get_post( $id );
		$subject       = lq_setting( 'author_name' )
			? array(
				'@type' => 'Person',
				'@id'   => $url . '#author',
				'name'  => lq_setting( 'author_name' ),
			)
			: array( '@id' => $org_id );
		if ( lq_setting( 'author_name' ) && lq_setting( 'author_url' ) ) {
			$subject['sameAs'] = array( lq_setting( 'author_url' ) );
		}
		foreach ( $graph as $i => $node ) {
			if ( 'WebPage' === $node['@type'] ) {
				$graph[ $i ]['@type']        = array( 'WebPage', 'ProfilePage' );
				$graph[ $i ]['mainEntity']   = $subject;
				$graph[ $i ]['dateCreated']  = get_post_time( 'c', true, $post );
				$graph[ $i ]['dateModified'] = get_post_modified_time( 'c', true, $post );
			}
		}
	}

	if ( in_array( $key, lq_article_keys(), true ) || is_singular( 'post' ) ) {
		$post    = get_post( $id );
		$author  = lq_setting( 'author_name' ) ? lq_setting( 'author_name' ) : get_the_author_meta( 'display_name', $post->post_author );
		$map     = lq_page_map();
		$about   = isset( $map[ $role ]['about'] ) ? get_permalink( (int) $map[ $role ]['about'] ) : home_url( '/' );
		$article = array(
			'@type'            => 'Article',
			'@id'              => $url . '#article',
			'headline'         => mb_substr( wp_strip_all_tags( get_the_title( $id ) ), 0, 110 ),
			'datePublished'    => get_post_time( 'c', true, $post ),
			'dateModified'     => get_post_modified_time( 'c', true, $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author,
				'url'   => lq_setting( 'author_url' ) ? lq_setting( 'author_url' ) : $about,
			),
			'publisher'        => array( '@id' => $org_id ),
			'mainEntityOfPage' => array( '@id' => $page_id ),
			'isPartOf'         => array( '@id' => $site_id ),
			'inLanguage'       => $lang,
		);
		if ( $desc ) {
			$article['description'] = $desc;
		}
		if ( has_post_thumbnail( $id ) ) {
			$article['image'] = array( get_the_post_thumbnail_url( $id, 'full' ) );
		}
		$graph[] = $article;
	}

	return apply_filters( 'lq_schema_graph', $graph );
}

/**
 * Print the graph.
 */
function lq_print_schema() {
	if ( lq_seo_plugin() || is_404() ) {
		return;
	}
	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => lq_schema_graph(),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'lq_print_schema', 20 );

/**
 * Keep the XML sitemap to real content: drop the user (author archive) and
 * taxonomy sitemaps, which on this site would list empty or thin archives.
 *
 * @param WP_Sitemaps_Provider|false $provider Provider.
 * @param string                     $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function lq_sitemap_providers( $provider, $name ) {
	if ( lq_seo_plugin() ) {
		return $provider;
	}
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'lq_sitemap_providers', 10, 2 );

/**
 * Webmaster tools verification tags, on the front page only.
 */
function lq_print_verification() {
	if ( ! is_front_page() || lq_seo_plugin() ) {
		return;
	}
	if ( lq_setting( 'verify_google' ) ) {
		printf( '<meta name="google-site-verification" content="%s">' . "\n", esc_attr( lq_setting( 'verify_google' ) ) );
	}
	if ( lq_setting( 'verify_bing' ) ) {
		printf( '<meta name="msvalidate.01" content="%s">' . "\n", esc_attr( lq_setting( 'verify_bing' ) ) );
	}
}
add_action( 'wp_head', 'lq_print_verification', 1 );
