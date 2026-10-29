<?php
/**
 * First-run setup: creates the guide pages in both languages, links
 * the two languages to each other, places the Malay pages beneath the
 * Malay home at /ms/ and sets the static front page.
 *
 * Runs on the first admin visit after activation or after an update, and
 * on demand from Appearance > Theme Options > Tools. Every page whose
 * article ships in content/<lang>/<key>.json is seeded and published; a
 * page the theme seeded and nobody has edited takes the new version when
 * the shipped article changes; an editor's changes are never overwritten.
 * Obsolete duplicates the theme created go to the trash. The routine is
 * idempotent: a second run with nothing changed does nothing.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flag setup on theme activation.
 */
function lq_flag_setup() {
	update_option( 'lailatulqadar_needs_setup', 1, false );
}
add_action( 'after_switch_theme', 'lq_flag_setup' );

/**
 * Run setup on the first admin visit by an administrator after the theme
 * is activated, and again after an update (a new LQ_VERSION). Only an
 * admin request with manage_options runs it; a front-end request never
 * writes.
 */
function lq_maybe_run_setup() {
	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! get_option( 'lailatulqadar_needs_setup' ) && LQ_VERSION === get_option( 'lailatulqadar_setup_version' ) ) {
		return;
	}
	if ( ! lq_page_definitions() ) {
		return; // inc/data/pages.json cannot be read (an update may still be unpacking): try on the next visit.
	}
	$report = lq_run_setup_locked();
	if ( null === $report ) {
		return; // Another request is running it; the flag stays for the next visit.
	}
	delete_option( 'lailatulqadar_needs_setup' );
	set_transient( 'lailatulqadar_setup_report', $report, MINUTE_IN_SECONDS * 10 );
}
add_action( 'admin_init', 'lq_maybe_run_setup' );

/**
 * Run setup under a lock, so two requests never run it at once.
 *
 * The lock is one INSERT IGNORE into the options table, as WordPress's own
 * upgrader lock does: the database lets only one request insert the row, so
 * two requests that arrive together cannot both take it. A lock older than
 * five minutes belongs to a run that died and is taken over.
 *
 * @return array|null The report, or null when another run holds the lock.
 */
function lq_run_setup_locked() {
	global $wpdb;
	$lock = 'lailatulqadar_setup_lock';
	$now  = time();
	$got  = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES ( %s, %s, 'off' ) /* LOCK */", $lock, $now ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( ! $got ) {
		$since = (int) $wpdb->get_var( $wpdb->prepare( "SELECT `option_value` FROM `$wpdb->options` WHERE `option_name` = %s", $lock ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $since > $now - 5 * MINUTE_IN_SECONDS ) {
			return null;
		}
		update_option( $lock, $now, false );
	}
	try {
		return lq_run_setup();
	} finally {
		delete_option( $lock );
	}
}

/**
 * Find or create one guide page.
 *
 * @param string $key      Page key.
 * @param string $role     primary or secondary.
 * @param array  $map      Stored page map.
 * @param array  $report   Report, passed by reference.
 * @return int Page ID, or 0 on failure.
 */
function lq_ensure_page( $key, $role, $map, &$report ) {
	$def = lq_page_definitions()[ $key ][ $role ];

	if ( ! empty( $map[ $role ][ $key ] ) ) {
		$status = get_post_status( (int) $map[ $role ][ $key ] );
		if ( $status && 'trash' !== $status ) {
			return (int) $map[ $role ][ $key ];
		}
	}

	$parent   = ( 'secondary' === $role && 'home' !== $key && ! empty( $map['secondary']['home'] ) ) ? (int) $map['secondary']['home'] : 0;
	$existing = $parent ? get_page_by_path( get_page_uri( $parent ) . '/' . $def['slug'] ) : null;
	$existing = $existing ? $existing : get_page_by_path( $def['slug'] );
	if ( $existing && 'trash' !== $existing->post_status && lq_page_is_claimable( (int) $existing->ID, $role, $map ) ) {
		$report['reused'][] = $def['title'];
		return (int) $existing->ID;
	}

	$pattern = 'secondary' === $role ? 'lailatulqadar/front-ms' : 'lailatulqadar/front-en';
	$content = array(
		'home'    => '<!-- wp:pattern {"slug":"' . $pattern . '"} /-->',
		'sitemap' => '<!-- wp:lailatulqadar/guide-menu {"set":"sitemap"} /-->',
	);
	$id      = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => $def['title'],
			'post_name'    => $def['slug'],
			'post_status'  => isset( $content[ $key ] ) ? 'publish' : 'draft',
			'post_content' => isset( $content[ $key ] ) ? $content[ $key ] : '',
			'post_parent'  => $parent,
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		$report['errors'][] = $def['title'] . ': ' . $id->get_error_message();
		return 0;
	}

	$report['created'][] = $def['title'];
	return (int) $id;
}

/**
 * Create or reuse every guide page and wire up languages.
 *
 * @return array Report with created, reused, renamed, seeded and errors.
 */
function lq_run_setup() {
	$report   = array(
		'created'   => array(),
		'reused'    => array(),
		'renamed'   => array(),
		'seeded'    => array(),
		'published' => array(),
		'refreshed' => array(),
		'edited'    => array(),
		'trashed'   => array(),
		'kept'      => array(),
		'errors'    => array(),
	);
	$map = lq_page_map();

	// Without the page definitions every guide page would look retired and
	// go to the trash, so setup stops here instead.
	if ( ! lq_page_definitions() ) {
		$report['stopped']  = true;
		$report['errors'][] = __( 'The page definitions (inc/data/pages.json) could not be read, so setup made no changes.', 'lailatulqadar' );
		return $report;
	}

	foreach ( array( 'primary', 'secondary' ) as $role ) {
		foreach ( array_keys( lq_page_definitions() ) as $key ) {
			$id = lq_ensure_page( $key, $role, $map, $report );
			if ( ! $id ) {
				continue;
			}
			$map[ $role ][ $key ] = $id;
			update_option( LQ_PAGES_OPTION, $map, false );
			lq_place_page( $id, $key, $role, $map );
			lq_sync_page( $id, $key, $role, $report );
		}
	}

	// Pair each page with its translation.
	foreach ( array_keys( lq_page_definitions() ) as $key ) {
		if ( ! empty( $map['primary'][ $key ] ) && ! empty( $map['secondary'][ $key ] ) ) {
			update_post_meta( (int) $map['primary'][ $key ], '_lq_translation', (int) $map['secondary'][ $key ] );
			update_post_meta( (int) $map['secondary'][ $key ], '_lq_translation', (int) $map['primary'][ $key ] );
		}
	}

	update_option( LQ_PAGES_OPTION, $map, false );
	lq_trash_duplicates( $map, $report );

	if ( ! empty( $map['primary']['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $map['primary']['home'] );
	}

	update_option( 'lailatulqadar_setup_version', LQ_VERSION, false );

	return $report;
}

/**
 * Put a guide page in its place: language marker, the Malay home at the
 * address /ms/ with the home template, and every other Malay page beneath
 * it, so its address begins with /ms/.
 *
 * @param int    $id   Page ID.
 * @param string $key  Page key.
 * @param string $role primary or secondary.
 * @param array  $map  Page map.
 */
function lq_place_page( $id, $key, $role, $map ) {
	update_post_meta( $id, '_lq_lang', 'secondary' === $role ? 'ms' : 'en' );
	update_post_meta( $id, '_lq_key', $key );
	if ( 'secondary' !== $role ) {
		return;
	}
	$post = get_post( $id );
	if ( 'home' === $key ) {
		$slug = lq_setting( 'secondary_slug' );
		if ( $post->post_name !== $slug || 0 !== (int) $post->post_parent ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_name'   => $slug,
					'post_parent' => 0,
				)
			);
		}
		update_post_meta( $id, '_wp_page_template', 'page-home' );
		return;
	}
	$home = empty( $map['secondary']['home'] ) ? 0 : (int) $map['secondary']['home'];
	if ( $home && (int) $post->post_parent !== $home ) {
		wp_update_post(
			array(
				'ID'          => $id,
				'post_parent' => $home,
			)
		);
	}
}

/**
 * Bring an existing page in line with the registry without touching work
 * the editor has done.
 *
 * An untouched draft (no content yet) takes the current title and slug,
 * which carries renamed pages forward from earlier versions. The title tag
 * and meta description are filled when empty or over their limits.
 *
 * @param int    $id     Page ID.
 * @param string $key    Page key.
 * @param string $role   primary or secondary.
 * @param array  $report Report, passed by reference.
 */
function lq_sync_page( $id, $key, $role, &$report ) {
	$def  = lq_page_definitions()[ $key ][ $role ];
	$post = get_post( $id );
	if ( ! $post ) {
		return;
	}

	if ( 'draft' === $post->post_status && '' === trim( $post->post_content ) && ( $post->post_title !== $def['title'] || $post->post_name !== $def['slug'] ) ) {
		wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => $def['title'],
				'post_name'  => $def['slug'],
			)
		);
		$report['renamed'][] = $def['title'];
	}

	lq_seed_content( $id, $key, $role, $report );
	lq_attach_featured_image( $id, $key );

	// Superseded defaults from earlier releases.
	$legacy = array(
		'Laylat al-Qadr Planner: Checklist for the Last Ten Nights',
		'Plan each of the last ten nights of Ramadan with a simple checklist for prayer, Qurʾān, duʿāʾ and charity. Start planning.',
		'Perancang Malam Lailatulqadar: Senarai Semak Sepuluh Malam',
		'Rancang setiap malam daripada sepuluh malam terakhir Ramadan dengan senarai semak solat, al-Quran, doa dan sedekah. Mulakan.',
	);
	foreach ( array( LQ_META_TITLE => 'seo', LQ_META_DESCRIPTION => 'meta' ) as $meta_key => $field ) {
		if ( in_array( (string) get_post_meta( $id, $meta_key, true ), $legacy, true ) ) {
			update_post_meta( $id, $meta_key, $def[ $field ] );
		}
	}

	// Empty values, values over the limit, and the fixed-year defaults of
	// earlier versions (with "2027" in place of [lq_ramadan]) take the
	// registry default.
	$title = (string) get_post_meta( $id, LQ_META_TITLE, true );
	if ( '' === $title || mb_strlen( $title ) > LQ_TITLE_LIMIT || ( str_replace( '[lq_ramadan]', '2027', $def['seo'] ) === $title && $title !== $def['seo'] ) ) {
		update_post_meta( $id, LQ_META_TITLE, $def['seo'] );
	}
	$desc = (string) get_post_meta( $id, LQ_META_DESCRIPTION, true );
	if ( '' === $desc || mb_strlen( $desc ) > LQ_META_LIMIT || ( str_replace( '[lq_ramadan]', '2027', $def['meta'] ) === $desc && $desc !== $def['meta'] ) ) {
		update_post_meta( $id, LQ_META_DESCRIPTION, $def['meta'] );
	}

	// Rank Math owns its own fields. The theme only fills a field that is
	// empty, or that still holds a default the theme itself wrote in an
	// earlier release; anything edited in Rank Math is left alone. Dated
	// wording uses Rank Math's own variable %lq_ramadan_year%, so the year
	// rolls over inside Rank Math and shows in its snippet preview.
	if ( defined( 'RANK_MATH_VERSION' ) ) {
		foreach ( array( 'rank_math_title' => 'seo', 'rank_math_description' => 'meta' ) as $rm_key => $field ) {
			$want    = str_replace( '[lq_ramadan]', '%lq_ramadan_year%', $def[ $field ] );
			$current = (string) get_post_meta( $id, $rm_key, true );
			$ours    = array_merge( $legacy, array( $def[ $field ], str_replace( '[lq_ramadan]', '2027', $def[ $field ] ) ) );
			if ( '' === $current || ( $current !== $want && in_array( $current, $ours, true ) ) ) {
				update_post_meta( $id, $rm_key, $want );
			}
		}
		if ( '' === (string) get_post_meta( $id, 'rank_math_focus_keyword', true ) && ! empty( $def['focus'] ) ) {
			update_post_meta( $id, 'rank_math_focus_keyword', $def['focus'] );
		}
	}
}

/**
 * Whether a page found by slug may be adopted for this role: it must not
 * already belong to the registry, and must not already be marked as the
 * other language.
 *
 * @param int    $id   Page ID.
 * @param string $role primary or secondary.
 * @param array  $map  Stored page map.
 * @return bool
 */
function lq_page_is_claimable( $id, $role, $map ) {
	foreach ( $map as $ids ) {
		if ( in_array( $id, array_map( 'intval', (array) $ids ), true ) ) {
			return false;
		}
	}
	return lq_role_of( $id ) === $role || ! get_post_meta( $id, '_lq_lang', true );
}

/**
 * The article shipped in content/<lang>/<key>.json: its block markup under
 * "content" and, when it has any, its footnotes under "footnotes".
 *
 * The file is data only, read with json_decode() and never executed or
 * included. Anything not shaped as expected is refused whole.
 *
 * @param string $lang en or ms.
 * @param string $key  Page key.
 * @return array{content:string,footnotes:array<int,array{id:string,content:string}>}|null
 */
function lq_article_data( $lang, $key ) {
	if ( ! preg_match( '/^[a-z]{2}$/', $lang ) || ! preg_match( '/^[a-z0-9]+$/', $key ) ) {
		return null;
	}
	$file = LQ_DIR . '/content/' . $lang . '/' . $key . '.json';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $data ) || ! isset( $data['content'] ) || ! is_string( $data['content'] ) ) {
		return null;
	}
	$list = $data['footnotes'] ?? array();
	if ( ! is_array( $list ) || array_values( $list ) !== $list ) {
		return null;
	}
	$notes = array();
	foreach ( $list as $note ) {
		if ( ! is_array( $note ) || ! isset( $note['id'], $note['content'] ) || ! is_string( $note['id'] ) || ! is_string( $note['content'] ) ) {
			return null;
		}
		$notes[] = array(
			'id'      => $note['id'],
			'content' => $note['content'],
		);
	}
	// Every footnote marker in the text needs its note, or the article would
	// seed with citations that point at nothing.
	preg_match_all( '/\bdata-fn="([^"]+)"/', $data['content'], $marks );
	if ( array_diff( array_unique( $marks[1] ), array_column( $notes, 'id' ) ) ) {
		return null;
	}
	return array(
		'content'   => $data['content'],
		'footnotes' => $notes,
	);
}

/**
 * Content normalised for comparison: line endings unified, outer space
 * trimmed, as setup compares content elsewhere.
 *
 * @param string $content Block markup.
 * @return string
 */
function lq_seed_normalise( $content ) {
	return trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $content ) );
}

/**
 * Fingerprint of a page's content and footnotes, stored as _lq_seed_hash
 * when setup writes a page.
 *
 * @param string $content   Content as written (links resolved).
 * @param array  $footnotes Footnotes: id and content of each.
 * @return string
 */
function lq_seed_hash( $content, $footnotes ) {
	$notes = array();
	foreach ( (array) $footnotes as $note ) {
		if ( is_array( $note ) && isset( $note['id'], $note['content'] ) ) {
			$notes[] = array(
				'id'      => (string) $note['id'],
				'content' => (string) $note['content'],
			);
		}
	}
	return md5( lq_seed_normalise( $content ) ) . ':' . md5( (string) wp_json_encode( $notes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

/**
 * A page's footnotes as stored in the core footnotes meta.
 *
 * @param int $id Page ID.
 * @return array
 */
function lq_page_footnotes( $id ) {
	$notes = json_decode( (string) get_post_meta( $id, 'footnotes', true ), true );
	return is_array( $notes ) ? $notes : array();
}

/**
 * The address a {{url:key}} token resolves to, the fallback address used
 * before the page exists, and the ?page_id= address WordPress gives a
 * draft (what a link to a page that was still a draft was written as).
 *
 * @param string $key  Page key in the token.
 * @param string $role primary or secondary.
 * @return string[] Resolved address first, then the others.
 */
function lq_article_link_urls( $key, $role ) {
	$defs = lq_page_definitions();
	if ( empty( $defs[ $key ] ) ) {
		return array( home_url( '/' ) );
	}
	$prefix   = 'secondary' === $role ? lq_setting( 'secondary_slug' ) . '/' : '';
	$slug     = 'home' === $key ? '' : $defs[ $key ][ $role ]['slug'] . '/';
	$fallback = home_url( '/' . $prefix . $slug );
	$map      = lq_page_map();
	if ( ! empty( $map[ $role ][ $key ] ) && get_post( (int) $map[ $role ][ $key ] ) ) {
		return array( get_permalink( (int) $map[ $role ][ $key ] ), $fallback, home_url( '/?page_id=' . (int) $map[ $role ][ $key ] ) );
	}
	return array( $fallback, $fallback );
}

/**
 * Resolve the {{url:key}} tokens of a shipped article to addresses in the
 * same language.
 *
 * @param string $content Shipped block markup.
 * @param string $role    primary or secondary.
 * @return string
 */
function lq_resolve_article_links( $content, $role ) {
	return preg_replace_callback(
		'/\{\{url:([a-z0-9]+)\}\}/',
		static function ( $m ) use ( $role ) {
			return lq_article_link_urls( $m[1], $role )[0];
		},
		$content
	);
}

/**
 * Turn guide-page addresses in a page's links back into {{url:key}}
 * tokens, so a page seeded by an earlier release can be compared with the
 * shipped text of that release.
 *
 * @param string $content Page content.
 * @param string $role    primary or secondary.
 * @return string
 */
function lq_unresolve_article_links( $content, $role ) {
	$urls = array();
	foreach ( array_keys( lq_page_definitions() ) as $key ) {
		foreach ( lq_article_link_urls( $key, $role ) as $url ) {
			if ( ! isset( $urls[ $url ] ) ) {
				$urls[ $url ] = $key;
			}
		}
	}
	return preg_replace_callback(
		'/href="([^"]*)"/',
		static function ( $m ) use ( $urls ) {
			return isset( $urls[ $m[1] ] ) ? 'href="{{url:' . $urls[ $m[1] ] . '}}"' : $m[0];
		},
		(string) $content
	);
}

/**
 * md5 of every version of each article the theme has shipped, from
 * inc/data/seed-history.json (built from the git history of content/).
 * Data only, read with json_decode().
 *
 * @param string $lang en or ms.
 * @param string $key  Page key.
 * @return string[]
 */
function lq_seed_history( $lang, $key ) {
	static $data = null;
	if ( null === $data ) {
		$file = LQ_DIR . '/inc/data/seed-history.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = is_array( $data ) ? $data : array();
	}
	$list = isset( $data[ $lang ][ $key ] ) && is_array( $data[ $lang ][ $key ] ) ? $data[ $lang ][ $key ] : array();
	return array_values( array_filter( $list, 'is_string' ) );
}

/**
 * Whether a page still holds text the theme wrote: it is empty, matches
 * its stored seed hash, or (for a page seeded before the hash existed)
 * matches a version of the article shipped in an earlier release.
 *
 * @param WP_Post $post Page.
 * @param string  $key  Page key.
 * @param string  $role primary or secondary.
 * @return bool
 */
function lq_page_is_unedited( $post, $key, $role ) {
	if ( '' === trim( $post->post_content ) ) {
		return true;
	}
	$stored = (string) get_post_meta( $post->ID, '_lq_seed_hash', true );
	if ( '' !== $stored ) {
		return lq_seed_hash( $post->post_content, lq_page_footnotes( $post->ID ) ) === $stored;
	}
	$lang    = 'secondary' === $role ? 'ms' : 'en';
	$hash    = md5( lq_seed_normalise( lq_unresolve_article_links( $post->post_content, $role ) ) );
	$known   = lq_seed_history( $lang, $key );
	$article = lq_article_data( $lang, $key );
	if ( $article ) {
		$known[] = md5( lq_seed_normalise( $article['content'] ) );
	}
	return in_array( $hash, $known, true );
}

/**
 * Write the article shipped in content/<lang>/<key>.json into a page, and
 * keep it current.
 *
 * - An empty page (draft or published) is seeded and published.
 * - A page the theme wrote and nobody has edited (its content and
 *   footnotes still match _lq_seed_hash, or, before the hash existed, an
 *   earlier shipped version) takes the new version when the shipped
 *   article changes, through wp_update_post(), which keeps a revision.
 *   A page seeded before the hash existed is published; one the theme
 *   has hashed keeps its status, so a page an editor returns to draft
 *   stays a draft.
 * - A page an editor has changed is never overwritten. When the shipped
 *   article differs from it, it is marked _lq_seed_edited and listed on
 *   the Tools tab as "Edited, not updated".
 *
 * Internal links written as {{url:key}} resolve to the page's address in
 * the same language, and footnotes load into the core footnotes meta.
 * A page with no shipped article is left alone.
 *
 * @param int    $id     Page ID.
 * @param string $key    Page key.
 * @param string $role   primary or secondary.
 * @param array  $report Report, passed by reference.
 * @param bool   $force  Replace even an edited page (the Tools button).
 */
function lq_seed_content( $id, $key, $role, &$report, $force = false ) {
	$lang = 'secondary' === $role ? 'ms' : 'en';
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_status, array( 'draft', 'pending', 'publish' ), true ) ) {
		return;
	}
	$article = lq_article_data( $lang, $key );
	if ( null === $article ) {
		return;
	}

	$title    = lq_page_definitions()[ $key ][ $role ]['title'];
	$content  = lq_resolve_article_links( $article['content'], $role );
	$new      = lq_seed_hash( $content, $article['footnotes'] );
	$stored   = (string) get_post_meta( $id, '_lq_seed_hash', true );
	$empty    = '' === trim( $post->post_content );
	$current  = lq_seed_hash( $post->post_content, lq_page_footnotes( $id ) );
	$unedited = $force || lq_page_is_unedited( $post, $key, $role );
	$was      = $post->post_status;

	if ( ! $unedited ) {
		if ( $new === $stored || $new === $current ) {
			delete_post_meta( $id, '_lq_seed_edited' );
		} else {
			update_post_meta( $id, '_lq_seed_edited', 1 );
			$report['edited'][] = $title;
		}
		return;
	}
	delete_post_meta( $id, '_lq_seed_edited' );

	// Publish a page being seeded for the first time, or one seeded before
	// the hash existed; a hashed page keeps the status an editor gave it.
	$status  = ( $empty || '' === $stored || $force ) ? 'publish' : $was;
	$changed = $new !== $current;

	if ( ! $changed && $status === $was ) {
		if ( $stored !== $new ) {
			update_post_meta( $id, '_lq_seed_hash', $new );
		}
		return;
	}

	$args = array(
		'ID'          => $id,
		'post_status' => $status,
	);
	if ( $changed ) {
		$args['post_content'] = wp_slash( $content );
	}
	$result = wp_update_post( $args, true );
	if ( is_wp_error( $result ) ) {
		$report['errors'][] = $title . ': ' . $result->get_error_message();
		return;
	}

	if ( $changed ) {
		if ( $article['footnotes'] ) {
			update_post_meta( $id, 'footnotes', wp_slash( wp_json_encode( $article['footnotes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
		} else {
			delete_post_meta( $id, 'footnotes' );
		}
	}
	update_post_meta( $id, '_lq_seed_hash', $new );

	if ( $empty ) {
		$report['seeded'][] = $title;
	} elseif ( $changed ) {
		$report['refreshed'][] = $title;
	}
	if ( 'publish' === $status && 'publish' !== $was ) {
		$report['published'][] = $title;
	}
}

/**
 * Move obsolete copies of guide pages to the trash: pages the theme
 * created or adopted (they carry its _lq_lang marker) that the registry
 * no longer points to, such as drafts left by an earlier setup or a page
 * renamed to slug-2. Only a copy that still holds text the theme wrote
 * (or none) is trashed; an edited copy is kept and reported. Pages
 * without the theme's marker are never touched.
 *
 * @param array $map    Page map.
 * @param array $report Report, passed by reference.
 */
function lq_trash_duplicates( $map, &$report ) {
	$owned = array();
	foreach ( $map as $ids ) {
		$owned = array_merge( $owned, array_values( array_map( 'intval', (array) $ids ) ) );
	}
	$defs  = lq_page_definitions();
	$posts = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => array( 'draft', 'pending', 'publish', 'private' ),
			'numberposts' => -1,
			'meta_key'    => '_lq_lang', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	foreach ( $posts as $post ) {
		if ( in_array( (int) $post->ID, $owned, true ) ) {
			continue;
		}
		$lang = (string) get_post_meta( $post->ID, '_lq_lang', true );
		if ( 'en' !== $lang && 'ms' !== $lang ) {
			continue;
		}
		$role = 'ms' === $lang ? 'secondary' : 'primary';
		$key  = (string) get_post_meta( $post->ID, '_lq_key', true );
		if ( '' === $key || empty( $defs[ $key ] ) ) {
			$key = '';
			foreach ( $defs as $k => $def ) {
				if ( preg_match( '/^' . preg_quote( $def[ $role ]['slug'], '/' ) . '(-\d+)?$/', $post->post_name ) ) {
					$key = $k;
					break;
				}
			}
		}
		if ( '' === $key || empty( $map[ $role ][ $key ] ) ) {
			continue; // Not a copy of a page the registry holds.
		}
		$label = $post->post_title . ' (#' . $post->ID . ')';
		if ( in_array( $key, array( 'home', 'sitemap' ), true ) ? lq_is_default_block_page( $post, $key ) : lq_page_is_unedited( $post, $key, $role ) ) {
			if ( wp_trash_post( $post->ID ) ) {
				$report['trashed'][] = $label;
			}
		} else {
			$report['kept'][] = $label;
		}
	}
}

/**
 * Whether a home or sitemap page still holds the block setup wrote.
 *
 * @param WP_Post $post Page.
 * @param string  $key  home or sitemap.
 * @return bool
 */
function lq_is_default_block_page( $post, $key ) {
	$body = trim( $post->post_content );
	if ( '' === $body ) {
		return true;
	}
	return 'home' === $key
		? (bool) preg_match( '#^<!-- wp:pattern \{"slug":"lailatulqadar/front-(en|ms)"\} /-->$#', $body )
		: '<!-- wp:lailatulqadar/guide-menu {"set":"sitemap"} /-->' === $body;
}
