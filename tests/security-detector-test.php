<?php
/**
 * Self-test for inc/security-detector.php. Run: php tests/security-detector-test.php
 * Exit code 0 when every shipped article, every page title, slug, SEO title
 * and description, and every pattern scores 0, and every attack sample flags.
 *
 * @package Lailatulqadar
 */

define( 'LQ_SECURITY_TEST', true );
define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/inc/security-detector.php';

// helpers.php holds lq_page_definitions(); stub the one call it makes on load.
if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode() {}
}
require dirname( __DIR__ ) . '/inc/helpers.php';

$fail = 0;
$n    = 0;
$root = dirname( __DIR__ );

/**
 * Score one seeded item and report any match at all.
 */
$check = function ( $label, $title, $slug, $content ) use ( &$fail, &$n ) {
	++$n;
	$r = lq_spam_score( $title, $slug, $content );
	if ( $r['score'] > 0 ) {
		++$fail;
		echo 'FALSE POSITIVE (score ' . $r['score'] . '): ' . $label . ' ' . implode( '; ', $r['reasons'] ) . "\n";
	}
};

foreach ( glob( $root . '/content/*/*.json' ) as $file ) {
	$data = json_decode( file_get_contents( $file ), true );
	$text = isset( $data['content'] ) ? $data['content'] : '';
	if ( ! empty( $data['footnotes'] ) ) {
		$text .= ' ' . wp_json_like( $data['footnotes'] );
	}
	$check( basename( dirname( $file ) ) . '/' . basename( $file ), '', '', $text );
}
foreach ( lq_page_definitions() as $key => $def ) {
	foreach ( array( 'primary', 'secondary' ) as $role ) {
		$d = $def[ $role ];
		$check( "page $key/$role", $d['title'], $d['slug'], $d['seo'] . ' ' . $d['meta'] . ' ' . $d['short'] . ' ' . $d['focus'] );
	}
}
foreach ( glob( $root . '/{patterns/*.php,templates/*.html,parts/*.html}', GLOB_BRACE ) as $file ) {
	$check( substr( $file, strlen( $root ) + 1 ), '', '', preg_replace( '/<\?php.*?\?>/s', ' ', file_get_contents( $file ) ) );
}
$images = json_decode( file_get_contents( $root . '/inc/data/images.json' ), true );
$check( 'inc/data/images.json', '', '', wp_json_like( $images ) );
echo "Seeded items checked: $n\n";

/**
 * Flatten decoded JSON to its string values.
 *
 * @param mixed $value Value.
 * @return string
 */
function wp_json_like( $value ) {
	if ( is_array( $value ) ) {
		return implode( ' ', array_map( 'wp_json_like', $value ) );
	}
	return is_string( $value ) ? $value : '';
}

$clean = array( 'Bethlehem and Bethel in Genesis', 'The Hebrew alphabet', 'Between the Testaments', 'A better reading of Isaiah', 'Kumar and the Vedas', 'What is at stake in the Council of Nicaea', 'The crown of the Dome of the Rock', 'Elizabeth and Bethany', 'Judith in the Apocrypha', 'Beth, the second letter of the Hebrew alphabet', 'Bet in the Hebrew letters: alef, bet, gimel', 'Stake: a word in the Psalms', 'A review of the odd nights', 'The bonus page on injil.help', 'A time slot for the night prayer', 'Beth Shemesh and Beit Lahm', 'The odd nights of the last ten' );
foreach ( $clean as $t ) {
	$r = lq_spam_score( $t, '', '' );
	if ( $r['spam'] ) {
		++$fail;
		echo "FALSE POSITIVE sample: $t\n";
	}
}

$spam = array(
	array( 'Bet Big Win Big with Bodog Sports Edge', '' ),
	array( 'Exclusive Coolbet Bonus Code Unlocks Big Wins', '' ),
	array( 'Скачать Пинап КЗ: Официальный сайт', '' ),
	array( 'Krok po kroku: Kompleksowy przewodnik po Pelican Casino', '' ),
	array( 'Live-Roulette bei Mr Pacho', '' ),
	array( 'Crash-Games als neuer Trend', '' ),
	array( 'Persoonlijke ervaringen met top 10 online casino’s in Nederland 2026', '' ),
	array( 'Panduan Lengkap 1xBet Download', '' ),
	array( 'Pusulabet 2026 Giriş Adresi', '' ),
	array( 'Sweepstakes Casinos Explained', '' ),
	array( 'Stake Casino Australia', '' ),
	array( 'Sportwetten-Analyse vor dem Spiel', '' ),
	array( 'Connection check jk5g5zw7', '' ),
	array( 'cl-probe', '' ),
	array( 'Mostbet giriş', '' ),
	array( 'Surfplay review', '' ),
	array( 'Crownplay bonus', '' ),
	array( 'Trino Casino', '' ),
	array( 'Syndicate Casino login', '' ),
	array( 'Stellar Spins', '' ),
	array( 'Starda официальный сайт', '' ),
	array( 'Slotspalace', '' ),
	array( 'Sportaza', '' ),
	array( 'Spybet', '' ),
	array( 'Squeenaud', '' ),
	array( 'Spinjo', '' ),
	array( 'Spellwin', '' ),
	array( 'Spinsup', '' ),
	array( 'Spinch', '' ),
	array( 'Spin Samurai', '' ),
	array( 'Slots Gallery', '' ),
	array( 'Najboljše igralne avtomate v Sloveniji', '' ),
	array( 'Casino Oyunlarında Bankroll Yönetimi', '' ),
	array( 'Glücksspiel in der Schweiz', '' ),
	array( 'Ausländische Online Casinos', '' ),
	array( 'Untitled', 'casinos-online-mas-seguros-espana-2026' ),
	array( 'Untitled', 'zahranicne-stavkove-kancelarie-na-slovensku-3' ),
	array( 'Untitled', 'pin-ap-kazino-oficialnyj-sajt' ),
	array( 'Untitled', 'kto-brasil-cassino-online-e-apostas' ),
	array( 'Vavada зеркало', '' ),
	array( 'Melbet app', '' ),
	array( 'Betify login', '' ),
	array( 'Casinozer avis', '' ),
	array( 'AureonPlay', '' ),
	array( 'PlatinCasino', '' ),
	array( 'True Blue Casino', '' ),
	array( 'SydSpin', '' ),
	array( 'TCL99', '' ),
	array( 'Vegas Stars', '' ),
	array( 'Surge Casino', '' ),
	array( '1win', '' ),
);
foreach ( $spam as $s ) {
	$r = lq_spam_score( $s[0], $s[1], '' );
	if ( ! $r['spam'] ) {
		++$fail;
		echo "MISSED: {$s[0]} {$s[1]} (score {$r['score']})\n";
	}
}
$r = lq_spam_score( 'A guide', '', '<p>See <a href="https://best-casino.example/">here</a></p>' );
if ( ! $r['spam'] ) {
	++$fail;
	echo "MISSED: gambling link\n";
}
echo 'Spam samples checked: ' . ( count( $spam ) + 1 ) . "\n";
echo $fail ? "FAILED: $fail\n" : "PASS\n";
exit( $fail ? 1 : 0 );
