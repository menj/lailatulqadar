<?php
/**
 * Contextual links — in-content links to other sites.
 *
 * Turns configured phrases found inside paragraphs of single posts into
 * ordinary links to approved domains. Nothing is labelled or styled
 * differently from the theme's normal links, and no domains are stored in
 * code: everything is configured under Appearance → Contextual Links.
 *
 * @package lailatulqadar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LQ_CL_OPTION', 'lq_cl' );
define( 'LQ_CL_META', '_' . 'lq_cl_phrases' );

/** Post types that receive links (filterable). */
function lq_cl_post_types() {
	return (array) apply_filters( 'lq_cl_post_types', array( 'post' ) );
}

function lq_cl_options() {
	$saved = get_option( LQ_CL_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	return array_merge(
		array( 'enabled' => 1, 'domains' => '', 'phrases' => '', 'max' => 3, 'new_tab' => 0 ),
		$saved
	);
}

/** Normalised host: lowercase, no www. */
function lq_cl_host( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	return preg_replace( '/^www\./', '', $host );
}

/** Approved hosts from the Domains tab, minus this site. */
function lq_cl_allowed_hosts( $opts ) {
	$own   = lq_cl_host( home_url() );
	$hosts = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $opts['domains'] ) as $line ) {
		$h = lq_cl_host( false === strpos( $line, '//' ) ? 'https://' . trim( $line ) : trim( $line ) );
		if ( $h && $h !== $own ) {
			$hosts[ $h ] = true;
		}
	}
	return $hosts;
}

/** Parse "phrase | URL" lines; keep only URLs on approved hosts. */
function lq_cl_parse( $text, $hosts ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( 2 !== count( $parts ) || '' === $parts[0] ) {
			continue;
		}
		$url = esc_url_raw( $parts[1], array( 'http', 'https' ) );
		if ( $url && isset( $hosts[ lq_cl_host( $url ) ] ) ) {
			$out[] = array( 'phrase' => $parts[0], 'url' => $url );
		}
	}
	return $out;
}

/**
 * Insert links into paragraph text. Each phrase links once per post, at most
 * one link per paragraph, never inside headings, links, code or captions.
 */
function lq_cl_filter( $content ) {
	if ( ! is_singular( lq_cl_post_types() ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$opts = lq_cl_options();
	if ( empty( $opts['enabled'] ) ) {
		return $content;
	}
	$hosts = lq_cl_allowed_hosts( $opts );
	$items = array_merge(
		lq_cl_parse( get_post_meta( get_the_ID(), LQ_CL_META, true ), $hosts ),
		lq_cl_parse( $opts['phrases'], $hosts )
	);
	if ( ! $hosts || ! $items ) {
		return $content;
	}
	$budget = max( 1, (int) $opts['max'] );
	$rel    = ! empty( $opts['new_tab'] ) ? ' target="_blank" rel="noopener"' : ' rel="noopener"';
	$skip   = array( 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'code', 'pre', 'script', 'style', 'button', 'figcaption', 'blockquote' );
	$used   = array();
	$tokens = preg_split( '/(<[^>]*>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$depth  = array_fill_keys( $skip, 0 );
	$in_p   = false;
	$done_p = false;
	foreach ( $tokens as $i => $tok ) {
		if ( '' !== $tok && '<' === $tok[0] ) {
			if ( preg_match( '#^<(/?)([a-z0-9]+)#i', $tok, $m ) ) {
				$tag = strtolower( $m[2] );
				$end = '/' === $m[1];
				if ( 'p' === $tag ) {
					$in_p   = ! $end;
					$done_p = false;
				} elseif ( isset( $depth[ $tag ] ) ) {
					$depth[ $tag ] = max( 0, $depth[ $tag ] + ( $end ? -1 : 1 ) );
				}
			}
			continue;
		}
		if ( ! $in_p || $done_p || $budget < 1 || array_sum( $depth ) ) {
			continue;
		}
		foreach ( $items as $n => $item ) {
			if ( isset( $used[ $n ] ) ) {
				continue;
			}
			$re = '/(?<![\p{L}\p{N}])(' . preg_quote( $item['phrase'], '/' ) . ')(?![\p{L}\p{N}])/iu';
			if ( preg_match( $re, $tok, $mm, PREG_OFFSET_CAPTURE ) ) {
				$link          = '<a href="' . esc_url( $item['url'] ) . '"' . $rel . '>' . $mm[1][0] . '</a>';
				$tokens[ $i ]  = $tok = substr_replace( $tok, $link, $mm[1][1], strlen( $mm[1][0] ) );
				$used[ $n ]    = true;
				$done_p        = true;
				--$budget;
				break;
			}
		}
	}
	return implode( '', $tokens );
}
add_filter( 'the_content', 'lq_cl_filter', 25 );

/* ── Per-post phrases ──────────────────────────────────────────────── */

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'lq_cl', __( 'Contextual links', 'lailatulqadar' ), function ( $post ) {
		wp_nonce_field( 'lq_cl_meta', 'lq_cl_nonce' );
		echo '<p class="description">' . esc_html__( 'One per line: phrase | URL. The phrase must appear in a paragraph; the URL must be on an approved domain.', 'lailatulqadar' ) . '</p>';
		printf( '<textarea class="widefat" rows="5" name="lq_cl_phrases">%s</textarea>', esc_textarea( (string) get_post_meta( $post->ID, LQ_CL_META, true ) ) );
	}, lq_cl_post_types(), 'side' );
} );

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['lq_cl_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['lq_cl_nonce'] ) ), 'lq_cl_meta' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, LQ_CL_META, sanitize_textarea_field( wp_unslash( isset( $_POST['lq_cl_phrases'] ) ? $_POST['lq_cl_phrases'] : '' ) ) );
} );

/* ── Settings page (tabbed) ────────────────────────────────────────── */

add_action( 'admin_menu', function () {
	add_theme_page( __( 'Contextual Links', 'lailatulqadar' ), __( 'Contextual Links', 'lailatulqadar' ), 'manage_options', 'lq_cl', 'lq_cl_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'lq_cl', LQ_CL_OPTION, array( 'sanitize_callback' => 'lq_cl_sanitize' ) );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_lq_cl' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'lq_cl-admin', get_theme_file_uri( 'css/contextual-links-admin.css' ), array(), filemtime( get_theme_file_path( 'css/contextual-links-admin.css' ) ) );
	wp_enqueue_script( 'lq_cl-admin', get_theme_file_uri( 'js/contextual-links-admin.js' ), array(), filemtime( get_theme_file_path( 'js/contextual-links-admin.js' ) ), true );
} );

function lq_cl_sanitize( $in ) {
	$in = is_array( $in ) ? $in : array();
	return array(
		'enabled' => empty( $in['enabled'] ) ? 0 : 1,
		'domains' => sanitize_textarea_field( isset( $in['domains'] ) ? $in['domains'] : '' ),
		'phrases' => sanitize_textarea_field( isset( $in['phrases'] ) ? $in['phrases'] : '' ),
		'max'     => isset( $in['max'] ) ? min( 12, max( 1, (int) $in['max'] ) ) : 3,
		'new_tab' => empty( $in['new_tab'] ) ? 0 : 1,
	);
}

function lq_cl_page() {
	$o = lq_cl_options();
	$n = LQ_CL_OPTION;
	?>
	<div class="wrap cl-admin">
		<h1><?php esc_html_e( 'Contextual Links', 'lailatulqadar' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'lq_cl' ); ?>
			<nav class="cl-admin__tabs" role="tablist">
				<button type="button" class="cl-admin__tab" role="tab" data-tab="domains"><?php esc_html_e( 'Domains', 'lailatulqadar' ); ?></button>
				<button type="button" class="cl-admin__tab" role="tab" data-tab="phrases"><?php esc_html_e( 'Phrases', 'lailatulqadar' ); ?></button>
				<button type="button" class="cl-admin__tab" role="tab" data-tab="rules"><?php esc_html_e( 'Rules', 'lailatulqadar' ); ?></button>
			</nav>

			<section class="cl-admin__panel" data-panel="domains">
				<p class="description"><?php esc_html_e( 'Approved domains, one per line. Links to any other domain, and to this site, are ignored.', 'lailatulqadar' ); ?></p>
				<textarea class="large-text code" rows="8" name="<?php echo esc_attr( $n ); ?>[domains]"><?php echo esc_textarea( $o['domains'] ); ?></textarea>
			</section>

			<section class="cl-admin__panel" data-panel="phrases">
				<p class="description"><?php esc_html_e( 'Site-wide, one per line: phrase | URL. The phrase is linked where it already appears in a paragraph.', 'lailatulqadar' ); ?></p>
				<textarea class="large-text code" rows="14" name="<?php echo esc_attr( $n ); ?>[phrases]"><?php echo esc_textarea( $o['phrases'] ); ?></textarea>
			</section>

			<section class="cl-admin__panel" data-panel="rules">
				<div class="cl-admin__row"><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[enabled]" value="1" <?php checked( $o['enabled'] ); ?>> <?php esc_html_e( 'Insert links', 'lailatulqadar' ); ?></label></div>
				<div class="cl-admin__row"><label><?php esc_html_e( 'Maximum links per post', 'lailatulqadar' ); ?> <input type="number" min="1" max="12" name="<?php echo esc_attr( $n ); ?>[max]" value="<?php echo esc_attr( $o['max'] ); ?>"></label></div>
				<div class="cl-admin__row"><label><input type="checkbox" name="<?php echo esc_attr( $n ); ?>[new_tab]" value="1" <?php checked( $o['new_tab'] ); ?>> <?php esc_html_e( 'Open in a new tab', 'lailatulqadar' ); ?></label></div>
			</section>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
