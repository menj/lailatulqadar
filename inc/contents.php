<?php
/**
 * The contents list placed by hand: the [auto_toc] shortcode and the
 * lailatulqadar/contents block, on any page or post.
 *
 * Adapted from "WordPress Table-of-Contents Shortcode Done Properly" by
 * Ikram Hawramani (MIT licence, notice below). The shortcode keeps its name
 * and its `tags` and `title` attributes, so text written for that plugin
 * works unchanged. The theme's version differs in four ways:
 *
 * - The shortcode and the block leave a marker; the list is built on
 *   `the_content` at 29, once the whole text is rendered. The plugin built
 *   it at 99, after shortcodes had already run (11), so it came out empty.
 * - Headings keep any id they have; the others get one, unique on the page.
 *   The text is changed only where an id is added, so Arabic and other
 *   UTF-8 text passes through untouched.
 * - The nested lists always close, at any depth.
 * - The list reads in English or Malay as the page does, and uses the
 *   theme's contents style (css/page-parts.css).
 *
 * When a page's text carries a placed list, the automatic contents list of
 * the guide pages (inc/page-parts.php) is left out.
 *
 * Copyright (c) 2025 Ikram Hawramani. Permission is hereby granted, free of
 * charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions: The above copyright notice and
 * this permission notice shall be included in all copies or substantial
 * portions of the Software. THE SOFTWARE IS PROVIDED "AS IS", WITHOUT
 * WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package Lailatulqadar
 * @since   1.19.0
 */

defined( 'ABSPATH' ) || exit;

/** The marker the shortcode and the block leave in the text. */
const LAILATULQADAR_TOC_MARK = 'lailatulqadar-contents:';

/**
 * Heading levels from "h2,h3" (or "2,3", or an array), sorted; h2 and h3
 * when none is valid.
 *
 * @param string|array $tags Levels.
 * @return int[]
 */
function lailatulqadar_toc_levels( $tags ) {
	$parts  = is_array( $tags ) ? $tags : explode( ',', (string) $tags );
	$levels = array();
	foreach ( $parts as $part ) {
		$n = (int) ltrim( strtolower( trim( (string) $part ) ), 'h' );
		if ( $n >= 1 && $n <= 6 ) {
			$levels[ $n ] = $n;
		}
	}
	sort( $levels );
	return $levels ? array_values( $levels ) : array( 2, 3 );
}

/**
 * The marker for one placed list.
 *
 * @param int[]       $levels Heading levels.
 * @param string|null $title  Title; null for the default ("On this page"), '' for none.
 * @return string
 */
function lailatulqadar_toc_mark( $levels, $title ) {
	$data = wp_json_encode(
		array(
			'levels' => array_values( $levels ),
			'title'  => $title,
		)
	);
	return '<!--' . LAILATULQADAR_TOC_MARK . base64_encode( (string) $data ) . '-->'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- keeps "-->" in a title from ending the comment.
}

/**
 * [auto_toc tags="h2,h3" title="In this article"]. An empty title leaves
 * the title out.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function lailatulqadar_toc_shortcode( $atts ) {
	$atts  = (array) $atts;
	$tags  = isset( $atts['tags'] ) ? (string) $atts['tags'] : 'h2,h3';
	$title = array_key_exists( 'title', $atts ) ? (string) $atts['title'] : null;
	return lailatulqadar_toc_mark( lailatulqadar_toc_levels( $tags ), $title );
}

/**
 * Registers the shortcode on init, after plugins have loaded, so the theme's
 * version replaces the plugin's when both are present.
 */
function lailatulqadar_toc_register() {
	add_shortcode( 'auto_toc', 'lailatulqadar_toc_shortcode' );
}
add_action( 'init', 'lailatulqadar_toc_register', 20 );

/**
 * Plain text of a heading's inner HTML: footnote markers and tags removed,
 * spaces folded, entities decoded.
 *
 * @param string $html Inner HTML.
 * @return string
 */
function lailatulqadar_toc_text( $html ) {
	$html = preg_replace( '#<sup\b[^>]*>.*?</sup>#is', '', (string) $html );
	$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $html ) ) );
	return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Gives an id to every heading of the given levels that has none. Ids
 * already in the text are kept and never given twice. Pure.
 *
 * @param string $html   Rendered text.
 * @param int[]  $levels Heading levels.
 * @return string
 */
function lailatulqadar_toc_add_ids( $html, $levels ) {
	if ( ! $levels ) {
		return $html;
	}
	$used = array();
	if ( preg_match_all( '/\sid\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) {
		$used = array_fill_keys( $m[1], true );
	}
	$pattern = '#<h([' . implode( '', $levels ) . '])\b([^>]*)>(.*?)</h\1>#is';
	return (string) preg_replace_callback(
		$pattern,
		static function ( $h ) use ( &$used ) {
			if ( preg_match( '/\sid\s*=/i', $h[2] ) ) {
				return $h[0];
			}
			$base = sanitize_title( remove_accents( lailatulqadar_toc_text( $h[3] ) ) );
			$base = '' === $base ? 'section' : $base;
			$id   = $base;
			for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
				$id = $base . '-' . $n;
			}
			$used[ $id ] = true;
			return '<h' . $h[1] . ' id="' . esc_attr( $id ) . '"' . $h[2] . '>' . $h[3] . '</h' . $h[1] . '>';
		},
		$html
	);
}

/**
 * The headings of the given levels that carry an id, in page order, as
 * arrays of level, id and text. Pure.
 *
 * @param string $html   Rendered text.
 * @param int[]  $levels Heading levels.
 * @return array[]
 */
function lailatulqadar_toc_items( $html, $levels ) {
	$items = array();
	if ( ! $levels || ! preg_match_all( '#<h([' . implode( '', $levels ) . '])\b([^>]*)>(.*?)</h\1>#is', $html, $m, PREG_SET_ORDER ) ) {
		return $items;
	}
	foreach ( $m as $h ) {
		$text = lailatulqadar_toc_text( $h[3] );
		if ( '' !== $text && preg_match( '/\sid\s*=\s*["\']([^"\']+)["\']/i', $h[2], $id ) ) {
			$items[] = array(
				'level' => (int) $h[1],
				'id'    => $id[1],
				'text'  => $text,
			);
		}
	}
	return $items;
}

/**
 * The nested list for the items. A heading deeper than the one before opens
 * a list inside it; a shallower one closes lists back to its level; a level
 * between two open lists joins the deeper one. Every list closes. Pure.
 *
 * @param array[] $items From lailatulqadar_toc_items().
 * @return string
 */
function lailatulqadar_toc_list( $items ) {
	if ( ! $items ) {
		return '';
	}
	$out   = '<ol class="lq-contents__list">';
	$stack = array(); // Levels of the open lists, outermost first.
	foreach ( $items as $item ) {
		$level = $item['level'];
		if ( ! $stack ) {
			$stack[] = $level;
		} elseif ( $level > end( $stack ) ) {
			$out    .= '<ol>';
			$stack[] = $level;
		} else {
			$out .= '</li>';
			while ( count( $stack ) > 1 && $level < end( $stack ) ) {
				if ( $level > $stack[ count( $stack ) - 2 ] ) {
					$stack[ count( $stack ) - 1 ] = $level;
					break;
				}
				array_pop( $stack );
				$out .= '</ol></li>';
			}
		}
		$out .= '<li><a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['text'] ) . '</a>';
	}
	$out .= '</li>' . str_repeat( '</ol></li>', count( $stack ) - 1 ) . '</ol>';
	return $out;
}

/**
 * The placed contents list, or '' when the text has no heading of its
 * levels.
 *
 * @param string      $html   Rendered text, with heading ids.
 * @param int[]       $levels Heading levels.
 * @param string|null $title  Title; null for the default, '' for none.
 * @param string|null $role   primary or secondary; defaults to the current language.
 * @return string
 */
function lailatulqadar_toc_html( $html, $levels, $title = null, $role = null ) {
	$list = lailatulqadar_toc_list( lailatulqadar_toc_items( $html, $levels ) );
	if ( '' === $list ) {
		return '';
	}
	$default = lailatulqadar_t( 'On this page', 'Kandungan halaman', $role );
	if ( '' === $title ) {
		return '<nav class="lq-contents lq-contents--placed" aria-label="' . esc_attr( $default ) . '">' . $list . '</nav>';
	}
	$label = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'lq-contents-title-' ) : 'lq-contents-title';
	$title = null === $title ? $default : $title;
	return '<nav class="lq-contents lq-contents--placed" aria-labelledby="' . esc_attr( $label ) . '"><p class="lq-contents__title" id="' . esc_attr( $label ) . '">' . esc_html( $title ) . '</p>' . $list . '</nav>';
}

/**
 * Replaces each marker with its list. In feeds and excerpts, where in-page
 * links lead nowhere, the markers are removed. Priority 29: after the
 * contextual links (25), before the automatic contents list (30).
 *
 * @param string $content Rendered text.
 * @return string
 */
function lailatulqadar_toc_filter( $content ) {
	if ( false === strpos( (string) $content, '<!--' . LAILATULQADAR_TOC_MARK ) ) {
		return $content;
	}
	$mark = '<!--' . preg_quote( LAILATULQADAR_TOC_MARK, '#' ) . '([A-Za-z0-9+/=]*)-->';
	// wpautop wraps a shortcode on its own line in a paragraph.
	$content = (string) preg_replace( '#<p>\s*(' . $mark . ')\s*</p>#', '$1', $content );
	if ( is_feed() || doing_filter( 'get_the_excerpt' ) ) {
		return (string) preg_replace( '#' . $mark . '#', '', $content );
	}
	preg_match_all( '#' . $mark . '#', $content, $found );
	$marks = array();
	$all   = array();
	foreach ( array_unique( $found[1] ) as $code ) {
		$data           = json_decode( (string) base64_decode( $code, true ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data           = is_array( $data ) ? $data : array();
		$levels         = lailatulqadar_toc_levels( isset( $data['levels'] ) ? (array) $data['levels'] : array() );
		$title          = isset( $data['title'] ) && is_string( $data['title'] ) ? $data['title'] : null;
		$marks[ $code ] = array( $levels, $title );
		$all            = array_merge( $all, $levels );
	}
	$content = lailatulqadar_toc_add_ids( $content, array_values( array_unique( $all ) ) );
	$id      = (int) get_the_ID();
	$role    = $id && function_exists( 'lq_role_of' ) ? lq_role_of( $id ) : null;
	return (string) preg_replace_callback(
		'#' . $mark . '#',
		static function ( $m ) use ( $marks, $content, $role ) {
			list( $levels, $title ) = $marks[ $m[1] ];
			return lailatulqadar_toc_html( $content, $levels, $title, $role );
		},
		$content
	);
}
add_filter( 'the_content', 'lailatulqadar_toc_filter', 29 );

/**
 * Whether a post's text places a contents list.
 *
 * @param int|WP_Post|null $post Post.
 * @return bool
 */
function lailatulqadar_toc_placed( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	return has_shortcode( $post->post_content, 'auto_toc' ) || has_block( 'lailatulqadar/contents', $post );
}

/**
 * The contents style on a page or post that places a list; guide pages
 * load it already (inc/page-parts.php).
 */
function lailatulqadar_toc_assets() {
	if ( is_singular() && lailatulqadar_toc_placed( get_queried_object_id() ) ) {
		wp_enqueue_style( 'lailatulqadar-page-parts', LQ_URI . '/css/page-parts.css', array( 'lailatulqadar-front' ), LQ_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_toc_assets', 20 );
