<?php
/**
 * Contextual links: the first mention of a known phrase in the running text
 * of a guide page links to the inner page that treats it, on this site or on
 * one of the six sister sites.
 *
 * English pages read inc/data/network-links.json, a shared file kept
 * identical in all seven themes. Entries for this site link to the
 * published guide page; entries for a sister site link to that site.
 * Malay pages read inc/data/contextual-links-ms.json and link only to Malay
 * pages here, since the sister sites are in English.
 *
 * Links are added as the page renders, so the stored text is untouched. A
 * phrase is matched whole, capitals aside, in the running text only: never
 * inside a link, a heading, a quotation, a figure or caption, a table, a
 * footnote, a summary box, or Arabic. Each address is linked once a page, at
 * most one new link goes into any paragraph or list item, and the two caps
 * on the Contextual links tab limit the links per page.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

const LQ_SITE_KEY = 'lailatulqadar';

/**
 * A phrase or matched text in the form used for lookups: entities decoded,
 * curly apostrophes made straight, spaces collapsed, lower case.
 *
 * @param string $text Text.
 * @return string
 */
function lq_ctx_norm( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( "\u{2019}", "\u{2018}", "\u{02BC}", "\u{00A0}" ), array( "'", "'", "'", ' ' ), $text );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) ) ?? trim( $text );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

/**
 * The shared map, decoded once a request.
 *
 * @return array{sites:array<string,string>,links:array<int,array{site:string,path:string,phrases:string[]}>}
 */
function lq_ctx_shared_map() {
	static $map = null;
	if ( null === $map ) {
		$file = LQ_DIR . '/inc/data/network-links.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$map  = array(
			'sites' => isset( $data['sites'] ) && is_array( $data['sites'] ) ? $data['sites'] : array(),
			'links' => isset( $data['links'] ) && is_array( $data['links'] ) ? $data['links'] : array(),
		);
		$map  = apply_filters( 'lq_contextual_links_map', $map );
	}
	return $map;
}

/**
 * The Malay map: page key => phrases.
 *
 * @return array<string,string[]>
 */
function lq_ctx_malay_map() {
	$file = LQ_DIR . '/inc/data/contextual-links-ms.json';
	$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return isset( $data['links'] ) && is_array( $data['links'] ) ? $data['links'] : array();
}

/**
 * Address of a published guide page in one language, or '' while it is a
 * draft or missing, so no link ever leads to a page readers cannot open.
 *
 * @param string $key  Page key.
 * @param string $role primary or secondary.
 * @return string
 */
function lq_ctx_page_url( $key, $role ) {
	$map = lq_page_map();
	$id  = empty( $map[ $role ][ $key ] ) ? 0 : (int) $map[ $role ][ $key ];
	return $id && 'publish' === get_post_status( $id ) ? (string) get_permalink( $id ) : '';
}

/**
 * Address on this site for a path from the shared map: the published guide
 * page when the path is a guide page, else the path under the home URL.
 *
 * @param string $path Path such as /surah-al-qadr/.
 * @return string
 */
function lq_ctx_own_url( $path ) {
	$slug = trim( (string) $path, '/' );
	foreach ( lq_page_definitions() as $key => $def ) {
		if ( $def['primary']['slug'] === $slug ) {
			return lq_ctx_page_url( $key, 'primary' );
		}
	}
	return home_url( '/' . ltrim( (string) $path, '/' ) );
}

/**
 * Hosts of the sister sites, lower case.
 *
 * @return string[]
 */
function lq_ctx_sister_hosts() {
	$hosts = array();
	foreach ( lq_ctx_shared_map()['sites'] as $key => $base ) {
		if ( LQ_SITE_KEY !== $key ) {
			$hosts[] = strtolower( (string) wp_parse_url( $base, PHP_URL_HOST ) );
		}
	}
	return array_filter( $hosts );
}

/**
 * Lines "phrase | address" from a settings field. An address beginning with
 * / is on this site; a full address must be on a sister site (or on this
 * site), anything else is ignored.
 *
 * @param string $text      Field value.
 * @param bool   $allow_out Whether sister addresses are accepted.
 * @return array<int,array{phrase:string,url:string,sister:bool}>
 */
function lq_ctx_parse_extra( $text, $allow_out ) {
	$out    = array();
	$own    = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$sister = lq_ctx_sister_hosts();
	// Byte-wise split (no /u), so a stray invalid byte drops one line, not all.
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
			continue;
		}
		if ( '/' === $parts[1][0] && '/' !== substr( $parts[1], 1, 1 ) ) {
			$out[] = array( 'phrase' => $parts[0], 'url' => home_url( $parts[1] ), 'sister' => false );
			continue;
		}
		if ( ! preg_match( '#^https?://#i', $parts[1] ) ) {
			continue;
		}
		$host = strtolower( (string) wp_parse_url( $parts[1], PHP_URL_HOST ) );
		if ( $host === $own ) {
			$out[] = array( 'phrase' => $parts[0], 'url' => $parts[1], 'sister' => false );
		} elseif ( $allow_out && in_array( $host, $sister, true ) ) {
			$out[] = array( 'phrase' => $parts[0], 'url' => $parts[1], 'sister' => true );
		}
	}
	return $out;
}

/**
 * Compile a list of targets into one pattern and a lookup table. The first
 * entry for a phrase wins, so lines from the settings override the maps.
 *
 * @param array<int,array{phrase:string,url:string,sister:bool}> $entries Targets.
 * @return array{pattern:string,by:array<string,array{url:string,sister:bool}>}
 */
function lq_ctx_compile( $entries ) {
	$by      = array();
	$phrases = array();
	foreach ( $entries as $entry ) {
		$key = lq_ctx_norm( $entry['phrase'] );
		if ( '' === $entry['url'] || ( function_exists( 'mb_strlen' ) ? mb_strlen( $key ) : strlen( $key ) ) < 3 || isset( $by[ $key ] ) ) {
			continue;
		}
		$by[ $key ] = array( 'url' => $entry['url'], 'sister' => $entry['sister'] );
		$phrases[]  = trim( $entry['phrase'] );
	}
	if ( ! $phrases ) {
		return array( 'patterns' => array(), 'parts' => array(), 'by' => array() );
	}
	usort(
		$phrases,
		static function ( $a, $b ) {
			return strlen( $b ) <=> strlen( $a );
		}
	);
	$parts = array();
	foreach ( $phrases as $phrase ) {
		$quoted = preg_quote( $phrase, '/' );
		$quoted = str_replace( array( "'", "\u{2019}", "\u{2018}", "\u{02BC}" ), '(?&q)', $quoted );
		$quoted = preg_replace( '/(?:\\\\ |\s)+/u', '(?&s)', $quoted );
		if ( null !== $quoted ) {
			$parts[] = $quoted;
		}
	}
	return array(
		'patterns' => lq_ctx_patterns( $parts ),
		'parts'    => $parts,
		'by'       => $by,
	);
}

/**
 * The phrase alternatives as compiled patterns: one pattern while it fits
 * PCRE's size limit, else halves until each half compiles. A phrase that
 * cannot compile on its own is left out, never the rest.
 *
 * @param string[] $parts Quoted phrase alternatives, longest first.
 * @param int      $size  Most alternatives a pattern (0: as many as compile).
 * @return string[]
 */
function lq_ctx_patterns( $parts, $size = 0 ) {
	if ( ! $parts ) {
		return array();
	}
	if ( $size > 0 && count( $parts ) > $size ) {
		$out = array();
		foreach ( array_chunk( $parts, $size ) as $chunk ) {
			$out = array_merge( $out, lq_ctx_patterns( $chunk ) );
		}
		return $out;
	}
	// An apostrophe in a phrase matches the straight one, the curly one and
	// the entities wptexturize writes; a space matches any run of spaces.
	// Both are named once and called by name, which keeps the compiled
	// pattern small.
	$define  = "(?(DEFINE)(?<q>'|\u{2019}|\u{2018}|\u{02BC}|&\#8217;|&\#8216;|&\#039;|&\#39;|&rsquo;|&apos;)(?<s>(?:\s|&nbsp;|&\#160;)+))";
	$pattern = '/' . $define . '(?<![\p{L}\p{N}])(?:' . implode( '|', $parts ) . ')(?![\p{L}\p{N}])/iu';
	if ( false !== @preg_match( $pattern, '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
		return array( $pattern );
	}
	if ( 1 === count( $parts ) ) {
		return array();
	}
	$half = (int) ceil( count( $parts ) / 2 );
	return array_merge( lq_ctx_patterns( array_slice( $parts, 0, $half ) ), lq_ctx_patterns( array_slice( $parts, $half ) ) );
}

/**
 * Replace every phrase in a text run, left to right, as one pattern would:
 * the earliest match wins, and of two at the same place the longer. With
 * several patterns each is searched from the current place, so the result
 * is the same however the phrases are split.
 *
 * @param string[] $patterns From lq_ctx_patterns().
 * @param string   $text     Text run, no tags.
 * @param callable $link     Receives the matched text, returns its markup.
 * @return string|null Null when the text cannot be searched (invalid UTF-8).
 */
function lq_ctx_replace( $patterns, $text, $link ) {
	$out = '';
	$pos = 0;
	$len = strlen( $text );
	while ( $pos < $len ) {
		$best = null;
		foreach ( $patterns as $pattern ) {
			$hit = preg_match( $pattern, $text, $m, PREG_OFFSET_CAPTURE, $pos );
			if ( false === $hit ) {
				return null;
			}
			if ( $hit && ( null === $best || $m[0][1] < $best[1] || ( $m[0][1] === $best[1] && strlen( $m[0][0] ) > strlen( $best[0] ) ) ) ) {
				$best = $m[0];
			}
		}
		if ( null === $best || '' === $best[0] ) {
			break;
		}
		$out .= substr( $text, $pos, $best[1] - $pos ) . $link( $best[0] );
		$pos  = $best[1] + strlen( $best[0] );
	}
	return $out . substr( $text, $pos );
}

/**
 * Compiled targets for one language, once a request.
 *
 * @param string $role primary (English) or secondary (Malay).
 * @return array{pattern:string,by:array<string,array{url:string,sister:bool}>}
 */
function lq_ctx_targets( $role ) {
	static $cache = array();
	if ( isset( $cache[ $role ] ) ) {
		return $cache[ $role ];
	}
	$own     = '1' === (string) lq_setting( 'ctx_own' );
	$entries = array();

	if ( 'secondary' === $role ) {
		if ( $own ) {
			foreach ( lq_ctx_parse_extra( lq_setting( 'ctx_extra_ms' ), false ) as $entry ) {
				$entries[] = $entry;
			}
			foreach ( lq_ctx_malay_map() as $key => $phrases ) {
				$url = lq_ctx_page_url( (string) $key, 'secondary' );
				foreach ( (array) $phrases as $phrase ) {
					$entries[] = array( 'phrase' => (string) $phrase, 'url' => $url, 'sister' => false );
				}
			}
		}
		return $cache[ $role ] = lq_ctx_compile( $entries );
	}

	$sister = '1' === (string) lq_setting( 'ctx_sister' );
	foreach ( lq_ctx_parse_extra( lq_setting( 'ctx_extra' ), $sister ) as $entry ) {
		if ( $entry['sister'] ? $sister : $own ) {
			$entries[] = $entry;
		}
	}
	$map = lq_ctx_shared_map();
	foreach ( $map['links'] as $link ) {
		$site = isset( $link['site'] ) ? (string) $link['site'] : '';
		$path = isset( $link['path'] ) ? (string) $link['path'] : '';
		if ( '' === $path || empty( $link['phrases'] ) ) {
			continue;
		}
		if ( LQ_SITE_KEY === $site ) {
			if ( ! $own ) {
				continue;
			}
			$url = lq_ctx_own_url( $path );
		} else {
			if ( ! $sister || empty( $map['sites'][ $site ] ) ) {
				continue;
			}
			$url = untrailingslashit( (string) $map['sites'][ $site ] ) . '/' . ltrim( $path, '/' );
		}
		foreach ( (array) $link['phrases'] as $phrase ) {
			$entries[] = array( 'phrase' => (string) $phrase, 'url' => $url, 'sister' => LQ_SITE_KEY !== $site );
		}
	}
	return $cache[ $role ] = lq_ctx_compile( $entries );
}

/**
 * An address reduced for comparison: host and path, lower case, without the
 * trailing slash, query or fragment.
 *
 * @param string $url Address.
 * @return string
 */
function lq_ctx_url_key( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$path = strtolower( rtrim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' ) );
	return $host . $path;
}

/**
 * Add the links to rendered HTML. Pure: everything it needs is passed in.
 *
 * @param string $content    Rendered content.
 * @param array  $targets    From lq_ctx_targets().
 * @param string $here       Address of the page being rendered.
 * @param int    $own_max    Most links to this site.
 * @param int    $sister_max Most links to sister sites.
 * @return string
 */
function lq_ctx_apply( $content, $targets, $here, $own_max, $sister_max ) {
	if ( empty( $targets['patterns'] ) || false === strpos( $content, '<' ) || ( $own_max < 1 && $sister_max < 1 ) ) {
		return $content;
	}
	$pieces = preg_split( '/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $pieces ) {
		return $content;
	}

	$skip_tags  = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'q', 'figure', 'figcaption', 'cite', 'code', 'pre', 'sup', 'sub', 'nav', 'table', 'button', 'label', 'select', 'textarea', 'summary', 'details', 'script', 'style', 'aside', 'svg', 'math' );
	$skip_class = '/\bclass\s*=\s*["\'][^"\']*(?<![\w-])(?:fn|footnotes|wp-block-footnotes|lq-quran|lq-verse|lq-verse__translation|lq-hadith|lq-hadith-card|lq-table|lq-brief|lq-brief__label|lq-figure|lq-calli|lq-dua)(?![\w-])/i';
	$skip_lang  = '/\blang\s*=\s*["\']?(?:ar|he|el|syr)(?:-[a-z]+)?\b/i';
	$void       = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' );

	$here_key = lq_ctx_url_key( $here );
	$done     = array();
	$made     = array( 'own' => 0, 'sister' => 0 );
	$used     = false; // Whether the current paragraph or list item already has a new link.
	$blocks   = 0;     // Open p, li and dd outside skip regions; $used resets only at the outermost.
	$skips    = 0;     // Open elements that forbid links.
	// Every open element as array( tag, forbids links, is a block ), so closing
	// an element also closes any left open inside it: an unclosed <code> in a
	// list item ends with the item instead of switching links off for the page.
	$open     = array();
	$out      = '';

	$link = static function ( $text ) use ( $targets, $here_key, $own_max, $sister_max, &$done, &$made, &$used ) {
		$key = lq_ctx_norm( $text );
		// Without mbstring only ASCII letters are lowered, so a match in
		// non-ASCII capitals ("SŪRAT AL-QADR") is looked up key by key.
		if ( ! isset( $targets['by'][ $key ] ) && ! function_exists( 'mb_strtolower' ) ) {
			foreach ( array_keys( $targets['by'] ) as $candidate ) {
				if ( preg_match( '/^' . preg_quote( (string) $candidate, '/' ) . '$/iu', $key ) ) {
					$key = $candidate;
					break;
				}
			}
		}
		if ( $used || ! isset( $targets['by'][ $key ] ) ) {
			return $text;
		}
		$target = $targets['by'][ $key ];
		$url    = lq_ctx_url_key( $target['url'] );
		$kind   = $target['sister'] ? 'sister' : 'own';
		$max    = $target['sister'] ? $sister_max : $own_max;
		if ( isset( $done[ $url ] ) || $url === $here_key || $made[ $kind ] >= $max ) {
			return $text;
		}
		$done[ $url ] = true;
		++$made[ $kind ];
		$used = true;
		if ( $target['sister'] ) {
			return '<a class="lq-ctx-link lq-ctx-link--sister" href="' . esc_url( $target['url'] ) . '" rel="noopener">' . $text . '</a>';
		}
		return '<a class="lq-ctx-link" href="' . esc_url( $target['url'] ) . '">' . $text . '</a>';
	};

	foreach ( $pieces as $piece ) {
		if ( '' === $piece ) {
			continue;
		}
		if ( '<' === $piece[0] ) {
			if ( preg_match( '#^<(/?)([a-zA-Z][a-zA-Z0-9-]*)#', $piece, $m ) ) {
				$tag = strtolower( $m[2] );
				$is_block = 'p' === $tag || 'li' === $tag || 'dd' === $tag;
				if ( '/' === $m[1] ) {
					for ( $i = count( $open ) - 1; $i >= 0; $i-- ) {
						if ( $open[ $i ][0] === $tag ) {
							foreach ( array_splice( $open, $i ) as $closed ) {
								$skips  -= $closed[1] ? 1 : 0;
								$blocks -= $closed[2] ? 1 : 0;
							}
							break;
						}
					}
				} elseif ( ! in_array( $tag, $void, true ) && '/>' !== substr( $piece, -2 ) ) {
					$forbid = $skips > 0 || in_array( $tag, $skip_tags, true ) || preg_match( $skip_class, $piece ) || preg_match( $skip_lang, $piece );
					$block  = ! $forbid && $is_block;
					if ( $block ) {
						// A paragraph inside a list item belongs to that item.
						if ( 0 === $blocks ) {
							$used = false;
						}
						++$blocks;
					}
					$skips  += $forbid ? 1 : 0;
					$open[]  = array( $tag, (bool) $forbid, $block );
				}
			}
			$out .= $piece;
			continue;
		}
		if ( $skips > 0 || '' === trim( $piece ) || ( $made['own'] >= $own_max && $made['sister'] >= $sister_max ) ) {
			$out .= $piece;
			continue;
		}
		// A run that cannot be searched (an invalid UTF-8 byte) stays as it is.
		$out .= lq_ctx_replace( $targets['patterns'], $piece, $link ) ?? $piece;
	}
	return $out;
}

/**
 * Whether the page being rendered takes contextual links.
 *
 * @param WP_Post $post Page.
 * @return bool
 */
function lq_ctx_enabled_for( $post ) {
	$excluded = array_filter( array_map( 'sanitize_title', preg_split( '/[\s,]+/', (string) lq_setting( 'ctx_exclude' ) ) ) );
	$enabled  = ! in_array( $post->post_name, $excluded, true );
	return (bool) apply_filters( 'lq_contextual_links_enabled', $enabled, $post );
}

/**
 * the_content filter. Block templates render Post Content outside the
 * classic loop, so the guard checks the main query and the queried page
 * rather than in_the_loop(). Priority 25: after do_blocks (9), wptexturize
 * and wpautop (10), shortcodes (11) and image tags (12), so the text is in
 * its final form; the theme has no other content filter.
 *
 * @param string $content Rendered content.
 * @return string
 */
function lq_contextual_links_filter( $content ) {
	if ( is_admin() || is_feed() || ! is_singular() || ! is_main_query() || doing_filter( 'get_the_excerpt' ) || lq_is_home() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || (int) $post->ID !== (int) get_queried_object_id() || ! lq_ctx_enabled_for( $post ) ) {
		return $content;
	}
	$role = lq_role_of( $post->ID );
	return lq_ctx_apply(
		$content,
		lq_ctx_targets( $role ),
		(string) get_permalink( $post ),
		max( 0, min( 15, (int) lq_setting( 'ctx_own_max' ) ) ),
		'secondary' === $role ? 0 : max( 0, min( 10, (int) lq_setting( 'ctx_sister_max' ) ) )
	);
}
add_filter( 'the_content', 'lq_contextual_links_filter', 25 );
