<?php
/**
 * Settings screen: Appearance > Theme Options, organised in tabs.
 *
 * Tabs: General, Languages, Colours, Ramadan Dates, Login, Search,
 * Contextual links, Images, Security, Tools.
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
			'icon'   => 'admin-generic',
			'label'  => __( 'General', 'lailatulqadar' ),
			'fields' => array( 'calligraphy_id', 'footer_note_primary', 'footer_note_secondary', 'author_name', 'author_url' ),
		),
		'languages' => array(
			'icon'   => 'translation',
			'label'  => __( 'Languages', 'lailatulqadar' ),
			'fields' => array( 'primary_slug', 'secondary_slug', 'primary_label', 'secondary_label', 'site_name_secondary' ),
		),
		'colours'   => array(
			'icon'   => 'art',
			'label'  => __( 'Colours', 'lailatulqadar' ),
			'fields' => array( 'scheme', 'scheme_toggle', 'accent_night', 'accent_dawn' ),
		),
		'dates'     => array(
			'icon'   => 'calendar-alt',
			'label'  => __( 'Ramadan Dates', 'lailatulqadar' ),
			'fields' => array( 'ramadan_year', 'ramadan_start', 'ramadan_alt' ),
		),
		'login'     => array(
			'icon'   => 'admin-users',
			'label'  => __( 'Login', 'lailatulqadar' ),
			'fields' => array( 'login_tagline_primary', 'login_tagline_secondary', 'login_note_primary', 'login_note_secondary' ),
		),
		'search'    => array(
			'icon'   => 'search',
			'label'  => __( 'Search', 'lailatulqadar' ),
			'fields' => array( 'verify_google', 'verify_bing' ),
		),
		'links'     => array(
			'icon'   => 'admin-links',
			'label'  => __( 'Contextual links', 'lailatulqadar' ),
			'fields' => array( 'ctx_own', 'ctx_sister', 'ctx_own_max', 'ctx_sister_max', 'ctx_extra', 'ctx_extra_ms', 'ctx_exclude' ),
		),
		'images'    => array(
			'icon'   => 'format-image',
			'label'  => __( 'Images', 'lailatulqadar' ),
			'fields' => array( 'commons_featured' ),
		),
		'security'  => array(
			'icon'   => 'shield',
			'label'  => __( 'Security', 'lailatulqadar' ),
			'fields' => array( 'sec_xmlrpc_off', 'sec_app_passwords_off', 'sec_rest_lock', 'sec_spam_gate', 'sec_gone' ),
		),
		'tools'     => array(
			'icon'   => 'admin-tools',
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
 * Add Appearance > Theme Options.
 */
function lq_settings_menu() {
	$hook = add_theme_page(
		__( 'Theme Options', 'lailatulqadar' ),
		__( 'Theme Options', 'lailatulqadar' ),
		'manage_options',
		'lailatulqadar',
		'lq_render_settings'
	);
	if ( $hook ) {
		$GLOBALS['lailatulqadar_options_hook'] = $hook;
	}
}
add_action( 'admin_menu', 'lq_settings_menu' );

/**
 * Enqueue the Theme Options assets on that screen only, by its hook suffix.
 *
 * @param string $hook_suffix Current admin screen.
 */
function lailatulqadar_options_enqueue( $hook_suffix ) {
	if ( empty( $GLOBALS['lailatulqadar_options_hook'] ) || $GLOBALS['lailatulqadar_options_hook'] !== $hook_suffix ) {
		return;
	}
	lq_admin_assets();
}
add_action( 'admin_enqueue_scripts', 'lailatulqadar_options_enqueue' );

/**
 * Settings screen assets. The Security tab's script and styles load only
 * while that tab is open, since only then is its panel rendered.
 */
function lq_admin_assets() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_style( 'lailatulqadar-admin', LQ_URI . '/css/admin.css', array( 'wp-color-picker', 'dashicons' ), LQ_VERSION );
	wp_enqueue_media();
	wp_enqueue_script( 'lailatulqadar-admin', LQ_URI . '/js/admin.js', array( 'jquery', 'wp-color-picker' ), LQ_VERSION, true );
	if ( 'security' === $tab ) {
		lq_sec_assets();
	}
}

/**
 * A contextual-links textarea, sanitised like any textarea but keeping
 * percent-encoded characters: sanitize_textarea_field() strips "%D8%B5", so an
 * encoded address or slug would be saved broken ("/glossary//").
 *
 * @param string $value Submitted text.
 * @return string
 */
function lq_sanitize_ctx_textarea( $value ) {
	$mark  = "\u{E000}";
	$value = str_replace( array( $mark, '%' ), array( '', $mark ), (string) $value );
	return str_replace( $mark, '%', sanitize_textarea_field( $value ) );
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
		// A field the tab did not render keeps its stored value: with an SEO
		// plugin active the Search tab prints no verification inputs, and
		// saving it must not clear the codes. Toggles always post a value.
		if ( ! array_key_exists( $key, $input ) ) {
			continue;
		}
		$value = wp_unslash( $input[ $key ] );

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
				if ( preg_match( '/content=["\']([^"\']+)["\']/', (string) $value, $m ) ) {
					$value = $m[1];
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
			case 'ctx_extra':
			case 'ctx_extra_ms':
			case 'ctx_exclude':
				$value = lq_sanitize_ctx_textarea( $value );
				break;
			case 'ctx_own':
			case 'ctx_sister':
			case 'commons_featured':
			case 'sec_xmlrpc_off':
			case 'sec_app_passwords_off':
			case 'sec_rest_lock':
			case 'sec_spam_gate':
			case 'sec_gone':
				$value = '1' === (string) $value ? '1' : '0';
				break;
			case 'ctx_own_max':
				$value = (string) max( 0, min( 15, (int) $value ) );
				break;
			case 'ctx_sister_max':
				$value = (string) max( 0, min( 10, (int) $value ) );
				break;
			default:
				$value = sanitize_text_field( $value );
		}

		$is_optional    = in_array( $key, array( 'footer_note_primary', 'footer_note_secondary', 'login_tagline_primary', 'login_tagline_secondary', 'login_note_primary', 'login_note_secondary', 'verify_google', 'verify_bing', 'author_name', 'author_url', 'ramadan_year', 'ramadan_start', 'ramadan_alt', 'ctx_extra', 'ctx_extra_ms', 'ctx_exclude' ), true );
		$stored[ $key ] = ( '' === $value || null === $value ) && ! $is_optional ? $defaults[ $key ] : $value;
	}

	return $stored;
}

/**
 * Render the settings screen: a header, a tab list and one panel per tab.
 * Every panel is on the page and the script switches between them without
 * a reload; without the script, each tab is a link that opens its panel.
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
			<span class="lq-admin__icon dashicons dashicons-admin-appearance" aria-hidden="true"></span>
			<div class="lq-admin__titles">
				<h1><?php esc_html_e( 'Theme Options', 'lailatulqadar' ); ?></h1>
				<p class="lq-admin__lede"><?php esc_html_e( 'Settings for the Lailatulqadar guide in English and Malay. Each tab saves only its own fields.', 'lailatulqadar' ); ?></p>
			</div>
			<span class="lq-admin__version"><?php echo esc_html( sprintf( /* translators: %s: version */ __( 'Version %s', 'lailatulqadar' ), LQ_VERSION ) ); ?></span>
		</header>
		<hr class="wp-header-end">

		<?php lq_render_notices(); ?>

		<div class="lq-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'lailatulqadar' ); ?>">
			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a class="lq-tabs__tab<?php echo $slug === $current ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( 'lq-tab-' . $slug ); ?>" href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base ) ); ?>" aria-controls="<?php echo esc_attr( 'lq-panel-' . $slug ); ?>" aria-selected="<?php echo $slug === $current ? 'true' : 'false'; ?>" data-tab="<?php echo esc_attr( $slug ); ?>">
					<span class="dashicons <?php echo esc_attr( 'dashicons-' . $tab['icon'] ); ?>" aria-hidden="true"></span>
					<span class="lq-tabs__label"><?php echo esc_html( $tab['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php foreach ( $tabs as $slug => $tab ) : ?>
			<?php $deferred = $slug !== $current && lailatulqadar_tab_is_heavy( $slug ); ?>
			<div class="lq-panel" role="tabpanel" id="<?php echo esc_attr( 'lq-panel-' . $slug ); ?>" aria-labelledby="<?php echo esc_attr( 'lq-tab-' . $slug ); ?>" tabindex="0"<?php echo $slug === $current ? '' : ' hidden'; ?><?php echo $deferred ? ' data-lq-reload' : ''; ?>>
				<?php
				if ( $deferred ) {
					printf(
						'<p class="lq-section"><a href="%1$s">%2$s</a></p>',
						esc_url( add_query_arg( 'tab', $slug, $base ) ),
						esc_html( sprintf( /* translators: %s: tab name */ __( 'Open the %s tab', 'lailatulqadar' ), $tab['label'] ) )
					);
				} else {
					lailatulqadar_render_panel( $slug );
				}
				?>
			</div>
		<?php endforeach; ?>

		<footer class="lq-admin__foot">
			<a href="<?php echo esc_url( LQ_URI . '/docs/settings.md' ); ?>"><?php esc_html_e( 'Settings reference', 'lailatulqadar' ); ?></a>
			<a href="<?php echo esc_url( LQ_URI . '/readme.txt' ); ?>"><?php esc_html_e( 'Theme readme', 'lailatulqadar' ); ?></a>
			<a href="<?php echo esc_url( 'https://menj.me' ); ?>" rel="noopener"><?php esc_html_e( 'Support', 'lailatulqadar' ); ?></a>
		</footer>
	</div>
	<?php
}

/**
 * Tabs whose panels run report queries (the Security reports and log, the
 * Tools page states and word counts, the Search table of every page's meta,
 * the General page table). They render only when they are the open tab; the
 * other panels hold a link, and the script loads the tab with a full reload.
 *
 * @param string $slug Tab key.
 * @return bool
 */
function lailatulqadar_tab_is_heavy( $slug ) {
	return in_array( $slug, array( 'general', 'search', 'security', 'tools' ), true );
}

/**
 * One tab's panel: its settings form, or the Tools actions.
 *
 * @param string $slug Tab key.
 */
function lailatulqadar_render_panel( $slug ) {
	if ( 'tools' === $slug ) {
		lq_render_tools_tab();
		return;
	}
	if ( 'search' === $slug ) {
		lq_render_search_tab();
	}
	?>
	<form method="post" action="options.php" class="lq-form">
		<?php lailatulqadar_settings_fields( $slug ); ?>
		<?php
		if ( 'search' === $slug ) {
			lq_render_verification_fields();
		} else {
			call_user_func( 'lq_render_' . $slug . '_tab' );
		}
		?>
		<div class="lq-form__foot">
			<?php submit_button( __( 'Save changes', 'lailatulqadar' ), 'primary', 'submit', false, array( 'id' => 'lq-save-' . $slug ) ); ?>
		</div>
	</form>
	<?php
	if ( 'security' === $slug ) {
		lq_sec_render_tools();
	}
}

/**
 * The hidden fields settings_fields() prints, without its fixed element IDs
 * (every panel has a form, so they would repeat) and with a referer that
 * brings the browser back to this tab after saving.
 *
 * @param string $slug Tab key.
 */
function lailatulqadar_settings_fields( $slug ) {
	?>
	<input type="hidden" name="option_page" value="lailatulqadar_settings">
	<input type="hidden" name="action" value="update">
	<input type="hidden" name="<?php echo esc_attr( LQ_OPTION ); ?>[_tab]" value="<?php echo esc_attr( $slug ); ?>">
	<?php
	lailatulqadar_nonce_field( 'lailatulqadar_settings-options' );

	printf(
		'<input type="hidden" name="_wp_http_referer" value="%s">',
		esc_attr( wp_make_link_relative( add_query_arg( 'tab', $slug, admin_url( 'themes.php?page=lailatulqadar' ) ) ) )
	);
}

/**
 * A nonce field without an element ID, so several forms can share a page.
 *
 * @param string $action Nonce action.
 */
function lailatulqadar_nonce_field( $action ) {
	printf( '<input type="hidden" name="_wpnonce" value="%s">', esc_attr( wp_create_nonce( $action ) ) );
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
 * HTML attributes from an array, escaped. A value of true prints the
 * attribute alone; false and null leave it out.
 *
 * @param array $attrs Attributes.
 * @return string
 */
function lailatulqadar_attrs( $attrs ) {
	$out = '';
	foreach ( $attrs as $name => $value ) {
		if ( false === $value || null === $value ) {
			continue;
		}
		$out .= true === $value ? ' ' . esc_attr( $name ) : sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}
	return $out;
}

/**
 * One field row: label and help on the left, the control on the right
 * (stacked on small screens).
 *
 * @param string   $id      Control ID.
 * @param string   $label   Label text.
 * @param callable $control Prints the control; receives the help ID or ''.
 * @param string   $help    Help text, optional.
 * @param string   $class   Extra row class, optional.
 */
function lailatulqadar_row( $id, $label, $control, $help = '', $class = '' ) {
	$help_id = '' === $help ? '' : $id . '-help';
	?>
	<div class="lq-row<?php echo $class ? ' ' . esc_attr( $class ) : ''; ?>">
		<div class="lq-row__label">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<?php if ( $help_id ) : ?>
				<p class="lq-help" id="<?php echo esc_attr( $help_id ); ?>"><?php echo esc_html( $help ); ?></p>
			<?php endif; ?>
		</div>
		<div class="lq-row__control">
			<?php call_user_func( $control, $help_id ); ?>
		</div>
	</div>
	<?php
}

/**
 * A text-like input row bound to a setting.
 *
 * @param string $key   Setting key.
 * @param string $id    Control ID.
 * @param string $label Label.
 * @param string $help  Help text.
 * @param array  $attrs Extra attributes (type, placeholder, lang, class).
 */
function lailatulqadar_input_row( $key, $id, $label, $help = '', $attrs = array() ) {
	lailatulqadar_row(
		$id,
		$label,
		static function ( $help_id ) use ( $key, $id, $attrs ) {
			$attrs = array_merge(
				array(
					'type'             => 'text',
					'class'            => 'regular-text',
					'id'               => $id,
					'name'             => lq_field_name( $key ),
					'value'            => lq_setting( $key ),
					'aria-describedby' => $help_id ? $help_id : null,
				),
				$attrs
			);
			echo '<input' . lailatulqadar_attrs( $attrs ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lailatulqadar_attrs() escapes.
		},
		$help
	);
}

/**
 * A textarea row bound to a setting.
 *
 * @param string $key   Setting key.
 * @param string $id    Control ID.
 * @param string $label Label.
 * @param string $help  Help text.
 * @param array  $attrs Extra attributes (rows, class, lang, placeholder).
 */
function lailatulqadar_textarea_row( $key, $id, $label, $help = '', $attrs = array() ) {
	lailatulqadar_row(
		$id,
		$label,
		static function ( $help_id ) use ( $key, $id, $attrs ) {
			$attrs = array_merge(
				array(
					'id'               => $id,
					'name'             => lq_field_name( $key ),
					'rows'             => 3,
					'aria-describedby' => $help_id ? $help_id : null,
				),
				$attrs
			);
			echo '<textarea' . lailatulqadar_attrs( $attrs ) . '>' . esc_textarea( lq_setting( $key ) ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lailatulqadar_attrs() escapes.
		},
		$help
	);
}

/**
 * A toggle switch row for a two-valued setting. A hidden field carries the
 * off value, so an unticked switch still saves.
 *
 * @param string $key   Setting key.
 * @param string $id    Control ID.
 * @param string $label Label.
 * @param string $help  Help text.
 * @param string $on    Stored value when on.
 * @param string $off   Stored value when off.
 */
function lailatulqadar_toggle_row( $key, $id, $label, $help = '', $on = '1', $off = '0' ) {
	lailatulqadar_row(
		$id,
		$label,
		static function ( $help_id ) use ( $key, $id, $on, $off ) {
			$name = lq_field_name( $key );
			echo '<input' . lailatulqadar_attrs( array( 'type' => 'hidden', 'name' => $name, 'value' => $off ) ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by lailatulqadar_attrs().
			echo '<span class="lq-toggle"><input' . lailatulqadar_attrs( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by lailatulqadar_attrs().
				array(
					'type'             => 'checkbox',
					'role'             => 'switch',
					'id'               => $id,
					'name'             => $name,
					'value'            => $on,
					'checked'          => (string) lq_setting( $key ) === (string) $on,
					'aria-describedby' => $help_id ? $help_id : null,
				)
			) . '><span class="lq-toggle__track" aria-hidden="true"></span></span>';
		},
		$help,
		'lq-row--toggle'
	);
}

/**
 * A range slider row with its value shown beside it.
 *
 * @param string $key   Setting key.
 * @param string $id    Control ID.
 * @param string $label Label.
 * @param int    $min   Lowest value.
 * @param int    $max   Highest value.
 * @param string $help  Help text.
 */
function lailatulqadar_range_row( $key, $id, $label, $min, $max, $help = '' ) {
	lailatulqadar_row(
		$id,
		$label,
		static function ( $help_id ) use ( $key, $id, $min, $max ) {
			$value = (int) lq_setting( $key );
			echo '<span class="lq-range"><input' . lailatulqadar_attrs( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by lailatulqadar_attrs().
				array(
					'type'             => 'range',
					'id'               => $id,
					'name'             => lq_field_name( $key ),
					'min'              => $min,
					'max'              => $max,
					'step'             => 1,
					'value'            => $value,
					'aria-describedby' => $help_id ? $help_id : null,
					'data-lq-output'   => $id . '-value',
				)
			) . '><output class="lq-range__value" id="' . esc_attr( $id . '-value' ) . '" for="' . esc_attr( $id ) . '">' . esc_html( (string) $value ) . '</output></span>';
		},
		$help
	);
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
		<div class="lq-media" data-lq-media role="group" aria-label="<?php esc_attr_e( 'Logo calligraphy', 'lailatulqadar' ); ?>">
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
		<?php
		lailatulqadar_textarea_row( 'footer_note_primary', 'lq-note-primary', lq_setting( 'primary_label' ), '', array( 'rows' => 2 ) );
		lailatulqadar_textarea_row( 'footer_note_secondary', 'lq-note-secondary', lq_setting( 'secondary_label' ), '', array( 'rows' => 2, 'lang' => 'ms' ) );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Author for structured data', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Named as the author of every guide article in the Article structured data. Leave empty to use each page author’s display name and the About page.', 'lailatulqadar' ); ?></p>
		<?php if ( lq_seo_plugin() ) : ?>
			<p class="lq-callout lq-callout--warn"><?php echo esc_html( sprintf( /* translators: %s: SEO plugin name */ __( '%s is active and outputs the structured data, so these fields are not used. Set the author and publisher in the plugin.', 'lailatulqadar' ), lq_seo_plugin() ) ); ?></p>
		<?php endif; ?>
		<?php
		lailatulqadar_input_row( 'author_name', 'lq-author-name', __( 'Author name', 'lailatulqadar' ) );
		lailatulqadar_input_row( 'author_url', 'lq-author-url', __( 'Author profile URL', 'lailatulqadar' ), '', array( 'type' => 'url' ) );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Guide pages', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Pages appear in the site menus once published. Drafts stay hidden.', 'lailatulqadar' ); ?></p>
		<div class="lq-table-wrap">
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
		</div>
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

	<section class="lq-section">
		<h2><?php esc_html_e( 'English', 'lailatulqadar' ); ?></h2>
		<?php lailatulqadar_input_row( 'primary_label', 'lq-primary-label', __( 'Switcher label', 'lailatulqadar' ) ); ?>
		<input type="hidden" name="<?php echo esc_attr( lq_field_name( 'primary_slug' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'primary_slug' ) ); ?>">
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Malay', 'lailatulqadar' ); ?></h2>
		<?php
		lailatulqadar_input_row( 'secondary_label', 'lq-secondary-label', __( 'Switcher label', 'lailatulqadar' ), '', array( 'lang' => 'ms' ) );
		lailatulqadar_input_row( 'site_name_secondary', 'lq-site-name-secondary', __( 'Site name on Malay pages', 'lailatulqadar' ), '', array( 'lang' => 'ms' ) );
		?>
		<input type="hidden" name="<?php echo esc_attr( lq_field_name( 'secondary_slug' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'secondary_slug' ) ); ?>">
	</section>
	<?php
}

/**
 * Contextual links tab.
 */
function lq_render_links_tab() {
	$map    = lq_ctx_shared_map();
	$counts = array();
	foreach ( $map['links'] as $link ) {
		$site            = isset( $link['site'] ) ? (string) $link['site'] : '';
		$counts[ $site ] = ( isset( $counts[ $site ] ) ? $counts[ $site ] : 0 ) + 1;
	}
	$own_count    = isset( $counts[ LQ_SITE_KEY ] ) ? $counts[ LQ_SITE_KEY ] : 0;
	$sister_count = array_sum( $counts ) - $own_count;
	$sister_sites = count( $map['sites'] ) - ( isset( $map['sites'][ LQ_SITE_KEY ] ) ? 1 : 0 );
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'How contextual links work', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'In the running text of a guide page or blog post, the first mention of a known phrase links to the page that treats it, here or on a sister site. Links are added as the page is shown, so the stored text is never changed. Headings, quotations, verses, hadith, Arabic, figures, tables, footnotes and the "In brief" box are never linked. Each address is linked once a page, and no paragraph or list item receives more than one new link. Malay pages link only to Malay pages on this site.', 'lailatulqadar' ); ?></p>
		<p class="lq-callout">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: pages on this site, 2: pages on sister sites, 3: number of sister sites */
					__( 'The shared map lists %1$d pages on this site and %2$d pages on %3$d sister sites. It lives in inc/data/network-links.json and is the same file in every theme of the network.', 'lailatulqadar' ),
					$own_count,
					$sister_count,
					$sister_sites
				)
			);
			?>
		</p>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'This site', 'lailatulqadar' ); ?></h2>
		<?php
		lailatulqadar_toggle_row( 'ctx_own', 'lq-ctx-own', __( 'Link to other guide pages on this site', 'lailatulqadar' ) );
		lailatulqadar_range_row( 'ctx_own_max', 'lq-ctx-own-max', __( 'Links of this kind on one page, at most (0 to 15)', 'lailatulqadar' ), 0, 15 );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Sister sites', 'lailatulqadar' ); ?></h2>
		<?php
		lailatulqadar_toggle_row( 'ctx_sister', 'lq-ctx-sister', __( 'Link to pages on the sister sites (English pages only)', 'lailatulqadar' ) );
		lailatulqadar_range_row( 'ctx_sister_max', 'lq-ctx-sister-max', __( 'Links of this kind on one page, at most (0 to 10)', 'lailatulqadar' ), 0, 10 );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Extra phrases', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'One per line: phrase | address. An address beginning with / is a page on this site; a full address must be on this site or a sister site, and any other address is ignored. These lines are read before the shared map, so a phrase listed here takes its address from here.', 'lailatulqadar' ); ?></p>
		<?php
		lailatulqadar_textarea_row( 'ctx_extra', 'lq-ctx-extra', __( 'English pages', 'lailatulqadar' ), '', array( 'class' => 'code', 'rows' => 5, 'placeholder' => 'night vigil | /laylat-al-qadr-prayer/' ) );
		lailatulqadar_textarea_row( 'ctx_extra_ms', 'lq-ctx-extra-ms', __( 'Malay pages (addresses on this site only)', 'lailatulqadar' ), '', array( 'class' => 'code', 'rows' => 4, 'lang' => 'ms', 'placeholder' => 'malam kemuliaan | /ms/apa-itu-lailatulqadar/' ) );
		?>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Pages without links', 'lailatulqadar' ); ?></h2>
		<?php lailatulqadar_textarea_row( 'ctx_exclude', 'lq-ctx-exclude', __( 'Page slugs that never receive contextual links, one per line', 'lailatulqadar' ), '', array( 'class' => 'code' ) ); ?>
	</section>
	<?php
}

/**
 * Images tab: the guide's images and the featured-image switch.
 */
function lq_render_images_tab() {
	$count = 0;
	foreach ( lq_images_data() as $list ) {
		$count += count( $list );
	}
	?>
	<section class="lq-section">
		<h2><?php esc_html_e( 'Images', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'The guide pages carry freely licensed images from Wikimedia Commons (public domain, CC0, CC BY or CC BY-SA), Pixabay and Pexels (each under its own licence). Each is shown with its author, its licence linked to the licence deed or terms, and a link to its page on its source. The images and their credits ship with the theme in images/commons/ and inc/data/images.json; bin/fetch-commons-images.py checks each licence against its source and rebuilds both.', 'lailatulqadar' ); ?></p>
		<p class="lq-callout"><?php echo esc_html( sprintf( /* translators: %d: number of images. */ _n( '%d image is listed.', '%d images are listed.', $count, 'lailatulqadar' ), $count ) ); ?></p>
		<?php lailatulqadar_toggle_row( 'commons_featured', 'lq-commons-featured', __( 'When setup runs, set each page\'s lead image as its featured image', 'lailatulqadar' ), __( 'A featured image an editor has chosen is never replaced, and one an editor has removed is not put back.', 'lailatulqadar' ) ); ?>
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
				<label class="lq-scheme lq-scheme--<?php echo esc_attr( $value ); ?>" for="<?php echo esc_attr( 'lq-scheme-' . $value ); ?>">
					<input type="radio" id="<?php echo esc_attr( 'lq-scheme-' . $value ); ?>" name="<?php echo esc_attr( lq_field_name( 'scheme' ) ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $scheme, $value ); ?>>
					<span class="lq-scheme__swatch" aria-hidden="true"></span>
					<span class="lq-scheme__label"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Visitor switch', 'lailatulqadar' ); ?></h2>
		<?php
		lailatulqadar_toggle_row(
			'scheme_toggle',
			'lq-scheme-toggle',
			__( 'Light and dark switch in the header', 'lailatulqadar' ),
			__( 'On: visitors can switch, and their choice is remembered in their browser. Off: everyone sees the scheme chosen above.', 'lailatulqadar' ),
			'show',
			'hide'
		);
		?>
		<p class="lq-help"><?php esc_html_e( 'The scheme chosen above is what first-time visitors see. A visitor who uses the switch keeps their choice on later visits.', 'lailatulqadar' ); ?></p>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Accent colours', 'lailatulqadar' ); ?></h2>
		<p class="lq-help" id="lq-accent-help"><?php esc_html_e( 'Accents carry the Arabic verse, links and buttons. Keep a contrast ratio of at least 4.5:1 against the background: light accents for night, dark accents for dawn.', 'lailatulqadar' ); ?></p>
		<div class="lq-colours">
		<div class="lq-colour">
			<label for="lq-accent-night"><?php esc_html_e( 'Night accent', 'lailatulqadar' ); ?></label>
			<input id="lq-accent-night" type="text" class="lq-color-input" data-default-color="#c9a85c" data-lq-preview="night" aria-describedby="lq-accent-help" name="<?php echo esc_attr( lq_field_name( 'accent_night' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'accent_night' ) ); ?>">
			<div class="lq-preview lq-preview--night" data-lq-preview-target="night">
				<span class="lq-preview__ayah" lang="ar" dir="rtl">لَيۡلَةُ ٱلۡقَدۡرِ</span>
				<span class="lq-preview__link"><?php esc_html_e( 'Link colour', 'lailatulqadar' ); ?></span>
			</div>
		</div>
		<div class="lq-colour">
			<label for="lq-accent-dawn"><?php esc_html_e( 'Dawn accent', 'lailatulqadar' ); ?></label>
			<input id="lq-accent-dawn" type="text" class="lq-color-input" data-default-color="#7a5a14" data-lq-preview="dawn" aria-describedby="lq-accent-help" name="<?php echo esc_attr( lq_field_name( 'accent_dawn' ) ); ?>" value="<?php echo esc_attr( lq_setting( 'accent_dawn' ) ); ?>">
			<div class="lq-preview lq-preview--dawn" data-lq-preview-target="dawn">
				<span class="lq-preview__ayah" lang="ar" dir="rtl">لَيۡلَةُ ٱلۡقَدۡرِ</span>
				<span class="lq-preview__link"><?php esc_html_e( 'Link colour', 'lailatulqadar' ); ?></span>
			</div>
		</div>
		</div>
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
		<div class="lq-table-wrap">
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
		</div>
		<p class="lq-help"><?php esc_html_e( 'The first row is the Ramadan the site currently shows. An asterisk marks a year with an override.', 'lailatulqadar' ); ?></p>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Override one year (optional)', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Use this only when an official announcement differs from the Umm al-Qura date. Leave the fields empty to use the automatic dates.', 'lailatulqadar' ); ?></p>
		<?php
		lailatulqadar_input_row( 'ramadan_year', 'lq-ramadan-year', __( 'Hijri year', 'lailatulqadar' ), '', array( 'class' => 'small-text', 'inputmode' => 'numeric', 'placeholder' => $current ? (string) $current['hijri'] : '' ) );
		lailatulqadar_input_row( 'ramadan_start', 'lq-ramadan-start', __( '1 Ramadan', 'lailatulqadar' ), '', array( 'type' => 'date', 'class' => null ) );
		lailatulqadar_input_row( 'ramadan_alt', 'lq-ramadan-alt', __( 'Alternative 1 Ramadan', 'lailatulqadar' ), '', array( 'type' => 'date', 'class' => null ) );
		?>
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
		<?php
		lailatulqadar_input_row( 'login_tagline_primary', 'lq-login-tagline-primary', __( 'Tagline (English)', 'lailatulqadar' ), '', array( 'placeholder' => 'A guide to Laylat al-Qadr from the Qurʾān and sound hadith.' ) );
		lailatulqadar_input_row( 'login_tagline_secondary', 'lq-login-tagline-secondary', __( 'Tagline (Malay)', 'lailatulqadar' ), '', array( 'lang' => 'ms', 'placeholder' => 'Panduan Lailatulqadar berdasarkan al-Quran dan hadis sahih.' ) );
		lailatulqadar_input_row( 'login_note_primary', 'lq-login-note-primary', __( 'Note (English)', 'lailatulqadar' ), '', array( 'placeholder' => 'Editors only' ) );
		lailatulqadar_input_row( 'login_note_secondary', 'lq-login-note-secondary', __( 'Note (Malay)', 'lailatulqadar' ), '', array( 'lang' => 'ms', 'placeholder' => 'Untuk penyunting sahaja' ) );
		?>
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
		<?php
		lailatulqadar_input_row( 'verify_google', 'lq-verify-google', __( 'Google Search Console', 'lailatulqadar' ) );
		lailatulqadar_input_row( 'verify_bing', 'lq-verify-bing', __( 'Bing Webmaster Tools', 'lailatulqadar' ) );
		?>
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
						__( 'Title tags read “title | Lailatulqadar” and run to %1$d characters at most, the site name included; meta descriptions to %2$d. Edit each value in the Search appearance box on the page.', 'lailatulqadar' ),
						LQ_TITLE_LIMIT,
						LQ_META_LIMIT
					)
				);
				?>
			</p>
		<?php endif; ?>
		<div class="lq-table-wrap">
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
							<td><?php lq_render_seo_cell( '' === $title ? '' : lq_compose_title( $title, false ), LQ_TITLE_LIMIT ); ?></td>
							<td><?php lq_render_seo_cell( $desc, LQ_META_LIMIT ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
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
		<div class="lq-table-wrap">
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
		</div>
		<div class="lq-actions">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lq_publish_ready">
				<?php lailatulqadar_nonce_field( 'lq_publish_ready' ); ?>
				<?php
				submit_button(
					/* translators: %d: number of drafts ready to publish */
					sprintf( __( 'Publish written drafts (%d)', 'lailatulqadar' ), isset( $counts['ready'] ) ? $counts['ready'] : 0 ),
					'primary',
					'submit',
					false,
					empty( $counts['ready'] ) ? array( 'id' => 'lq-publish-ready', 'disabled' => 'disabled' ) : array( 'id' => 'lq-publish-ready', 'data-lq-confirm' => __( 'Publish every written draft listed as ready? Check each one in the editor first.', 'lailatulqadar' ) )
				);
				?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lq_unpublish_empty">
				<?php lailatulqadar_nonce_field( 'lq_unpublish_empty' ); ?>
				<?php
				submit_button(
					/* translators: %d: number of published pages with little text */
					sprintf( __( 'Return empty published pages to draft (%d)', 'lailatulqadar' ), isset( $counts['empty-live'] ) ? $counts['empty-live'] : 0 ),
					'secondary',
					'submit',
					false,
					empty( $counts['empty-live'] ) ? array( 'id' => 'lq-unpublish-empty', 'disabled' => 'disabled' ) : array( 'id' => 'lq-unpublish-empty' )
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

	<?php
	$edited = array_filter(
		$rows,
		static function ( $row ) {
			return $row['id'] && get_post_meta( $row['id'], '_lq_seed_edited', true );
		}
	);
	if ( $edited ) :
		?>
		<section class="lq-section">
			<h2><?php esc_html_e( 'Edited, not updated', 'lailatulqadar' ); ?></h2>
			<p class="lq-help"><?php esc_html_e( 'The theme ships a newer version of these articles, but an editor has changed the page, so setup left it as it is. Compare the page with the shipped version and either keep the edits or replace the page. Replacing publishes the shipped version; the current text stays in the page\'s revisions.', 'lailatulqadar' ); ?></p>
			<table class="lq-pages">
				<tbody>
					<?php foreach ( $edited as $row ) : ?>
						<tr>
							<th scope="row"><a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></th>
							<td><?php echo esc_html( $langs[ $row['role'] ] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="lq_replace_shipped">
									<input type="hidden" name="page_id" value="<?php echo esc_attr( $row['id'] ); ?>">
									<?php lailatulqadar_nonce_field( 'lq_replace_shipped_' . $row['id'] ); ?>
									<?php submit_button( __( 'Replace with the shipped version', 'lailatulqadar' ), 'secondary', 'submit', false, array( 'id' => 'lq-replace-' . $row['id'], 'data-lq-confirm' => __( 'Replace this page with the version shipped with the theme? The edited text stays in its revisions.', 'lailatulqadar' ) ) ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Run setup', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Setup runs by itself on the first admin visit after the theme is activated or updated; this button runs it again. It creates any missing guide pages in both languages, places the Malay pages beneath the Malay home at /ms/, pairs each page with its translation and sets the front page. Every page whose article ships with the theme is filled and published; pages with no article (About and Contact) stay drafts. When the theme ships a new version of an article, a page nobody has edited takes it (WordPress keeps the old text as a revision); a page an editor has changed is never overwritten and is listed under "Edited, not updated". Obsolete copies of guide pages left by earlier setups go to the trash; pages the theme did not create are never touched. Featured images an editor chose are kept. Empty title tags and meta descriptions are filled from the keyword plan.', 'lailatulqadar' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lq_run_setup">
			<?php lailatulqadar_nonce_field( 'lq_run_setup' ); ?>
			<?php submit_button( __( 'Run setup', 'lailatulqadar' ), 'primary', 'submit', false, array( 'id' => 'lq-run-setup' ) ); ?>
		</form>
	</section>

	<section class="lq-section">
		<h2><?php esc_html_e( 'Reset settings', 'lailatulqadar' ); ?></h2>
		<p class="lq-help"><?php esc_html_e( 'Restores every setting on this screen to its default. Pages and content are not touched.', 'lailatulqadar' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="lq_reset_settings">
			<?php lailatulqadar_nonce_field( 'lq_reset_settings' ); ?>
			<?php submit_button( __( 'Reset settings', 'lailatulqadar' ), 'secondary', 'submit', false, array( 'id' => 'lq-reset-settings', 'data-lq-confirm' => __( 'Reset every Lailatulqadar setting to its default?', 'lailatulqadar' ) ) ); ?>
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
		if ( ! empty( $report['stopped'] ) ) {
			// Setup refused to run: say so, never "Setup finished".
			$html = '';
			foreach ( $report['errors'] as $error ) {
				$html .= '<p>' . esc_html( $error ) . '</p>';
			}
			lailatulqadar_notice( $html, 'warning' );
			$report = null;
		}
	}
	if ( $report ) {
		$created = count( $report['created'] );
		$html    = '<p>' . esc_html(
			sprintf(
				/* translators: 1: pages created, 2: pages reused, 3: drafts renamed */
				__( 'Setup finished. %1$d pages created, %2$d existing pages reused, %3$d untouched drafts renamed.', 'lailatulqadar' ),
				$created,
				count( $report['reused'] ),
				isset( $report['renamed'] ) ? count( $report['renamed'] ) : 0
			)
		) . '</p>';
		$lists = array(
			/* translators: %s: list of page titles */
			'seeded'    => __( 'Filled with the shipped article: %s.', 'lailatulqadar' ),
			/* translators: %s: list of page titles */
			'published' => __( 'Published: %s.', 'lailatulqadar' ),
			/* translators: %s: list of page titles */
			'refreshed' => __( 'Updated to the new shipped version (the old text is kept as a revision): %s.', 'lailatulqadar' ),
			/* translators: %s: list of page titles */
			'edited'    => __( 'Edited by an editor, so not updated: %s.', 'lailatulqadar' ),
			/* translators: %s: list of page titles */
			'trashed'   => __( 'Obsolete copies moved to the trash: %s.', 'lailatulqadar' ),
			/* translators: %s: list of page titles */
			'kept'      => __( 'Older copies kept because they have been edited: %s.', 'lailatulqadar' ),
		);
		foreach ( $lists as $list => $message ) {
			if ( ! empty( $report[ $list ] ) ) {
				$html .= '<p>' . esc_html( sprintf( $message, implode( ', ', $report[ $list ] ) ) ) . '</p>';
			}
		}
		foreach ( $report['errors'] as $error ) {
			$html .= '<p>' . esc_html( $error ) . '</p>';
		}
		lailatulqadar_notice( $html, $report['errors'] ? 'warning' : 'success' );
	}

	$status = get_transient( 'lailatulqadar_status_report' );
	if ( $status ) {
		delete_transient( 'lailatulqadar_status_report' );
		$message = 'publish' === $status['status']
			/* translators: %s: list of page titles */
			? __( 'Published: %s.', 'lailatulqadar' )
			/* translators: %s: list of page titles */
			: __( 'Returned to draft: %s.', 'lailatulqadar' );
		lailatulqadar_notice( '<p>' . esc_html( $status['pages'] ? sprintf( $message, implode( ', ', $status['pages'] ) ) : __( 'No pages needed changing.', 'lailatulqadar' ) ) . '</p>' );
	}

	if ( isset( $_GET['lq-reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		lailatulqadar_notice( '<p>' . esc_html__( 'Settings reset to their defaults.', 'lailatulqadar' ) . '</p>' );
	}

	if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		lailatulqadar_notice( '<p>' . esc_html__( 'Changes saved.', 'lailatulqadar' ) . '</p>' );
	}
}

/**
 * A dismissible notice through the core notice API.
 *
 * @param string $html Escaped paragraphs.
 * @param string $type success, warning, error or info.
 */
function lailatulqadar_notice( $html, $type = 'success' ) {
	wp_admin_notice(
		$html,
		array(
			'type'           => $type,
			'dismissible'    => true,
			'paragraph_wrap' => false,
		)
	);
}

/**
 * Tools: run setup.
 */
function lq_handle_run_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run setup.', 'lailatulqadar' ) );
	}
	check_admin_referer( 'lq_run_setup' );
	$report = lq_run_setup_locked();
	if ( null === $report ) {
		wp_die( esc_html__( 'Setup is already running. Try again in a minute.', 'lailatulqadar' ) );
	}
	set_transient( 'lailatulqadar_setup_report', $report, MINUTE_IN_SECONDS * 10 );
	wp_safe_redirect( admin_url( 'themes.php?page=lailatulqadar&tab=tools' ) );
	exit;
}
add_action( 'admin_post_lq_run_setup', 'lq_handle_run_setup' );

/**
 * Tools: replace an edited guide page with the article the theme ships.
 */
function lq_handle_replace_shipped() {
	$id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to replace pages.', 'lailatulqadar' ) );
	}
	check_admin_referer( 'lq_replace_shipped_' . $id );
	$report = array(
		'created'   => array(),
		'reused'    => array(),
		'seeded'    => array(),
		'published' => array(),
		'refreshed' => array(),
		'edited'    => array(),
		'trashed'   => array(),
		'kept'      => array(),
		'errors'    => array(),
	);
	$found = false;
	foreach ( lq_page_map() as $role => $ids ) {
		$key = array_search( $id, array_map( 'intval', (array) $ids ), true );
		if ( $id && false !== $key ) {
			$GLOBALS['lq_seeding'] = true;
			lq_seed_content( $id, (string) $key, (string) $role, $report, true );
			$GLOBALS['lq_seeding'] = false;
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		wp_die( esc_html__( 'That page is not one of the guide pages, so there is no shipped version to replace it with.', 'lailatulqadar' ) );
	}
	set_transient( 'lailatulqadar_setup_report', $report, MINUTE_IN_SECONDS * 10 );
	wp_safe_redirect( admin_url( 'themes.php?page=lailatulqadar&tab=tools' ) );
	exit;
}
add_action( 'admin_post_lq_replace_shipped', 'lq_handle_replace_shipped' );

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
	lailatulqadar_notice(
		sprintf(
			'<p>%1$s <a href="%2$s">%3$s</a></p>',
			esc_html__( 'Lailatulqadar has created the guide pages.', 'lailatulqadar' ),
			esc_url( admin_url( 'themes.php?page=lailatulqadar&tab=tools' ) ),
			esc_html__( 'Review the setup report', 'lailatulqadar' )
		)
	);
}
add_action( 'admin_notices', 'lq_setup_admin_notice' );
