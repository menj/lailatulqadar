<?php
/**
 * Page parts for the guide pages (the site has no blog, inc/no-blog.php):
 * the details line under the title (published and updated dates, the last
 * review, the reading time), the In brief box for a page whose text has
 * none, the contents list, the sources block and the previous and next row
 * of the reading path. Every string reads in English or in Malay, as the
 * page does. The author's name stays in the structured data only.
 *
 * A guide page is a page of the reading path (the `path` set in
 * inc/data/pages.json). The language homes, About, Contact and the Sitemap
 * take none of these parts.
 *
 * Every Gregorian date shown carries its Hijri equivalent (SSOT.md, Dates).
 * The conversion uses PHP's intl extension and the Umm al-Qura calendar; on
 * a server without intl the dates are left out rather than shown without
 * their Hijri date.
 *
 * Filters: lailatulqadar_is_guide_page (bool, page ID),
 * lailatulqadar_contents_min (int, default 3).
 *
 * @package Lailatulqadar
 * @since   1.18.10
 */

defined( 'ABSPATH' ) || exit;

/** Post meta: the In brief points, one a line, for a page whose text has no In brief box. */
const LAILATULQADAR_META_BRIEF = '_lq_brief';

/** Post meta: the date the page was last checked against its sources (Y-m-d). */
const LAILATULQADAR_META_REVIEWED = '_lq_reviewed';

/**
 * The English or the Malay string, by the language of the page.
 *
 * @param string      $en   English.
 * @param string      $ms   Malay.
 * @param string|null $role primary or secondary; defaults to the current language.
 * @return string
 */
function lailatulqadar_t( $en, $ms, $role = null ) {
	$role = $role ? $role : lq_current_role();
	return 'secondary' === $role ? $ms : $en;
}

/**
 * Whether a page is a guide page of the reading path.
 *
 * @param int $id Page ID.
 * @return bool
 */
function lailatulqadar_is_guide_page( $id ) {
	$key  = (string) lq_page_key_of( $id );
	$defs = lq_page_definitions();
	$is   = '' !== $key && isset( $defs[ $key ] ) && in_array( 'path', (array) $defs[ $key ]['sets'], true );
	return (bool) apply_filters( 'lailatulqadar_is_guide_page', $is, (int) $id );
}

/**
 * The guide page being viewed, or 0.
 *
 * @return int
 */
function lailatulqadar_current_guide_page() {
	if ( ! is_page() || lq_is_home() ) {
		return 0;
	}
	$id = (int) get_queried_object_id();
	return $id && lailatulqadar_is_guide_page( $id ) ? $id : 0;
}

/**
 * Hijri date of a Gregorian day under the Umm al-Qura calendar, as
 * "25 Jumada al-Ula 1448 AH" (Malay "25 Jamadilawal 1448 H"), or '' when
 * the intl extension is missing.
 *
 * @param string      $ymd  Date, Y-m-d.
 * @param string|null $role primary or secondary.
 * @return string
 */
function lailatulqadar_hijri_date( $ymd, $role = null ) {
	// A date as Y-m-d.
	if ( ! class_exists( 'IntlDateFormatter' ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $ymd ) ) {
		return '';
	}
	$format = new IntlDateFormatter( 'en_US@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'UTC', IntlDateFormatter::TRADITIONAL, 'd|M|y' );
	$parts  = explode( '|', (string) $format->format( strtotime( $ymd . ' 12:00:00 UTC' ) ) );
	if ( 3 !== count( $parts ) || (int) $parts[1] < 1 || (int) $parts[1] > 12 ) {
		return '';
	}
	$en = array( 'Muharram', 'Safar', 'Rabiʿ al-Awwal', 'Rabiʿ al-Thani', 'Jumada al-Ula', 'Jumada al-Akhirah', 'Rajab', 'Shaʿban', 'Ramadan', 'Shawwal', 'Dhu al-Qaʿdah', 'Dhu al-Hijjah' );
	$ms = array( 'Muharam', 'Safar', 'Rabiulawal', 'Rabiulakhir', 'Jamadilawal', 'Jamadilakhir', 'Rejab', 'Syaaban', 'Ramadan', 'Syawal', 'Zulkaedah', 'Zulhijah' );
	$m  = (int) $parts[1] - 1;
	return (int) $parts[0] . ' ' . lailatulqadar_t( $en[ $m ], $ms[ $m ], $role ) . ' ' . (int) $parts[2] . ' ' . lailatulqadar_t( 'AH', 'H', $role );
}

/**
 * A Gregorian date with its Hijri equivalent: "5 November 2026
 * (25 Jumada al-Ula 1448 AH)". '' when the Hijri date cannot be worked out.
 *
 * @param string      $ymd  Date, Y-m-d.
 * @param string|null $role primary or secondary.
 * @return string
 */
function lailatulqadar_date_label( $ymd, $role = null ) {
	$hijri = lailatulqadar_hijri_date( $ymd, $role );
	if ( '' === $hijri ) {
		return '';
	}
	$t  = strtotime( $ymd . ' 12:00:00 UTC' );
	$m  = (int) gmdate( 'n', $t ) - 1;
	$en = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
	$ms = array( 'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember' );
	return gmdate( 'j', $t ) . ' ' . lailatulqadar_t( $en[ $m ], $ms[ $m ], $role ) . ' ' . gmdate( 'Y', $t ) . ' (' . $hijri . ')';
}

/**
 * Minutes of reading at 200 words a minute, at least one.
 *
 * @param int $id Page ID.
 * @return int
 */
function lailatulqadar_reading_minutes( $id ) {
	return max( 1, (int) round( lq_word_count( $id ) / 200 ) );
}

/**
 * The date a page was last reviewed, Y-m-d, or ''.
 *
 * @param int $id Page ID.
 * @return string
 */
function lailatulqadar_reviewed_date( $id ) {
	$date = (string) get_post_meta( $id, LAILATULQADAR_META_REVIEWED, true );
	// A date as Y-m-d.
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
}

/**
 * The items of the details line: kind => array( label, text, datetime ).
 *
 * @param int $id Page ID.
 * @return array<string,array{0:string,1:string,2:string}>
 */
function lailatulqadar_page_details( $id ) {
	$role      = lq_role_of( $id );
	$items     = array();
	$published = (string) get_post_time( 'Y-m-d', false, $id );
	$modified  = (string) get_post_modified_time( 'Y-m-d', false, $id );
	$label     = lailatulqadar_date_label( $published, $role );
	if ( '' !== $label ) {
		$items['published'] = array( lailatulqadar_t( 'Published', 'Diterbitkan', $role ), $label, $published );
		if ( $modified > $published ) {
			$items['updated'] = array( lailatulqadar_t( 'Updated', 'Dikemas kini', $role ), lailatulqadar_date_label( $modified, $role ), $modified );
		}
	}
	$reviewed = lailatulqadar_reviewed_date( $id );
	$label    = '' === $reviewed ? '' : lailatulqadar_date_label( $reviewed, $role );
	if ( '' !== $label ) {
		$items['reviewed'] = array( lailatulqadar_t( 'Last reviewed', 'Kali terakhir disemak', $role ), $label, $reviewed );
	}
	$minutes          = lailatulqadar_reading_minutes( $id );
	$items['reading'] = array(
		lailatulqadar_t( 'Reading time', 'Masa bacaan', $role ),
		sprintf( lailatulqadar_t( 1 === $minutes ? '%d minute' : '%d minutes', '%d minit', $role ), $minutes ),
		'',
	);
	return $items;
}

/**
 * In brief points a page carries in its meta box, one a line, at most six.
 *
 * @param int $id Page ID.
 * @return string[]
 */
function lailatulqadar_brief_points( $id ) {
	// One point a line, whatever the line endings.
	$lines = preg_split( '/\r\n|\r|\n/', (string) get_post_meta( $id, LAILATULQADAR_META_BRIEF, true ) );
	return array_slice( array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) ), 0, 6 );
}

/**
 * The In brief points the template shows: the meta box points, only when the
 * page's own text has no In brief box.
 *
 * @param int $id Page ID.
 * @return string[]
 */
function lailatulqadar_template_brief( $id ) {
	if ( '' !== lq_sc_brief( (string) get_post_field( 'post_content', $id ) ) ) {
		return array();
	}
	return lailatulqadar_brief_points( $id );
}

/**
 * The In brief box markup, the same as the box the articles carry.
 *
 * @param string[] $points Points.
 * @param string   $role   primary or secondary.
 * @return string
 */
function lailatulqadar_brief_html( $points, $role ) {
	$label = lailatulqadar_t( 'In brief', 'Secara ringkas', $role );
	$items = '';
	foreach ( $points as $point ) {
		$items .= '<li>' . esc_html( $point ) . '</li>';
	}
	return '<aside class="lq-brief" aria-label="' . esc_attr( $label ) . '"><p class="lq-brief__label">' . esc_html( $label ) . '</p><ul>' . $items . '</ul></aside>';
}

/**
 * An id for a heading from its text, unique on the page. With $taken, the
 * text is an id a heading already carries: it is kept and recorded when it
 * is free, and given a number when an earlier heading holds it already.
 *
 * @param string $text  Heading text, or the id when $taken.
 * @param bool   $reset Start a new page.
 * @param bool   $taken Record an existing id.
 * @return string
 */
function lailatulqadar_heading_id( $text, $reset = false, $taken = false ) {
	static $used = array();
	if ( $reset ) {
		$used = array();
		return '';
	}
	if ( $taken ) {
		$base = (string) $text;
	} else {
		$base = sanitize_title( remove_accents( wp_strip_all_tags( $text ) ) );
		$base = '' === $base ? 'section' : $base;
	}
	$id   = $base;
	for ( $n = 2; isset( $used[ $id ] ); $n++ ) {
		$id = $base . '-' . $n;
	}
	$used[ $id ] = true;
	return $id;
}

/**
 * H2 headings in a guide page's rendered text get an id, so the contents
 * list can link to them. A heading with an id keeps it.
 *
 * @param string $html Rendered heading block.
 * @param array  $block Parsed block.
 * @return string
 */
function lailatulqadar_heading_anchor( $html, $block ) {
	$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
	if ( 2 !== $level || ! lailatulqadar_current_guide_page() || ! in_the_loop() && ! doing_filter( 'the_content' ) ) {
		return $html;
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	if ( ! $tags->next_tag( 'h2' ) ) {
		return $html;
	}
	$own  = $tags->get_attribute( 'id' );
	$own  = is_string( $own ) && '' !== $own ? $own : '';
	$id   = '' !== $own ? lailatulqadar_heading_id( $own, false, true ) : lailatulqadar_heading_id( $html );
	if ( $id === $own ) {
		return $html;
	}
	$tags->set_attribute( 'id', $id );
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/heading', 'lailatulqadar_heading_anchor', 10, 2 );

/**
 * The contents list for rendered text: the H2 headings that carry an id.
 * Pure: everything it needs is passed in.
 *
 * @param string $html Rendered text.
 * @param int    $min  Fewest headings for a list.
 * @param string $role primary or secondary.
 * @return string '' below the minimum.
 */
function lailatulqadar_contents_html( $html, $min, $role ) {
	// Each H2 that carries an id: group 1 the id, group 2 its inner HTML.
	if ( ! preg_match_all( '#<h2\b[^>]*\sid="([^"]+)"[^>]*>(.*?)</h2>#is', $html, $m, PREG_SET_ORDER ) || count( $m ) < $min ) {
		return '';
	}
	$title = lailatulqadar_t( 'On this page', 'Kandungan halaman', $role );
	$label = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'lq-contents-title-' ) : 'lq-contents-title';
	$items = '';
	foreach ( $m as $heading ) {
		// Runs of white space become one space.
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $heading[2] ) ) );
		if ( '' !== $text ) {
			$items .= '<li><a href="#' . esc_attr( $heading[1] ) . '">' . esc_html( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) . '</a></li>';
		}
	}
	return '<nav class="lq-contents" aria-labelledby="' . esc_attr( $label ) . '"><p class="lq-contents__title" id="' . esc_attr( $label ) . '">' . esc_html( $title ) . '</p><ol class="lq-contents__list">' . $items . '</ol></nav>';
}

/**
 * Put the contents list into a guide page's text, after the In brief box
 * when the text opens with one, else at the top, unless the text places
 * its own list (the [auto_toc] shortcode or the Contents block). Priority 30: after the
 * contextual links (25), which never link inside a nav.
 *
 * @param string $content Rendered text.
 * @return string
 */
function lailatulqadar_contents_filter( $content ) {
	$id = lailatulqadar_current_guide_page();
	if ( ! $id || is_admin() || is_feed() || ! is_main_query() || (int) get_the_ID() !== $id || doing_filter( 'get_the_excerpt' ) ) {
		return $content;
	}
	// A list placed by hand (inc/contents.php, at 29) takes the place of this one.
	if ( false !== strpos( $content, 'lq-contents--placed' ) ) {
		return $content;
	}
	$nav = lailatulqadar_contents_html( $content, max( 1, (int) apply_filters( 'lailatulqadar_contents_min', 3 ) ), lq_role_of( $id ) );
	if ( '' === $nav ) {
		return $content;
	}
	$at  = strpos( $content, '<aside class="lq-brief"' );
	$end = false === $at ? false : strpos( $content, '</aside>', $at );
	if ( false !== $end ) {
		$end += strlen( '</aside>' );
		return substr( $content, 0, $end ) . "\n" . $nav . substr( $content, $end );
	}
	return $nav . "\n" . $content;
}
add_filter( 'the_content', 'lailatulqadar_contents_filter', 30 );

/**
 * The sources a guide page shows and its Article cites: the Qurʾān verses and
 * hadith it quotes, and the footnotes that name a source. The schema graph
 * reads the same lists (inc/schema.php), so what the page shows and what
 * the structured data cites never part.
 *
 * @param int $id Page ID.
 * Each quotation keeps its number (#quote-N, as the Quotation nodes) and
 * the note that gives its source. A footnote that names a work already
 * listed is left out here and in the citation, so every #note-N the
 * structured data cites is on the page.
 *
 * @return array{dalil:array<int,array{n:int,cite:string,kind:string,note:string}>,refs:array<int,array{n:int,note:string,name:string,text:string,url:string}>}
 */
function lailatulqadar_page_sources( $id ) {
	$out   = array(
		'dalil' => array(),
		'refs'  => array(),
	);
	$post  = get_post( $id );
	$text  = $post ? (string) $post->post_content : '';
	$quote = lq_sc_quotations( $text );
	foreach ( $quote as $i => $q ) {
		if ( '' !== $q['cite'] ) {
			$out['dalil'][] = array(
				'n'    => $i + 1,
				'cite' => $q['cite'],
				'kind' => $q['kind'],
				'note' => $q['notes'] ? (string) $q['notes'][0] : '',
			);
		}
	}
	$seen = array();
	foreach ( lq_page_footnotes( $id ) as $i => $note ) {
		if ( empty( $note['id'] ) || ! isset( $note['content'] ) ) {
			continue;
		}
		$cite = lq_sc_citation( $note['content'] );
		if ( $cite && ! isset( $seen[ $cite['name'] ] ) ) {
			$seen[ $cite['name'] ] = true;
			$out['refs'][] = array(
				'n'    => $i + 1,
				'note' => (string) $note['id'],
				'name' => $cite['name'],
				'text' => $cite['text'],
				'url'  => $cite['url'],
			);
		}
	}
	return $out;
}

/**
 * Tables on guide pages (core tables and the odd-nights dates table) take
 * the guide's table style, and each is a
 * focusable region named by its caption, so a table wider than a phone can
 * be scrolled from the keyboard.
 *
 * @param string $html Rendered table block.
 * @return string
 */
function lailatulqadar_table_parts( $html ) {
	if ( ! lailatulqadar_current_guide_page() ) {
		return $html;
	}
	// The table's caption, without footnote markers, as its accessible name.
	$label = preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#s', $html, $m ) ? trim( wp_strip_all_tags( preg_replace( '#<sup\b.*?</sup>#s', '', $m[1] ) ) ) : '';
	$tags  = new WP_HTML_Tag_Processor( $html );
	if ( ! $tags->next_tag( 'figure' ) ) {
		return $html;
	}
	$tags->add_class( 'lq-table' );
	if ( null === $tags->get_attribute( 'tabindex' ) ) {
		$tags->set_attribute( 'tabindex', '0' );
		$tags->set_attribute( 'role', 'region' );
		$tags->set_attribute( 'aria-label', '' !== $label ? $label : lailatulqadar_t( 'Table', 'Jadual' ) );
	}
	return $tags->get_updated_html();
}
add_filter( 'render_block_core/table', 'lailatulqadar_table_parts' );
// The odd-nights dates table is drawn by its own block: the same rules apply.
add_filter( 'render_block_lailatulqadar/odd-nights', 'lailatulqadar_table_parts' );

/**
 * The previous and next published pages of the reading path.
 *
 * @param int $id Page ID.
 * @return array{prev:?array,next:?array}
 */
function lailatulqadar_path_neighbours( $id ) {
	$items = array_values( lq_menu_items( 'path' ) );
	$out   = array(
		'prev' => null,
		'next' => null,
	);
	foreach ( $items as $i => $item ) {
		if ( (int) $item['id'] === (int) $id ) {
			$out['prev'] = $i > 0 ? $items[ $i - 1 ] : null;
			$out['next'] = isset( $items[ $i + 1 ] ) ? $items[ $i + 1 ] : null;
			break;
		}
	}
	return $out;
}

/**
 * The "Guide page" box on the page editor: In brief points and the review date.
 */
function lailatulqadar_add_page_parts_box() {
	add_meta_box( 'lailatulqadar-page-parts', __( 'Guide page', 'lailatulqadar' ), 'lailatulqadar_page_parts_box', 'page', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'lailatulqadar_add_page_parts_box' );

/**
 * Print the box.
 *
 * @param WP_Post $post Page being edited.
 */
function lailatulqadar_page_parts_box( $post ) {
	wp_nonce_field( 'lailatulqadar_page_parts', 'lailatulqadar_page_parts_nonce' );
	?>
	<p><label for="lailatulqadar-reviewed"><?php esc_html_e( 'Last reviewed against its sources', 'lailatulqadar' ); ?></label><br>
	<input type="date" id="lailatulqadar-reviewed" name="lailatulqadar_reviewed" value="<?php echo esc_attr( lailatulqadar_reviewed_date( $post->ID ) ); ?>"></p>
	<p><label for="lailatulqadar-brief"><?php esc_html_e( 'In brief, one point a line (only for a page whose text has no In brief box)', 'lailatulqadar' ); ?></label>
	<textarea id="lailatulqadar-brief" name="lailatulqadar_brief" rows="5" class="widefat"><?php echo esc_textarea( (string) get_post_meta( $post->ID, LAILATULQADAR_META_BRIEF, true ) ); ?></textarea></p>
	<?php
}

/**
 * Save the box.
 *
 * @param int $post_id Page ID.
 */
function lailatulqadar_save_page_parts_box( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['lailatulqadar_page_parts_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lailatulqadar_page_parts_nonce'] ) ), 'lailatulqadar_page_parts' ) ) {
		return;
	}
	$date  = sanitize_text_field( wp_unslash( $_POST['lailatulqadar_reviewed'] ?? '' ) );
	$brief = sanitize_textarea_field( wp_unslash( $_POST['lailatulqadar_brief'] ?? '' ) );
	// A date as Y-m-d; checkdate() then rejects days that do not exist.
	$valid = preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $d ) && checkdate( (int) $d[2], (int) $d[3], (int) $d[1] );
	if ( $valid ) {
		update_post_meta( $post_id, LAILATULQADAR_META_REVIEWED, $date );
	} else {
		delete_post_meta( $post_id, LAILATULQADAR_META_REVIEWED );
	}
	if ( '' === trim( $brief ) ) {
		delete_post_meta( $post_id, LAILATULQADAR_META_BRIEF );
	} else {
		update_post_meta( $post_id, LAILATULQADAR_META_BRIEF, $brief );
	}
}
add_action( 'save_post_page', 'lailatulqadar_save_page_parts_box' );

/**
 * The page parts stylesheet, on guide pages only.
 */
function lailatulqadar_page_parts_assets() {
	if ( lailatulqadar_current_guide_page() ) {
		wp_enqueue_style( 'lailatulqadar-page-parts', LQ_URI . '/css/page-parts.css', array( 'lailatulqadar-front' ), LQ_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_page_parts_assets', 20 );
