<?php
/**
 * Structured data read from what a page shows: questions and answers,
 * numbered steps, tables, Qurʾān verses, hadith cards, photographs and
 * footnotes. Each reader takes the stored block markup and returns plain
 * arrays; inc/schema.php turns them into nodes.
 *
 * Only marked-up elements are read (the classes the shipped articles and
 * blocks set). Free running text is never guessed at.
 *
 * These functions use no WordPress API, so tests/schema-graph-test.php can
 * call them directly.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plain text of an HTML fragment: tags and footnote markers removed,
 * entities decoded, white space collapsed.
 *
 * @param string $html Fragment.
 * @return string
 */
function lq_sc_text( $html ) {
	$html = preg_replace( '#<sup\b[^>]*class="fn"[^>]*>.*?</sup>#su', '', (string) $html );
	$html = preg_replace( '#<(script|style|svg)\b.*?</\1>#su', ' ', $html );
	$html = preg_replace( '#<!--.*?-->#su', ' ', $html );
	$text = html_entity_decode( strip_tags( preg_replace( '#<(br|/p|/li|/h[1-6]|/td|/th|/tr|/figcaption)\b[^>]*>#i', '$0 ', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * The footnote IDs a fragment points to, in order.
 *
 * @param string $html Fragment.
 * @return string[]
 */
function lq_sc_footnote_ids( $html ) {
	preg_match_all( '#data-fn="([^"]+)"#', (string) $html, $m );
	return array_values( array_unique( $m[1] ) );
}

/**
 * Sections under each H2: [ heading text, body HTML ].
 *
 * @param string $content Block markup.
 * @return array[]
 */
function lq_sc_sections( $content ) {
	$parts = preg_split( '#(<h2\b[^>]*>.*?</h2>)#su', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$out   = array();
	for ( $i = 1; $i < count( $parts ); $i += 2 ) {
		$out[] = array( lq_sc_text( $parts[ $i ] ), isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '' );
	}
	return $out;
}

/**
 * Questions shown as H2 headings with their answers below them.
 *
 * @param string $content Block markup.
 * @return array[] Each: question, answer.
 */
function lq_sc_faq( $content ) {
	$out = array();
	foreach ( lq_sc_sections( $content ) as $section ) {
		if ( '?' !== mb_substr( $section[0], -1 ) ) {
			continue;
		}
		$answer = lq_sc_text( $section[1] );
		if ( '' !== $answer ) {
			$out[] = array(
				'question' => $section[0],
				'answer'   => $answer,
			);
		}
	}
	return $out;
}

/**
 * Numbered steps: an ordered list under an H2 in which every item opens
 * with a bold step name ("<strong>At sunset.</strong> ..."). Three steps at
 * least; anything looser is an ordinary list and is not read.
 *
 * @param string $content Block markup.
 * @return array[] Each: name (the heading), steps (each: name, text).
 */
function lq_sc_howto( $content ) {
	$out = array();
	foreach ( lq_sc_sections( $content ) as $section ) {
		if ( ! preg_match( '#<ol\b[^>]*>(.*?)</ol>#su', $section[1], $ol ) ) {
			continue;
		}
		preg_match_all( '#<li\b[^>]*>(.*?)</li>#su', $ol[1], $items );
		$steps = array();
		foreach ( $items[1] as $item ) {
			if ( ! preg_match( '#^\s*<strong>(.*?)</strong>#su', $item, $s ) ) {
				$steps = array();
				break;
			}
			$steps[] = array(
				'name' => rtrim( lq_sc_text( $s[1] ), '.:' ),
				'text' => lq_sc_text( $item ),
			);
		}
		if ( count( $steps ) >= 3 ) {
			$out[] = array(
				'name'  => $section[0],
				'steps' => $steps,
			);
		}
	}
	return $out;
}

/**
 * Captioned tables (core table blocks).
 *
 * @param string $content Block markup.
 * @return array[] Each: caption, columns (header cell texts).
 */
function lq_sc_tables( $content ) {
	// Attribute order varies: a style or id may come before or after class.
	preg_match_all( '#<figure\b(?=[^>]*\sclass="(?:[^"]*\s)?wp-block-table[\s"])[^>]*>(.*?)</figure>#su', (string) $content, $m );
	$out = array();
	foreach ( $m[1] as $table ) {
		$caption = preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#su', $table, $c ) ? lq_sc_text( $c[1] ) : '';
		preg_match_all( '#<th\b[^>]*>(.*?)</th>#su', $table, $th );
		$out[] = array(
			'caption' => $caption,
			'columns' => array_values( array_filter( array_map( 'lq_sc_text', $th[1] ) ) ),
		);
	}
	return $out;
}

/**
 * Quoted scripture: Qurʾān verses (figure.lq-verse) and hadith cards
 * (figure.lq-hadith-card), with the translation and source line shown.
 *
 * @param string $content Block markup.
 * @return array[] Each: kind (quran or hadith), arabic, translation, cite,
 *                 surah, surah_name, verses, notes (footnote IDs).
 */
function lq_sc_quotations( $content ) {
	preg_match_all( '#<figure class="(lq-verse|lq-hadith-card)">(.*?)</figure>#su', (string) $content, $m, PREG_SET_ORDER );
	$out = array();
	foreach ( $m as $fig ) {
		$arabic = preg_match( '#<p class="lq-(?:quran|hadith)"[^>]*>(.*?)</p>#su', $fig[2], $a ) ? lq_sc_text( $a[1] ) : '';
		if ( '' === $arabic ) {
			continue;
		}
		$cite_html = preg_match( '#<cite>(.*?)</cite>#su', $fig[2], $c ) ? $c[1] : '';
		$cite      = lq_sc_text( $cite_html );
		$row       = array(
			'kind'        => 'lq-verse' === $fig[1] ? 'quran' : 'hadith',
			'arabic'      => $arabic,
			'translation' => preg_match( '#<span class="lq-verse__translation">(.*?)</span>#su', $fig[2], $t ) ? lq_sc_text( $t[1] ) : '',
			'cite'        => $cite,
			'surah'       => 0,
			'surah_name'  => '',
			'verses'      => '',
			'notes'       => lq_sc_footnote_ids( $fig[2] ),
		);
		if ( 'quran' === $row['kind'] && preg_match( '/^(.*?)\s*(\d{1,3}):(\d+(?:\s*[–-]\s*\d+)?)/u', $cite, $v ) ) {
			$row['surah']      = (int) $v[2];
			$row['surah_name'] = trim( $v[1] );
			$row['verses']     = preg_replace( '/\s+/', '', $v[3] );
		}
		$out[] = $row;
	}
	return $out;
}

/**
 * A hadith collection and number as a footnote gives them
 * ("Ṣaḥīḥ al-Bukhārī, no. 35.") and the grading, when the note states one
 * ("graded ṣaḥīḥ by al-Albānī").
 *
 * @param string $note Footnote HTML.
 * @return array|null collection, number, grading, graded_by.
 */
function lq_sc_hadith_source( $note ) {
	$text = lq_sc_text( $note );
	if ( ! preg_match( '/^([^,.;]{3,80}),\s*(?:no\.|hadith|hadis|nombor)\s*(\d+)/iu', $text, $m ) ) {
		return null;
	}
	$out = array(
		'collection' => trim( $m[1] ),
		'number'     => $m[2],
		'grading'    => '',
		'graded_by'  => '',
	);
	if ( preg_match( '/(?:graded|dinilai)\s+(.{2,40}?)\s+(?:by|oleh)\s+([^.,;]+)/iu', $text, $g ) ) {
		$out['grading']   = trim( $g[1], '.,' );
		$out['graded_by'] = trim( $g[2] );
	}
	return $out;
}

/**
 * A footnote as a cited work, when it names a source: a link, or a
 * reference with a number, volume, page or edition. A note that is only
 * commentary returns null; it is part of the article text.
 *
 * @param string $note Footnote HTML.
 * @return array|null name, text, url.
 */
function lq_sc_citation( $note ) {
	$text = lq_sc_text( $note );
	if ( '' === $text ) {
		return null;
	}
	$url = '';
	if ( preg_match_all( '#href="(https?://[^"]+)"#', (string) $note, $links ) ) {
		$url = html_entity_decode( $links[1][0], ENT_QUOTES, 'UTF-8' );
	}
	$source = '' !== $url || preg_match( '/\b(?:no|vol|jld|p|pp|hlm|ed)\.\s*\d|\b(?:edition|edisi|API|Ibid)\b|\d{1,3}:\d{1,3}/u', $text );
	if ( ! $source ) {
		return null;
	}
	// The name runs to the first full stop that does not close an
	// abbreviation (no., vol., p., hlm., jil., ed.).
	$abbr = '(?<!\bno)(?<!\bvol)(?<!\bjil)(?<!\bjld)(?<!\bhlm)(?<!\bpp)(?<!\bp)(?<!\bed)';
	$name = preg_match( '/^(.{8,160}?)(?:' . $abbr . '\.\s|\s\(|$)/u', $text, $n ) ? $n[1] : mb_substr( $text, 0, 160 );
	return array(
		'name' => rtrim( $name, ' .,' ),
		'text' => $text,
		'url'  => $url,
	);
}

/**
 * The theme's own blocks in a page: [ name, attributes ] in order.
 *
 * @param string $content Block markup.
 * @return array[]
 */
function lq_sc_blocks( $content ) {
	preg_match_all( '#<!--\s+wp:lailatulqadar/([a-z-]+)(\s+(\{.*?\}))?\s+/?-->#su', (string) $content, $m, PREG_SET_ORDER );
	$out = array();
	foreach ( $m as $block ) {
		$attrs = isset( $block[3] ) ? json_decode( $block[3], true ) : array();
		$out[] = array( $block[1], is_array( $attrs ) ? $attrs : array() );
	}
	return $out;
}

/**
 * Whether the content shows a Ramadan date (not just the year): a
 * [lq_ramadan] date, the odd-nights table or the night window.
 *
 * @param string $content Block markup.
 * @return bool
 */
function lq_sc_shows_ramadan_dates( $content ) {
	if ( preg_match( '/\[lq_ramadan[^\]]*show=\\\\?["\']?(start|alt|eid|night)/', (string) $content ) ) {
		return true;
	}
	foreach ( lq_sc_blocks( $content ) as $block ) {
		if ( in_array( $block[0], array( 'odd-nights', 'night-window' ), true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * The text of the "In brief" box, which opens most guide pages.
 *
 * @param string $content Block markup.
 * @return string
 */
function lq_sc_brief( $content ) {
	if ( ! preg_match( '#<aside class="lq-brief"[^>]*>(.*?)</aside>#su', (string) $content, $m ) ) {
		return '';
	}
	return lq_sc_text( preg_replace( '#<p class="lq-brief__label">.*?</p>#su', '', $m[1] ) );
}

/**
 * Diagrams the articles draw in SVG (figure.lq-figure): title and caption.
 *
 * @param string $content Block markup.
 * @return array[] Each: title, caption.
 */
function lq_sc_figures( $content ) {
	preg_match_all( '#<figure class="lq-figure">(.*?)</figure>#su', (string) $content, $m );
	$out = array();
	foreach ( $m[1] as $fig ) {
		$out[] = array(
			'title'   => preg_match( '#<title\b[^>]*>(.*?)</title>#su', $fig, $t ) ? lq_sc_text( $t[1] ) : '',
			'caption' => preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#su', $fig, $c ) ? lq_sc_text( $c[1] ) : '',
		);
	}
	return $out;
}

/**
 * Merge the theme's nodes into another graph (Rank Math's), so the page
 * keeps one graph.
 *
 * A theme node whose @id is already in the graph fills only the
 * properties that node lacks, and its types are added. A theme node of a
 * kind the graph already holds under another @id (the WebSite, the
 * publisher, the WebPage, the breadcrumb, the article) is folded into that
 * node the same way, and every reference to the theme's @id is pointed at
 * the existing one. Any other node is added once.
 *
 * @param array $graph Existing nodes, keyed as the plugin keys them.
 * @param array $ours  Theme nodes.
 * @return array
 */
function lq_sc_merge_graph( $graph, $ours ) {
	$families = array(
		'website' => array( 'WebSite' ),
		'org'     => array( 'Organization', 'Person' ),
		'page'    => array( 'WebPage', 'ProfilePage', 'AboutPage', 'ContactPage', 'FAQPage', 'CollectionPage', 'SearchResultsPage', 'ItemPage', 'QAPage' ),
		'crumbs'  => array( 'BreadcrumbList' ),
		'article' => array( 'Article', 'BlogPosting', 'NewsArticle', 'ScholarlyArticle', 'TechArticle', 'Report' ),
	);
	$types_of = function ( $node ) {
		return isset( $node['@type'] ) ? (array) $node['@type'] : array();
	};
	$family_of = function ( $node, $suffix ) use ( $families, $types_of ) {
		// The theme's own frame nodes are told apart by their @id fragment.
		$frag = isset( $node['@id'] ) ? (string) substr( strrchr( (string) $node['@id'], '#' ), 1 ) : '';
		$map  = array(
			'website'      => 'website',
			'organization' => 'org',
			'webpage'      => 'page',
			'breadcrumb'   => 'crumbs',
			'article'      => 'article',
		);
		if ( $suffix ) {
			return isset( $map[ $frag ] ) ? $map[ $frag ] : '';
		}
		foreach ( $families as $family => $types ) {
			if ( array_intersect( $types_of( $node ), $types ) ) {
				return $family;
			}
		}
		return '';
	};

	// Index the existing graph by @id and by family.
	$by_id     = array();
	$by_family = array();
	foreach ( $graph as $k => $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		if ( ! empty( $node['@id'] ) ) {
			$by_id[ $node['@id'] ] = $k;
		}
		$family = $family_of( $node, false );
		if ( $family && ! isset( $by_family[ $family ] ) && ( 'org' !== $family || 'publisher' === $k || ! empty( $node['logo'] ) ) ) {
			$by_family[ $family ] = $k;
		}
	}

	// Theme @id => existing @id for the frame nodes the graph already has.
	$alias = array();
	foreach ( $ours as $node ) {
		if ( empty( $node['@id'] ) || isset( $by_id[ $node['@id'] ] ) ) {
			continue;
		}
		$family = $family_of( $node, true );
		if ( $family && isset( $by_family[ $family ] ) && ! empty( $graph[ $by_family[ $family ] ]['@id'] ) ) {
			$alias[ $node['@id'] ] = $graph[ $by_family[ $family ] ]['@id'];
		}
	}
	// The same fragment swap applies to links to other pages of the site
	// (the article in the other language): page.html#article becomes
	// page.html#richSnippet when that is how the plugin names the article.
	$swap = array();
	foreach ( $alias as $from => $to ) {
		$f = strrchr( $from, '#' );
		$t = strrchr( $to, '#' );
		if ( false !== $f && false !== $t && substr( $from, 0, -strlen( $f ) ) === substr( $to, 0, -strlen( $t ) ) ) {
			$swap[ $f ] = $t;
		}
	}
	$repoint = function ( $value ) use ( &$repoint, $alias, $swap ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $k => $v ) {
			$frag = '@id' === $k && is_string( $v ) ? strrchr( $v, '#' ) : false;
			if ( '@id' === $k && is_string( $v ) && isset( $alias[ $v ] ) ) {
				$value[ $k ] = $alias[ $v ];
			} elseif ( false !== $frag && isset( $swap[ $frag ] ) ) {
				$value[ $k ] = substr( $v, 0, -strlen( $frag ) ) . $swap[ $frag ];
			} elseif ( is_array( $v ) ) {
				$value[ $k ] = $repoint( $v );
			}
		}
		return $value;
	};

	foreach ( $ours as $node ) {
		$node = $repoint( $node );
		$id   = isset( $node['@id'] ) ? $node['@id'] : '';
		if ( '' !== $id && isset( $by_id[ $id ] ) ) {
			$k        = $by_id[ $id ];
			$existing = $graph[ $k ];
			$types    = array_values( array_unique( array_merge( $types_of( $existing ), $types_of( $node ) ) ) );
			foreach ( $node as $prop => $value ) {
				if ( ! isset( $existing[ $prop ] ) || '' === $existing[ $prop ] || array() === $existing[ $prop ] ) {
					$existing[ $prop ] = $value;
				}
			}
			if ( $types ) {
				$existing['@type'] = 1 === count( $types ) ? $types[0] : $types;
			}
			$graph[ $k ] = $existing;
			continue;
		}
		$k = 'lq_' . ( '' !== $id ? md5( $id ) : md5( serialize( $node ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		if ( ! isset( $graph[ $k ] ) ) {
			$graph[ $k ] = $node;
			if ( '' !== $id ) {
				$by_id[ $id ] = $k;
			}
		}
	}
	return $graph;
}
