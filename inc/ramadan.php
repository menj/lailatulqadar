<?php
/**
 * Ramadan calendar: the dates of Ramadan for every year the site serves.
 *
 * The table holds 1 Ramadan and 1 Shawwal for each Hijri year under the
 * Umm al-Qura calendar of Saudi Arabia, as encoded in the Unicode CLDR
 * calendar "islamic-umalqura" (generated with ICU 78.2). Countries that
 * rely on local sighting may begin a day later, so every year also offers
 * the following day as an alternative start. The Ramadan Dates settings
 * tab can override one year when an announcement differs.
 *
 * Checked against published expectations for 1446 (1 March 2025),
 * 1447 (18 February 2026) and 1448 (8 February 2027).
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hijri year => [ 1 Ramadan, 1 Shawwal ] (Gregorian, Y-m-d).
 *
 * @return array
 */
function lq_uq_table() {
	return array(
		1446 => array( '2025-03-01', '2025-03-30' ),
		1447 => array( '2026-02-18', '2026-03-20' ),
		1448 => array( '2027-02-08', '2027-03-09' ),
		1449 => array( '2028-01-28', '2028-02-26' ),
		1450 => array( '2029-01-16', '2029-02-14' ),
		1451 => array( '2030-01-05', '2030-02-04' ),
		1452 => array( '2030-12-26', '2031-01-24' ),
		1453 => array( '2031-12-16', '2032-01-14' ),
		1454 => array( '2032-12-04', '2033-01-03' ),
		1455 => array( '2033-11-23', '2033-12-23' ),
		1456 => array( '2034-11-12', '2034-12-12' ),
		1457 => array( '2035-11-01', '2035-12-01' ),
		1458 => array( '2036-10-21', '2036-11-19' ),
		1459 => array( '2037-10-10', '2037-11-09' ),
		1460 => array( '2038-09-30', '2038-10-29' ),
		1461 => array( '2039-09-19', '2039-10-19' ),
		1462 => array( '2040-09-08', '2040-10-07' ),
		1463 => array( '2041-08-28', '2041-09-27' ),
		1464 => array( '2042-08-17', '2042-09-16' ),
		1465 => array( '2043-08-06', '2043-09-05' ),
		1466 => array( '2044-07-26', '2044-08-24' ),
		1467 => array( '2045-07-16', '2045-08-14' ),
		1468 => array( '2046-07-05', '2046-08-04' ),
		1469 => array( '2047-06-25', '2047-07-24' ),
		1470 => array( '2048-06-13', '2048-07-13' ),
		1471 => array( '2049-06-02', '2049-07-02' ),
		1472 => array( '2050-05-22', '2050-06-21' ),
		1473 => array( '2051-05-12', '2051-06-10' ),
	);
}

/**
 * Add days to a Y-m-d date.
 *
 * @param string $ymd  Date.
 * @param int    $days Days to add.
 * @return string
 */
function lq_add_days( $ymd, $days ) {
	$d = new DateTimeImmutable( $ymd . ' 12:00:00', new DateTimeZone( 'UTC' ) );
	return $d->modify( ( $days >= 0 ? '+' : '' ) . (int) $days . ' days' )->format( 'Y-m-d' );
}

/**
 * One Ramadan, with any override from the settings applied.
 *
 * @param int $hijri Hijri year.
 * @return array|null hijri, start, alt, end, days, override.
 */
function lq_ramadan_year( $hijri ) {
	$table = lq_uq_table();
	if ( empty( $table[ $hijri ] ) ) {
		return null;
	}
	list( $start, $end ) = $table[ $hijri ];
	$override            = false;
	if ( (string) $hijri === (string) lq_setting( 'ramadan_year' ) && lq_setting( 'ramadan_start' ) ) {
		$start    = lq_setting( 'ramadan_start' );
		$override = true;
	}
	$alt = ( $override && lq_setting( 'ramadan_alt' ) ) ? lq_setting( 'ramadan_alt' ) : lq_add_days( $start, 1 );
	return array(
		'hijri'    => (int) $hijri,
		'start'    => $start,
		'alt'      => $alt,
		'end'      => $end,
		'days'     => (int) round( ( strtotime( $end ) - strtotime( $table[ $hijri ][0] ) ) / DAY_IN_SECONDS ),
		'override' => $override,
	);
}

/**
 * The Ramadan in progress, or the next one if none is.
 *
 * A Ramadan counts as current until the day after its last possible date,
 * so the dates stay on screen through Eid.
 *
 * @return array|null
 */
function lq_ramadan() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$today = (string) apply_filters( 'lq_ramadan_today', current_time( 'Y-m-d' ) );
	foreach ( array_keys( lq_uq_table() ) as $hijri ) {
		$r = lq_ramadan_year( $hijri );
		if ( $r && $today <= lq_add_days( $r['end'], 1 ) ) {
			$cache = $r;
			return $cache;
		}
	}
	return null;
}

/**
 * Localised date for display.
 *
 * @param string $ymd   Date.
 * @param string $style long (Saturday 27 February 2027) or short (Sat 27 Feb).
 * @param string $role  primary or secondary; defaults to the current language.
 * @return string
 */
function lq_fmt_date( $ymd, $style = 'long', $role = null ) {
	$role = $role ? $role : lq_current_role();
	$t    = strtotime( $ymd . ' 12:00:00 UTC' );
	$w    = (int) gmdate( 'w', $t );
	$m    = (int) gmdate( 'n', $t ) - 1;
	$j    = gmdate( 'j', $t );
	$y    = gmdate( 'Y', $t );
	if ( 'secondary' === $role ) {
		$days   = array( 'Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu' );
		$months = array( 'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember' );
		return 'short' === $style ? $days[ $w ] . ', ' . $j . ' ' . mb_substr( $months[ $m ], 0, 3 ) : $days[ $w ] . ', ' . $j . ' ' . $months[ $m ] . ' ' . $y;
	}
	return 'short' === $style ? gmdate( 'D j M', $t ) : gmdate( 'l j F Y', $t );
}

/**
 * Hijri date label: "27 Ramadan 1448 AH" or, in Malay, "27 Ramadan 1448 H".
 *
 * @param int    $day   Day of the month.
 * @param int    $year  Hijri year.
 * @param string $month ramadan or shawwal.
 * @param bool   $night Prefix "night of" (Malay "malam").
 * @param string $role  primary or secondary; defaults to the current language.
 * @return string
 */
function lq_hijri( $day, $year, $month = 'ramadan', $night = false, $role = null ) {
	$role = $role ? $role : lq_current_role();
	if ( 'secondary' === $role ) {
		$name = 'shawwal' === $month ? 'Syawal' : 'Ramadan';
		return ( $night ? 'malam ' : '' ) . (int) $day . ' ' . $name . ' ' . (int) $year . ' H';
	}
	$name = 'shawwal' === $month ? 'Shawwal' : 'Ramadan';
	return ( $night ? 'night of ' : '' ) . (int) $day . ' ' . $name . ' ' . (int) $year . ' AH';
}

/**
 * Evening on which night n begins, for a given 1 Ramadan.
 *
 * @param string $start 1 Ramadan.
 * @param int    $n     Night number.
 * @return string Y-m-d.
 */
function lq_night_evening( $start, $n ) {
	return lq_add_days( $start, (int) $n - 2 );
}

/**
 * [lq_ramadan show="year|hijri|hijri_night|start|alt|eid|night" n="27" which="start|alt" style="long|short" hijri="1|0"]
 *
 * Dates carry their Hijri equivalent in brackets unless hijri="0".
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function lq_ramadan_shortcode( $atts ) {
	$a = shortcode_atts(
		array(
			'show'  => 'year',
			'n'     => 27,
			'which' => 'start',
			'style' => 'long',
			'hijri' => '1',
		),
		$atts,
		'lq_ramadan'
	);
	$r = lq_ramadan();
	if ( ! $r ) {
		return '';
	}
	$with = '0' !== (string) $a['hijri'];
	$h    = function ( $label ) use ( $with ) {
		return $with ? ' (' . $label . ')' : '';
	};
	switch ( $a['show'] ) {
		case 'hijri':
			return (string) $r['hijri'];
		case 'hijri_night':
			return esc_html( lq_hijri( $a['n'], $r['hijri'] ) );
		case 'start':
			return esc_html( lq_fmt_date( $r['start'], $a['style'] ) . $h( lq_hijri( 1, $r['hijri'] ) ) );
		case 'alt':
			return esc_html( lq_fmt_date( $r['alt'], $a['style'] ) . $h( lq_hijri( 1, $r['hijri'] ) ) );
		case 'eid':
			$eid = lq_add_days( 'alt' === $a['which'] ? $r['alt'] : $r['start'], $r['days'] );
			return esc_html( lq_fmt_date( $eid, $a['style'] ) . $h( lq_hijri( 1, $r['hijri'], 'shawwal' ) ) );
		case 'night':
			$base = 'alt' === $a['which'] ? $r['alt'] : $r['start'];
			return esc_html( lq_fmt_date( lq_night_evening( $base, $a['n'] ), $a['style'] ) . $h( lq_hijri( $a['n'], $r['hijri'], 'ramadan', true ) ) );
		default:
			return gmdate( 'Y', strtotime( $r['start'] ) );
	}
}
add_shortcode( 'lq_ramadan', 'lq_ramadan_shortcode' );
