<?php
/**
 * Structured data (JSON-LD) and breadcrumbs.
 *
 * One graph per page. It holds the WebSite (with its search), the
 * publisher, the page frame (header, menus, footer), the WebPage and its
 * breadcrumb, and, on the guide pages, the Article with what the page
 * shows: photographs and diagrams with their credits, footnotes as
 * citations, tables, Qurʾān verses and hadith, questions and answers
 * (FAQPage), numbered steps (HowTo), the Ramadan dates and nights as Event
 * nodes, and the link to the same article in the other language.
 *
 * The owner keeps every schema.org type that describes the page, Google
 * rich result or not (decision of 3 November 2026; SSOT.md). A type goes
 * on a page only when what it describes is shown there.
 *
 * With Rank Math active, the theme merges its nodes into Rank Math's graph
 * through rank_math/json_ld (lq_rank_math_json_ld()) and prints nothing of
 * its own. Under any other SEO plugin it stands aside. Without one, it
 * prints the graph itself.
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
	return lq_page_key_of( $id );
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
		$link    = $is_term ? get_term_link( get_queried_object() ) : '';
		$trail[] = array( $is_term ? single_term_title( '', false ) : wp_strip_all_tags( get_the_archive_title() ), is_wp_error( $link ) ? '' : $link );
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
 * Image for an Article node: the featured image, else the lead photograph
 * of the page (inc/data/images.json) at its largest size, else the first
 * photograph placed with a lailatulqadar/photo block, else the site mark.
 *
 * @param int    $id  Post ID.
 * @param string $key Registry key, or an empty string.
 * @return string Address.
 */
function lq_article_image_url( $id, $key ) {
	if ( has_post_thumbnail( $id ) ) {
		$url = get_the_post_thumbnail_url( $id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	$data = '' !== $key ? lq_images_data() : array();
	if ( ! empty( $data[ $key ] ) ) {
		$lead = $data[ $key ][0];
		foreach ( $data[ $key ] as $image ) {
			if ( $image['lead'] ) {
				$lead = $image;
				break;
			}
		}
		return lq_image_file( $lead, max( $lead['widths'] ), LQ_URI );
	}
	$post   = get_post( $id );
	$blocks = ( $post && function_exists( 'lq_photo_block_images' ) ) ? lq_photo_block_images( $post ) : array();
	if ( $blocks ) {
		return lq_image_file( $blocks[0], max( $blocks[0]['widths'] ), LQ_URI );
	}
	return LQ_URI . '/images/lailatulqadar-mark-512.png';
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
	$icon        = get_site_icon_url( 512 );
	$org['logo'] = array(
		'@type' => 'ImageObject',
		'url'   => $icon ? $icon : LQ_URI . '/images/lailatulqadar-mark-512.png',
	);

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

	// The search form works in both languages (?lang= keeps the Malay view).
	$search = home_url( '/' ) . '?s={search_term_string}';
	if ( 'secondary' === $role ) {
		$search .= '&lang=' . rawurlencode( lq_setting( 'secondary_slug' ) );
	}
	// The author Person, named in the structured data only (owner rule,
	// 4 November 2026). Every author reference points to its @id.
	$graph[] = lq_schema_author_node();

	$graph[1]['potentialAction'] = array(
		'@type'       => 'SearchAction',
		'target'      => array(
			'@type'       => 'EntryPoint',
			'urlTemplate' => $search,
		),
	);

	$frame = lq_schema_frame_nodes( $role );
	$graph = array_merge( $graph, $frame );
	$parts = array();
	foreach ( $frame as $node ) {
		$parts[] = array( '@id' => $node['@id'] );
	}

	if ( ! is_singular( array( 'page', 'post' ) ) ) {
		$listing = lq_schema_listing_page( $site_id, $lang, $parts );
		if ( $listing ) {
			$graph = array_merge( $graph, $listing );
		}
		return apply_filters( 'lq_schema_graph', $graph );
	}

	$id        = get_queried_object_id();
	$url       = get_permalink( $id );
	$page_id   = $url . '#webpage';
	$key       = lq_page_key_for_id( $id );
	$post      = get_post( $id );
	$content   = $post ? (string) $post->post_content : '';
	$desc      = wp_strip_all_tags( do_shortcode( (string) get_post_meta( $id, LQ_META_DESCRIPTION, true ) ) );
	$webpage   = array(
		'@type'      => 'WebPage',
		'@id'        => $page_id,
		'url'        => $url,
		'name'       => wp_strip_all_tags( get_the_title( $id ) ),
		'isPartOf'   => array( '@id' => $site_id ),
		'author'     => array( '@id' => lq_schema_author_id() ),
		'inLanguage' => $lang,
	);
	if ( $desc ) {
		$webpage['description'] = $desc;
	}
	if ( $parts ) {
		$webpage['hasPart'] = $parts;
	}
	if ( 'contact' === $key ) {
		$webpage['@type'] = array( 'WebPage', 'ContactPage' );
	}

	// Read aloud: the page title and the "In brief" box, where they show.
	$brief    = lq_sc_brief( $content );
	$speak    = array();
	$template = (string) get_page_template_slug( $id );
	if ( ! lq_is_home() && 'page-no-title' !== $template ) {
		$speak[] = '.lq-page-title';
	}
	if ( '' !== $brief ) {
		$speak[] = '.lq-brief';
	}
	if ( $speak ) {
		$webpage['speakable'] = array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => $speak,
		);
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
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $url . '#breadcrumb',
			'itemListElement' => $items,
		);
		$webpage['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}

	// Photographs placed with lailatulqadar/photo blocks, in page order.
	$images = lq_schema_page_images( $content, $key );
	$lead   = '';
	foreach ( $images as $node ) {
		if ( ! empty( $node['representativeOfPage'] ) ) {
			$lead = $node['@id'];
		}
	}
	if ( '' === $lead && $images ) {
		$lead = $images[0]['@id'];
	}
	if ( '' !== $lead ) {
		$webpage['primaryImageOfPage'] = array( '@id' => $lead );
	}
	$graph = array_merge( $graph, $images );

	// SVG diagrams drawn for the article.
	$media = array();
	foreach ( $images as $node ) {
		$media[] = array( '@id' => $node['@id'] );
	}
	foreach ( lq_sc_figures( $content ) as $i => $figure ) {
		if ( '' === $figure['title'] && '' === $figure['caption'] ) {
			continue;
		}
		$node = array(
			'@type'           => 'ImageObject',
			'@id'             => $url . '#figure-' . ( $i + 1 ),
			'name'            => '' !== $figure['title'] ? $figure['title'] : $figure['caption'],
			'encodingFormat'  => 'image/svg+xml',
			'creator'         => array( '@id' => $org_id ),
			'copyrightHolder' => array( '@id' => $org_id ),
			'inLanguage'      => $lang,
		);
		if ( '' !== $figure['caption'] ) {
			$node['caption'] = $figure['caption'];
		}
		$graph[] = $node;
		$media[] = array( '@id' => $node['@id'] );
	}

	// About page: ProfilePage about the author when one is named, otherwise
	// about the publishing organisation.
	if ( 'about' === $key ) {
		$subject = array( '@id' => lq_schema_author_id() );
		$webpage['@type']        = array( 'WebPage', 'ProfilePage', 'AboutPage' );
		$webpage['mainEntity']   = $subject;
		$webpage['dateCreated']  = get_post_time( 'c', true, $post );
		$webpage['dateModified'] = get_post_modified_time( 'c', true, $post );
	}

	// Questions shown with their answers: a page of them is an FAQPage.
	$faq = lq_sc_faq( $content );
	if ( count( $faq ) >= 3 ) {
		$types            = (array) $webpage['@type'];
		$types[]          = 'FAQPage';
		$webpage['@type'] = array_values( array_unique( $types ) );
		$questions        = array();
		foreach ( $faq as $i => $qa ) {
			$questions[] = array(
				'@type'          => 'Question',
				'@id'            => $url . '#question-' . ( $i + 1 ),
				'name'           => $qa['question'],
				'inLanguage'     => $lang,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $qa['answer'],
				),
			);
		}
		$webpage['mainEntity'] = $questions;
	}

	$is_article = in_array( $key, lq_article_keys(), true ) || is_singular( 'post' );
	$main_id    = $is_article ? $url . '#article' : $page_id;
	$extra      = array(); // Nodes the article (or page) has as parts.

	// Numbered steps with a practical end.
	foreach ( lq_sc_howto( $content ) as $i => $howto ) {
		$steps = array();
		foreach ( $howto['steps'] as $n => $step ) {
			$steps[] = array(
				'@type'    => 'HowToStep',
				'position' => $n + 1,
				'name'     => $step['name'],
				'text'     => $step['text'],
			);
		}
		$extra[] = array(
			'@type'      => 'HowTo',
			'@id'        => $url . '#howto-' . ( $i + 1 ),
			'name'       => $howto['name'],
			'inLanguage' => $lang,
			'step'       => $steps,
			'isPartOf'   => array( '@id' => $main_id ),
		);
	}

	// Captioned tables.
	foreach ( lq_sc_tables( lq_schema_table_markup( $content ) ) as $i => $table ) {
		if ( '' === $table['caption'] ) {
			continue;
		}
		$node = array(
			'@type'      => 'Table',
			'@id'        => $url . '#table-' . ( $i + 1 ),
			'name'       => $table['caption'],
			'inLanguage' => $lang,
			'isPartOf'   => array( '@id' => $main_id ),
		);
		if ( $table['columns'] ) {
			$node['about'] = implode( ', ', $table['columns'] );
		}
		$extra[] = $node;
	}

	// Footnotes that name a source, and the scripture the page quotes.
	$notes     = $post ? lq_page_footnotes( $id ) : array();
	$note_html = array();
	$citations = array();
	foreach ( $notes as $i => $note ) {
		if ( empty( $note['id'] ) || ! isset( $note['content'] ) ) {
			continue;
		}
		$note_html[ $note['id'] ] = (string) $note['content'];
		$cite                     = lq_sc_citation( $note['content'] );
		if ( ! $cite ) {
			continue;
		}
		$node = array(
			'@type' => 'CreativeWork',
			'@id'   => $url . '#note-' . ( $i + 1 ),
			'name'  => $cite['name'],
			'text'  => $cite['text'],
		);
		if ( '' !== $cite['url'] ) {
			$node['url'] = $cite['url'];
		}
		$citations[] = $node;
	}
	$quotes = lq_schema_quotations( $content, $url, $role, $lang, $note_html, $main_id );

	// The Ramadan dates the page shows.
	$events = lq_sc_shows_ramadan_dates( $content ) ? lq_schema_ramadan_nodes( $content, $url, $role ) : array();

	if ( $is_article ) {
		$article = array(
			'@type'               => 'Article',
			'@id'                 => $url . '#article',
			'headline'            => mb_substr( wp_strip_all_tags( get_the_title( $id ) ), 0, 110 ),
			'datePublished'       => get_post_time( 'c', true, $post ),
			'dateModified'        => get_post_modified_time( 'c', true, $post ),
			'author'              => array( '@id' => lq_schema_author_id() ),
			'publisher'           => array( '@id' => $org_id ),
			'mainEntityOfPage'    => array( '@id' => $page_id ),
			'isPartOf'            => array( '@id' => $site_id ),
			'inLanguage'          => $lang,
			'wordCount'           => lq_word_count( $id ),
			'isAccessibleForFree' => true,
			// The footer shows "© <year> <site name>".
			'copyrightYear'       => (int) wp_date( 'Y' ),
			'copyrightHolder'     => array( '@id' => $org_id ),
			'accessMode'          => $media ? array( 'textual', 'visual' ) : array( 'textual' ),
			'accessModeSufficient' => array(
				array(
					'@type'      => 'ItemList',
					'itemListElement' => array( 'textual' ),
				),
			),
			'accessibilityFeature' => array_values( array_filter( array( $media ? 'alternativeText' : '', 'structuralNavigation' ) ) ),
		);
		if ( $desc ) {
			$article['description'] = $desc;
		}
		if ( '' !== $brief ) {
			$article['abstract'] = $brief;
		}
		// Google asks for an image on every Article: the featured image, else
		// the page's lead photograph, else the site mark.
		$article['image'] = has_post_thumbnail( $id ) || '' === $lead ? array( lq_article_image_url( $id, $key ) ) : array( array( '@id' => $lead ) );
		if ( $media ) {
			$article['associatedMedia'] = $media;
		}
		if ( is_singular( 'post' ) ) {
			$cats = get_the_category( $id );
			if ( $cats ) {
				$article['articleSection'] = $cats[0]->name;
			}
			$tags = get_the_tags( $id );
			if ( $tags && ! is_wp_error( $tags ) ) {
				$article['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
			}
		}
		$graph[] = $article;
	}

	$graph[] = $webpage;

	// Attach what the page shows to its main node.
	$main = count( $graph ) - ( $is_article ? 2 : 1 );
	$refs = function ( $nodes ) {
		$out = array();
		foreach ( $nodes as $node ) {
			$out[] = array( '@id' => $node['@id'] );
		}
		return $out;
	};
	if ( $citations ) {
		$graph[ $main ]['citation'] = $refs( $citations );
	}
	if ( $extra ) {
		$graph[ $main ]['hasPart'] = array_merge( isset( $graph[ $main ]['hasPart'] ) ? $graph[ $main ]['hasPart'] : array(), $refs( $extra ) );
	}
	if ( $quotes['top'] ) {
		$graph[ $main ]['mentions'] = $refs( $quotes['top'] );
	}
	if ( $events ) {
		$graph[ $main ]['about'] = $refs( array_filter( $events, function ( $n ) {
			return 'Event' === $n['@type'] && empty( $n['superEvent'] );
		} ) );
		$graph[ $main ]['about'] = array_values( $graph[ $main ]['about'] );
	}

	// The same page in the other language.
	$other = lq_translation_of( $id );
	if ( $other && 'publish' === get_post_status( $other ) ) {
		$other_key = lq_page_key_for_id( $other );
		$other_id  = get_permalink( $other ) . ( in_array( $other_key, lq_article_keys(), true ) ? '#article' : '#webpage' );
		$graph[ $main ][ 'secondary' === $role ? 'translationOfWork' : 'workTranslation' ] = array( '@id' => $other_id );
	}

	$graph = array_merge( $graph, $extra, $citations, $quotes['nodes'], $events );

	return apply_filters( 'lq_schema_graph', $graph );
}

/**
 * Header, menus and footer: the frame every page shows.
 *
 * @param string $role primary or secondary.
 * @return array[]
 */
function lq_schema_frame_nodes( $role ) {
	$base  = trailingslashit( lq_home_url() );
	$ms    = 'secondary' === $role;
	$nodes = array(
		array(
			'@type' => 'WPHeader',
			'@id'   => $base . '#header',
			'name'  => $ms ? 'Pengepala laman' : 'Site header',
		),
	);
	$menus = array(
		'header' => $ms ? 'Panduan' : 'Guide',
		'footer' => $ms ? 'Maklumat laman' : 'Site information',
	);
	foreach ( $menus as $set => $label ) {
		$items = array();
		foreach ( lq_menu_items( $set ) as $item ) {
			if ( '' === $item['url'] ) {
				continue;
			}
			$items[] = array(
				'@type' => 'SiteNavigationElement',
				'name'  => wp_strip_all_tags( 'header' === $set ? $item['short'] : $item['title'] ),
				'url'   => $item['url'],
			);
		}
		if ( ! $items ) {
			continue;
		}
		$nodes[] = array(
			'@type'   => 'SiteNavigationElement',
			'@id'     => $base . '#menu-' . $set,
			'name'    => $label,
			'hasPart' => $items,
		);
	}
	$nodes[] = array(
		'@type' => 'WPFooter',
		'@id'   => $base . '#footer',
		'name'  => $ms ? 'Pengaki laman' : 'Site footer',
	);
	return $nodes;
}

/**
 * Search results and archive pages: SearchResultsPage or CollectionPage,
 * with the breadcrumb the page shows.
 *
 * @param string $site_id WebSite @id.
 * @param string $lang    Language tag.
 * @param array  $parts   Frame references.
 * @return array[]
 */
function lq_schema_listing_page( $site_id, $lang, $parts ) {
	if ( is_404() || lq_is_home() ) {
		return array();
	}
	$trail = lq_breadcrumb_trail();
	if ( ! $trail ) {
		return array();
	}
	$last = end( $trail );
	$url  = $last[1] ? $last[1] : ( is_search() ? get_search_link() : '' );
	if ( '' === $url ) {
		return array();
	}
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
	$page = array(
		'@type'      => is_search() ? 'SearchResultsPage' : 'CollectionPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => wp_strip_all_tags( $last[0] ),
		'isPartOf'   => array( '@id' => $site_id ),
		'inLanguage' => $lang,
		'breadcrumb' => array( '@id' => $url . '#breadcrumb' ),
	);
	if ( $parts ) {
		$page['hasPart'] = $parts;
	}
	return array(
		array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $url . '#breadcrumb',
			'itemListElement' => $items,
		),
		$page,
	);
}

/**
 * ImageObject nodes for the photographs a page shows through its
 * lailatulqadar/photo blocks, with the credit, licence and source each
 * figure prints.
 *
 * @param string $content Block markup.
 * @param string $key     Page key of the page.
 * @return array[]
 */
function lq_schema_page_images( $content, $key ) {
	$ms    = 'secondary' === lq_current_role();
	$nodes = array();
	foreach ( lq_sc_blocks( $content ) as $block ) {
		if ( 'photo' !== $block[0] ) {
			continue;
		}
		$k     = isset( $block[1]['key'] ) && '' !== $block[1]['key'] ? sanitize_key( $block[1]['key'] ) : $key;
		$image = '' !== $k ? lq_image( $k, max( 1, (int) ( isset( $block[1]['n'] ) ? $block[1]['n'] : 1 ) ) ) : null;
		if ( ! $image ) {
			continue;
		}
		$id = trailingslashit( home_url( '/' ) ) . '#image-' . $image['file'] . ( $ms ? '-ms' : '' );
		if ( isset( $nodes[ $id ] ) ) {
			continue;
		}
		$caption = $ms && ! empty( $image['caption_ms'] ) && is_string( $image['caption_ms'] ) ? $image['caption_ms'] : $image['caption'];
		$alt     = $ms && ! empty( $image['alt_ms'] ) && is_string( $image['alt_ms'] ) ? $image['alt_ms'] : $image['alt'];
		$open    = (bool) preg_match( '/^(CC0|Public domain|PD\b)/i', $image['licence'] );
		$node    = array(
			'@type'              => 'ImageObject',
			'@id'                => $id,
			'url'                => lq_image_file( $image, max( $image['widths'] ), LQ_URI ),
			'contentUrl'         => lq_image_file( $image, max( $image['widths'] ), LQ_URI ),
			'encodingFormat'     => 'image/webp',
			'name'               => $alt,
			'description'        => $alt,
			'creditText'         => sprintf( $ms ? 'Imej: %1$s, %2$s, %3$s' : 'Image: %1$s, %2$s, %3$s', $image['artist'], $image['licence'], $image['via'] ),
			'creator'            => array(
				'@type' => 'Person',
				'name'  => $image['artist'],
			),
			'acquireLicensePage' => $image['source'],
			'inLanguage'         => $ms ? 'ms-MY' : 'en',
		);
		if ( '' !== $caption ) {
			$node['caption'] = $caption;
		}
		if ( $image['width'] && $image['height'] ) {
			$node['width']  = $image['width'];
			$node['height'] = $image['height'];
		}
		if ( '' !== $image['licence_url'] ) {
			$node['license'] = $image['licence_url'];
		}
		if ( ! $open ) {
			$node['copyrightHolder'] = array(
				'@type' => 'Person',
				'name'  => $image['artist'],
			);
			$node['copyrightNotice'] = $image['artist'] . ', ' . $image['licence'];
		}
		if ( $image['lead'] && $k === $key ) {
			$node['representativeOfPage'] = true;
		}
		$nodes[ $id ] = $node;
	}
	return array_values( $nodes );
}

/**
 * Quotation nodes for the Qurʾān verses and hadith a page quotes: the
 * Arabic text, the translation shown beneath it (translationOfWork), and
 * the work it is part of (a surah of the Qurʾān, or the hadith collection
 * and number the footnote gives, with the grading where the note states
 * one).
 *
 * @param string $content   Block markup.
 * @param string $url       Page address.
 * @param string $role      primary or secondary.
 * @param string $lang      Language tag of the page.
 * @param array  $note_html Footnote ID => HTML.
 * @param string $main_id   @id of the page's main node.
 * @return array top (the quotations themselves), nodes (everything).
 */
function lq_schema_quotations( $content, $url, $role, $lang, $note_html, $main_id ) {
	$root  = trailingslashit( home_url( '/' ) );
	$quran = array(
		'@type'      => 'Book',
		'@id'        => $root . '#quran',
		'name'       => 'secondary' === $role ? 'Al-Quran' : 'The Qurʾān',
		'genre'      => 'Scripture',
		'inLanguage' => 'ar',
		'sameAs'     => 'https://www.wikidata.org/wiki/Q428',
	);
	$top   = array();
	$nodes = array();
	$works = array();
	foreach ( lq_sc_quotations( $content ) as $i => $q ) {
		$qid  = $url . '#quote-' . ( $i + 1 );
		$node = array(
			'@type'      => 'Quotation',
			'@id'        => $qid,
			'text'       => $q['arabic'],
			'inLanguage' => 'ar',
		);
		if ( 'quran' === $q['kind'] && $q['surah'] ) {
			$works[ $quran['@id'] ] = $quran;
			$chapter                = $root . '#quran-' . $q['surah'];
			$works[ $chapter ]      = array(
				'@type'    => 'Chapter',
				'@id'      => $chapter,
				'name'     => $q['surah_name'],
				'position' => $q['surah'],
				'isPartOf' => array( '@id' => $quran['@id'] ),
			);
			$node['isPartOf'] = array( '@id' => $chapter );
			$node['name']     = $q['cite'];
		} elseif ( 'hadith' === $q['kind'] ) {
			$node['name'] = $q['cite'];
			foreach ( $q['notes'] as $fn ) {
				$src = isset( $note_html[ $fn ] ) ? lq_sc_hadith_source( $note_html[ $fn ] ) : null;
				if ( ! $src ) {
					continue;
				}
				$book           = $root . '#book-' . sanitize_title( $src['collection'] );
				$works[ $book ] = array(
					'@type'      => 'Book',
					'@id'        => $book,
					'name'       => $src['collection'],
					'genre'      => 'secondary' === $role ? 'Koleksi hadis' : 'Hadith collection',
					'inLanguage' => 'ar',
				);
				$node['isPartOf']   = array( '@id' => $book );
				$node['identifier'] = $src['number'];
				if ( '' !== $src['grading'] ) {
					$node['additionalProperty'] = array(
						'@type'       => 'PropertyValue',
						'name'        => 'secondary' === $role ? 'darjat' : 'grading',
						'value'       => $src['grading'],
						'description' => $src['graded_by'],
					);
				}
				break;
			}
		}
		$top[]   = $node;
		$nodes[] = $node;
		if ( '' !== $q['translation'] ) {
			$nodes[] = array(
				'@type'             => 'Quotation',
				'@id'               => $qid . '-translation',
				'text'              => $q['translation'],
				'inLanguage'        => $lang,
				'translationOfWork' => array( '@id' => $qid ),
				'isPartOf'          => array( '@id' => $main_id ),
			);
		}
	}
	return array(
		'top'   => $top,
		'nodes' => array_merge( $nodes, array_values( $works ) ),
	);
}

/**
 * Markup to read tables from: the stored content plus the rendered output
 * of each odd-nights block, whose captioned table exists only once the
 * block renders.
 *
 * @param string $content Block markup.
 * @return string
 */
function lq_schema_table_markup( $content ) {
	$html = (string) $content;
	if ( ! function_exists( 'render_block' ) ) {
		return $html;
	}
	foreach ( lq_sc_blocks( $content ) as $block ) {
		if ( 'odd-nights' !== $block[0] ) {
			continue;
		}
		$html .= render_block(
			array(
				'blockName'    => 'lailatulqadar/odd-nights',
				'attrs'        => $block[1],
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
	}
	return $html;
}

/**
 * Event nodes for the Ramadan dates a page shows: the month itself, the
 * last ten nights where the night window shows them, and the odd nights
 * where the odd-nights table lists them. Dates follow the Umm al-Qura
 * table (or the override in the settings) exactly as the page prints them;
 * the day-later start each table also shows goes into the description.
 *
 * @param string $content Block markup.
 * @param string $url     Page address.
 * @param string $role    primary or secondary.
 * @return array[]
 */
function lq_schema_ramadan_nodes( $content, $url, $role ) {
	$r = lq_ramadan();
	if ( ! $r ) {
		return array();
	}
	$ms     = 'secondary' === $role;
	$base   = trailingslashit( lq_home_url() );
	$lang   = $ms ? 'ms-MY' : 'en';
	$eid    = lq_add_days( $r['start'], $r['days'] );
	$rid    = $base . '#ramadan-' . $r['hijri'];
	$blocks = wp_list_pluck( lq_sc_blocks( $content ), 0 );
	$nodes  = array(
		array(
			'@type'       => 'Event',
			'@id'         => $rid,
			'name'        => sprintf( $ms ? 'Ramadan %d H' : 'Ramadan %d AH', $r['hijri'] ),
			'startDate'   => $r['start'],
			'endDate'     => lq_add_days( $eid, -1 ),
			'inLanguage'  => $lang,
			'description' => $ms
				? sprintf( 'Takwim Umm al-Qura. Rukyah tempatan menentukan tarikh muktamad; 1 Ramadan mungkin jatuh pada %s.', lq_fmt_date( $r['alt'], 'long', $role ) )
				: sprintf( 'Umm al-Qura calendar. Local sighting decides the final calendar; 1 Ramadan may fall on %s instead.', lq_fmt_date( $r['alt'], 'long', $role ) ),
		),
	);
	if ( in_array( 'night-window', $blocks, true ) ) {
		$nodes[] = array(
			'@type'      => 'Event',
			'@id'        => $base . '#last-ten-' . $r['hijri'],
			'name'       => sprintf( $ms ? 'Sepuluh malam terakhir Ramadan %d H' : 'The last ten nights of Ramadan %d AH', $r['hijri'] ),
			'startDate'  => lq_night_evening( $r['start'], 21 ),
			'endDate'    => $eid,
			'inLanguage' => $lang,
			'superEvent' => array( '@id' => $rid ),
		);
	}
	if ( in_array( 'odd-nights', $blocks, true ) ) {
		$list = array();
		foreach ( array( 21, 23, 25, 27, 29 ) as $i => $n ) {
			$evening = lq_night_evening( $r['start'], $n );
			$alt     = lq_night_evening( $r['alt'], $n );
			$event   = array(
				'@type'       => 'Event',
				'@id'         => $base . '#night-' . $r['hijri'] . '-' . $n,
				'name'        => ucfirst( lq_hijri( $n, $r['hijri'], 'ramadan', true, $role ) ),
				'startDate'   => $evening,
				'endDate'     => lq_add_days( $evening, 1 ),
				'inLanguage'  => $lang,
				'superEvent'  => array( '@id' => $rid ),
				'description' => $ms
					? sprintf( 'Bermula selepas Maghrib, %1$s. Jika 1 Ramadan jatuh pada %2$s: %3$s.', lq_fmt_date( $evening, 'long', $role ), lq_fmt_date( $r['alt'], 'long', $role ), lq_fmt_date( $alt, 'long', $role ) )
					: sprintf( 'Begins at sunset on %1$s. If 1 Ramadan falls on %2$s: the evening of %3$s.', lq_fmt_date( $evening, 'long', $role ), lq_fmt_date( $r['alt'], 'long', $role ), lq_fmt_date( $alt, 'long', $role ) ),
			);
			$nodes[] = $event;
			$list[]  = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'item'     => array( '@id' => $event['@id'] ),
			);
		}
		$nodes[] = array(
			'@type'           => 'ItemList',
			'@id'             => $url . '#odd-nights',
			'name'            => $ms ? 'Malam-malam ganjil' : 'The odd nights',
			'itemListElement' => $list,
		);
	}
	return $nodes;
}

/**
 * Rank Math: fold the theme's nodes into Rank Math's graph, so the page
 * keeps one graph. Runs late, after Rank Math has added its own nodes.
 *
 * @param array $data   Rank Math's nodes.
 * @param mixed $jsonld Rank Math's JsonLD object.
 * @return array
 */
function lq_rank_math_json_ld( $data, $jsonld = null ) {
	if ( is_404() || ! is_array( $data ) ) {
		return $data;
	}
	return lq_schema_one_author( lq_sc_merge_graph( $data, lq_schema_graph() ) );
}
add_filter( 'rank_math/json_ld', 'lq_rank_math_json_ld', 99, 2 );

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

/**
 * A sameAs list with https://menj.bio appended when it is missing.
 *
 * Existing entries keep their order. The owner ruled on 4 November 2026 that
 * every sameAs the theme builds for the author includes this address.
 *
 * @param string[] $same_as Existing entries.
 * @return string[]
 */
function lq_schema_with_menj( $same_as ) {
	$same_as = array_values( (array) $same_as );
	if ( ! in_array( 'https://menj.bio', $same_as, true ) ) {
		$same_as[] = 'https://menj.bio';
	}
	return $same_as;
}

/**
 * The @id of the author Person.
 *
 * @return string
 */
function lq_schema_author_id() {
	return trailingslashit( home_url( '/' ) ) . '#author';
}

/**
 * The author Person: Mohd Elfie Nieshaem Juferi, at https://menj.bio. The
 * owner ruled on 4 November 2026 that the structured data names him as the
 * author; nothing visible changes. The author address set in the settings,
 * if any, stays in sameAs ahead of https://menj.bio.
 *
 * @return array
 */
function lq_schema_author_node() {
	$url = (string) lq_setting( 'author_url' );
	return array(
		'@type'  => 'Person',
		'@id'    => lq_schema_author_id(),
		'name'   => 'Mohd Elfie Nieshaem Juferi',
		'url'    => 'https://menj.bio',
		'sameAs' => lq_schema_with_menj( '' !== $url ? array( $url ) : array() ),
	);
}

/**
 * One author in a merged graph. Every author value, at any depth, points to
 * the author Person. A Person node Rank Math added for the WordPress user is
 * dropped once nothing points to it, and its sameAs entries join the
 * author's, ahead of https://menj.bio.
 *
 * @param array $graph Merged top-level nodes.
 * @return array
 */
function lq_schema_one_author( $graph ) {
	$id   = lq_schema_author_id();
	$old  = array();
	$walk = function ( $node ) use ( &$walk, &$old, $id ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}
		foreach ( $node as $k => $v ) {
			if ( 'author' === $k && is_array( $v ) ) {
				if ( ! empty( $v['@id'] ) && $id !== $v['@id'] ) {
					$old[ $v['@id'] ] = true;
				}
				$node[ $k ] = array( '@id' => $id );
			} elseif ( is_array( $v ) ) {
				$node[ $k ] = $walk( $v );
			}
		}
		return $node;
	};
	$graph = $walk( $graph );
	$extra = array();
	$out   = array();
	foreach ( $graph as $key => $node ) {
		if ( is_array( $node ) && ! empty( $node['@id'] ) && isset( $old[ $node['@id'] ] ) && in_array( 'Person', (array) ( $node['@type'] ?? array() ), true ) ) {
			$extra = array_merge( $extra, array_filter( (array) ( $node['sameAs'] ?? array() ), 'is_string' ) );
			continue;
		}
		$out[ $key ] = $node;
	}
	foreach ( $out as $key => $node ) {
		if ( is_array( $node ) && $id === ( $node['@id'] ?? '' ) ) {
			$same = array_merge( (array) ( $node['sameAs'] ?? array() ), $extra );
			$same = array_values( array_unique( array_diff( $same, array( 'https://menj.bio' ) ) ) );
			$out[ $key ]['sameAs'] = lq_schema_with_menj( $same );
		}
	}
	return array_values( $graph ) === $graph ? array_values( $out ) : $out;
}
