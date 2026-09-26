<?php
/**
 * Night window: city presets, strings and dates for the interactive
 * timetable of the last ten nights.
 *
 * Calculation runs in the visitor's browser with the adhan library
 * (js/vendor/adhan.min.js, MIT). Methods follow each country's authority
 * where known, or the nearest standard method; see docs/night-window.md.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * City presets: id => [ en name, ms name, en country, ms country, region, lat, lng, timezone, method ].
 *
 * Method is an adhan CalculationMethod name, or "custom:<fajr>:<isha>".
 *
 * @return array
 */
function lq_nw_cities() {
	return array(
		'makkah'      => array( 'Makkah', 'Makkah', 'Saudi Arabia', 'Arab Saudi', 'arabia', 21.4225, 39.8262, 'Asia/Riyadh', 'UmmAlQura' ),
		'madinah'     => array( 'Madinah', 'Madinah', 'Saudi Arabia', 'Arab Saudi', 'arabia', 24.4672, 39.6111, 'Asia/Riyadh', 'UmmAlQura' ),
		'riyadh'      => array( 'Riyadh', 'Riyadh', 'Saudi Arabia', 'Arab Saudi', 'arabia', 24.7136, 46.6753, 'Asia/Riyadh', 'UmmAlQura' ),
		'dubai'       => array( 'Dubai', 'Dubai', 'United Arab Emirates', 'Emiriah Arab Bersatu', 'arabia', 25.2048, 55.2708, 'Asia/Dubai', 'Dubai' ),
		'doha'        => array( 'Doha', 'Doha', 'Qatar', 'Qatar', 'arabia', 25.2854, 51.5310, 'Asia/Qatar', 'Qatar' ),
		'kuwait'      => array( 'Kuwait City', 'Bandar Kuwait', 'Kuwait', 'Kuwait', 'arabia', 29.3759, 47.9774, 'Asia/Kuwait', 'Kuwait' ),
		'muscat'      => array( 'Muscat', 'Muscat', 'Oman', 'Oman', 'arabia', 23.5880, 58.3829, 'Asia/Muscat', 'UmmAlQura' ),
		'baghdad'     => array( 'Baghdad', 'Baghdad', 'Iraq', 'Iraq', 'levant', 33.3152, 44.3661, 'Asia/Baghdad', 'MuslimWorldLeague' ),
		'amman'       => array( 'Amman', 'Amman', 'Jordan', 'Jordan', 'levant', 31.9454, 35.9284, 'Asia/Amman', 'MuslimWorldLeague' ),
		'quds'        => array( 'Jerusalem (al-Quds)', 'Baitulmaqdis', 'Palestine', 'Palestin', 'levant', 31.7767, 35.2345, 'Asia/Jerusalem', 'MuslimWorldLeague' ),
		'damascus'    => array( 'Damascus', 'Damsyik', 'Syria', 'Syria', 'levant', 33.5138, 36.2765, 'Asia/Damascus', 'MuslimWorldLeague' ),
		'beirut'      => array( 'Beirut', 'Beirut', 'Lebanon', 'Lubnan', 'levant', 33.8938, 35.5018, 'Asia/Beirut', 'MuslimWorldLeague' ),
		'cairo'       => array( 'Cairo', 'Kaherah', 'Egypt', 'Mesir', 'africa', 30.0444, 31.2357, 'Africa/Cairo', 'Egyptian' ),
		'khartoum'    => array( 'Khartoum', 'Khartoum', 'Sudan', 'Sudan', 'africa', 15.5007, 32.5599, 'Africa/Khartoum', 'Egyptian' ),
		'tripoli'     => array( 'Tripoli', 'Tripoli', 'Libya', 'Libya', 'africa', 32.8872, 13.1913, 'Africa/Tripoli', 'MuslimWorldLeague' ),
		'tunis'       => array( 'Tunis', 'Tunis', 'Tunisia', 'Tunisia', 'africa', 36.8065, 10.1815, 'Africa/Tunis', 'MuslimWorldLeague' ),
		'algiers'     => array( 'Algiers', 'Algiers', 'Algeria', 'Algeria', 'africa', 36.7538, 3.0588, 'Africa/Algiers', 'MuslimWorldLeague' ),
		'casablanca'  => array( 'Casablanca', 'Casablanca', 'Morocco', 'Maghribi', 'africa', 33.5731, -7.5898, 'Africa/Casablanca', 'custom:19:17' ),
		'kano'        => array( 'Kano', 'Kano', 'Nigeria', 'Nigeria', 'africa', 12.0022, 8.5920, 'Africa/Lagos', 'MuslimWorldLeague' ),
		'dakar'       => array( 'Dakar', 'Dakar', 'Senegal', 'Senegal', 'africa', 14.7167, -17.4677, 'Africa/Dakar', 'MuslimWorldLeague' ),
		'mogadishu'   => array( 'Mogadishu', 'Mogadishu', 'Somalia', 'Somalia', 'africa', 2.0469, 45.3182, 'Africa/Mogadishu', 'MuslimWorldLeague' ),
		'istanbul'    => array( 'Istanbul', 'Istanbul', 'Türkiye', 'Turkiye', 'central', 41.0082, 28.9784, 'Europe/Istanbul', 'Turkey' ),
		'tehran'      => array( 'Tehran', 'Tehran', 'Iran', 'Iran', 'central', 35.6892, 51.3890, 'Asia/Tehran', 'Tehran' ),
		'baku'        => array( 'Baku', 'Baku', 'Azerbaijan', 'Azerbaijan', 'central', 40.4093, 49.8671, 'Asia/Baku', 'MuslimWorldLeague' ),
		'tashkent'    => array( 'Tashkent', 'Tashkent', 'Uzbekistan', 'Uzbekistan', 'central', 41.2995, 69.2401, 'Asia/Tashkent', 'MuslimWorldLeague' ),
		'almaty'      => array( 'Almaty', 'Almaty', 'Kazakhstan', 'Kazakhstan', 'central', 43.2389, 76.8897, 'Asia/Almaty', 'MuslimWorldLeague' ),
		'kabul'       => array( 'Kabul', 'Kabul', 'Afghanistan', 'Afghanistan', 'south', 34.5553, 69.2075, 'Asia/Kabul', 'Karachi' ),
		'karachi'     => array( 'Karachi', 'Karachi', 'Pakistan', 'Pakistan', 'south', 24.8607, 67.0011, 'Asia/Karachi', 'Karachi' ),
		'lahore'      => array( 'Lahore', 'Lahore', 'Pakistan', 'Pakistan', 'south', 31.5204, 74.3587, 'Asia/Karachi', 'Karachi' ),
		'delhi'       => array( 'Delhi', 'Delhi', 'India', 'India', 'south', 28.6139, 77.2090, 'Asia/Kolkata', 'Karachi' ),
		'dhaka'       => array( 'Dhaka', 'Dhaka', 'Bangladesh', 'Bangladesh', 'south', 23.8103, 90.4125, 'Asia/Dhaka', 'Karachi' ),
		'kualalumpur' => array( 'Kuala Lumpur', 'Kuala Lumpur', 'Malaysia', 'Malaysia', 'southeast', 3.1390, 101.6869, 'Asia/Kuala_Lumpur', 'Singapore' ),
		'singapore'   => array( 'Singapore', 'Singapura', 'Singapore', 'Singapura', 'southeast', 1.3521, 103.8198, 'Asia/Singapore', 'Singapore' ),
		'bsb'         => array( 'Bandar Seri Begawan', 'Bandar Seri Begawan', 'Brunei', 'Brunei', 'southeast', 4.9031, 114.9398, 'Asia/Brunei', 'Singapore' ),
		'jakarta'     => array( 'Jakarta', 'Jakarta', 'Indonesia', 'Indonesia', 'southeast', -6.2088, 106.8456, 'Asia/Jakarta', 'Singapore' ),
		'makassar'    => array( 'Makassar', 'Makassar', 'Indonesia', 'Indonesia', 'southeast', -5.1477, 119.4327, 'Asia/Makassar', 'Singapore' ),
		'marawi'      => array( 'Marawi', 'Marawi', 'Philippines', 'Filipina', 'southeast', 8.0047, 124.2854, 'Asia/Manila', 'Singapore' ),
		'narathiwat'  => array( 'Narathiwat', 'Narathiwat', 'Thailand', 'Thailand', 'southeast', 6.4350, 101.8229, 'Asia/Bangkok', 'Singapore' ),
		'maungdaw'    => array( 'Maungdaw', 'Maungdaw', 'Myanmar', 'Myanmar', 'southeast', 20.8269, 92.3661, 'Asia/Yangon', 'Karachi' ),
		'london'      => array( 'London', 'London', 'United Kingdom', 'United Kingdom', 'west', 51.5074, -0.1278, 'Europe/London', 'MuslimWorldLeague' ),
		'newyork'     => array( 'New York', 'New York', 'United States', 'Amerika Syarikat', 'west', 40.7128, -74.0060, 'America/New_York', 'NorthAmerica' ),
	);
}

/**
 * Interface strings for the current language role.
 *
 * @param string $role primary or secondary.
 * @return array
 */
function lq_nw_strings( $role ) {
	if ( 'secondary' === $role ) {
		return array(
			'title'        => 'Waktu Sepuluh Malam Terakhir',
			'lede'         => 'Pilih bandar anda untuk melihat bila setiap malam bermula, bila sepertiga malam terakhir bermula dan bila fajar menjelma.',
			'city'         => 'Bandar',
			'locate'       => 'Gunakan lokasi saya',
			'locating'     => 'Mencari lokasi…',
			'myLocation'   => 'Lokasi anda',
			'locateFail'   => 'Lokasi tidak dapat ditentukan. Sila pilih bandar daripada senarai.',
			'start'        => 'Permulaan Ramadan',
			'startNote'    => 'Tarikh bergantung pada rukyah. Semak pengumuman pihak berkuasa agama di negara anda.',
			'night'        => 'Malam ke-%s',
			'odd'          => 'Malam ganjil',
			'even'         => 'Malam genap',
			'evening'      => 'Bermula selepas matahari terbenam, %s',
			'begins'       => 'Malam bermula',
			'lastThird'    => 'Sepertiga malam terakhir',
			'ends'         => 'Fajar (malam berakhir)',
			'length'       => 'Tempoh malam',
			'hours'        => '%1$d jam %2$d minit',
			'now'          => 'Sekarang',
			'sunset'       => 'Matahari terbenam',
			'dawn'         => 'Fajar',
			'midnight'     => 'Tengah malam',
			'addOne'       => 'Tambah malam ini ke kalendar',
			'addAll'       => 'Tambah kesemua sepuluh malam',
			'icsTitle'     => 'Sepertiga malam terakhir, malam %2$s',
			'icsDesc'      => 'Sepertiga malam terakhir hingga fajar. Dikira oleh lailatulqadar.guide; ikut jadual masjid tempatan jika berbeza.',
			'before'       => 'Sepuluh malam terakhir bermula dalam %d hari.',
			'hijriSuffix'  => ' H',
			'tonight'      => 'Malam ini ialah malam %s.',
			'tonightOdd'   => 'Malam ini ialah malam %s, malam ganjil.',
			'inProgress'   => 'Sepertiga malam terakhir sedang berlangsung hingga fajar.',
			'countdown'    => 'Sepertiga malam terakhir bermula dalam %s.',
			'after'        => 'Ramadan %s telah berakhir. Pilih Ramadan seterusnya untuk melihat tarikhnya.',
			'year'         => 'Ramadan %s',
			'prevYear'     => 'Ramadan sebelumnya',
			'nextYear'     => 'Ramadan seterusnya',
			'maybe'        => 'Malam ke-30 hanya berlaku jika Ramadan cukup 30 hari.',
			'next'         => 'Malam ke-%1$s (%4$s) bermula selepas matahari terbenam, %2$s, pukul %3$s.',
			'hijriDate'    => '%1$s Ramadan %2$s H',
			'hijriNight'   => 'Malam %1$s Ramadan %2$s H',
			'firstDay'     => '1 Ramadan: %s',
			'method'       => 'Kaedah kiraan: %s.',
			'note'         => 'Waktu dikira dalam pelayar anda dan tiada data lokasi dihantar. Ikut jadual masjid tempatan anda jika waktunya berbeza.',
			'regions'      => array(
				'arabia'    => 'Semenanjung Arab',
				'levant'    => 'Syam dan Iraq',
				'africa'    => 'Afrika',
				'central'   => 'Turkiye, Iran dan Asia Tengah',
				'south'     => 'Asia Selatan',
				'southeast' => 'Asia Tenggara',
				'west'      => 'Eropah dan Amerika',
			),
			'methods'      => array(
				'Singapore'         => '20° / 18° (jenis JAKIM, MUIS dan Kemenag)',
				'MuslimWorldLeague' => 'Liga Dunia Islam',
				'Egyptian'          => 'Pihak Berkuasa Ukur Am Mesir',
				'Karachi'           => 'Universiti Sains Islam, Karachi',
			),
			'locale'       => 'ms-MY',
			'defaultCity'  => 'kualalumpur',
		);
	}
	return array(
		'title'        => 'Your Night Window',
		'lede'         => 'Choose your city to see when each of the last ten nights begins, when its last third starts, and when dawn ends it.',
		'city'         => 'City',
		'locate'       => 'Use my location',
		'locating'     => 'Finding your location…',
		'myLocation'   => 'Your location',
		'locateFail'   => 'Your location could not be found. Please choose a city from the list.',
		'start'        => 'Start of Ramadan',
		'startNote'    => 'The start depends on the sighting of the crescent. Check your country’s religious authority.',
		'night'        => '%s night',
		'odd'          => 'Odd night',
		'even'         => 'Even night',
		'evening'      => 'Begins at sunset, %s',
		'begins'       => 'Night begins',
		'lastThird'    => 'Last third begins',
		'ends'         => 'Dawn (night ends)',
		'length'       => 'Length of night',
		'hours'        => '%1$dh %2$02dm',
		'now'          => 'Now',
		'sunset'       => 'Sunset',
		'dawn'         => 'Dawn',
		'midnight'     => 'Midnight',
		'addOne'       => 'Add this night to my calendar',
		'addAll'       => 'Add all ten nights',
		'icsTitle'     => 'Last third of the night of %2$s',
		'icsDesc'      => 'The last third of the night, until dawn. Calculated by lailatulqadar.guide; follow your local mosque where times differ.',
		'before'       => 'The last ten nights begin in %d days.',
		'hijriSuffix'  => ' AH',
		'tonight'      => 'Tonight is the night of %s.',
		'tonightOdd'   => 'Tonight is the night of %s, an odd night.',
		'inProgress'   => 'The last third of the night is under way until dawn.',
		'countdown'    => 'The last third begins in %s.',
		'after'        => 'Ramadan %s has ended. Choose the next Ramadan to see its dates.',
		'year'         => 'Ramadan %s',
		'prevYear'     => 'Previous Ramadan',
		'nextYear'     => 'Next Ramadan',
		'maybe'        => 'The 30th night occurs only if Ramadan lasts thirty days.',
		'next'         => 'The %1$s night (%4$s) begins at sunset on %2$s, at %3$s.',
		'hijriDate'    => '%1$s Ramadan %2$s AH',
		'hijriNight'   => 'Night of %1$s Ramadan %2$s AH',
		'firstDay'     => '1 Ramadan: %s',
		'method'       => 'Calculation method: %s.',
		'note'         => 'Times are calculated in your browser and no location data leaves your device. Follow your local mosque’s timetable where it differs.',
		'regions'      => array(
			'arabia'    => 'Arabian Peninsula',
			'levant'    => 'Levant and Iraq',
			'africa'    => 'Africa',
			'central'   => 'Türkiye, Iran and Central Asia',
			'south'     => 'South Asia',
			'southeast' => 'Southeast Asia',
			'west'      => 'Europe and the Americas',
		),
		'methods'      => array(),
		'locale'       => 'en-GB',
		'defaultCity'  => 'makkah',
	);
}

/**
 * Full configuration passed to the browser.
 *
 * @return array
 */
function lq_nw_config() {
	$role   = lq_current_role();
	$cities = array();
	foreach ( lq_nw_cities() as $id => $c ) {
		$ms       = 'secondary' === $role;
		$cities[] = array(
			'id'      => $id,
			'name'    => $ms ? $c[1] : $c[0],
			'country' => $ms ? $c[3] : $c[2],
			'region'  => $c[4],
			'lat'     => $c[5],
			'lng'     => $c[6],
			'tz'      => $c[7],
			'method'  => $c[8],
		);
	}
	$years   = array();
	$current = lq_ramadan();
	if ( $current ) {
		for ( $y = $current['hijri'] - 1; $y <= $current['hijri'] + 10; $y++ ) {
			$r = lq_ramadan_year( $y );
			if ( $r ) {
				$years[] = array(
					'hijri' => $r['hijri'],
					'start' => $r['start'],
					'alt'   => $r['alt'],
				);
			}
		}
	}
	$config = array(
		'cities'  => $cities,
		'strings' => lq_nw_strings( $role ),
		'years'   => $years,
		'current' => $current ? $current['hijri'] : null,
		'role'    => $role,
	);
	return apply_filters( 'lq_night_window_config', $config );
}
