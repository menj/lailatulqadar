<?php
/**
 * Settings screen: Appearance > Lailatulqadar, organised in tabs.
 *
 * Tabs: General, Languages, Colours, Ramadan Dates, Search, Tools.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tabs and the setting keys each one owns.
 *
 * @return array
 */
function lq_settings_tabs() {
	return array(
		'general'   => array(
			'label'  => __( 'General', 'lailatulqadar' ),
			'fields' => array( 'calligraphy_id', 'footer_note_primary', 'footer_note_secondary', 'author_name', 'author_url' ),
		),
		'languages' => array(
			'label'  => __( 'Languages', 'lailatulqadar' ),
			'fields' => array( 'primary_slug', 'secondary_slug', 'primary_label', 'secondary_label', 'site_name_secondary' ),
		),
		'colours'   => array(
			'label'  => __( 'Colours', 'lailatulqadar' ),
			'fields' => array( 'scheme', 'scheme_toggle', 'accent_night', 'accent_dawn' ),
		),
		'dates'     => array(
			'label'  => __( 'Ramadan Dates', 'lailatulqadar' ),
			'fields' => array( 'ramadan_year', 'ramadan_start', 'ramadan_alt' ),
		),
		'login'     => array(
			'label'  => __( 'Login', 'lailatulqadar' ),
			'fields' => array( 'login_tagline_primary', 'login_tagline_secondary', 'login_note_primary', 'login_note_secondary' ),
		),
		'search'    => array(
			'label'  => __( 'Search', 'lailatulqadar' ),
			'fields' => array( 'verify_google', 'verify_bing' ),
		),
		'tools'     => array(
			'label'  => __( 'Tools', 'lailatulqadar' ),
			'fields' => array(),
		),
	);
}

/**
 * Register the menu page and the setting.
 */
function lq_settings_init() {
	register_setting(
		'lailatulqadar_settings',
		LQ_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lq_sanitize_settings',
			'default'           => lq_default_settings(),
		)
	);
}
add_action( 'admin_init', 'lq_settings_init' );

/**
 * Add Appearance > Lailatulqadar.
 */
function lq_settings_menu() {
	$hook = add_theme_page(
		__( 'Lailatulqadar settings', 'lailatulqadar' ),
		__( 'Lailatulqadar', 'lailatulqadar' ),
		'manage_options',
		'lailatulqadar',
		'lq_render_settings'
	);
	add_action( 'admin_print_styles-' . $hook, 'lq_admin_assets' );
}
add_action( 'admin_menu', 'lq_settings_menu' );

/**
 * Settings screen assets.
 */
function lq_admin_assets() {
	wp_enqueue_style( 'lailatulqadar-admin', LQ_URI . '/css/admin.css', array(), LQ_VERSION );
	wp_enqueue_media();
	wp_enqueue_script( 'lailatulqadar-admin', LQ_URI . '/js/admin.js', array(), LQ_VERSION, true );
}

/**
 * Sanitize the submitted tab and merge it into the stored settings,
 * so saving one tab leaves the others untouched.
 *
 * @param mixed $input Submitted values.
 * @return array
 */
function lq_sanitize_settings( $input ) {
	$stored = wp_parse_args( (array) get_option( LQ_OPTION, array() ), lq_default_settings() );
	$input  = is_array( $input ) ? $input : array();
	$tabs   = lq_settings_tabs();
	$tab    = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : '';

	if ( ! isset( $tabs[ $tab ] ) ) {
		return $stored;
	}

	$defaults = lq_default_settings();

	foreach ( $tabs[ $tab ]['fields'] as $key ) {
		$value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';

		switch ( $key ) {
			case 'primary_slug':
			case 'secondary_slug':
				$value = sanitize_key( $value );
				break;
			case 'scheme':
				$value = in_array( $value, array( 'night', 'dawn', 'auto' ), true ) ? $value : 'night';
				break;
			case 'scheme_toggle':
				$value = 'hide' === $value ? 'hide' : 'show';
				break;
			case 'accent_night':
			case 'accent_dawn':
				$value = sanitize_hex_color( $value );
				break;
			case 'ramadan_start':
			case 'ramadan_alt':
				$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
				break;
			case 'ramadan_year':
				$value = preg_replace( '/[^0-9]/', '', (string) $value );
				break;
			case 'verify_google':
			case 'verify_bing':
				// Accept the bare code or the whole meta tag pasted from the tool.
				if ( preg_match( '/content=["\']([^"\']+)["\']/', (string) $value, $lq_m ) ) {
					$value = $lq_m[1];
				}
				$value = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $value );
				break;
			case 'calligraphy_id':
				$value = absint( $value );
				$value = ( $value && wp_attachment_is_image( $value ) ) ? $value : 0;
				break;
			case 'author_url':
				$value = esc_url_raw( $value );
				break;
			case 'footer_note_primary':
			case 'footer_note_secondary':
				$value = sanitize_textarea_field( $value );
				break;
			default:
				$value = sanitize_text_field( $value );
		}

		$is_optional    = in_array( $key, array( 'footer_note_primary', 'footer_note_secondary', 'login_tagline_primary', 'login_tagline_secondary', 'login_note_primary', 'login_note_secondary', 'verify_google', 'verify_bing', 'author_name', 'author_url', 'ramadan_year', 'ramadan_start', 'ramadan_alt' ), true );
		$stored[ $key ] = ( '' === $value || null === $value ) && ! $is_optional ? $defaults[ $key ] : $value;
	}

	return $stored;
}

/**
 * Render the settings screen.
 */
function lq_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs    = lq_settings_tabs();
	$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current = isset( $tabs[ $current ] ) ? $current : 'general';
	$base    = admin_url( 'themes.php?page=lailatulqadar' );
	?>
	<div class="wrap lq-admin">
		<header class="lq-admin__head">
			<h1><?php esc_html_e( 'Lailatulqadar', 'lailatulqadar' ); ?></h1>
			<p class="lq-admin__version"><?php echo esc_html( sprintf( /* translators: %s: version */ __( 'Version %s', 'lailatulqadar' ), LQ_VERSION ) ); ?></p>
		</header>

		<?php lq_render_notices(); ?>

		<nav class="lq-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'lailatulqadar' ); ?>">
			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a class="lq-tabs__tab" href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base ) ); ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tab['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="lq-panel">
			<?php
			if ( 'tools' === $current ) {
				lq_render_tools_tab();
			} elseif ( 'search' === $current ) {
				lq_render_search_tab();
				?>
				<form method="post" action="options.php">
					<?php settings_fields( 'lailatulqadar_settings' ); ?>
					<input type="hidden" name="<?php echo esc_attr( LQ_OPTION ); ?>[_tab]" value="search">
					<?php lq_render_verification_fields(); ?>
					<?php submit_button( __( 'Save changes', 'lailatulqadar' ) ); ?>
				</form>
				<?php
			} else {
				?>
				<form method="post" action="options.php">
					<?php settings_fields( 'lailatulqadar_settings' ); ?>
					<input type="hidden" name="<?php echo esc_attr( LQ_OPTION ); ?>[_tab]" value="<?php echo esc_attr( $current ); ?>">
					<?php call_user_func( 'lq_render_' . $current . '_tab' ); ?>
					<?php submit_button( __( 'Save changes', 'lailatulqadar' ) ); ?>
				</form>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}

/**
 * Field name helper.
 *
 * @param string $key Setting key.
 * @return string
 */
function lq_field_name( $key ) {
	return LQ_OPTION . '[' . $key . ']';
}

/**
 * General tab.
 */
function lq_render_general_tab() {
	$map  = lq_page_map();
	$cid  = (int) lq_setting( 'calligraphy_id' );
	$curl = $cid ? wp_get_attachment_url( $cid ) : '';
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Logo calligraphy', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The Arabic calligraphy of ليلة القدر shown beside the lamp in the header and on the login screen. Choose a transparent PNG from the Media Library (or an SVG, if the site allows SVG uploads); the theme recolours it to the scheme\'s gold. With no image chosen, the words are typeset in Arslan Wessam B. Use only artwork you are licensed to use.', 'lailatulqadar' ); ?></p>
		<div class="lq-media" data-lq-media>
			<input type="hidden" name="<?php echo esc_attr( lq_field_name( 'calligraphy_id' ) ); ?>" value="<?php echo esc_attr( $cid ? (string) $cid : '' ); ?>" data-lq-media-id>
			<div class="lq-media__preview" data-lq-media-preview<?php echo $curl ? '' : ' hidden'; ?>>
				<img src="<?php echo esc_url( $curl ); ?>" alt="">
			</div>
			<p>
				<button type="button" class="button" data-lq-media-choose data-title="<?php esc_attr_e( 'Choose the calligraphy image', 'lailatulqadar' ); ?>" data-button="<?php esc_attr_e( 'Use this image', 'lailatulqadar' ); ?>"><?php esc_html_e( 'Choose image', 'lailatulqadar' ); ?></button>
				<button type="button" class="button-link lq-media__remove" data-lq-media-remove<?php echo $curl ? '' : ' hidden'; ?>><?php esc_html_e( 'Use the typeset calligraphy', 'lailatulqadar' ); ?></button>
			</p>
		</div>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Footer note', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'A short line shown in the footer above the copyright. Leave empty to hide it.', 'lailatulqadar' ); ?></p>
		<div class="lq-field">
			<label for="lq-note-primary"><?php echo esc_html( lq_setting( 'primary_label' ) ); ?></label>
			<textarea id="lq-note-primary" name="<?php echo esc_attr( lq_field_name( 'footer_note_primary' ) ); ?>" rows="2"><?php echo esc_textarea( lq_setting( 'footer_note_primary' ) ); ?></textarea>
		</div>
		<div class="lq-field">
			<label for="lq-note-secondary"><?php echo esc_html( lq_setting( 'secondary_label' ) ); ?></label>
			<textarea id="lq-note-secondary" name="<?php echo esc_attr( lq_field_name( 'footer_note_secondary' ) ); ?>" rows="2" lang="ms"><?php echo esc_textarea( lq_setting( 'footer_note_secondary' ) ); ?></textarea>
		</div>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Author for structured data', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Named as the author of every guide article in the Article structured data. Leave empty to use each page author’s display name and the About page.', 'lailatulqadar' ); ?></p>
		<?php if ( lq_seo_plugin() ) : ?>
			<p class="lq-callout lq-callout--warn"><?php echo esc_html( sprintf( /* translators: %s: SEO plugin name */ __( '%s is active and outputs the structured data, so these fields are not used. Set the author and publisher in the plugin.', 'lailatulqadar' ), lq_seo_plugin() ) ); ?></p>
		<?php endif; ?>
		<div class="lq-grid">
			<div class="lq-field">
				<label for="lq-author-name"><?php esc_html_e( 'Author name', 'lailatulqadar' ); ?></label>
				<input id="lq-author-name" type="text" name="<?php echo esc_attr( lq_field_name( 'author_name' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'author_name' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-author-url"><?php esc_html_e( 'Author profile URL', 'lailatulqadar' ); ?></label>
				<input id="lq-author-url" type="url" name="<?php echo esc_attr( lq_field_name( 'author_url' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'author_url' ) ); ?>">
			</div>
		</div>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Guide pages', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Pages appear in the site menus once published. Drafts stay hidden.', 'lailatulqadar' ); ?></p>
		<table class="lq-pages">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Page', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php echo esc_html( lq_setting( 'primary_label' ) ); ?></th>
					<th scope="col"><?php echo esc_html( lq_setting( 'secondary_label' ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( lq_page_definitions() as $key => $def ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $def['primary']['short'] ); ?></th>
						<?php foreach ( array( 'primary', 'secondary' ) as $role ) : ?>
							<td><?php lq_render_page_status( isset( $map[ $role ][ $key ] ) ? (int) $map[ $role ][ $key ] : 0 ); ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
	<?php
}

/**
 * One page status cell.
 *
 * @param int $id Page ID.
 */
function lq_render_page_status( $id ) {
	$status = $id ? get_post_status( $id ) : false;
	if ( ! $status || 'trash' === $status ) {
		echo '<span class="lq-status lq-status--missing">' . esc_html__( 'Not created', 'lailatulqadar' ) . '</span>';
		return;
	}
	$label = 'publish' === $status ? __( 'Published', 'lailatulqadar' ) : __( 'Draft', 'lailatulqadar' );
	printf(
		'<a class="lq-status lq-status--%1$s" href="%2$s">%3$s</a>',
		esc_attr( 'publish' === $status ? 'live' : 'draft' ),
		esc_url( get_edit_post_link( $id ) ),
		esc_html( $label )
	);
}

/**
 * Languages tab.
 */
function lq_render_languages_tab() {
	$map  = lq_page_map();
	$home = empty( $map['secondary']['home'] ) ? 0 : (int) $map['secondary']['home'];
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'How the two languages work', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The theme handles English and Malay itself; no plugin is needed. English pages sit at the root of the site. The Malay home is the page at /ms/, and every Malay page sits beneath it, so its address begins with /ms/. Run setup creates both sets of pages and pairs each page with its translation; the language switcher, the page language and the hreflang links for search engines follow from that pairing.', 'lailatulqadar' ); ?></p>
		<p class="lq-callout <?php echo $home ? 'lq-callout--ok' : 'lq-callout--warn'; ?>">
			<?php
			if ( $home ) {
				echo esc_html( sprintf( /* translators: %s: address of the Malay home page */ __( 'Malay home: %s', 'lailatulqadar' ), get_permalink( $home ) ) );
			} else {
				esc_html_e( 'The Malay pages do not exist yet. Press Run setup on the Tools tab.', 'lailatulqadar' );
			}
			?>
		</p>
		<?php if ( function_exists( 'pll_current_language' ) ) : ?>
			<p class="lq-callout lq-callout--warn"><?php esc_html_e( 'Polylang is still active. Deactivate it: its own language filtering would hide pages the theme now manages.', 'lailatulqadar' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="lq-section lq-grid">
		<div>
			<h2><?php esc_html_e( 'English', 'lailatulqadar' ); ?></h2>
			<div class="lq-field">
				<label for="lq-primary-label"><?php esc_html_e( 'Switcher label', 'lailatulqadar' ); ?></label>
				<input id="lq-primary-label" type="text" name="<?php echo esc_attr( lq_field_name( 'primary_label' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'primary_label' ) ); ?>">
			</div>
			<input type="hidden" name="<?php echo esc_attr( lq_field_name( 'primary_slug' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'primary_slug' ) ); ?>">
		</div>
		<div>
			<h2><?php esc_html_e( 'Malay', 'lailatulqadar' ); ?></h2>
			<div class="lq-field">
				<label for="lq-secondary-label"><?php esc_html_e( 'Switcher label', 'lailatulqadar' ); ?></label>
				<input id="lq-secondary-label" type="text" name="<?php echo esc_attr( lq_field_name( 'secondary_label' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'secondary_label' ) ); ?>" lang="ms">
			</div>
			<div class="lq-field">
				<label for="lq-site-name-secondary"><?php esc_html_e( 'Site name on Malay pages', 'lailatulqadar' ); ?></label>
				<input id="lq-site-name-secondary" type="text" name="<?php echo esc_attr( lq_field_name( 'site_name_secondary' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'site_name_secondary' ) ); ?>" lang="ms">
			</div>
			<input type="hidden" name="<?php echo esc_attr( lq_field_name( 'secondary_slug' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'secondary_slug' ) ); ?>">
		</div>
	</section>
	<?php
}

/**
 * Colours tab.
 */
function lq_render_colours_tab() {
	$scheme  = lq_setting( 'scheme' );
	$options = array(
		'night' => __( 'Night (dark)', 'lailatulqadar' ),
		'dawn'  => __( 'Dawn (light)', 'lailatulqadar' ),
		'auto'  => __( 'Follow the visitor’s device', 'lailatulqadar' ),
	);
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Colour scheme', 'lailatulqadar' ); ?></h2>
		<fieldset class="lq-schemes">
			<legend class="screen-reader-text"><?php esc_html_e( 'Colour scheme', 'lailatulqadar' ); ?></legend>
			<?php foreach ( $options as $value => $label ) : ?>
				<label class="lq-scheme lq-scheme--<?php echo esc_attr( $value ); ?>">
					<input type="radio" name="<?php echo esc_attr( lq_field_name( 'scheme' ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $scheme, $value ); ?>>
					<span class="lq-scheme__swatch" aria-hidden="true"></span>
					<span class="lq-scheme__label"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Visitor switch', 'lailatulqadar' ); ?></h2>
		<div class="lq-field">
			<label for="lq-scheme-toggle"><?php esc_html_e( 'Light and dark switch in the header', 'lailatulqadar' ); ?></label>
			<select id="lq-scheme-toggle" name="<?php echo esc_attr( lq_field_name( 'scheme_toggle' ) ); ?>">
				<option value="show" <?php selected( lq_setting( 'scheme_toggle' ), 'show' ); ?>><?php esc_html_e( 'Show: visitors can switch; their choice is remembered in their browser', 'lailatulqadar' ); ?></option>
				<option value="hide" <?php selected( lq_setting( 'scheme_toggle' ), 'hide' ); ?>><?php esc_html_e( 'Hide: everyone sees the scheme chosen above', 'lailatulqadar' ); ?></option>
			</select>
		</div>
		<p class="lq-help"><?php esc_html_e( 'The scheme chosen above is what first-time visitors see. A visitor who uses the switch keeps their choice on later visits.', 'lailatulqadar' ); ?></p>
	</section>

	<section class="lq-section lq-grid">
		<div class="lq-field">
			<label for="lq-accent-night"><?php esc_html_e( 'Night accent', 'lailatulqadar' ); ?></label>
			<input id="lq-accent-night" type="color" data-lq-preview="night" name="<?php echo esc_attr( lq_field_name( 'accent_night' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'accent_night' ) ); ?>">
			<div class="lq-preview lq-preview--night" data-lq-preview-target="night">
				<span class="lq-preview__ayah" lang="ar" dir="rtl">لَيۡلَةُ ٱلۡقَدۡرِ</span>
				<span class="lq-preview__link"><?php esc_html_e( 'Link colour', 'lailatulqadar' ); ?></span>
			</div>
		</div>
		<div class="lq-field">
			<label for="lq-accent-dawn"><?php esc_html_e( 'Dawn accent', 'lailatulqadar' ); ?></label>
			<input id="lq-accent-dawn" type="color" data-lq-preview="dawn" name="<?php echo esc_attr( lq_field_name( 'accent_dawn' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'accent_dawn' ) ); ?>">
			<div class="lq-preview lq-preview--dawn" data-lq-preview-target="dawn">
				<span class="lq-preview__ayah" lang="ar" dir="rtl">لَيۡلَةُ ٱلۡقَدۡرِ</span>
				<span class="lq-preview__link"><?php esc_html_e( 'Link colour', 'lailatulqadar' ); ?></span>
			</div>
		</div>
		<p class="lq-help lq-grid__full"><?php esc_html_e( 'Accents carry the Arabic verse, links and buttons. Keep a contrast ratio of at least 4.5:1 against the background: light accents for night, dark accents for dawn.', 'lailatulqadar' ); ?></p>
	</section>
	<?php
}

/**
 * Ramadan Dates tab: the expected start and the alternative start used by
 * the night window.
 */
function lq_render_dates_tab() {
	$current = lq_ramadan();
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Automatic dates', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The site takes the dates of Ramadan from the Umm al-Qura calendar for every year up to 1473 AH (2051), and offers visitors the following day as an alternative start for countries that rely on local sighting. Articles, title tags and the night window switch to the next Ramadan the day after Eid, with no yearly update.', 'lailatulqadar' ); ?></p>
		<table class="lq-pages">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Hijri year', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( '1 Ramadan (Umm al-Qura)', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Alternative start', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( '27th night begins', 'lailatulqadar' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$first = $current ? $current['hijri'] : 0;
				for ( $y = $first; $y < $first + 5; $y++ ) :
					$r = lq_ramadan_year( $y );
					if ( ! $r ) {
						break;
					}
					?>
					<tr>
						<th scope="row"><?php echo esc_html( $r['hijri'] . ( $r['override'] ? ' *' : '' ) ); ?></th>
						<td><?php echo esc_html( lq_fmt_date( $r['start'], 'long', 'primary' ) . ' = ' . lq_hijri( 1, $r['hijri'], 'ramadan', false, 'primary' ) ); ?></td>
						<td><?php echo esc_html( lq_fmt_date( $r['alt'], 'long', 'primary' ) . ' = ' . lq_hijri( 1, $r['hijri'], 'ramadan', false, 'primary' ) ); ?></td>
						<td><?php echo esc_html( lq_fmt_date( lq_night_evening( $r['start'], 27 ), 'long', 'primary' ) . ' (' . lq_hijri( 27, $r['hijri'], 'ramadan', true, 'primary' ) . ')' ); ?></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
		<p class="lq-help"><?php esc_html_e( 'The first row is the Ramadan the site currently shows. An asterisk marks a year with an override.', 'lailatulqadar' ); ?></p>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Override one year (optional)', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Use this only when an official announcement differs from the Umm al-Qura date. Leave the fields empty to use the automatic dates.', 'lailatulqadar' ); ?></p>
		<div class="lq-grid">
			<div class="lq-field">
				<label for="lq-ramadan-year"><?php esc_html_e( 'Hijri year', 'lailatulqadar' ); ?></label>
				<input id="lq-ramadan-year" type="text" class="small-text" placeholder="<?php echo esc_attr( $current ? (string) $current['hijri'] : '' ); ?>" name="<?php echo esc_attr( lq_field_name( 'ramadan_year' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'ramadan_year' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-ramadan-start"><?php esc_html_e( '1 Ramadan', 'lailatulqadar' ); ?></label>
				<input id="lq-ramadan-start" type="date" name="<?php echo esc_attr( lq_field_name( 'ramadan_start' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'ramadan_start' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-ramadan-alt"><?php esc_html_e( 'Alternative 1 Ramadan', 'lailatulqadar' ); ?></label>
				<input id="lq-ramadan-alt" type="date" name="<?php echo esc_attr( lq_field_name( 'ramadan_alt' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'ramadan_alt' ) ); ?>">
			</div>
		</div>
	</section>
	<?php
}

/**
 * Login tab: the wording on the login screen, per language.
 */
function lq_render_login_tab() {
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Login screen', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The login screen follows the theme: logo mark, site name, colour scheme and accent colours. The tagline and note below appear under the site name, in the language the visitor logs in with. Leave the tagline empty to use the default; leave the note empty to show none.', 'lailatulqadar' ); ?></p>
		<div class="lq-grid">
			<div class="lq-field">
				<label for="lq-login-tagline-primary"><?php esc_html_e( 'Tagline (English)', 'lailatulqadar' ); ?></label>
				<input id="lq-login-tagline-primary" type="text" class="regular-text" placeholder="A guide to Laylat al-Qadr from the Qurʾān and sound hadith." name="<?php echo esc_attr( lq_field_name( 'login_tagline_primary' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'login_tagline_primary' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-login-tagline-secondary"><?php esc_html_e( 'Tagline (Malay)', 'lailatulqadar' ); ?></label>
				<input id="lq-login-tagline-secondary" type="text" class="regular-text" placeholder="Panduan Lailatulqadar berdasarkan al-Quran dan hadis sahih." name="<?php echo esc_attr( lq_field_name( 'login_tagline_secondary' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'login_tagline_secondary' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-login-note-primary"><?php esc_html_e( 'Note (English)', 'lailatulqadar' ); ?></label>
				<input id="lq-login-note-primary" type="text" class="regular-text" placeholder="Editors only" name="<?php echo esc_attr( lq_field_name( 'login_note_primary' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'login_note_primary' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-login-note-secondary"><?php esc_html_e( 'Note (Malay)', 'lailatulqadar' ); ?></label>
				<input id="lq-login-note-secondary" type="text" class="regular-text" placeholder="Untuk penyunting sahaja" name="<?php echo esc_attr( lq_field_name( 'login_note_secondary' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'login_note_secondary' ) ); ?>">
			</div>
		</div>
		<p><a class="button" href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview the login screen', 'lailatulqadar' ); ?></a></p>
		<p class="lq-help"><?php esc_html_e( 'The preview opens in a new tab; log out there, or use a private window, to see the screen as visitors do.', 'lailatulqadar' ); ?></p>
	</section>
	<?php
}

/**
 * Search Console and Bing Webmaster Tools verification codes.
 */
function lq_render_verification_fields() {
	$plugin = lq_seo_plugin();
	if ( $plugin ) {
		?>
		<section class="lq-section">
			<h2><?php esc_html_e( 'Webmaster tools verification', 'lailatulqadar' ); ?></h2>
			<p class="lq-help">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: SEO plugin name */
						__( '%s is active and handles verification codes. Enter them in its own settings (in Rank Math: General Settings > Webmaster Tools). The theme prints none of its own.', 'lailatulqadar' ),
						$plugin
					)
				);
				?>
			</p>
		</section>
		<?php
		return;
	}
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Webmaster tools verification', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Paste the HTML-tag verification code from Google Search Console or Bing Webmaster Tools, either the code alone or the whole meta tag. The theme prints it on the front page; remove it only after verifying another way.', 'lailatulqadar' ); ?></p>
		<div class="lq-grid">
			<div class="lq-field">
				<label for="lq-verify-google"><?php esc_html_e( 'Google Search Console', 'lailatulqadar' ); ?></label>
				<input id="lq-verify-google" type="text" name="<?php echo esc_attr( lq_field_name( 'verify_google' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'verify_google' ) ); ?>">
			</div>
			<div class="lq-field">
				<label for="lq-verify-bing"><?php esc_html_e( 'Bing Webmaster Tools', 'lailatulqadar' ); ?></label>
				<input id="lq-verify-bing" type="text" name="<?php echo esc_attr( lq_field_name( 'verify_bing' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'verify_bing' ) ); ?>">
			</div>
		</div>
	</section>
	<?php
}

/**
 * Search tab: title tags and meta descriptions for every guide page.
 */
function lq_render_search_tab() {
	$plugin = lq_seo_plugin();
	$map    = lq_page_map();
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Search appearance', 'lailatulqadar' ); ?></h2>
		<?php if ( $plugin ) : ?>
			<p class="lq-callout lq-callout--warn">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: SEO plugin name */
						__( '%s is active, so the theme does not print its own title tags or meta descriptions. The values below are kept for reference.', 'lailatulqadar' ),
						$plugin
					)
				);
				?>
			</p>
		<?php else : ?>
			<p class="lq-help">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: title limit, 2: description limit */
						__( 'Limits: %1$d characters for title tags, %2$d for meta descriptions. Edit each value in the Search appearance box on the page.', 'lailatulqadar' ),
						LQ_TITLE_LIMIT,
						LQ_META_LIMIT
					)
				);
				?>
			</p>
		<?php endif; ?>
		<table class="lq-pages lq-seo-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Page', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Title tag', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Meta description', 'lailatulqadar' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( lq_page_definitions() as $key => $def ) : ?>
					<?php foreach ( array( 'primary', 'secondary' ) as $role ) : ?>
						<?php
						$id = isset( $map[ $role ][ $key ] ) ? (int) $map[ $role ][ $key ] : 0;
						if ( ! $id || ! get_post( $id ) ) {
							continue;
						}
						$title = (string) get_post_meta( $id, LQ_META_TITLE, true );
						$desc  = (string) get_post_meta( $id, LQ_META_DESCRIPTION, true );
						?>
						<tr>
							<th scope="row"><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></th>
							<td><?php lq_render_seo_cell( $title, LQ_TITLE_LIMIT ); ?></td>
							<td><?php lq_render_seo_cell( $desc, LQ_META_LIMIT ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
	<?php
}

/**
 * One value with its length badge.
 *
 * @param string $value Text.
 * @param int    $limit Character limit.
 */
function lq_render_seo_cell( $value, $limit ) {
	if ( '' === $value ) {
		echo '<span class="lq-status lq-status--missing">' . esc_html__( 'Empty', 'lailatulqadar' ) . '</span>';
		return;
	}
	$value  = wp_strip_all_tags( do_shortcode( $value ) );
	$length = mb_strlen( $value );
	printf(
		'<span class="lq-seo-table__text">%1$s</span> <span class="lq-status %2$s">%3$d / %4$d</span>',
		esc_html( $value ),
		esc_attr( $length > $limit ? 'lq-status--over' : 'lq-status--live' ),
		(int) $length,
		(int) $limit
	);
}

/**
 * Tools tab.
 */
function lq_render_tools_tab() {
	$rows   = lq_page_status_report();
	$counts = array_count_values( wp_list_pluck( $rows, 'state' ) );
	$labels = array(
		'live'       => __( 'Published', 'lailatulqadar' ),
		'ready'      => __( 'Draft, written: ready to publish', 'lailatulqadar' ),
		'unwritten'  => __( 'Draft, not written yet', 'lailatulqadar' ),
		'empty-live' => __( 'Published, but has little or no text', 'lailatulqadar' ),
		'missing'    => __( 'Not created', 'lailatulqadar' ),
	);
	$langs  = array(
		'primary'   => lq_setting( 'primary_label' ),
		'secondary' => lq_setting( 'secondary_label' ),
	);
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Guide pages', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Menus, the reading path, the HTML sitemap and search engines see published pages only. Pages still in preparation appear in the reading path as "Coming soon", without a link.', 'lailatulqadar' ); ?></p>
		<table class="lq-pages">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Page', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Language', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Words', 'lailatulqadar' ); ?></th>
					<th scope="col"><?php esc_html_e( 'State', 'lailatulqadar' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr class="lq-state lq-state--<?php echo esc_attr( $row['state'] ); ?>">
						<th scope="row">
							<?php if ( $row['id'] ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $row['title'] ); ?>
							<?php endif; ?>
						</th>
						<td><?php echo esc_html( $langs[ $row['role'] ] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $row['words'] ) ); ?></td>
						<td><?php echo esc_html( isset( $labels[ $row['state'] ] ) ? $labels[ $row['state'] ] : $row['state'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="lq-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lq_publish_ready">
				<?php wp_nonce_field( 'lq_publish_ready' ); ?>
				<?php
				submit_button(
					/* translators: %d: number of drafts ready to publish */
					sprintf( __( 'Publish written drafts (%d)', 'lailatulqadar' ), isset( $counts['ready'] ) ? $counts['ready'] : 0 ),
					'primary',
					'submit',
					false,
					empty( $counts['ready'] ) ? array( 'disabled' => 'disabled' ) : array( 'data-lq-confirm' => __( 'Publish every written draft listed as ready? Check each one in the editor first.', 'lailatulqadar' ) )
				);
				?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lq_unpublish_empty">
				<?php wp_nonce_field( 'lq_unpublish_empty' ); ?>
				<?php
				submit_button(
					/* translators: %d: number of published pages with little text */
					sprintf( __( 'Return empty published pages to draft (%d)', 'lailatulqadar' ), isset( $counts['empty-live'] ) ? $counts['empty-live'] : 0 ),
					'secondary',
					'submit',
					false,
					empty( $counts['empty-live'] ) ? array( 'disabled' => 'disabled' ) : array()
				);
				?>
			</form>
		</div>
		<?php
		$sample = get_page_by_path( 'sample-page' );
		if ( $sample && 'publish' === $sample->post_status ) :
			?>
			<p class="lq-help">
				<?php esc_html_e( 'WordPress\'s own "Sample Page" is still published. It is not part of the guide;', 'lailatulqadar' ); ?>
				<a href="<?php echo esc_url( get_delete_post_link( $sample->ID ) ); ?>"><?php esc_html_e( 'move it to the trash', 'lailatulqadar' ); ?></a>.
			</p>
		<?php endif; ?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Run setup', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Creates any missing guide pages in both languages, places the Malay pages beneath the Malay home at /ms/, pairs each page with its translation and sets the front page. Drafts with no content take the current titles and slugs; pages with content are never changed. Empty title tags and meta descriptions are filled from the keyword plan.', 'lailatulqadar' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lq_run_setup">
			<?php wp_nonce_field( 'lq_run_setup' ); ?>
			<?php submit_button( __( 'Run setup', 'lailatulqadar' ), 'primary', 'submit', false ); ?>
		</form>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Reset settings', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Restores every setting on this screen to its default. Pages and content are not touched.', 'lailatulqadar' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lq_reset_settings">
			<?php wp_nonce_field( 'lq_reset_settings' ); ?>
			<?php submit_button( __( 'Reset settings', 'lailatulqadar' ), 'secondary', 'submit', false, array( 'data-lq-confirm' => __( 'Reset every Lailatulqadar setting to its default?', 'lailatulqadar' ) ) ); ?>
		</form>
	</section>
	<?php
}

/**
 * Notices for setup reports, resets and saves.
 */
function lq_render_notices() {
	$report = get_transient( 'lailatulqadar_setup_report' );
	if ( $report ) {
		delete_transient( 'lailatulqadar_setup_report' );
		$created = count( $report['created'] );
		echo '<div class="lq-callout lq-callout--ok"><p>';
		echo esc_html(
			sprintf(
				/* translators: 1: pages created, 2: pages reused, 3: drafts renamed */
				__( 'Setup finished. %1$d pages created, %2$d existing pages reused, %3$d untouched drafts renamed.', 'lailatulqadar' ),
				$created,
				count( $report['reused'] ),
				isset( $report['renamed'] ) ? count( $report['renamed'] ) : 0
			)
		);
		echo '</p>';
		foreach ( $report['errors'] as $error ) {
			echo '<p>' . esc_html( $error ) . '</p>';
		}
		echo '</div>';
	}

	$status = get_transient( 'lailatulqadar_status_report' );
	if ( $status ) {
		delete_transient( 'lailatulqadar_status_report' );
		$message = 'publish' === $status['status']
			/* translators: %s: list of page titles */
			? __( 'Published: %s.', 'lailatulqadar' )
			/* translators: %s: list of page titles */
			: __( 'Returned to draft: %s.', 'lailatulqadar' );
		echo '<div class="lq-callout lq-callout--ok"><p>' . esc_html( $status['pages'] ? sprintf( $message, implode( ', ', $status['pages'] ) ) : __( 'No pages needed changing.', 'lailatulqadar' ) ) . '</p></div>';
	}

	if ( isset( $_GET['lq-reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="lq-callout lq-callout--ok"><p>' . esc_html__( 'Settings reset to their defaults.', 'lailatulqadar' ) . '</p></div>';
	}

	if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="lq-callout lq-callout--ok"><p>' . esc_html__( 'Changes saved.', 'lailatulqadar' ) . '</p></div>';
	}
}

/**
 * Tools: run setup.
 */
function lq_handle_run_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run setup.', 'lailatulqadar' ) );
	}
	check_admin_referer( 'lq_run_setup' );
	set_transient( 'lailatulqadar_setup_report', lq_run_setup(), MINUTE_IN_SECONDS * 10 );
	wp_safe_redirect( admin_url( 'themes.php?page=lailatulqadar&tab=tools' ) );
	exit;
}
add_action( 'admin_post_lq_run_setup', 'lq_handle_run_setup' );

/**
 * Tools: reset settings.
 */
function lq_handle_reset_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to reset settings.', 'lailatulqadar' ) );
	}
	check_admin_referer( 'lq_reset_settings' );
	delete_option( LQ_OPTION );
	wp_safe_redirect( admin_url( 'themes.php?page=lailatulqadar&tab=tools&lq-reset=1' ) );
	exit;
}
add_action( 'admin_post_lq_reset_settings', 'lq_handle_reset_settings' );

/**
 * Change the status of guide pages in one state, then report.
 *
 * @param string $state  State to act on (ready or empty-live).
 * @param string $status New post status.
 * @param string $nonce  Nonce action.
 */
function lq_bulk_page_status( $state, $status, $nonce ) {
	if ( ! current_user_can( 'publish_pages' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'lailatulqadar' ) );
	}
	check_admin_referer( $nonce );
	$done = array();
	foreach ( lq_page_status_report() as $row ) {
		if ( $state === $row['state'] && $row['id'] ) {
			wp_update_post(
				array(
					'ID'          => $row['id'],
					'post_status' => $status,
				)
			);
			$done[] = $row['title'];
		}
	}
	set_transient( 'lailatulqadar_status_report', array( 'status' => $status, 'pages' => $done ), 60 );
	wp_safe_redirect( admin_url( 'themes.php?page=lailatulqadar&tab=tools' ) );
	exit;
}

/**
 * Publish drafts that have been written.
 */
function lq_handle_publish_ready() {
	lq_bulk_page_status( 'ready', 'publish', 'lq_publish_ready' );
}
add_action( 'admin_post_lq_publish_ready', 'lq_handle_publish_ready' );

/**
 * Return published pages with little or no text to draft.
 */
function lq_handle_unpublish_empty() {
	lq_bulk_page_status( 'empty-live', 'draft', 'lq_unpublish_empty' );
}
add_action( 'admin_post_lq_unpublish_empty', 'lq_handle_unpublish_empty' );

/**
 * After first-run setup, point administrators to the report on other screens.
 */
function lq_setup_admin_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'appearance_page_lailatulqadar' === $screen->id || ! get_transient( 'lailatulqadar_setup_report' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-success"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
		esc_html__( 'Lailatulqadar has created the guide pages.', 'lailatulqadar' ),
		esc_url( admin_url( 'themes.php?page=lailatulqadar' ) ),
		esc_html__( 'Review the setup report', 'lailatulqadar' )
	);
}
add_action( 'admin_notices', 'lq_setup_admin_notice' );
