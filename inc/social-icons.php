<?php
/**
 * Social profile icons: the lailatulqadar/social-icons block (in the footer
 * part), the [lailatulqadar_social_icons] shortcode and the "Social icons"
 * pattern. Inline SVGs from the Minimalist Social Icons Pack 2.8 (GPL; the
 * ten in images/social/ come from CC0 sources). Profile URLs are set in
 * Theme Options > Reading and sharing.
 *
 * @package Lailatulqadar
 * @since   1.17.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Networks in display order: key => label.
 *
 * @return array
 */
function lailatulqadar_social_networks() {
	return array(
		'x'         => 'X',
		'facebook'  => 'Facebook',
		'youtube'   => 'YouTube',
		'instagram' => 'Instagram',
		'tiktok'    => 'TikTok',
		'threads'   => 'Threads',
		'bluesky'   => 'Bluesky',
		'mastodon'  => 'Mastodon',
		'telegram'  => 'Telegram',
		'whatsapp'  => 'WhatsApp',
	);
}

/**
 * Networks with a URL: key => array( label, url ).
 *
 * @return array
 */
function lailatulqadar_social_profiles() {
	$out = array();
	foreach ( lailatulqadar_social_networks() as $key => $label ) {
		$url = esc_url_raw( (string) lq_setting( 'social_' . $key ), array( 'http', 'https' ) );
		if ( '' !== $url ) {
			$out[ $key ] = array( $label, $url );
		}
	}
	return $out;
}

/**
 * One icon's SVG, decorative.
 *
 * @param string $key Network key.
 * @return string
 */
function lailatulqadar_social_svg( $key ) {
	static $cache = array();
	if ( ! isset( $cache[ $key ] ) ) {
		$file          = LQ_DIR . '/images/social/' . sanitize_key( $key ) . '.svg';
		$svg           = is_readable( $file ) ? trim( (string) file_get_contents( $file ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$cache[ $key ] = '' === $svg ? '' : preg_replace( '/^<svg\b/', '<svg class="lq-social__icon" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"', $svg );
	}
	return $cache[ $key ];
}

/**
 * The icon list, or '' with no profile set. Labels follow the page language.
 *
 * @param string $context Class suffix.
 * @return string
 */
function lailatulqadar_social_icons_html( $context = '' ) {
	$profiles = lailatulqadar_social_profiles();
	if ( ! $profiles ) {
		return '';
	}
	wp_enqueue_style( 'lailatulqadar-social', LQ_URI . '/css/social-icons.css', array(), LQ_VERSION );
	$ms    = 'secondary' === lq_current_role();
	$class = 'lq-social' . ( '' !== $context ? ' lq-social--' . sanitize_html_class( $context ) : '' );
	$html  = '<ul class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $ms ? 'Profil kami' : __( 'Our profiles', 'lailatulqadar' ) ) . '">';
	foreach ( $profiles as $key => $p ) {
		/* translators: %s: network name, e.g. YouTube. */
		$label = $ms ? sprintf( 'Profil %s', $p[0] ) : sprintf( __( '%s profile', 'lailatulqadar' ), $p[0] );
		$html .= '<li><a class="lq-social__link" href="' . esc_url( $p[1] ) . '" rel="me noopener" aria-label="' . esc_attr( $label ) . '">' . lailatulqadar_social_svg( $key ) . '</a></li>';
	}
	return $html . '</ul>';
}

/**
 * Load the stylesheet in the head when the footer prints icons.
 */
function lailatulqadar_social_enqueue() {
	if ( '1' === lq_setting( 'social_footer' ) && lailatulqadar_social_profiles() ) {
		wp_enqueue_style( 'lailatulqadar-social', LQ_URI . '/css/social-icons.css', array(), LQ_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'lailatulqadar_social_enqueue', 25 );

/**
 * [lailatulqadar_social_icons]
 *
 * @return string
 */
function lailatulqadar_social_shortcode() {
	return lailatulqadar_social_icons_html( 'inline' );
}
add_shortcode( 'lailatulqadar_social_icons', 'lailatulqadar_social_shortcode' );

/**
 * The block and the pattern.
 */
function lailatulqadar_social_register() {
	register_block_type( LQ_DIR . '/blocks/social-icons' );
	if ( function_exists( 'register_block_pattern' ) ) {
		register_block_pattern(
			'lailatulqadar/social-icons',
			array(
				'title'       => __( 'Social icons', 'lailatulqadar' ),
				'description' => __( 'Links to the site profiles set in Theme Options.', 'lailatulqadar' ),
				'categories'  => array( 'footer', 'text' ),
				'content'     => '<!-- wp:lailatulqadar/social-icons /-->',
			)
		);
	}
}
add_action( 'init', 'lailatulqadar_social_register' );
