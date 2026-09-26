<?php
/**
 * Login page: styled to match the theme in both colour schemes, with the
 * logo mark, the site name and a tagline in the visitor's login language.
 *
 * Covers every screen wp-login.php serves: log in, lost password, reset
 * password, registration and the interim (session expired) login.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Language role for the login screen, from the locale WordPress is using
 * (the visitor may pick a language from the login screen's switcher).
 *
 * @return string primary or secondary.
 */
function lq_login_role() {
	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	return 0 === strpos( $locale, 'ms' ) ? 'secondary' : 'primary';
}

/**
 * Tagline and note for the login screen.
 *
 * @return array tagline, note.
 */
function lq_login_text() {
	$role    = lq_login_role();
	$tagline = lq_setting( 'login_tagline_' . $role );
	if ( '' === $tagline ) {
		$tagline = 'secondary' === $role
			? 'Panduan Lailatulqadar berdasarkan al-Quran dan hadis sahih.'
			: 'A guide to Laylat al-Qadr from the Qurʾān and sound hadith.';
	}
	return array(
		'tagline' => $tagline,
		'note'    => lq_setting( 'login_note_' . $role ),
	);
}

/**
 * Styles, fonts and the accent colours chosen in the Colours tab.
 */
function lq_login_assets() {
	wp_enqueue_style( 'lailatulqadar-login', LQ_URI . '/css/login.css', array( 'login' ), LQ_VERSION );
	$css = sprintf(
		'body.login{--lq-accent-night:%1$s;--lq-accent-dawn:%2$s;}' .
		'@font-face{font-family:"EB Garamond";font-style:normal;font-weight:400 700;font-display:swap;src:url("%3$s") format("woff2");}' .
		'@font-face{font-family:"EB Garamond";font-style:italic;font-weight:400 700;font-display:swap;src:url("%4$s") format("woff2");}' .
		'@font-face{font-family:"Arslan Wessam B";font-style:normal;font-weight:400;font-display:swap;size-adjust:140%%;src:url("%5$s") format("woff2");}',
		esc_attr( lq_setting( 'accent_night' ) ),
		esc_attr( lq_setting( 'accent_dawn' ) ),
		esc_url( LQ_URI . '/fonts/eb-garamond/eb-garamond-roman.woff2' ),
		esc_url( LQ_URI . '/fonts/eb-garamond/eb-garamond-italic.woff2' ),
		esc_url( LQ_URI . '/fonts/arslan-wessam/arslan-wessam-b.woff2' )
	);
	wp_add_inline_style( 'lailatulqadar-login', $css );
}
add_action( 'login_enqueue_scripts', 'lq_login_assets' );

/**
 * Scheme class on the body: night, dawn, or auto (follows the device).
 *
 * @param array $classes Body classes.
 * @return array
 */
function lq_login_body_class( $classes ) {
	$classes[] = 'lq-login';
	$classes[] = 'lq-scheme-' . lq_setting( 'scheme' );
	return $classes;
}
add_filter( 'login_body_class', 'lq_login_body_class' );

/**
 * The brand block above the form: mark, site name, tagline and note.
 */
function lq_login_brand() {
	$text = lq_login_text();
	?>
	<div class="lq-login-brand">
		<a class="lq-login-brand__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="lq-mark lq-login-brand__mark"><?php echo lq_mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php echo lq_calligraphy_html( 'lq-calli--login' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="lq-login-brand__name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</a>
		<p class="lq-login-brand__tagline"><?php echo esc_html( $text['tagline'] ); ?></p>
		<?php if ( '' !== $text['note'] ) : ?>
			<p class="lq-login-brand__note"><?php echo esc_html( $text['note'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'login_header', 'lq_login_brand' );

/**
 * The default WordPress logo link points to the site, named after it.
 *
 * @return string
 */
function lq_login_header_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'lq_login_header_url' );

/**
 * Accessible name for the (visually hidden) default logo link.
 *
 * @return string
 */
function lq_login_header_text() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'lq_login_header_text' );

/**
 * Browser tab title without "WordPress": "Log In ‹ Laylat al-Qadr".
 *
 * @param string $login_title Full title.
 * @param string $title       Screen title (Log In, Lost Password, and so on).
 * @return string
 */
function lq_login_title( $login_title, $title ) {
	return $title . ' ‹ ' . get_bloginfo( 'name' );
}
add_filter( 'login_title', 'lq_login_title', 10, 2 );

/**
 * Favicon on the login screen, as on the site.
 */
function lq_login_icons() {
	if ( function_exists( 'lq_default_icons' ) ) {
		lq_default_icons();
	}
}
add_action( 'login_head', 'lq_login_icons' );
