<?php
/**
 * Footnote references: unique ids for a footnote cited more than once.
 *
 * @package Lailatulqadar
 * @since   1.19.1 (in inc/contents.php until 1.22.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * A footnote cited twice gets the same reference id twice from the core
 * footnotes format (`<sup data-fn="X"><a id="X-link">`), which makes the
 * page's ids repeat. The second and later references become X-link-2,
 * X-link-3 and so on; the note's back link keeps pointing to the first.
 * Pure.
 *
 * @param string $content Rendered text.
 * @return string
 */
function lailatulqadar_footnote_ref_ids( $content ) {
	if ( false === strpos( (string) $content, 'data-fn=' ) ) {
		return $content;
	}
	$seen = array();
	// A footnote reference: <sup data-fn=…><a … id="<note>-link">; group 2 is the id.
	return (string) preg_replace_callback(
		'#(<sup\b[^>]*\sdata-fn="[^"]*"[^>]*>\s*<a\b[^>]*\sid=")([^"]+-link)(")#i',
		static function ( $m ) use ( &$seen ) {
			$id          = $m[2];
			$seen[ $id ] = isset( $seen[ $id ] ) ? $seen[ $id ] + 1 : 1;
			return 1 === $seen[ $id ] ? $m[0] : $m[1] . $id . '-' . $seen[ $id ] . $m[3];
		},
		(string) $content
	);
}
add_filter( 'the_content', 'lailatulqadar_footnote_ref_ids', 12 );
