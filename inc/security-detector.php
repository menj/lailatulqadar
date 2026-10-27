<?php
/**
 * Gambling spam detector. Plain PHP with no WordPress calls, so the
 * self-test in tests/security-detector-test.php can load it on the command line.
 *
 * A post is spam when its score reaches LQ_SPAM_THRESHOLD. Strong terms are
 * gambling words that never occur in writing on religion or history; weak terms
 * (poker, jackpot, a bare "bet") count only when another term joins them.
 *
 * @package Lailatulqadar
 */

defined( 'ABSPATH' ) || defined( 'LQ_SECURITY_TEST' ) || exit;

if ( ! defined( 'LQ_SPAM_THRESHOLD' ) ) {
	define( 'LQ_SPAM_THRESHOLD', 3 );
}

/**
 * Weighted patterns: label => array( regex, weight ). Every regex runs on
 * lower-case text with the u flag; \b keeps "bet" out of Bethlehem, Bethel,
 * alphabet, between and better.
 *
 * @return array
 */
function lq_spam_patterns() {
	static $patterns = null;
	if ( null !== $patterns ) {
		return $patterns;
	}
	$brands = array(
		'1xbet', '1x bet', 'pin-up', 'pinup', 'pin up casino', 'пинап', 'пин ап', 'mostbet', 'melbet', 'betify', 'pusulabet',
		'stake casino', 'stake.com', 'stake bonus', 'coolbet', 'bodog', 'vavada', 'вавада', '1win', 'casinozer', 'aureonplay',
		'platincasino', 'mr pacho', 'mrpacho', 'true blue casino', 'trueblue casino', 'sydspin', 'tcl99', 'vegas stars',
		'surge casino', 'pelican casino', 'surfplay', 'crownplay', 'crown play', 'trino casino', 'syndicate casino',
		'stellar spins', 'starda', 'slotspalace', 'slots palace', 'sportaza', 'spybet', 'squeenaud', 'spinjo', 'spellwin',
		'spinsup', 'spins up', 'spinch', 'spin samurai', 'slots gallery', 'slotsgallery', 'bet365', 'betway', 'parimatch',
		'leovegas', 'pokerstars', 'joycasino', 'pin-ap', 'pinap',
	);
	$quoted = array_map(
		function ( $b ) {
			return preg_quote( $b, '/' );
		},
		$brands
	);
	$patterns = array(
		// Strong: one is enough.
		'casino'          => array( '/(?<![\p{L}])(casino|casinos|cassino|cassinos|kasyno|kasyna|kasynie|kazino|kasino|kasinos|казино|казіно|kumarhane)/u', 3 ),
		'gambling brand'  => array( '/(?<![\p{L}\d])(' . implode( '|', $quoted ) . ')(?![\p{L}])/u', 3 ),
		'betting words'   => array( '/(sportwetten|wettanbieter|wettquoten|wettbüro|sportsbook|bookmaker|buchmacher|bukmacher|apuestas|apostas(?![\p{L}])|scommess[ae]|stávkov|stavkov|stavková|bahis|taruhan|букмекер|ставк[аиуеой]|ставок|tipsport|glücksspiel|gluecksspiel|igralne avtomate|igralnih avtomatov|oyunlarında)/u', 3 ),
		'gambling phrase' => array( '/\b(free spins?|freispiele|darmowe spiny|bonus codes?|promo codes?|no deposit|ohne einzahlung|bez depozytu|sweepstakes casinos?|sweepstakes|crash[- ]games?|live dealers?|live casino|live-casino|live-roulette|online gambling|slot machines?|slot gacor|judi online|judi slot|judi bola|welcome bonus|cashback bonus|wagering requirements?|bankroll)\b/u', 3 ),
		'probe title'     => array( '/^\s*(connection check\b.*|cl-probe\S*|probe|test post|test)\s*$/u', 5 ),
		// Weak: they count only together with another match.
		'betting'         => array( '/\b(betting|bettor|bets|wett(en|e|tipps)?)\b/u', 2 ),
		'bet'             => array( '/\bbet\b/u', 1 ),
		'casino games'    => array( '/\b(roulette|blackjack|baccarat|poker|jackpots?|slots?|spins|payout|rtp)\b/u', 1 ),
		'odds'            => array( '/\b(odds|wager|wagers)\b/u', 1 ),
	);
	return $patterns;
}

/**
 * Hosts that point at gambling sites.
 *
 * @return string
 */
function lq_spam_link_pattern() {
	return '/(casino|cassino|kasyno|kazino|casinò|1xbet|pinup|pin-up|mostbet|melbet|betify|pusulabet|vavada|1win|bet365|betway|sportsbook|sportwetten|bahis|slot|poker|stake\.com|coolbet|bodog|starda|crownplay|surfplay|spinjo|spybet|sportaza)/u';
}

/**
 * Lower-case plain text.
 *
 * @param string $text Raw text or HTML.
 * @return string
 */
function lq_spam_normalise( $text ) {
	$text = html_entity_decode( strip_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

/**
 * Score one item.
 *
 * @param string $title   Title.
 * @param string $slug    Slug.
 * @param string $content Content (HTML allowed).
 * @return array { score: int, reasons: string[], spam: bool }
 */
function lq_spam_score( $title, $slug = '', $content = '' ) {
	$title_text = lq_spam_normalise( $title );
	$slug_text  = lq_spam_normalise( str_replace( array( '-', '_' ), ' ', rawurldecode( (string) $slug ) ) );
	$body_text  = lq_spam_normalise( $content );
	$score      = 0;
	$reasons    = array();

	foreach ( lq_spam_patterns() as $label => $rule ) {
		list( $regex, $weight ) = $rule;
		if ( 'probe title' === $label ) {
			if ( preg_match( $regex, $title_text, $m ) ) {
				$score    += $weight;
				$reasons[] = 'probe title: ' . trim( $m[0] );
			}
			continue;
		}
		foreach ( array( 'title' => $title_text, 'slug' => $slug_text, 'content' => $body_text ) as $where => $text ) {
			if ( '' !== $text && preg_match( $regex, $text, $m ) ) {
				$score    += $weight;
				$reasons[] = $label . ' in ' . $where . ': ' . trim( $m[0] );
				break;
			}
		}
	}

	// Outbound links to gambling hosts.
	if ( preg_match_all( '#href\s*=\s*["\']?\s*(?:https?:)?//([^/"\'\s>]+)#i', (string) $content, $hosts ) ) {
		foreach ( array_unique( array_map( 'strtolower', $hosts[1] ) ) as $host ) {
			if ( preg_match( lq_spam_link_pattern(), $host ) ) {
				$score    += 3;
				$reasons[] = 'gambling link: ' . $host;
				break;
			}
		}
	}

	return array(
		'score'   => $score,
		'reasons' => $reasons,
		'spam'    => $score >= LQ_SPAM_THRESHOLD,
	);
}

/**
 * Gambling hosts linked from some HTML.
 *
 * @param string $content HTML.
 * @return string[]
 */
function lq_spam_links( $content ) {
	$out = array();
	if ( preg_match_all( '#href\s*=\s*["\']?\s*(?:https?:)?//([^/"\'\s>]+)#i', (string) $content, $hosts ) ) {
		foreach ( array_unique( array_map( 'strtolower', $hosts[1] ) ) as $host ) {
			if ( preg_match( lq_spam_link_pattern(), $host ) ) {
				$out[] = $host;
			}
		}
	}
	return $out;
}
