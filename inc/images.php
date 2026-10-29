<?php
/**
 * Freely licensed Wikimedia Commons images for the guide pages.
 *
 * The images live in images/commons/ as WebP at two widths; their credits
 * live in inc/data/images.json, keyed by page key. Like the article files,
 * that file is data only: it is read with json_decode() and checked, never
 * included or executed. bin/fetch-commons-images.py writes both.
 *
 * Each image renders as a figure with its caption and a credit naming the
 * author, the licence (linked to its deed) and the source file page, as the
 * Commons licences require.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Licences the theme accepts. Anything else in the data file is dropped.
 *
 * @return string[] Regular expressions matched against the licence name.
 */
function lq_image_licences() {
	return array( '/^CC0/i', '/^Public domain$/i', '/^PD\b/i', '/^CC BY(-SA)? \d\.\d/i' );
}

/**
 * The image data, validated: page key => list of images.
 *
 * @return array
 */
function lq_images_data() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}
	$data = array();
	$file = LQ_DIR . '/inc/data/images.json';
	if ( ! is_readable( $file ) ) {
		return $data;
	}
	$raw = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $raw ) ) {
		return $data;
	}
	$fields = array( 'file', 'alt', 'caption', 'artist', 'licence', 'licence_url', 'source' );
	foreach ( $raw as $key => $list ) {
		if ( ! is_string( $key ) || ! preg_match( '/^[a-z0-9]+$/', $key ) || ! is_array( $list ) ) {
			continue;
		}
		foreach ( $list as $image ) {
			if ( ! is_array( $image ) ) {
				continue;
			}
			foreach ( $fields as $field ) {
				if ( ! isset( $image[ $field ] ) || ! is_string( $image[ $field ] ) ) {
					continue 2;
				}
			}
			if ( ! preg_match( '/^[a-z0-9-]+$/', $image['file'] ) || empty( $image['widths'] ) || ! is_array( $image['widths'] ) ) {
				continue;
			}
			$ok = false;
			foreach ( lq_image_licences() as $pattern ) {
				$ok = $ok || preg_match( $pattern, $image['licence'] );
			}
			if ( ! $ok || 0 !== strpos( $image['source'], 'https://commons.wikimedia.org/wiki/File:' ) ) {
				continue;
			}
			$image['widths'] = array_values( array_filter( array_map( 'absint', $image['widths'] ) ) );
			$image['width']  = isset( $image['width'] ) ? absint( $image['width'] ) : 0;
			$image['height'] = isset( $image['height'] ) ? absint( $image['height'] ) : 0;
			$image['lead']   = ! empty( $image['lead'] );
			$data[ $key ][]  = $image;
		}
	}
	return $data;
}

/**
 * One image of a page, by position (1 is the lead image).
 *
 * @param string $key Page key.
 * @param int    $n   1-based position.
 * @return array|null
 */
function lq_image( $key, $n ) {
	$data = lq_images_data();
	return isset( $data[ $key ][ $n - 1 ] ) ? $data[ $key ][ $n - 1 ] : null;
}

/**
 * Path or address of one size of an image.
 *
 * @param array  $image Image entry.
 * @param int    $width Width in pixels.
 * @param string $base  LQ_DIR or LQ_URI.
 * @return string
 */
function lq_image_file( $image, $width, $base ) {
	return $base . '/images/commons/' . $image['file'] . '-' . (int) $width . '.webp';
}

/**
 * The credit line: "Photo: Author, CC BY-SA 4.0, via Wikimedia Commons",
 * with the licence linked to its deed and the source to the file page.
 *
 * @param array $image Image entry.
 * @return string Escaped HTML.
 */
function lq_image_credit_html( $image ) {
	$licence = '' !== $image['licence_url']
		? '<a href="' . esc_url( $image['licence_url'] ) . '" rel="license noopener">' . esc_html( $image['licence'] ) . '</a>'
		: '<span>' . esc_html( $image['licence'] ) . '</span>';

	return '<span class="lq-credit">'
		/* translators: %s: author of the image. */
		. '<span>' . esc_html( sprintf( 'secondary' === lq_current_role() ? 'Imej: %s' : __( 'Image: %s', 'lailatulqadar' ), $image['artist'] ) ) . '</span>'
		. $licence
		. '<a href="' . esc_url( $image['source'] ) . '" rel="noopener">' . esc_html__( 'Wikimedia Commons', 'lailatulqadar' ) . '</a>'
		. '</span>';
}

/**
 * Plain-text credit, for an attachment caption.
 *
 * @param array $image Image entry.
 * @return string
 */
function lq_image_credit_text( $image ) {
	/* translators: 1: author, 2: licence, 3: source page. */
	return sprintf( __( 'Image: %1$s, %2$s, via Wikimedia Commons (%3$s)', 'lailatulqadar' ), $image['artist'], $image['licence'], $image['source'] );
}

/**
 * A figure for one image: responsive srcset, width and height for layout
 * stability, lazy loading except for the lead image, caption and credit.
 * On Malay pages, alt_ms and caption_ms replace the English text.
 *
 * @param array $image Image entry.
 * @return string
 */
function lq_image_figure( $image ) {
	// Malay pages take the Malay alt text and caption where the entry has them.
	if ( 'secondary' === lq_current_role() ) {
		foreach ( array( 'alt', 'caption' ) as $field ) {
			if ( isset( $image[ $field . '_ms' ] ) && is_string( $image[ $field . '_ms' ] ) && '' !== $image[ $field . '_ms' ] ) {
				$image[ $field ] = $image[ $field . '_ms' ];
			}
		}
	}
	$srcset = array();
	foreach ( $image['widths'] as $w ) {
		if ( file_exists( lq_image_file( $image, $w, LQ_DIR ) ) ) {
			$srcset[] = esc_url( lq_image_file( $image, $w, LQ_URI ) ) . ' ' . $w . 'w';
		}
	}
	if ( ! $srcset ) {
		return '';
	}
	$largest = max( $image['widths'] );
	$loading = $image['lead'] ? 'fetchpriority="high"' : 'loading="lazy"';

	return sprintf(
		'<figure class="lq-photo%1$s"><img src="%2$s" srcset="%3$s" sizes="(max-width: 720px) 100vw, 720px" alt="%4$s" width="%5$d" height="%6$d" %7$s decoding="async" /><figcaption>%8$s%9$s</figcaption></figure>',
		$image['lead'] ? ' lq-photo--lead' : '',
		esc_url( lq_image_file( $image, $largest, LQ_URI ) ),
		implode( ', ', $srcset ),
		esc_attr( $image['alt'] ),
		$image['width'],
		$image['height'],
		$loading,
		'' !== $image['caption'] ? '<span class="lq-photo__caption">' . esc_html( $image['caption'] ) . '</span>' : '',
		lq_image_credit_html( $image )
	);
}

/**
 * The page key of a guide page, from the page map.
 *
 * @param int $id Page ID.
 * @return string
 */
function lq_page_key_of( $id ) {
	foreach ( lq_page_map() as $pages ) {
		$key = array_search( (int) $id, array_map( 'intval', (array) $pages ), true );
		if ( false !== $key ) {
			return (string) $key;
		}
	}
	return '';
}

/**
 * Every image, for the credits list on the Sources page.
 *
 * @return string
 */
function lq_image_credits_html() {
	$defs  = lq_page_definitions();
	$role  = lq_current_role();
	$items = '';
	foreach ( lq_images_data() as $key => $list ) {
		$page = isset( $defs[ $key ][ $role ]['title'] ) ? $defs[ $key ][ $role ]['title'] : $key;
		foreach ( $list as $image ) {
			$alt    = 'secondary' === $role && ! empty( $image['alt_ms'] ) && is_string( $image['alt_ms'] ) ? $image['alt_ms'] : $image['alt'];
			$items .= '<li><strong>' . esc_html( $page ) . ':</strong> ' . esc_html( $alt ) . '. ' . lq_image_credit_html( $image ) . '</li>';
		}
	}
	return $items ? '<ul class="lq-credits-list">' . $items . '</ul>' : '';
}

/**
 * Attach a page's lead image as its featured image, once. An image an
 * editor has set is never replaced, and an image an editor has removed is
 * not put back. Off when the Images setting is switched off.
 *
 * @param int    $id  Page ID.
 * @param string $key Page key.
 */
function lq_attach_featured_image( $id, $key ) {
	if ( '1' !== lq_setting( 'commons_featured' ) || has_post_thumbnail( $id ) || get_post_meta( $id, '_lq_featured_seeded', true ) ) {
		return;
	}
	$image = lq_image( $key, 1 );
	if ( ! $image || ! $image['lead'] ) {
		return;
	}
	$media = get_option( 'lailatulqadar_commons_media', array() );
	$media = is_array( $media ) ? $media : array();
	$att   = isset( $media[ $image['file'] ] ) ? (int) $media[ $image['file'] ] : 0;

	if ( ! $att || ! get_post( $att ) ) {
		$path = lq_image_file( $image, max( $image['widths'] ), LQ_DIR );
		if ( ! is_readable( $path ) ) {
			return;
		}
		$upload = wp_upload_bits( basename( $path ), null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! empty( $upload['error'] ) ) {
			return;
		}
		$att = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/webp',
				'post_title'     => $image['alt'],
				'post_excerpt'   => lq_image_credit_text( $image ),
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$id
		);
		if ( ! $att || is_wp_error( $att ) ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $upload['file'] ) );
		update_post_meta( $att, '_wp_attachment_image_alt', $image['alt'] );
		update_post_meta(
			$att,
			'_lq_credit',
			array(
				'artist'      => $image['artist'],
				'licence'     => $image['licence'],
				'licence_url' => $image['licence_url'],
				'source'      => $image['source'],
			)
		);
		$media[ $image['file'] ] = (int) $att;
		update_option( 'lailatulqadar_commons_media', $media, false );
	}

	set_post_thumbnail( $id, $att );
	update_post_meta( $id, '_lq_featured_seeded', 1 );
}
