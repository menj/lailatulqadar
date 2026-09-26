<?php
/**
 * First-run setup: creates the guide pages in both languages, links
 * the two languages to each other, places the Malay pages beneath the
 * Malay home at /ms/ and sets the static front page.
 *
 * Runs on the first admin visit after activation and again on demand
 * from Appearance > Lailatulqadar > Tools. The routine is idempotent:
 * existing pages are reused and never overwritten.
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
 * Run the flagged setup on the first admin visit by an administrator.
 */
function lq_maybe_run_setup() {
	if ( wp_doing_ajax() || ! get_option( 'lailatulqadar_needs_setup' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_option( 'lailatulqadar_needs_setup' );
	set_transient( 'lailatulqadar_setup_report', lq_run_setup(), MINUTE_IN_SECONDS * 10 );
}
add_action( 'admin_init', 'lq_maybe_run_setup' );

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
		'created'  => array(),
		'reused'   => array(),
		'renamed'  => array(),
		'seeded'   => array(),
		'errors'   => array(),
	);
	$map = lq_page_map();

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
	if ( '' === $title || mb_strlen( $title ) > LQ_TITLE_LIMIT || str_replace( '[lq_ramadan]', '2027', $def['seo'] ) === $title && $title !== $def['seo'] ) {
		update_post_meta( $id, LQ_META_TITLE, $def['seo'] );
	}
	$desc = (string) get_post_meta( $id, LQ_META_DESCRIPTION, true );
	if ( '' === $desc || mb_strlen( $desc ) > LQ_META_LIMIT || str_replace( '[lq_ramadan]', '2027', $def['meta'] ) === $desc && $desc !== $def['meta'] ) {
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
 * Fill an empty draft with the article shipped in content/<lang>/<key>.html.
 *
 * Only drafts with no content are touched, so an editor's work is never
 * overwritten. Internal links written as {{url:key}} resolve to the page's
 * address in the same language, and footnotes load into the core
 * footnotes meta. Seeded pages stay drafts until an editor publishes them.
 *
 * @param int    $id     Page ID.
 * @param string $key    Page key.
 * @param string $role   primary or secondary.
 * @param array  $report Report, passed by reference.
 */
function lq_seed_content( $id, $key, $role, &$report ) {
	$lang = 'secondary' === $role ? 'ms' : 'en';
	$file = LQ_DIR . '/content/' . $lang . '/' . $key . '.html';
	$post = get_post( $id );
	if ( ! $post || 'draft' !== $post->post_status || '' !== trim( $post->post_content ) || ! is_readable( $file ) ) {
		return;
	}

	$content = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$defs    = lq_page_definitions();
	$map     = lq_page_map();
	$content = preg_replace_callback(
		'/\{\{url:([a-z0-9]+)\}\}/',
		function ( $m ) use ( $defs, $role, $map ) {
			if ( empty( $defs[ $m[1] ] ) ) {
				return home_url( '/' );
			}
			if ( ! empty( $map[ $role ][ $m[1] ] ) && get_post( (int) $map[ $role ][ $m[1] ] ) ) {
				return get_permalink( (int) $map[ $role ][ $m[1] ] );
			}
			$prefix = 'secondary' === $role ? lq_setting( 'secondary_slug' ) . '/' : '';
			$slug   = 'home' === $m[1] ? '' : $defs[ $m[1] ][ $role ]['slug'] . '/';
			return home_url( '/' . $prefix . $slug );
		},
		$content
	);

	wp_update_post(
		array(
			'ID'           => $id,
			'post_content' => wp_slash( $content ),
		)
	);

	$notes = LQ_DIR . '/content/' . $lang . '/' . $key . '.footnotes.json';
	if ( is_readable( $notes ) ) {
		update_post_meta( $id, 'footnotes', wp_slash( (string) file_get_contents( $notes ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
	$report['seeded'][] = $defs[ $key ][ $role ]['title'];
}
