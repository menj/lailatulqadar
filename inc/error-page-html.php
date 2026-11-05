<?php
/**
 * The error pages as plain HTML: the three wp-content drop-ins and the static
 * pages in errors/, from one builder.
 *
 * Pure PHP, no WordPress function: the drop-ins run while WordPress is half
 * loaded or has no database, and bin/build-error-pages.php runs this file on
 * the command line. The style is inline for the same reason, in the theme's
 * colours and fonts with its brandmark, light and dark, so no file of the
 * theme is needed to show a page.
 *
 * @package lailatulqadar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Status, title and wording of each page.
 *
 * @return array<string,array> Kind => status, retry, eyebrow, title, lines, again.
 */
function lailatulqadar_error_page_kinds() {
	return array(
		'maintenance' => array(
			'status' => 503,
			'retry' => 600,
			'eyebrow' => 'Maintenance',
			'title' => 'Back shortly',
			'lines' => array( 'The site is offline for a few minutes while it is updated.', 'Please try again shortly. Nothing you were reading has gone anywhere.' ),
			'again' => 'Try again',
			'alt' => 'Laman ini dikemas kini. Sila cuba sebentar lagi.',
		),
		'php-error' => array(
			'status' => 500,
			'retry' => 0,
			'eyebrow' => 'Error 500',
			'title' => 'Something went wrong',
			'lines' => array( 'A fault on our side stopped this page from loading. It is not something you did.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Ada sesuatu yang tidak kena. Sila cuba sebentar lagi.',
		),
		'db-error' => array(
			'status' => 503,
			'retry' => 600,
			'eyebrow' => 'Database unavailable',
			'title' => 'We cannot reach our database',
			'lines' => array( 'The site cannot reach its database just now. This is temporary, and nothing you did caused it.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Pangkalan data tidak dapat dihubungi buat masa ini. Sila cuba sebentar lagi.',
		),
		'403' => array(
			'status' => 403,
			'retry' => 0,
			'eyebrow' => 'Error 403',
			'title' => 'Access denied',
			'lines' => array( 'This address is closed to visitors.', 'Every page of the site is open from the home page.' ),
			'again' => 'Go to the home page',
			'alt' => 'Akses ke alamat ini tidak dibenarkan.',
		),
		'500' => array(
			'status' => 500,
			'retry' => 0,
			'eyebrow' => 'Error 500',
			'title' => 'Something went wrong',
			'lines' => array( 'A fault on our side stopped this page from loading. It is not something you did.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Ada sesuatu yang tidak kena. Sila cuba sebentar lagi.',
		),
		'502' => array(
			'status' => 502,
			'retry' => 0,
			'eyebrow' => 'Error 502',
			'title' => 'Bad gateway',
			'lines' => array( 'A server between you and the site gave a wrong answer.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Pelayan perantara memberi jawapan yang salah. Sila cuba sebentar lagi.',
		),
		'504' => array(
			'status' => 504,
			'retry' => 0,
			'eyebrow' => 'Error 504',
			'title' => 'Gateway timeout',
			'lines' => array( 'A server between you and the site took too long to answer.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Pelayan perantara mengambil masa terlalu lama untuk menjawab. Sila cuba sebentar lagi.',
		),
		'503' => array(
			'status' => 503,
			'retry' => 600,
			'eyebrow' => 'Error 503',
			'title' => 'Temporarily unavailable',
			'lines' => array( 'The site is busy or offline for a short while.', 'Please try again in a few minutes.' ),
			'again' => 'Try again',
			'alt' => 'Laman ini tidak tersedia buat sementara. Sila cuba sebentar lagi.',
		),
	);
}

/**
 * One page as a complete HTML document.
 *
 * @param string $kind A key of lailatulqadar_error_page_kinds().
 * @param string $site Site name, plain text.
 * @param string $home Address of the home page.
 * @return string
 */
function lailatulqadar_error_page_html( $kind, $site, $home = '/' ) {
	$kinds = lailatulqadar_error_page_kinds();
	$k     = isset( $kinds[ $kind ] ) ? $kinds[ $kind ] : $kinds['500'];
	$e     = static function ( $s ) {
		return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
	};
	$lines = '';
	foreach ( $k['lines'] as $line ) {
		$lines .= '<p>' . $e( $line ) . "</p>\n";
	}
	if ( ! empty( $k['alt'] ) ) {
		$lines .= '<p class="alt" lang="ms">' . $e( $k['alt'] ) . "</p>\n";
	}
	$css = <<<'CSS'
:root{--night:#141b2e;--card:#1c2540;--ink:#e8e4d8;--muted:#b9c0d0;--gold:#c9a85c;--glow:#e3cf9a;--line:#2c3654}
@media (prefers-color-scheme:light){:root{--night:#f4efe3;--card:#fffdf7;--ink:#141b2e;--muted:#3f4865;--gold:#7d6224;--glow:#5c4718;--line:#ddd3bd}}
*{box-sizing:border-box}
html,body{height:100%;margin:0}
body{display:flex;align-items:center;justify-content:center;padding:24px 16px;background:var(--night) radial-gradient(circle at 50% 0,rgba(201,168,92,.14),transparent 60%);color:var(--ink);font:1.1rem/1.6 "EB Garamond",Garamond,Georgia,"Times New Roman",serif}
main{width:min(34rem,100%);padding:2.6rem clamp(1.25rem,6vw,2.4rem) 2.4rem;background:var(--card);border:1px solid var(--line);border-radius:22px;text-align:center;box-shadow:0 24px 60px -30px rgba(0,0,0,.6)}
.mark{display:inline-block;width:4.5rem;height:4.5rem}
.mark svg{display:block;width:100%;height:100%;border-radius:14px}
.name{margin:.8rem 0 0;font-size:.9rem;letter-spacing:.24em;text-transform:uppercase;color:var(--muted)}
.code{margin:1.5rem 0 .2rem;font:600 .78rem/1.2 system-ui,-apple-system,"Segoe UI",sans-serif;letter-spacing:.22em;text-transform:uppercase;color:var(--gold)}
h1{margin:.3rem 0 1rem;font-size:clamp(1.6rem,5vw,2.1rem);font-weight:500;line-height:1.2;color:var(--glow)}
p{margin:0 auto .6rem;max-width:30rem;color:var(--muted)}
.alt{font-style:italic}
.again{display:inline-block;margin-top:1.4rem;padding:.7rem 1.8rem;border-radius:999px;background:var(--gold);color:#141b2e;font:600 1rem system-ui,-apple-system,"Segoe UI",sans-serif;text-decoration:none}
@media (prefers-color-scheme:light){.again{color:#fffdf7}}
.again:hover{background:var(--glow)}
.again:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
CSS;
	// The mark is the page's tab icon too, as a data address: an error page
	// answers at any path, in a subfolder install too, and asks for no file.
	$mark = <<<'MARK'
<svg xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="2 0 44 44"><style>.lq-mark__metal{fill:#c9a85c}.lq-mark__glass{fill:#c9a85c;fill-opacity:.16;stroke:#c9a85c;stroke-width:2.2;stroke-linejoin:round}.lq-mark__glow{fill:#e3cf9a}.lq-mark__flame{fill:#c9a85c}.lq-mark__cut{fill:none;stroke:#141b2e;stroke-width:1.3;stroke-linecap:round}.lq-mark__ring{fill:none;stroke:#9aa3b8;stroke-width:1.5}.lq-mark__crescent{fill:#c9a85c;fill-opacity:.45}.lq-mark__star{fill:#e3cf9a}</style><rect x="2" y="0" width="44" height="44" rx="10" fill="#141b2e"/><circle class="lq-mark__ring" cx="24" cy="3.4" r="1.7"/><path class="lq-mark__metal" d="M14.5 17.2 C14.5 12.2 19.5 10.2 24 5.2 C28.5 10.2 33.5 12.2 33.5 17.2 Z"/><path class="lq-mark__cut" d="M24 6.6 C21.6 10.4 20.7 13.6 21.1 17.2 M24 6.6 C26.4 10.4 27.3 13.6 26.9 17.2"/><rect class="lq-mark__metal" x="13" y="17" width="22" height="2.8" rx="1"/><rect class="lq-mark__glass" x="15.4" y="20.9" width="17.2" height="14.4" rx="1.2"/><path class="lq-mark__glow" d="M19.8 35.3 V27.6 A4.2 4.2 0 0 1 28.2 27.6 V35.3 Z"/><path class="lq-mark__flame" d="M24 26.4 Q27 30.1 24 33.6 Q21 30.1 24 26.4 Z"/><rect class="lq-mark__metal" x="13" y="35.3" width="22" height="2.8" rx="1"/><rect class="lq-mark__metal" x="10.5" y="38.9" width="27" height="2.8" rx="1.4"/></svg>
MARK;
	return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>' . $e( $k['title'] ) . ' | ' . $e( $site ) . '</title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,' . $e( rawurlencode( $mark ) ) . '">
<style>
' . $css . '
</style>
</head>
<body>
<main role="main">
<div class="mark" aria-hidden="true">' . $mark . '</div>
<p class="name">' . $e( $site ) . '</p>
<p class="code">' . $e( $k['eyebrow'] ) . '</p>
<h1>' . $e( $k['title'] ) . '</h1>
' . $lines . '<a class="again" href="' . $e( $home ) . '">' . $e( $k['again'] ) . '</a>
</main>
</body>
</html>';
}

/**
 * The PHP header of a drop-in: the marker line, then the status headers.
 *
 * @param string $kind    Drop-in kind.
 * @param string $version Theme version.
 * @return string
 */
function lailatulqadar_error_dropin_head( $kind, $version ) {
	$k      = lailatulqadar_error_page_kinds()[ $kind ];
	$reason = array(
		500 => 'Internal Server Error',
		503 => 'Service Unavailable',
	);
	return "<?php\n// Lailatulqadar error page drop-in (" . $kind . ') ' . $version . ". Written by the Lailatulqadar theme; safe to delete.\n"
		. "// Self-contained: no theme file, no WordPress function and no database.\n"
		. "if ( ! headers_sent() ) {\n"
		. "\theader( 'HTTP/1.1 " . $k['status'] . ' ' . $reason[ $k['status'] ] . "', true, " . $k['status'] . " );\n"
		. "\theader( 'Content-Type: text/html; charset=utf-8' );\n"
		. ( $k['retry'] ? "\theader( 'Retry-After: " . (int) $k['retry'] . "' );\n" : '' )
		. "\theader( 'X-Robots-Tag: noindex' );\n"
		. "\theader( 'Cache-Control: no-store, max-age=0' );\n"
		. "}\n?>\n";
}
