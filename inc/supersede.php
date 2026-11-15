<?php
/**
 * Superseded guide pages, renames in place, retired pages, the setup log and the dry run.
 *
 * Identity is the page key in inc/data/pages.json. Two data files ship empty:
 *
 *   - inc/data/supersedes.json: a map of old page key => new page key, applied to both languages.
 *   - a "former" list of earlier slugs on a page's language entry in inc/data/pages.json.
 *
 * While the new page is published, the old page:
 *
 *   - answers with a 301 to the new one (template_redirect, priority 1);
 *   - is left out of lists, search, feeds, sitemaps and menus (it joins the unlisted pages, so the unlist
 *     module's rules apply, noindex included);
 *   - is moved to the Trash by setup, never deleted, when nobody has edited it.
 *
 * A page key that pages.json no longer holds (a dropped page) is treated the same way: an unedited page goes
 * to the Trash, an edited one is kept and leaves the registry. Nothing the owner created is touched, because
 * only pages in the registry are considered.
 *
 * A published page whose slug is one of its "former" slugs is renamed in place by setup (same post, same
 * edits) and the old address answers with a 301. A page whose text was edited keeps its address.
 *
 * Guards. Removals stop when the number of page keys fell by more than 10 percent since the last good run, or
 * when LQ_DISABLE_SUPERSEDE is true in wp-config.php (which also ends the redirect and the hiding). Setup
 * already does nothing when pages.json cannot be read.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

const LQ_LOG_OPTION       = 'lailatulqadar_sync_log';
const LQ_REDIRECTS_OPTION = 'lailatulqadar_slug_redirects';
const LQ_COUNT_OPTION     = 'lailatulqadar_defs_count';

/**
 * The kill switch.
 *
 * @return bool
 */
function lq_supersede_disabled() {
	return defined( 'LQ_DISABLE_SUPERSEDE' ) && LQ_DISABLE_SUPERSEDE;
}

/**
 * Clean a raw supersedes map: lower case keys, no empty key, no key that replaces itself, and no cycle
 * (A to B, B to A): none of a cycle's keys is superseded. Pure.
 *
 * @param mixed $raw Decoded JSON.
 * @return array<string,string>
 */
function lq_clean_supersedes( $raw ) {
	$map = array();
	foreach ( is_array( $raw ) ? $raw : array() as $old => $new ) {
		$old = sanitize_key( (string) $old );
		$new = sanitize_key( (string) $new );
		if ( '' !== $old && '' !== $new && $old !== $new ) {
			$map[ $old ] = $new;
		}
	}
	foreach ( array_keys( $map ) as $start ) {
		$at   = $start;
		$seen = array();
		while ( isset( $map[ $at ] ) && ! in_array( $at, $seen, true ) ) {
			$seen[] = $at;
			$at     = $map[ $at ];
		}
		if ( $at === $start ) {
			foreach ( $seen as $key ) {
				unset( $map[ $key ] );
			}
		}
	}
	return $map;
}

/**
 * The supersedes map from inc/data/supersedes.json: old page key => new page key.
 *
 * @return array<string,string>
 */
function lq_supersedes_map() {
	static $map = null;
	if ( null === $map ) {
		$file = LQ_DIR . '/inc/data/supersedes.json';
		$raw  = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$map  = lq_clean_supersedes( $raw );
	}
	return $map;
}

/**
 * Whether removals may run. Pure.
 *
 * @param bool $disabled    The kill switch.
 * @param int  $count_now   Page keys now.
 * @param int  $count_prior Page keys at the last good run, 0 when none.
 * @return string '' when allowed, otherwise the reason.
 */
function lq_removal_guard( $disabled, $count_now, $count_prior ) {
	if ( $disabled ) {
		return __( 'the kill switch LQ_DISABLE_SUPERSEDE is on', 'lailatulqadar' );
	}
	if ( $count_prior > 0 && $count_now < $count_prior * 0.9 ) {
		return __( 'the page definitions list more than 10 percent fewer pages than at the last good run', 'lailatulqadar' );
	}
	return '';
}

/**
 * The reason removals are stopped now, or ''.
 *
 * @return string
 */
function lq_removal_blocked() {
	return lq_removal_guard( lq_supersede_disabled(), count( lq_page_definitions() ), (int) get_option( LQ_COUNT_OPTION, 0 ) );
}

/**
 * Whether a page is the theme's and still holds the text the theme wrote. Fails closed: a page that is not in
 * the registry, or whose text matches no shipped version, counts as the owner's.
 *
 * @param WP_Post $post Page.
 * @param string  $key  Page key.
 * @param string  $role primary or secondary.
 * @return bool
 */
function lq_is_unedited( $post, $key, $role ) {
	return (bool) get_post_meta( $post->ID, '_lq_lang', true ) && lq_page_is_unedited( $post, $key, $role );
}

/**
 * Old page ID => new page ID, for every pair in both languages where both pages are published and the old one
 * has not been edited. Static for one request.
 *
 * @param bool $reset Read the pairs again.
 * @return array<int,int>
 */
function lq_superseded_pairs( $reset = false ) {
	static $cache = null;
	if ( $reset ) {
		$cache = null;
	}
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( lq_supersede_disabled() ) {
		return $cache;
	}
	$map = lq_page_map();
	foreach ( lq_supersedes_map() as $old => $new ) {
		foreach ( array( 'primary', 'secondary' ) as $role ) {
			$old_id = isset( $map[ $role ][ $old ] ) ? (int) $map[ $role ][ $old ] : 0;
			$new_id = isset( $map[ $role ][ $new ] ) ? (int) $map[ $role ][ $new ] : 0;
			$old_p  = $old_id ? get_post( $old_id ) : null;
			$new_p  = $new_id ? get_post( $new_id ) : null;
			if ( $old_p && $new_p && $old_id !== $new_id && 'publish' === $old_p->post_status && 'publish' === $new_p->post_status && lq_is_unedited( $old_p, $old, $role ) ) {
				$cache[ $old_id ] = $new_id;
			}
		}
	}
	return $cache;
}

/**
 * IDs of the superseded pages, for the unlist module and the menus.
 *
 * @return int[]
 */
function lq_superseded_ids() {
	return array_keys( lq_superseded_pairs() );
}

/**
 * Whether setup leaves a page key alone: the key is named as old in the map and its replacement is published
 * in this language, so the old page must not be created again or refreshed.
 *
 * @param string $key  Page key.
 * @param string $role primary or secondary.
 * @return bool
 */
function lq_is_retired_key( $key, $role ) {
	$map = lq_supersedes_map();
	if ( lq_supersede_disabled() || ! isset( $map[ $key ] ) ) {
		return false;
	}
	$pages = lq_page_map();
	return ! empty( $pages[ $role ][ $map[ $key ] ] ) && 'publish' === get_post_status( (int) $pages[ $role ][ $map[ $key ] ] );
}

/**
 * Remember that an old address now belongs to a page, so it answers with a 301 even when the old page is gone.
 *
 * Earlier entries that pointed at the old page now point at its replacement, so a chain (old, newer, current)
 * ends at the page that is live.
 *
 * @param string $old_uri Old page path without slashes at the ends.
 * @param int    $new_id  Page that replaces it.
 * @param int    $old_id  The old page's ID, when it has one.
 */
function lq_note_redirect( $old_uri, $new_id, $old_id = 0 ) {
	$old_uri = trim( (string) $old_uri, '/' );
	if ( '' === $old_uri || (int) $new_id === (int) $old_id ) {
		return;
	}
	$all = (array) get_option( LQ_REDIRECTS_OPTION, array() );
	if ( $old_id ) {
		foreach ( $all as $path => $target ) {
			if ( (int) $target === (int) $old_id ) {
				$all[ $path ] = (int) $new_id;
			}
		}
	}
	$all[ $old_uri ] = (int) $new_id;
	update_option( LQ_REDIRECTS_OPTION, array_slice( $all, -500, null, true ), false );
}

/**
 * The 301 from a superseded page to its replacement.
 */
function lq_supersede_redirect() {
	if ( lq_supersede_disabled() || is_admin() || is_preview() || is_feed() || ! is_singular() ) {
		return;
	}
	$pairs = lq_superseded_pairs();
	$id    = (int) get_queried_object_id();
	if ( isset( $pairs[ $id ] ) ) {
		wp_safe_redirect( get_permalink( $pairs[ $id ] ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'lq_supersede_redirect', 1 );

/**
 * An address that is a 404 now (the page is in the Trash, or its slug changed) still reaches the page that
 * took its place.
 */
function lq_supersede_redirect_gone() {
	if ( lq_supersede_disabled() || ! is_404() ) {
		return;
	}
	$path = isset( $GLOBALS['wp']->request ) ? trim( (string) $GLOBALS['wp']->request, '/' ) : '';
	$all  = (array) get_option( LQ_REDIRECTS_OPTION, array() );
	if ( '' === $path || empty( $all[ $path ] ) ) {
		return;
	}
	$id = (int) $all[ $path ];
	if ( 'publish' === get_post_status( $id ) ) {
		wp_safe_redirect( get_permalink( $id ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'lq_supersede_redirect_gone', 2 );

/**
 * With the Unlist Posts plugin active the theme's unlist module stands down, so the hiding of superseded
 * pages is done here: out of listing queries (feeds and search included) and noindex on the page itself.
 *
 * @param WP_Query $query Query.
 */
function lq_supersede_pre_get_posts( $query ) {
	if ( lailatulqadar_unlist_active() || lq_supersede_disabled() || lailatulqadar_unlist_is_editor_context() || $query->is_singular() ) {
		return;
	}
	$ids = lq_superseded_ids();
	if ( $ids ) {
		$query->set( 'post__not_in', array_values( array_unique( array_merge( array_filter( array_map( 'intval', (array) $query->get( 'post__not_in' ) ) ), $ids ) ) ) );
	}
}
add_action( 'pre_get_posts', 'lq_supersede_pre_get_posts', 20 );

/**
 * Noindex on the page of a superseded item while the plugin has the unlist module's place.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function lq_supersede_robots( $robots ) {
	if ( ! lailatulqadar_unlist_active() && is_singular() && in_array( (int) get_queried_object_id(), lq_superseded_ids(), true ) ) {
		return wp_robots_no_robots( $robots );
	}
	return $robots;
}
add_filter( 'wp_robots', 'lq_supersede_robots', 20 );

/**
 * Plugin mode: superseded pages are also left out of wp_list_pages() and the previous and next links.
 */
add_filter(
	'wp_list_pages_excludes',
	static function ( $exclude ) {
		if ( lailatulqadar_unlist_active() || lq_supersede_disabled() || lailatulqadar_unlist_is_editor_context() ) {
			return $exclude;
		}
		return array_values( array_unique( array_merge( array_map( 'intval', (array) $exclude ), lq_superseded_ids() ) ) );
	}
);
/**
 * Add the NOT IN clause for superseded pages to an adjacent-post query (plugin mode only).
 *
 * @param string $where Where clause.
 * @return string
 */
function lq_supersede_adjacent_where( $where ) {
	$ids = ( lailatulqadar_unlist_active() || lq_supersede_disabled() ) ? array() : lq_superseded_ids();
	return $ids ? $where . ' AND p.ID NOT IN (' . implode( ',', array_map( 'intval', $ids ) ) . ')' : $where;
}
add_filter( 'get_next_post_where', 'lq_supersede_adjacent_where', 20 );
add_filter( 'get_previous_post_where', 'lq_supersede_adjacent_where', 20 );

/**
 * Append a line to the setup log (the last 100 lines).
 *
 * @param string $item   Page.
 * @param string $action What happened.
 */
function lq_log( $item, $action ) {
	$log   = (array) get_option( LQ_LOG_OPTION, array() );
	$log[] = array( 't' => time(), 'item' => (string) $item, 'action' => (string) $action );
	update_option( LQ_LOG_OPTION, array_slice( $log, -100 ), false );
}

/**
 * Whether a published page may be renamed in place: it holds text the theme wrote, and its slug is one of the
 * earlier slugs the definition lists.
 *
 * @param WP_Post $post Page.
 * @param string  $key  Page key.
 * @param string  $role primary or secondary.
 * @param array   $def  The page's definition for the role.
 * @return bool
 */
function lq_can_rename_published( $post, $key, $role, $def ) {
	if ( lq_supersede_disabled() || 'publish' !== $post->post_status || $post->post_name === $def['slug'] ) {
		return false;
	}
	$former = array_map( 'sanitize_title', array_filter( (array) ( $def['former'] ?? array() ), 'is_string' ) );
	return in_array( $post->post_name, $former, true ) && lq_is_unedited( $post, $key, $role );
}

/**
 * Rename a published page in place and keep its old address alive.
 *
 * @param WP_Post $post   Page.
 * @param array   $def    The page's definition for the role.
 * @param array   $report Report, passed by reference.
 */
function lq_rename_published( $post, $def, &$report ) {
	$old_uri = get_page_uri( $post->ID );
	$args    = array( 'ID' => $post->ID, 'post_name' => $def['slug'] );
	$titles  = array_filter( (array) ( $def['former_titles'] ?? array() ), 'is_string' );
	if ( in_array( $post->post_title, $titles, true ) ) {
		$args['post_title'] = $def['title'];
	}
	$result = wp_update_post( $args, true );
	if ( is_wp_error( $result ) ) {
		$report['errors'][] = $def['title'] . ': ' . $result->get_error_message();
		return;
	}
	lq_note_redirect( $old_uri, $post->ID );
	$report['renamed'][] = $def['title'];
	lq_log( $def['title'], __( 'renamed in place; the old address redirects', 'lailatulqadar' ) );
}

/**
 * Setup step: move superseded and dropped pages to the Trash, unless a guard stops it or the page was edited.
 *
 * @param array $map    Page map, passed by reference (a dropped page leaves it).
 * @param array $report Report, passed by reference.
 */
function lq_retire_pages( &$map, &$report ) {
	$reason = lq_removal_blocked();
	if ( '' !== $reason ) {
		update_option( 'lailatulqadar_supersede_blocked', $reason, false );
		if ( ! lq_supersede_disabled() ) {
			$report['errors'][] = sprintf( /* translators: %s: reason */ __( 'Removals were stopped: %s.', 'lailatulqadar' ), $reason );
		}
		return;
	}
	delete_option( 'lailatulqadar_supersede_blocked' );
	$pairs = lq_superseded_pairs( true );
	foreach ( $pairs as $old_id => $new_id ) {
		$post = get_post( $old_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			continue;
		}
		$old_uri = get_page_uri( $old_id );
		// Follow a chain (old, newer, current) to the page that stays, with a guard against a loop.
		$target = $new_id;
		$seen   = array( $old_id );
		while ( isset( $pairs[ $target ] ) && ! in_array( $target, $seen, true ) ) {
			$seen[] = $target;
			$target = $pairs[ $target ];
		}
		if ( wp_trash_post( $old_id ) ) {
			lq_note_redirect( $old_uri, $target, $old_id );
			$report['trashed'][] = sprintf( /* translators: 1: page title, 2: slug of the replacing page */ __( '%1$s (replaced by %2$s)', 'lailatulqadar' ), $post->post_title, get_post_field( 'post_name', $new_id ) );
			lq_log( $post->post_title, sprintf( /* translators: %s: slug of the replacing page */ __( 'moved to the Trash, replaced by %s', 'lailatulqadar' ), get_post_field( 'post_name', $new_id ) ) );
		} else {
			$report['errors'][] = sprintf( /* translators: %s: page title */ __( '%s: could not move to the Trash.', 'lailatulqadar' ), $post->post_title );
		}
	}
	lq_superseded_pairs( true );
	// An old page that was already in the Trash before the pair existed still needs its redirect.
	$defs_now = lq_page_definitions();
	foreach ( lq_supersedes_map() as $old => $new ) {
		foreach ( array( 'primary', 'secondary' ) as $role ) {
			$old_id = isset( $map[ $role ][ $old ] ) ? (int) $map[ $role ][ $old ] : 0;
			$new_id = isset( $map[ $role ][ $new ] ) ? (int) $map[ $role ][ $new ] : 0;
			if ( $old_id && $new_id && 'trash' === get_post_status( $old_id ) && 'publish' === get_post_status( $new_id ) && isset( $defs_now[ $old ][ $role ]['slug'] ) ) {
				lq_note_redirect( ( 'secondary' === $role && 'home' !== $old ? 'ms/' : '' ) . $defs_now[ $old ][ $role ]['slug'], $new_id, $old_id );
			}
		}
	}
	// A page key the definitions no longer hold.
	$defs = lq_page_definitions();
	foreach ( $map as $role => $ids ) {
		foreach ( (array) $ids as $key => $id ) {
			if ( isset( $defs[ $key ] ) || ! in_array( $role, array( 'primary', 'secondary' ), true ) ) {
				continue;
			}
			$post = get_post( (int) $id );
			if ( $post && 'trash' !== $post->post_status ) {
				if ( ! lq_is_unedited( $post, $key, $role ) ) {
					$report['kept'][] = sprintf( /* translators: %s: page title */ __( '%s (no longer in the theme)', 'lailatulqadar' ), $post->post_title );
					lq_log( $post->post_title, __( 'no longer in the theme; kept, because it was edited', 'lailatulqadar' ) );
					unset( $map[ $role ][ $key ] );
					continue;
				}
				$old_uri = get_page_uri( (int) $id );
				if ( ! wp_trash_post( (int) $id ) ) {
					$report['errors'][] = sprintf( /* translators: %s: page title */ __( '%s: could not move to the Trash.', 'lailatulqadar' ), $post->post_title );
					continue;
				}
				$report['trashed'][] = sprintf( /* translators: %s: page title */ __( '%s (removed from the theme)', 'lailatulqadar' ), $post->post_title );
				lq_log( $post->post_title, __( 'moved to the Trash, removed from the theme', 'lailatulqadar' ) );
				$supersedes = lq_supersedes_map();
				if ( isset( $supersedes[ $key ], $map[ $role ][ $supersedes[ $key ] ] ) ) {
					lq_note_redirect( $old_uri, (int) $map[ $role ][ $supersedes[ $key ] ], (int) $id );
				}
			}
			unset( $map[ $role ][ $key ] );
		}
	}
}

/**
 * Record the page count as the baseline for the 10 percent guard, after a run that was free to remove things.
 */
function lq_record_count() {
	$count = count( lq_page_definitions() );
	if ( $count > 0 ) {
		update_option( LQ_COUNT_OPTION, $count, false );
	}
}

/**
 * What setup would do with one page. Pure: the facts in, the action out.
 *
 * @param array $f Facts: exists, trashed, edited, differs (the shipped text differs from the page),
 *                 renamable, retired (superseded and the replacement is live), defined (the key is still
 *                 in the definitions), blocked (a guard stops removals).
 * @return string create, current, refresh, kept, rename, supersede, supersede-kept, dropped,
 *                dropped-kept or held.
 */
function lq_classify( $f ) {
	if ( empty( $f['defined'] ) ) {
		if ( empty( $f['exists'] ) || ! empty( $f['trashed'] ) ) {
			return 'current';
		}
		if ( ! empty( $f['edited'] ) ) {
			return 'dropped-kept';
		}
		return ! empty( $f['blocked'] ) ? 'held' : 'dropped';
	}
	if ( ! empty( $f['retired'] ) && ! empty( $f['exists'] ) && empty( $f['trashed'] ) ) {
		if ( ! empty( $f['edited'] ) ) {
			return 'supersede-kept';
		}
		return ! empty( $f['blocked'] ) ? 'held' : 'supersede';
	}
	if ( empty( $f['exists'] ) || ! empty( $f['trashed'] ) ) {
		return ! empty( $f['retired'] ) ? 'current' : 'create';
	}
	if ( ! empty( $f['renamable'] ) ) {
		return 'rename';
	}
	if ( ! empty( $f['edited'] ) ) {
		return ! empty( $f['differs'] ) ? 'kept' : 'current';
	}
	return ! empty( $f['differs'] ) ? 'refresh' : 'current';
}

/**
 * What the next setup run would do with each page, without changing anything.
 *
 * @return array[] Each row: key, role, title, action, id.
 */
function lq_setup_preview() {
	$map     = lq_page_map();
	$defs    = lq_page_definitions();
	$blocked = '' !== lq_removal_blocked();
	$rows    = array();
	foreach ( array( 'primary', 'secondary' ) as $role ) {
		$lang = 'secondary' === $role ? 'ms' : 'en';
		foreach ( array_unique( array_merge( array_keys( $defs ), array_keys( (array) ( $map[ $role ] ?? array() ) ) ) ) as $key ) {
			$id    = isset( $map[ $role ][ $key ] ) ? (int) $map[ $role ][ $key ] : 0;
			$post  = $id ? get_post( $id ) : null;
			$def   = $defs[ $key ][ $role ] ?? null;
			$facts = array(
				'defined' => (bool) $def,
				'exists'  => (bool) $post,
				'trashed' => $post && 'trash' === $post->post_status,
				'blocked' => $blocked,
				'retired' => $def && lq_is_retired_key( $key, $role ),
			);
			if ( $post && 'trash' !== $post->post_status ) {
				$facts['edited']    = ! lq_is_unedited( $post, $key, $role );
				$facts['renamable'] = $def && lq_can_rename_published( $post, $key, $role, $def );
				$article            = $def ? lq_article_data( $lang, $key ) : null;
				$facts['differs']   = $article && lq_seed_hash( lq_resolve_article_links( $article['content'], $role ), $article['footnotes'] ) !== lq_seed_hash( $post->post_content, lq_page_footnotes( $id ) );
			}
			$action = lq_classify( $facts );
			$rows[] = array(
				'key'    => $key,
				'role'   => $role,
				'title'  => $post ? $post->post_title : ( $def ? $def['title'] : $key ),
				'action' => $action,
				'id'     => $post ? (int) $post->ID : 0,
			);
		}
	}
	return $rows;
}

/**
 * The labels of the dry-run table.
 *
 * @return array<string,string>
 */
function lq_preview_labels() {
	return array(
		'create'         => __( 'Would be created', 'lailatulqadar' ),
		'current'        => __( 'Matches the theme', 'lailatulqadar' ),
		'refresh'        => __( 'Would take the theme\'s new text, because nobody has edited it', 'lailatulqadar' ),
		'kept'           => __( 'Stays as it was edited; compare it with the theme\'s text', 'lailatulqadar' ),
		'rename'         => __( 'Would be renamed in place; the old address redirects', 'lailatulqadar' ),
		'supersede'      => __( 'Would be redirected, hidden and moved to the Trash, because its replacement is live', 'lailatulqadar' ),
		'supersede-kept' => __( 'Replaced, but it was edited, so it stays as it is', 'lailatulqadar' ),
		'dropped'        => __( 'No longer in the theme; would be moved to the Trash', 'lailatulqadar' ),
		'dropped-kept'   => __( 'No longer in the theme, but it was edited, so it stays', 'lailatulqadar' ),
		'held'           => __( 'Would be removed, but removals are stopped now, so it stays', 'lailatulqadar' ),
	);
}

/**
 * A line-by-line comparison of a page with the article the theme ships.
 *
 * @param int    $id   Page ID.
 * @param string $key  Page key.
 * @param string $role primary or secondary.
 * @return string HTML table, or ''.
 */
function lq_page_diff( $id, $key, $role ) {
	$post    = get_post( $id );
	$article = lq_article_data( 'secondary' === $role ? 'ms' : 'en', $key );
	if ( ! $post || ! $article ) {
		return '';
	}
	$mine  = preg_replace( '#(<!-- /wp:[^>]*-->)#', "$1\n", $post->post_content );
	$theme = preg_replace( '#(<!-- /wp:[^>]*-->)#', "$1\n", lq_resolve_article_links( $article['content'], $role ) );
	return (string) wp_text_diff( $mine, $theme, array( 'title_left' => __( 'This page', 'lailatulqadar' ), 'title_right' => __( 'The theme\'s text', 'lailatulqadar' ) ) );
}

/**
 * The dry-run table, the comparison of one edited page and the log, for the Tools tab.
 */
function lq_render_setup_preview() {
	$labels = lq_preview_labels();
	$rows   = array_filter( lq_setup_preview(), static function ( $r ) {
		return 'current' !== $r['action'];
	} );
	echo '<section class="lq-section"><h2>' . esc_html__( 'Next setup, as a dry run', 'lailatulqadar' ) . '</h2>';
	$blocked = lq_removal_blocked();
	if ( '' !== $blocked ) {
		echo '<p class="lq-help">' . esc_html( sprintf( /* translators: %s: reason */ __( 'Removals are stopped: %s.', 'lailatulqadar' ), $blocked ) ) . '</p>';
	}
	if ( ! $rows ) {
		echo '<p class="lq-help">' . esc_html__( 'Nothing would change.', 'lailatulqadar' ) . '</p>';
	} else {
		echo '<table class="lq-pages"><thead><tr><th scope="col">' . esc_html__( 'Page', 'lailatulqadar' ) . '</th><th scope="col">' . esc_html__( 'Language', 'lailatulqadar' ) . '</th><th scope="col">' . esc_html__( 'What setup would do', 'lailatulqadar' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$cell = esc_html( $row['title'] );
			if ( 'kept' === $row['action'] ) {
				$cell .= ' <a href="' . esc_url( add_query_arg( array( 'page' => 'lailatulqadar', 'tab' => 'tools', 'compare' => $row['role'] . ':' . $row['key'] ), admin_url( 'themes.php' ) ) ) . '">' . esc_html__( 'Compare', 'lailatulqadar' ) . '</a>';
			}
			$lang = 'secondary' === $row['role'] ? esc_html__( 'Malay', 'lailatulqadar' ) : esc_html__( 'English', 'lailatulqadar' );
			echo '<tr><th scope="row">' . $cell . '</th><td>' . $lang . '</td><td>' . esc_html( $labels[ $row['action'] ] ?? $row['action'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput -- every part is escaped above.
		}
		echo '</tbody></table>';
	}
	$compare = isset( $_GET['compare'] ) ? sanitize_text_field( wp_unslash( $_GET['compare'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only view.
	if ( preg_match( '/^(primary|secondary):([a-z0-9_-]+)$/', $compare, $m ) ) {
		$map = lq_page_map();
		$id  = isset( $map[ $m[1] ][ $m[2] ] ) ? (int) $map[ $m[1] ][ $m[2] ] : 0;
		if ( $id ) {
			echo '<h3>' . esc_html( sprintf( /* translators: %s: page title */ __( 'This page and the theme\'s text: %s', 'lailatulqadar' ), get_the_title( $id ) ) ) . '</h3>';
			$diff = lq_page_diff( $id, $m[2], $m[1] );
			echo '' !== $diff ? $diff : '<p class="lq-help">' . esc_html__( 'The two texts are the same.', 'lailatulqadar' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_text_diff() returns escaped HTML.
			echo '<p class="lq-help">' . esc_html__( 'To take the theme\'s text, use "Replace with the shipped version" under "Edited, not updated". To keep this page, do nothing.', 'lailatulqadar' ) . '</p>';
		}
	}
	$log = array_reverse( (array) get_option( LQ_LOG_OPTION, array() ) );
	if ( $log ) {
		echo '<h3>' . esc_html__( 'What changed', 'lailatulqadar' ) . '</h3><table class="lq-pages"><tbody>';
		foreach ( array_slice( $log, 0, 20 ) as $line ) {
			echo '<tr><td>' . esc_html( wp_date( 'Y-m-d H:i', (int) $line['t'] ) ) . '</td><th scope="row">' . esc_html( (string) $line['item'] ) . '</th><td>' . esc_html( (string) $line['action'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</section>';
}
