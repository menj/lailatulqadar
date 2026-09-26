<?php
/**
 * Shared helpers: settings, language detection and the guide page registry.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

const LQ_OPTION       = 'lailatulqadar_settings';
const LQ_PAGES_OPTION = 'lailatulqadar_pages';

/**
 * Default settings.
 *
 * @return array
 */
function lq_default_settings() {
	return array(
		'primary_slug'          => 'en',
		'secondary_slug'        => 'ms',
		'primary_label'         => 'English',
		'secondary_label'       => 'Bahasa Melayu',
		'site_name_secondary'   => 'Lailatulqadar',
		'footer_note_primary'   => '',
		'footer_note_secondary' => '',
		'scheme'                => 'night',
		'scheme_toggle'         => 'show',
		'accent_night'          => '#c9a85c',
		'accent_dawn'           => '#7a5a14',
		'calligraphy_id'          => 0,
		'login_tagline_primary'   => '',
		'login_tagline_secondary' => '',
		'login_note_primary'      => '',
		'login_note_secondary'    => '',
		'verify_google'         => '',
		'verify_bing'           => '',
		'author_name'           => '',
		'author_url'            => '',
		'ramadan_year'          => '',
		'ramadan_start'         => '',
		'ramadan_alt'           => '',
	);
}

/**
 * Read one setting, falling back to its default.
 *
 * @param string $key Setting key.
 * @return string
 */
function lq_setting( $key ) {
	$settings = wp_parse_args( (array) get_option( LQ_OPTION, array() ), lq_default_settings() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : '';
}

/**
 * Role of a page: 'secondary' for the Malay home and every page beneath it
 * (or marked Malay), 'primary' otherwise.
 *
 * @param int $id Page or post ID.
 * @return string
 */
function lq_role_of( $id ) {
	$id = (int) $id;
	if ( ! $id ) {
		return 'primary';
	}
	if ( 'ms' === get_post_meta( $id, '_lq_lang', true ) ) {
		return 'secondary';
	}
	$map  = lq_page_map();
	$home = empty( $map['secondary']['home'] ) ? 0 : (int) $map['secondary']['home'];
	if ( $home && ( $id === $home || in_array( $home, array_map( 'intval', get_post_ancestors( $id ) ), true ) ) ) {
		return 'secondary';
	}
	if ( ! empty( $map['secondary'] ) && in_array( $id, array_map( 'intval', (array) $map['secondary'] ), true ) ) {
		return 'secondary';
	}
	return 'primary';
}

/**
 * Current language role: 'primary' (English) or 'secondary' (Malay).
 *
 * The theme handles both languages itself: the Malay pages live beneath the
 * Malay home at /ms/. Singular views take the role of the page; other views
 * (search, archives, not found) follow the address, or ?lang=ms.
 *
 * @return string
 */
function lq_current_role() {
	static $role = null;
	if ( null !== $role ) {
		return $role;
	}
	if ( ! did_action( 'wp' ) ) {
		return 'primary';
	}
	if ( is_singular() ) {
		$role = lq_role_of( get_queried_object_id() );
		return $role;
	}
	$path   = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$base   = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	$path   = '' !== $base && 0 === strpos( $path, $base ) ? trim( substr( $path, strlen( $base ) ), '/' ) : $path;
	$prefix = lq_setting( 'secondary_slug' );
	$lang   = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$role   = ( $prefix === $path || 0 === strpos( $path, $prefix . '/' ) || $prefix === $lang ) ? 'secondary' : 'primary';
	return $role;
}

/**
 * Whether the current view is a language's home page (the English front
 * page or the Malay home at /ms/).
 *
 * @return bool
 */
function lq_is_home() {
	if ( is_front_page() ) {
		return true;
	}
	$map = lq_page_map();
	return is_page() && ! empty( $map['secondary']['home'] ) && (int) $map['secondary']['home'] === get_queried_object_id();
}

/**
 * The same guide page in the other language, if it exists.
 *
 * @param int $id Page ID.
 * @return int Page ID or 0.
 */
function lq_translation_of( $id ) {
	$map  = lq_page_map();
	$role = lq_role_of( $id );
	$key  = array_search( (int) $id, array_map( 'intval', isset( $map[ $role ] ) ? (array) $map[ $role ] : array() ), true );
	$to   = 'secondary' === $role ? 'primary' : 'secondary';
	if ( false === $key || empty( $map[ $to ][ $key ] ) ) {
		$other = (int) get_post_meta( $id, '_lq_translation', true );
		return $other && get_post( $other ) ? $other : 0;
	}
	return (int) $map[ $to ][ $key ];
}

/**
 * The guide pages the theme creates and manages.
 *
 * Each entry holds a title (the H1), slug, short menu label, title tag
 * (seo), meta description and focus keyword per language role, plus the menu sets it
 * belongs to: header, path (reading order) and footer. The order of the
 * entries is the reading order. Keyword targets: docs/seo.md.
 *
 * @return array
 */
function lq_page_definitions() {
	return array(
		'home' => array(
			'sets'      => array(),
			'primary'   => array( 'title' => 'Home', 'slug' => 'home', 'short' => 'Home', 'seo' => 'Laylat al-Qadr (Laylatul Qadr): Guide to the Night of Power', 'meta' => 'Laylat al-Qadr, the Night of Power, explained from the Qurʾān and sound hadith: dates, signs, prayer and duʿāʾ. Start reading.', 'focus' => 'laylatul qadr' ),
			'secondary' => array( 'title' => 'Utama', 'slug' => 'ms', 'short' => 'Utama', 'seo' => 'Lailatulqadar: Panduan Malam Kemuliaan', 'meta' => 'Panduan Lailatulqadar berdasarkan al-Quran dan hadis sahih: tarikh, tanda-tanda, amalan dan doa. Mulakan pembacaan.', 'focus' => 'lailatulqadar' ),
		),
		'start' => array(
			'sets'      => array( 'path' ),
			'primary'   => array( 'title' => 'Start Here', 'slug' => 'start-here', 'short' => 'Start here', 'seo' => 'Start Here: Laylat al-Qadr for Newcomers', 'meta' => 'New to Laylat al-Qadr? A short introduction to the Night of Power and how to read this guide. Begin here.', 'focus' => 'laylatul qadr for beginners' ),
			'secondary' => array( 'title' => 'Mula di Sini', 'slug' => 'mula-di-sini', 'short' => 'Mula di Sini', 'seo' => 'Mula di Sini: Pengenalan Ringkas Lailatulqadar', 'meta' => 'Pertama kali mengenali Lailatulqadar? Pengenalan ringkas tentang malam kemuliaan dan cara membaca panduan ini. Mula di sini.', 'focus' => 'lailatulqadar' ),
		),
		'what' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'What Is Laylat al-Qadr?', 'slug' => 'what-is-laylat-al-qadr', 'short' => 'What it is', 'seo' => 'What Is Laylat al-Qadr? Meaning of the Night of Power', 'meta' => 'What is Laylat al-Qadr? The meaning of qadr, why the night outweighs a thousand months, and what happens on it. Read more.', 'focus' => 'what is laylatul qadr' ),
			'secondary' => array( 'title' => 'Apa Itu Lailatulqadar?', 'slug' => 'apa-itu-lailatulqadar', 'short' => 'Pengenalan', 'seo' => 'Apa Itu Lailatulqadar? Maksud dan Kelebihannya', 'meta' => 'Apa itu Lailatulqadar? Maksud qadar, kelebihan malam yang lebih baik daripada seribu bulan dan peristiwanya. Baca lanjut.', 'focus' => 'lailatulqadar' ),
		),
		'surah' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'Sūrat al-Qadr (Surah 97)', 'slug' => 'surah-al-qadr', 'short' => 'Surah', 'seo' => 'Surah al-Qadr (97): Arabic, Transliteration and Translation', 'meta' => 'Surah al-Qadr in Arabic with transliteration, English translation and a verse-by-verse explanation. Read the surah.', 'focus' => 'surah qadr' ),
			'secondary' => array( 'title' => 'Surah al-Qadr', 'slug' => 'terjemahan-surah-al-qadr', 'short' => 'Surah', 'seo' => 'Surah al-Qadr: Teks Arab, Rumi dan Terjemahan', 'meta' => 'Surah al-Qadr dalam tulisan Arab, rumi dan terjemahan bahasa Melayu, beserta huraian setiap ayat. Baca surah ini.', 'focus' => 'surah lailatulqadar' ),
		),
		'when' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'When Is Laylat al-Qadr?', 'slug' => 'when-is-laylat-al-qadr', 'short' => 'When', 'seo' => 'When Is Laylat al-Qadr? The Odd Nights and [lq_ramadan] Dates', 'meta' => 'When is Laylat al-Qadr? The odd nights of the last ten, the case for the 27th, and expected [lq_ramadan] dates. Check the dates.', 'focus' => 'when is laylatul qadr' ),
			'secondary' => array( 'title' => 'Bilakah Lailatulqadar?', 'slug' => 'bilakah-lailatulqadar', 'short' => 'Masa', 'seo' => 'Bilakah Lailatulqadar? Malam Ganjil dan Tarikh [lq_ramadan]', 'meta' => 'Bilakah Lailatulqadar? Malam-malam ganjil sepuluh terakhir, pandangan tentang malam ke-27 dan jangkaan tarikh [lq_ramadan]. Semak tarikh.', 'focus' => 'bilakah lailatulqadar' ),
		),
		'last10' => array(
			'sets'      => array( 'path' ),
			'primary'   => array( 'title' => 'The Last Ten Nights of Ramadan', 'slug' => 'last-ten-nights-of-ramadan', 'short' => 'Last ten', 'seo' => 'Last 10 Nights of Ramadan [lq_ramadan]: Dates, Iʿtikāf and Worship', 'meta' => 'The last 10 nights of Ramadan in [lq_ramadan]: expected dates, iʿtikāf, and how the Prophet spent them. See the full guide.', 'focus' => 'last 10 days of ramadan' ),
			'secondary' => array( 'title' => 'Sepuluh Malam Terakhir Ramadan', 'slug' => 'sepuluh-malam-terakhir-ramadan', 'short' => '10 Malam', 'seo' => 'Sepuluh Malam Terakhir Ramadan [lq_ramadan]: Tarikh dan Iktikaf', 'meta' => 'Sepuluh malam terakhir Ramadan [lq_ramadan]: jangkaan tarikh, iktikaf dan cara Nabi SAW menghidupkannya. Baca panduan penuh.', 'focus' => 'sepuluh malam terakhir ramadan' ),
		),
		'signs' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'Signs of Laylat al-Qadr', 'slug' => 'signs-of-laylat-al-qadr', 'short' => 'Signs', 'seo' => 'Signs of Laylat al-Qadr: Moon, Sunrise and Rain in Hadith', 'meta' => 'The signs of Laylat al-Qadr in authentic hadith: the calm night, the moon, the rayless sunrise and rain. See the evidence.', 'focus' => 'laylatul qadr signs' ),
			'secondary' => array( 'title' => 'Tanda-Tanda Lailatulqadar', 'slug' => 'tanda-tanda-lailatulqadar', 'short' => 'Tanda', 'seo' => 'Tanda-Tanda Lailatulqadar Menurut Hadis Sahih', 'meta' => 'Tanda-tanda Lailatulqadar dalam hadis sahih: malam yang tenang, bulan, matahari terbit tanpa sinar dan hujan. Lihat dalilnya.', 'focus' => 'tanda tanda lailatulqadar' ),
		),
		'worship' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'Prayer and Worship on Laylat al-Qadr', 'slug' => 'laylat-al-qadr-prayer', 'short' => 'Prayer', 'seo' => 'How to Pray on Laylat al-Qadr: Prayer, Qiyām and Worship', 'meta' => 'How to pray on Laylat al-Qadr: night prayer, qiyām al-layl, Qurʾān recitation and dhikr, step by step. Plan your night.', 'focus' => 'laylatul qadr prayer' ),
			'secondary' => array( 'title' => 'Amalan Malam Lailatulqadar', 'slug' => 'amalan-malam-lailatulqadar', 'short' => 'Amalan', 'seo' => 'Amalan Malam Lailatulqadar: Solat, Qiamullail dan Zikir', 'meta' => 'Amalan malam Lailatulqadar: solat malam, qiamullail, bacaan al-Quran dan zikir, langkah demi langkah. Rancang malam anda.', 'focus' => 'amalan lailatulqadar' ),
		),
		'dua' => array(
			'sets'      => array( 'header', 'path' ),
			'primary'   => array( 'title' => 'Laylat al-Qadr Duʿāʾ', 'slug' => 'laylat-al-qadr-dua', 'short' => 'Duʿāʾ', 'seo' => 'Laylat al-Qadr Duʿāʾ (Laylatul Qadr Dua): Arabic and English', 'meta' => 'The duʿāʾ the Prophet taught for Laylat al-Qadr, in Arabic with transliteration and meaning, plus its source. Learn it.', 'focus' => 'laylatul qadr dua' ),
			'secondary' => array( 'title' => 'Doa Lailatulqadar', 'slug' => 'doa-lailatulqadar', 'short' => 'Doa', 'seo' => 'Doa Lailatulqadar: Teks Arab, Rumi dan Maksud', 'meta' => 'Doa yang diajarkan Nabi SAW untuk Lailatulqadar dalam tulisan Arab, rumi dan maksud, beserta sumbernya. Hafaz doa ini.', 'focus' => 'doa lailatulqadar' ),
		),
		'planner' => array(
			'sets'      => array( 'path' ),
			'primary'   => array( 'title' => 'Night Planner', 'slug' => 'night-planner', 'short' => 'Planner', 'seo' => 'Laylat al-Qadr Night Planner: Last Third Times by City', 'meta' => 'Sunset, last third and dawn for each of the last ten nights of Ramadan in your city, with calendar reminders. Find your times.', 'focus' => 'laylatul qadr checklist' ),
			'secondary' => array( 'title' => 'Perancang Malam', 'slug' => 'perancang-malam', 'short' => 'Perancang', 'seo' => 'Perancang Malam Lailatulqadar: Waktu Sepertiga Malam', 'meta' => 'Waktu Maghrib, sepertiga malam terakhir dan fajar bagi sepuluh malam terakhir Ramadan di bandar anda. Semak waktu anda.', 'focus' => 'amalan lailatulqadar' ),
		),
		'faq' => array(
			'sets'      => array( 'path' ),
			'primary'   => array( 'title' => 'Frequently Asked Questions', 'slug' => 'faq', 'short' => 'FAQ', 'seo' => 'Laylat al-Qadr FAQ: Menstruation, Time Zones and More', 'meta' => 'Answers on Laylat al-Qadr: worship during menstruation, different countries, how long the night lasts and more. Find answers.', 'focus' => 'laylatul qadr questions' ),
			'secondary' => array( 'title' => 'Soalan Lazim', 'slug' => 'soalan-lazim', 'short' => 'Soalan Lazim', 'seo' => 'Soalan Lazim Lailatulqadar: Wanita Haid dan Zon Waktu', 'meta' => 'Jawapan tentang Lailatulqadar: amalan wanita haid, perbezaan negara, tempoh malam dan lain-lain. Cari jawapan anda.', 'focus' => 'lailatulqadar' ),
		),
		'sources' => array(
			'sets'      => array( 'path', 'footer' ),
			'primary'   => array( 'title' => 'Hadith and Sources', 'slug' => 'hadith-and-sources', 'short' => 'Sources', 'seo' => 'Hadith on Laylat al-Qadr: Authentic Narrations and Sources', 'meta' => 'Authentic hadith on Laylat al-Qadr with collection, number and grading, and the sources this guide relies on. Check sources.', 'focus' => 'laylatul qadr hadith' ),
			'secondary' => array( 'title' => 'Hadis dan Sumber Rujukan', 'slug' => 'hadis-dan-sumber-rujukan', 'short' => 'Sumber', 'seo' => 'Hadis Lailatulqadar: Riwayat Sahih dan Sumber Rujukan', 'meta' => 'Hadis sahih tentang Lailatulqadar beserta kitab, nombor dan status, serta sumber rujukan panduan ini. Semak sumbernya.', 'focus' => 'lailatulqadar hadis' ),
		),
		'about' => array(
			'sets'      => array( 'footer' ),
			'primary'   => array( 'title' => 'About', 'slug' => 'about', 'short' => 'About', 'seo' => 'About This Guide', 'meta' => 'Who writes this Laylat al-Qadr guide, how sources are checked, and how to report an error. Read about us.', 'focus' => '' ),
			'secondary' => array( 'title' => 'Tentang Kami', 'slug' => 'tentang-kami', 'short' => 'Tentang Kami', 'seo' => 'Tentang Panduan Ini', 'meta' => 'Siapa penulis panduan Lailatulqadar ini, cara sumber disemak dan cara melaporkan kesilapan. Baca tentang kami.', 'focus' => '' ),
		),
		'sitemap' => array(
			'sets'      => array( 'footer' ),
			'primary'   => array( 'title' => 'Sitemap', 'slug' => 'sitemap', 'short' => 'Sitemap', 'seo' => 'Sitemap: Every Page of the Laylat al-Qadr Guide', 'meta' => 'Every page of the Laylat al-Qadr guide in one list, in English and Malay. Find the page you need.', 'focus' => '' ),
			'secondary' => array( 'title' => 'Peta Laman', 'slug' => 'peta-laman', 'short' => 'Peta Laman', 'seo' => 'Peta Laman: Semua Halaman Panduan Lailatulqadar', 'meta' => 'Semua halaman panduan Lailatulqadar dalam satu senarai, dalam bahasa Melayu dan Inggeris. Cari halaman anda.', 'focus' => '' ),
		),
		'contact' => array(
			'sets'      => array( 'footer' ),
			'primary'   => array( 'title' => 'Contact', 'slug' => 'contact', 'short' => 'Contact', 'seo' => 'Contact', 'meta' => 'Send a correction, a question or a source for the Laylat al-Qadr guide. Contact the editor.', 'focus' => '' ),
			'secondary' => array( 'title' => 'Hubungi Kami', 'slug' => 'hubungi-kami', 'short' => 'Hubungi Kami', 'seo' => 'Hubungi Kami', 'meta' => 'Hantar pembetulan, soalan atau sumber untuk panduan Lailatulqadar. Hubungi penyunting.', 'focus' => '' ),
		),
	);
}

/**
 * Stored page IDs, keyed by role then page key.
 *
 * @return array
 */
function lq_page_map() {
	$map = get_option( LQ_PAGES_OPTION, array() );
	return is_array( $map ) ? $map : array();
}

/**
 * Published guide pages for one menu set in the current language.
 *
 * Draft pages are skipped, so a page appears in menus only once published.
 *
 * @param string $set header, path, footer or sitemap (every page).
 * @return array[] Each item: id, url, title, short.
 */
function lq_menu_items( $set, $upcoming = false ) {
	$role  = lq_current_role();
	$map   = lq_page_map();
	$items = array();

	foreach ( lq_page_definitions() as $key => $def ) {
		$member = 'sitemap' === $set ? 'sitemap' !== $key : in_array( $set, $def['sets'], true );
		if ( ! $member ) {
			continue;
		}
		$id        = empty( $map[ $role ][ $key ] ) ? 0 : (int) $map[ $role ][ $key ];
		$published = $id && 'publish' === get_post_status( $id );
		if ( ! $published && ! $upcoming ) {
			continue;
		}
		$items[] = array(
			'id'       => $id,
			'url'      => $published ? get_permalink( $id ) : '',
			'title'    => $published ? get_the_title( $id ) : $def[ $role ]['title'],
			'short'    => $def[ $role ]['short'],
			'upcoming' => ! $published,
		);
	}

	return $items;
}

/**
 * Words of visible text in a page, ignoring markup.
 *
 * @param int $id Page ID.
 * @return int
 */
function lq_word_count( $id ) {
	$text = wp_strip_all_tags( do_shortcode( (string) get_post_field( 'post_content', $id ) ) );
	return (int) str_word_count( preg_replace( '/[^\\p{L}\\p{N}\\s\'-]/u', ' ', $text ) );
}

/**
 * Status of every guide page in both languages, for the Tools tab.
 *
 * Each row: key, role, id, title, status (missing, draft, publish, other),
 * words, and state: live, ready (draft with content), unwritten (draft
 * with little or no text), empty-live (published with little or no text),
 * or missing.
 *
 * @return array
 */
function lq_page_status_report() {
	$map  = lq_page_map();
	$rows = array();
	$skip = array( 'home', 'sitemap' ); // Built from blocks, with little prose.
	foreach ( array( 'primary', 'secondary' ) as $role ) {
		foreach ( lq_page_definitions() as $key => $def ) {
			$id     = empty( $map[ $role ][ $key ] ) ? 0 : (int) $map[ $role ][ $key ];
			$status = $id ? get_post_status( $id ) : 'missing';
			$words  = $id ? lq_word_count( $id ) : 0;
			$thin   = ! in_array( $key, $skip, true ) && $words < 150;
			if ( ! $id || ! $status ) {
				$state = 'missing';
			} elseif ( 'publish' === $status ) {
				$state = $thin ? 'empty-live' : 'live';
			} elseif ( 'draft' === $status || 'pending' === $status ) {
				$state = $thin ? 'unwritten' : 'ready';
			} else {
				$state = $status;
			}
			$rows[] = array(
				'key'    => $key,
				'role'   => $role,
				'id'     => $id,
				'title'  => $id ? get_the_title( $id ) : $def[ $role ]['title'],
				'status' => $status,
				'words'  => $words,
				'state'  => $state,
			);
		}
	}
	return $rows;
}

/**
 * The logo mark as inline SVG: a lantern with an onion dome, an arched
 * window lit by a flame and a stepped base, set against a crescent with a
 * star. The "icon" variant drops the crescent and star for very small sizes.
 * Colours come from CSS (.lq-mark__metal, __glass, __glow, __flame, __cut,
 * __ring, __crescent, __star).
 *
 * @param string $variant full or icon.
 * @return string
 */
function lq_mark_svg( $variant = 'full' ) {
	$lantern = '<circle class="lq-mark__ring" cx="24" cy="3.4" r="1.7"/><path class="lq-mark__metal" d="M14.5 17.2 C14.5 12.2 19.5 10.2 24 5.2 C28.5 10.2 33.5 12.2 33.5 17.2 Z"/><path class="lq-mark__cut" d="M24 6.6 C21.6 10.4 20.7 13.6 21.1 17.2 M24 6.6 C26.4 10.4 27.3 13.6 26.9 17.2"/><rect class="lq-mark__metal" x="13" y="17" width="22" height="2.8" rx="1"/><rect class="lq-mark__glass" x="15.4" y="20.9" width="17.2" height="14.4" rx="1.2"/><path class="lq-mark__glow" d="M19.8 35.3 V27.6 A4.2 4.2 0 0 1 28.2 27.6 V35.3 Z"/><path class="lq-mark__flame" d="M24 26.4 Q27 30.1 24 33.6 Q21 30.1 24 26.4 Z"/><rect class="lq-mark__metal" x="13" y="35.3" width="22" height="2.8" rx="1"/><rect class="lq-mark__metal" x="10.5" y="38.9" width="27" height="2.8" rx="1.4"/>';
	$extra   = 'icon' === $variant ? array( '', '' ) : array( '<path class="lq-mark__crescent" d="M27 4.6 A19.5 19.5 0 1 0 44.2 30.5 A16.4 16.4 0 1 1 27 4.6 Z"/>', '<path class="lq-mark__star" d="M40.5 9l1.2 3.1 3.1 1.2-3.1 1.2-1.2 3.1-1.2-3.1-3.1-1.2 3.1-1.2z"/>' );
	return '<svg viewBox="0 0 48 48" focusable="false" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">' . $extra[0] . $lantern . $extra[1] . '</svg>';
}

/**
 * The Arabic calligraphy of ليلة القدر for the logo lockup.
 *
 * Uses the image chosen on the General tab (a transparent PNG or an SVG,
 * recoloured by CSS to the scheme's gold); until one is chosen, the words
 * are typeset in Arslan Wessam B, the theme's display Arabic face.
 *
 * @param string $class Extra class for sizing in context.
 * @return string
 */
function lq_calligraphy_html( $class = '' ) {
	$id  = (int) lq_setting( 'calligraphy_id' );
	$url = $id ? wp_get_attachment_url( $id ) : '';
	if ( $url ) {
		$meta  = wp_get_attachment_metadata( $id );
		$ratio = ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) ? $meta['width'] / $meta['height'] : 3;
		return sprintf(
			'<span class="lq-calli lq-calli--image %1$s" role="img" aria-label="%2$s" lang="ar" style="--lq-calli-src:url(\'%3$s\');--lq-calli-ratio:%4$s"></span>',
			esc_attr( $class ),
			esc_attr( 'ليلة القدر' ),
			esc_url( $url ),
			esc_attr( round( $ratio, 4 ) )
		);
	}
	return sprintf( '<span class="lq-calli lq-calli--type %s" lang="ar" dir="rtl">ليلة القدر</span>', esc_attr( $class ) );
}
