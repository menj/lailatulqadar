<?php
/**
 * Unlisted posts.
 *
 * Native replacement for the Unlist Posts & Pages plugin (1.2.1). An unlisted
 * page stays reachable at its own URL but is left out of every listing: search
 * (both languages), the core and Rank Math sitemaps, the image sitemap,
 * previous and next links, wp_list_pages(), get_posts() lists and REST
 * collections. Its page carries noindex,
 * through Rank Math's robots filter when Rank Math is active.
 *
 * Each post carries a flag (meta _lq_unlisted); lailatulqadar_unlisted_posts and the
 * plugin's unlist_posts hold the list rebuilt from the flags. A list the
 * plugin changes is synced back on the next request. The theme feature steps aside
 * while the plugin is active, so the two never run together.
 *
 * Admin: an "Unlisted" box in the editor sidebar, Quick Edit and Bulk Edit,
 * an Unlisted column and post state, and an "Unlisted (n)" view.
 *
 * @package Lailatulqadar
 * @since   1.17.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the theme feature runs. Off while the plugin is active, and
 * switchable with the lailatulqadar_unlist_enabled filter.
 */
function lailatulqadar_unlist_active(): bool {
	return ! class_exists( 'Unlist_Posts' ) && (bool) apply_filters( 'lailatulqadar_unlist_enabled', true );
}

/**
 * Clean a list of IDs: positive integers, unique, sorted.
 *
 * @param mixed $ids Raw list.
 * @return int[]
 */
function lailatulqadar_unlist_clean( $ids ): array {
	if ( ! is_array( $ids ) ) {
		return array();
	}
	$ids = array_unique( array_filter( array_map( 'intval', $ids ), static fn( $id ) => $id > 0 ) );
	sort( $ids );
	return array_values( $ids );
}

/**
 * The unlisted IDs. The truth is the per-post flag (meta _lq_unlisted);
 * lailatulqadar_unlisted_posts is a cache of it, rebuilt on every change, so two
 * editors saving at once cannot drop each other's change.
 *
 * @return int[]
 */
function lailatulqadar_unlisted_ids(): array {
	lailatulqadar_unlist_sync_legacy();
	$ids = (array) get_option( 'lailatulqadar_unlisted_posts', array() );
	// Pages that a newer page supersedes are hidden the same way (inc/supersede.php).
	if ( function_exists( 'lq_superseded_ids' ) ) {
		$ids = array_merge( $ids, lq_superseded_ids() );
	}
	return lailatulqadar_unlist_clean( $ids );
}

/**
 * Whether a post is unlisted.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lailatulqadar_is_unlisted( $post_id ): bool {
	return in_array( (int) $post_id, lailatulqadar_unlisted_ids(), true );
}

/**
 * IDs flagged in post meta.
 *
 * @return int[]
 */
function lailatulqadar_unlist_ids_from_meta(): array {
	global $wpdb;
	return lailatulqadar_unlist_clean( $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s", '_lq_unlisted', '1' ) ) );
}

/**
 * Rebuild the cached list from the flags, and keep the plugin's
 * unlist_posts option equal to it, so switching to the plugin and back
 * loses nothing.
 */
function lailatulqadar_unlist_rebuild(): void {
	$ids = lailatulqadar_unlist_ids_from_meta();
	update_option( 'lailatulqadar_unlisted_posts', $ids );
	update_option( 'unlist_posts', $ids );
	update_option( 'lailatulqadar_unlist_legacy_hash', md5( (string) wp_json_encode( $ids ) ), false );
}

/**
 * Unlist or list a post: the flag lives in post meta, and the cached list
 * (and the plugin's option) is rebuilt from the flags.
 *
 * @param int  $post_id Post ID.
 * @param bool $unlist  True to unlist, false to list.
 */
function lailatulqadar_set_unlisted( $post_id, bool $unlist ): void {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return;
	}
	if ( $unlist ) {
		update_post_meta( $post_id, '_lq_unlisted', '1' );
	} else {
		delete_post_meta( $post_id, '_lq_unlisted' );
	}
	lailatulqadar_unlist_rebuild();
}

/**
 * Import the plugin's list (option unlist_posts) on any request, front end
 * included, and again whenever it changed after the last sync (the plugin
 * active and editing it). The first import adds to what the theme holds;
 * later ones mirror the plugin's list exactly.
 */
function lailatulqadar_unlist_sync_legacy(): void {
	// Once per request.
	if ( ! empty( $GLOBALS['lailatulqadar_unlist_synced'] ) ) {
		return;
	}
	$GLOBALS['lailatulqadar_unlist_synced'] = true;
	$legacy = lailatulqadar_unlist_clean( get_option( 'unlist_posts', array() ) );
	$seen   = get_option( 'lailatulqadar_unlist_legacy_hash', false );
	if ( md5( (string) wp_json_encode( $legacy ) ) === $seen ) {
		return;
	}
	$flagged = lailatulqadar_unlist_ids_from_meta();
	$target  = false === $seen
		? lailatulqadar_unlist_clean( array_merge( $legacy, $flagged, (array) get_option( 'lailatulqadar_unlisted_posts', array() ) ) )
		: $legacy;
	foreach ( array_diff( $target, $flagged ) as $id ) {
		update_post_meta( $id, '_lq_unlisted', '1' );
	}
	foreach ( array_diff( $flagged, $target ) as $id ) {
		delete_post_meta( $id, '_lq_unlisted' );
	}
	lailatulqadar_unlist_rebuild();
}
add_action( 'init', 'lailatulqadar_unlist_sync_legacy', 1 );

/**
 * Sitemap exclusions run while the plugin is active as well (it has none of
 * its own); the IDs then come from its list through lailatulqadar_unlist_sync_legacy().
 */
function lailatulqadar_unlist_sitemaps_on(): bool {
	return (bool) apply_filters( 'lailatulqadar_unlist_enabled', true );
}

/**
 * IDs to leave out of every sitemap, the theme's or the plugin's.
 *
 * @return int[]
 */
function lailatulqadar_unlist_sitemap_ids(): array {
	return lailatulqadar_unlist_sitemaps_on() ? lailatulqadar_unlisted_ids() : array();
}

/**
 * Whether an admin-originated request should still see unlisted posts. The
 * plugin's rule: the admin screens, and AJAX sent from an admin page by a
 * user who can edit posts. The theme adds REST requests from such a user,
 * so the block editor can still link to an unlisted post.
 */
function lailatulqadar_unlist_is_editor_context(): bool {
	if ( is_admin() && ( ! wp_doing_ajax() || lailatulqadar_unlist_is_admin_referer() ) ) {
		return true;
	}
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) ) {
		return true;
	}
	return false;
}

/**
 * Same-host admin referer from a user who can edit posts (plugin 1.1.10).
 */
function lailatulqadar_unlist_is_admin_referer(): bool {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return false;
	}
	$referer = wp_get_referer();
	if ( ! $referer ) {
		return false;
	}
	$r = wp_parse_url( $referer );
	$a = wp_parse_url( admin_url() );
	if ( ! is_array( $r ) || ! is_array( $a ) || empty( $r['host'] ) || empty( $a['host'] ) || empty( $r['path'] ) || empty( $a['path'] ) ) {
		return false;
	}
	if ( strtolower( $r['host'] ) !== strtolower( $a['host'] ) ) {
		return false;
	}
	return 0 === strpos( trailingslashit( $r['path'] ), trailingslashit( $a['path'] ) );
}

/**
 * Leave unlisted posts out of every listing query. pre_get_posts also runs
 * for get_posts(), whose suppress_filters skips posts_where, so related
 * lists and widgets are covered too. Singular views are untouched.
 *
 * @param WP_Query $query Query being prepared.
 */
function lailatulqadar_unlist_pre_get_posts( $query ): void {
	if ( ! lailatulqadar_unlist_active() || lailatulqadar_unlist_is_editor_context() || $query->is_singular() ) {
		return;
	}
	$ids = lailatulqadar_unlisted_ids();
	if ( ! $ids ) {
		return;
	}
	$not_in = array_filter( array_map( 'intval', (array) $query->get( 'post__not_in' ) ) );
	$query->set( 'post__not_in', array_values( array_unique( array_merge( $not_in, $ids ) ) ) );
}
add_action( 'pre_get_posts', 'lailatulqadar_unlist_pre_get_posts', 20 );

/**
 * Previous and next post links.
 */
function lailatulqadar_unlist_adjacent_where( $where ) {
	if ( ! lailatulqadar_unlist_active() || ( is_admin() && ( ! wp_doing_ajax() || lailatulqadar_unlist_is_admin_referer() ) ) ) {
		return $where;
	}
	$ids = lailatulqadar_unlisted_ids();
	return $ids ? $where . ' AND p.ID NOT IN (' . implode( ',', $ids ) . ')' : $where;
}
add_filter( 'get_next_post_where', 'lailatulqadar_unlist_adjacent_where', 20 );
add_filter( 'get_previous_post_where', 'lailatulqadar_unlist_adjacent_where', 20 );

/**
 * wp_list_pages().
 */
function lailatulqadar_unlist_list_pages_excludes( $exclude ) {
	if ( ! lailatulqadar_unlist_active() ) {
		return $exclude;
	}
	return array_merge( (array) $exclude, lailatulqadar_unlisted_ids() );
}
add_filter( 'wp_list_pages_excludes', 'lailatulqadar_unlist_list_pages_excludes' );

/**
 * Core sitemaps.
 */
function lailatulqadar_unlist_sitemap_args( $args ) {
	if ( ! lailatulqadar_unlist_sitemaps_on() || ! lailatulqadar_unlisted_ids() ) {
		return $args;
	}
	$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), lailatulqadar_unlisted_ids() );
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'lailatulqadar_unlist_sitemap_args', 20 );

/**
 * Rank Math sitemaps: an entry for an unlisted post is dropped.
 *
 * @param array|false $url    Sitemap entry.
 * @param string      $type   Entry type.
 * @param object      $object Post or term.
 */
function lailatulqadar_unlist_rankmath_sitemap_entry( $url, $type = '', $object = null ) {
	if ( lailatulqadar_unlist_sitemaps_on() && 'post' === $type && is_object( $object ) && isset( $object->ID ) && lailatulqadar_is_unlisted( $object->ID ) ) {
		return false;
	}
	return $url;
}
add_filter( 'rank_math/sitemap/entry', 'lailatulqadar_unlist_rankmath_sitemap_entry', 20, 3 );

/**
 * The current singular post is unlisted.
 */
function lailatulqadar_unlist_current_is_unlisted(): bool {
	if ( ! lailatulqadar_unlist_active() || ! is_singular() ) {
		return false;
	}
	$id = get_queried_object_id();
	return $id > 0 && lailatulqadar_is_unlisted( $id );
}

/**
 * noindex for core robots. Yoast's own robots array is emptied, as the plugin
 * does, so the theme directive wins.
 */
function lailatulqadar_unlist_wp_robots( $robots ) {
	if ( ! lailatulqadar_unlist_current_is_unlisted() ) {
		return $robots;
	}
	add_filter( 'wpseo_robots_array', '__return_empty_array' );
	$robots['noindex'] = true;
	$robots['follow']  = true;
	unset( $robots['index'], $robots['max-image-preview'] );
	return $robots;
}
add_filter( 'wp_robots', 'lailatulqadar_unlist_wp_robots', 20 );

/**
 * noindex through Rank Math when it prints the robots tag.
 */
function lailatulqadar_unlist_rankmath_robots( $robots ) {
	if ( lailatulqadar_unlist_current_is_unlisted() ) {
		$robots['index'] = 'noindex';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'lailatulqadar_unlist_rankmath_robots', 20 );


/* ── Admin ─────────────────────────────────────────────────────────────── */

function lailatulqadar_unlist_post_types(): array {
	return array_values( get_post_types( array( 'public' => true ), 'names' ) );
}

/**
 * The Unlisted box in the editor sidebar.
 */
function lailatulqadar_unlist_register_metabox(): void {
	if ( ! lailatulqadar_unlist_active() ) {
		return;
	}
	add_meta_box( 'lailatulqadar-unlist', __( 'Unlisted', 'lailatulqadar' ), 'lailatulqadar_unlist_metabox_render', lailatulqadar_unlist_post_types(), 'side', 'high' );
}
add_action( 'add_meta_boxes', 'lailatulqadar_unlist_register_metabox' );

/**
 * Draw the Unlisted box.
 *
 * @param WP_Post $post Post being edited.
 */
function lailatulqadar_unlist_metabox_render( $post ): void {
	wp_nonce_field( 'lailatulqadar_unlist_save', 'lailatulqadar_unlist_nonce' );
	?>
	<p><label><input type="checkbox" name="lailatulqadar_unlisted" value="1"<?php checked( lailatulqadar_is_unlisted( $post->ID ) ); ?>> <?php esc_html_e( 'Unlist this post', 'lailatulqadar' ); ?></label></p>
	<p class="description"><?php esc_html_e( 'Hidden from the home page, archives, feeds, search, sitemaps and related links, and marked noindex. Anyone with the URL can still open it.', 'lailatulqadar' ); ?></p>
	<?php
}

/**
 * Save from the editor box, Quick Edit or Bulk Edit.
 */
function lailatulqadar_unlist_save( $post_id ): void {
	if ( ! lailatulqadar_unlist_active() || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// phpcs:disable WordPress.Security.NonceVerification -- each branch verifies its own nonce.
	if ( isset( $_REQUEST['bulk_edit'], $_REQUEST['lailatulqadar_unlist_bulk'] ) ) {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-posts' ) ) {
			return;
		}
		$value = sanitize_key( wp_unslash( $_REQUEST['lailatulqadar_unlist_bulk'] ) );
		if ( 'unlist' === $value || 'list' === $value ) {
			lailatulqadar_set_unlisted( $post_id, 'unlist' === $value );
		}
		return;
	}
	if ( isset( $_POST['lailatulqadar_unlist_qe_nonce'] ) ) {
		if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lailatulqadar_unlist_qe_nonce'] ) ), 'lailatulqadar_unlist_quick_edit' ) ) {
			lailatulqadar_set_unlisted( $post_id, isset( $_POST['lailatulqadar_unlisted_qe'] ) );
		}
		return;
	}
	if ( isset( $_POST['lailatulqadar_unlist_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lailatulqadar_unlist_nonce'] ) ), 'lailatulqadar_unlist_save' ) ) {
		lailatulqadar_set_unlisted( $post_id, isset( $_POST['lailatulqadar_unlisted'] ) );
	}
	// phpcs:enable
}
add_action( 'save_post', 'lailatulqadar_unlist_save' );

/**
 * Column, Quick Edit and Bulk Edit on every public post type list.
 */
function lailatulqadar_unlist_list_table_hooks(): void {
	if ( ! lailatulqadar_unlist_active() ) {
		return;
	}
	foreach ( lailatulqadar_unlist_post_types() as $type ) {
		add_filter( "manage_{$type}_posts_columns", 'lailatulqadar_unlist_column' );
		add_action( "manage_{$type}_posts_custom_column", 'lailatulqadar_unlist_column_cell', 10, 2 );
		add_filter( "views_edit-{$type}", 'lailatulqadar_unlist_view' );
	}
	add_action( 'quick_edit_custom_box', 'lailatulqadar_unlist_quick_edit', 10, 2 );
	add_action( 'bulk_edit_custom_box', 'lailatulqadar_unlist_bulk_edit', 10, 2 );
	add_filter( 'display_post_states', 'lailatulqadar_unlist_post_state', 10, 2 );
	add_action( 'parse_query', 'lailatulqadar_unlist_filter_view' );
}
add_action( 'admin_init', 'lailatulqadar_unlist_list_table_hooks' );

/**
 * Add the Unlisted column to the posts list.
 *
 * @param array $columns Column names keyed by slug.
 * @return array
 */
function lailatulqadar_unlist_column( $columns ) {
	$columns['lailatulqadar_unlisted'] = __( 'Unlisted', 'lailatulqadar' );
	return $columns;
}

/**
 * Fill the Unlisted column. The data-unlisted value lets Quick Edit tick
 * its box from the row.
 *
 * @param string $column  Column slug.
 * @param int    $post_id Post ID.
 */
function lailatulqadar_unlist_column_cell( $column, $post_id ): void {
	if ( 'lailatulqadar_unlisted' !== $column ) {
		return;
	}
	$on = lailatulqadar_is_unlisted( $post_id );
	?>
	<span class="lq-unlist-status" data-unlisted="<?php echo $on ? '1' : '0'; ?>"><?php echo $on ? esc_html__( 'Yes', 'lailatulqadar' ) : '&mdash;'; ?></span>
	<?php
}

/**
 * The Unlisted box in Quick Edit.
 *
 * @param string $column    Column slug.
 * @param string $post_type Post type of the list.
 */
function lailatulqadar_unlist_quick_edit( $column, $post_type ): void {
	if ( 'lailatulqadar_unlisted' !== $column || ! in_array( $post_type, lailatulqadar_unlist_post_types(), true ) ) {
		return;
	}
	wp_nonce_field( 'lailatulqadar_unlist_quick_edit', 'lailatulqadar_unlist_qe_nonce' );
	?>
	<fieldset class="inline-edit-col-right"><div class="inline-edit-col"><label class="alignleft"><input type="checkbox" name="lailatulqadar_unlisted_qe" value="1"> <span class="checkbox-title"><?php esc_html_e( 'Unlist this post', 'lailatulqadar' ); ?></span></label></div></fieldset>
	<?php
}

/**
 * The Unlisted choice in Bulk Edit.
 *
 * @param string $column    Column slug.
 * @param string $post_type Post type of the list.
 */
function lailatulqadar_unlist_bulk_edit( $column, $post_type ): void {
	if ( 'lailatulqadar_unlisted' !== $column || ! in_array( $post_type, lailatulqadar_unlist_post_types(), true ) ) {
		return;
	}
	?>
	<fieldset class="inline-edit-col-right">
		<div class="inline-edit-col">
			<label class="inline-edit-group">
				<span class="title"><?php esc_html_e( 'Unlisted', 'lailatulqadar' ); ?></span>
				<select name="lailatulqadar_unlist_bulk">
					<option value="-1"><?php esc_html_e( 'No change', 'lailatulqadar' ); ?></option>
					<option value="unlist"><?php esc_html_e( 'Unlist', 'lailatulqadar' ); ?></option>
					<option value="list"><?php esc_html_e( 'List', 'lailatulqadar' ); ?></option>
				</select>
			</label>
		</div>
	</fieldset>
	<?php
}

/**
 * Whether the posts list is showing the Unlisted view.
 *
 * @return bool
 */
function lailatulqadar_unlist_viewing_unlisted(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- list filter only.
	$status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';
	return is_admin() && 'unlisted' === $status;
}

/**
 * Mark an unlisted post "Unlisted" in the list, except in the Unlisted view.
 *
 * @param array   $states Post states.
 * @param WP_Post $post   Post.
 * @return array
 */
function lailatulqadar_unlist_post_state( $states, $post ) {
	if ( ! lailatulqadar_unlist_viewing_unlisted() && lailatulqadar_is_unlisted( $post->ID ) ) {
		$states['lailatulqadar_unlisted'] = __( 'Unlisted', 'lailatulqadar' );
	}
	return $states;
}

/**
 * The "Unlisted (n)" link above the posts list, when any post of this type
 * is unlisted.
 *
 * @param array $views View links keyed by slug.
 * @return array
 */
function lailatulqadar_unlist_view( $views ) {
	$ids = lailatulqadar_unlisted_ids();
	if ( ! $ids ) {
		return $views;
	}
	$screen = get_current_screen();
	$q      = new WP_Query(
		array(
			'post_type'      => $screen && $screen->post_type ? $screen->post_type : 'any',
			'post__in'       => $ids,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( $q->found_posts > 0 ) {
		$current           = lailatulqadar_unlist_viewing_unlisted() ? ' class="current" aria-current="page"' : '';
		$views['unlisted'] = '<a href="' . esc_url( add_query_arg( 'post_status', 'unlisted' ) ) . '"' . $current . '>' . esc_html__( 'Unlisted', 'lailatulqadar' ) . ' <span class="count">(' . (int) $q->found_posts . ')</span></a>';
	}
	return $views;
}

/**
 * Limit the posts list to unlisted posts in the Unlisted view.
 *
 * @param WP_Query $query Query.
 */
function lailatulqadar_unlist_filter_view( $query ): void {
	global $pagenow;
	if ( 'edit.php' === $pagenow && $query->is_main_query() && lailatulqadar_unlist_viewing_unlisted() ) {
		$ids = lailatulqadar_unlisted_ids();
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
		$query->set( 'post_status', 'any' );
	}
}

/**
 * Quick Edit: tick the box from the row's current state.
 */
function lailatulqadar_unlist_admin_script( $hook ): void {
	if ( 'edit.php' !== $hook || ! lailatulqadar_unlist_active() ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, lailatulqadar_unlist_post_types(), true ) ) {
		return;
	}
	wp_enqueue_script( 'lailatulqadar-unlist-admin', LQ_URI . '/js/unlist-admin.js', array( 'jquery', 'inline-edit-post' ), LQ_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'lailatulqadar_unlist_admin_script' );
