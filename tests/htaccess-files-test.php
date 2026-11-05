<?php
/**
 * Self-test for the "block direct file access" lines that inc/security.php
 * writes into .htaccess. Run: php tests/htaccess-files-test.php
 *
 * Takes lq_sec_rewrite_prefix(), lq_sec_rewrite_quote() and
 * lq_sec_files_htaccess_lines() out of inc/security.php, builds the lines
 * for a site at the domain root and for one in a subfolder, checks their
 * shape (balanced IfModule, each RewriteCond followed by a RewriteRule,
 * every pattern a valid regular expression) and runs them over request
 * paths the way mod_rewrite does in .htaccess: the pattern is matched
 * against the path below the .htaccess folder, a RewriteCond against the
 * full REQUEST_URI.
 *
 * @since 1.18.8
 * @package Lailatulqadar
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'ABSPATH', '/srv/site/' );
function wp_normalize_path( $p ) { return str_replace( '\\', '/', (string) $p ); } // phpcs:ignore
function trailingslashit( $p ) { return rtrim( (string) $p, '/\\' ) . '/'; } // phpcs:ignore
function untrailingslashit( $p ) { return rtrim( (string) $p, '/\\' ); } // phpcs:ignore
function get_stylesheet_directory() { return $GLOBALS['lq_theme_root'] . '/lailatulqadar'; } // phpcs:ignore
function get_theme_root() { return $GLOBALS['lq_theme_root']; } // phpcs:ignore

$lq_src = (string) file_get_contents( dirname( __DIR__ ) . '/inc/security.php' );
foreach ( array( 'lq_sec_htaccess_lines', 'lq_sec_rewrite_prefix', 'lq_sec_rewrite_quote', 'lq_sec_files_htaccess_lines' ) as $lq_fn ) {
	if ( ! preg_match( '/^function ' . $lq_fn . '\(.*?^}$/ms', $lq_src, $lq_m ) ) {
		echo "FAIL $lq_fn not found in inc/security.php\n";
		exit( 1 );
	}
	eval( $lq_m[0] ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test only.
}

$lq_fail = 0;
function lq_check( $ok, $label ) {
	global $lq_fail;
	echo ( $ok ? 'ok   ' : 'FAIL ' ) . $label . "\n";
	$lq_fail += $ok ? 0 : 1;
}

/**
 * Whether the lines answer 403 for a request, as mod_rewrite would.
 *
 * @param string[] $lines Lines.
 * @param string   $uri   REQUEST_URI.
 * @param string   $base  URL path of the .htaccess folder, with slashes.
 * @return bool
 */
function lq_forbidden( $lines, $uri, $base ) {
	$path  = (string) substr( $uri, strlen( $base ) );
	$conds = array();
	foreach ( $lines as $line ) {
		if ( preg_match( '/^RewriteCond %\{REQUEST_URI\} (!?)(\S+)$/', $line, $m ) ) {
			$conds[] = array( '!' === $m[1], $m[2] );
			continue;
		}
		if ( preg_match( '/^RewriteRule (\S+) - \[F\]$/', $line, $m ) ) {
			$pass = 1 === preg_match( '#' . $m[1] . '#', $path );
			foreach ( $conds as $c ) {
				$hit  = 1 === preg_match( '#' . $c[1] . '#', $uri );
				$pass = $pass && ( $c[0] ? ! $hit : $hit );
			}
			$conds = array();
			if ( $pass ) {
				return true;
			}
		}
	}
	return false;
}

$lq_denied  = array( '.git/config', '.git/HEAD', '.gitignore', '.claude/CLAUDE.md', 'wp-content/themes/lailatulqadar/.git/config', 'wp-content/themes/lailatulqadar/.claude/FARUQI.md', 'wp-includes/version.php', 'wp-includes/blocks/archives.php', 'wp-includes/version.php/x', 'wp-includes/blocks/archives.php/wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/blocks/archives.php/wp-includes/ms-files.php', 'wp-settings.php/x', 'wp-admin/includes/admin.php', 'wp-content/themes/lailatulqadar/functions.php', 'wp-content/themes/twentytwentyfive/functions.php', 'wp-content/themes/lailatulqadar/tests/unlist-test.php', 'wp-content/themes/lailatulqadar/bin/build-error-pages.php', 'wp-content/themes/lailatulqadar/bin/', 'wp-content/themes/lailatulqadar/tests', 'wp-content/themes/lailatulqadar/docs/settings.md/x', 'wp-content/themes/lailatulqadar/docs/seo.md', 'wp-content/themes/lailatulqadar/docs/', 'wp-content/themes/lailatulqadar/docs', 'wp-config.php', 'wp-settings.php', 'wp-config-sample.php', 'wp-admin/install.php', 'readme.html' );
$lq_allowed = array( '', 'index.php', 'wp-login.php', 'wp-cron.php', 'xmlrpc.php', 'wp-admin/', 'wp-admin/index.php', 'wp-admin/admin-ajax.php', 'wp-admin/post.php', 'wp-admin/load-scripts.php', 'wp-json/wp/v2/posts', 'wp-includes/js/tinymce/wp-tinymce.php', 'wp-includes/ms-files.php', 'wp-includes/js/dist/block-editor.min.js', 'wp-includes/css/dist/block-library/style.min.css', 'wp-content/themes/lailatulqadar/style.css', 'wp-content/themes/lailatulqadar/assets/js/theme.js', 'wp-content/themes/lailatulqadar/testsuite.css', 'wp-content/uploads/2026/11/photo.webp', 'what-is-the-qibla/', 'binary-choices/', 'documents/', 'articles/does-god-exist/', 'journeys/', 'quiz/', 'wp-content/themes/lailatulqadar/docs/settings.md', 'wp-content/themes/lailatulqadar/readme.txt', 'wp-content/themes/lailatulqadar/tools/x.css', 'wp-content/themes/lailatulqadar/documents/readme.css', 'wp-content/themes/lailatulqadar/docs.css' );

$lq_sites = array(
	'at the domain root' => array( '/srv/site/', '/' ),
	'in a subfolder'     => array( '/srv/site/', '/blog/' ),
);
foreach ( $lq_sites as $lq_label => $lq_site ) {
	$GLOBALS['lq_theme_root'] = '/srv/site/wp-content/themes';
	$lq_lines = lq_sec_files_htaccess_lines( $lq_site[0] );

	lq_check( '<IfModule mod_rewrite.c>' === $lq_lines[0] && '</IfModule>' === end( $lq_lines ) && 2 === count( preg_grep( '/IfModule/', $lq_lines ) ), "$lq_label: one balanced IfModule guard" );
	$lq_shape = true;
	foreach ( $lq_lines as $lq_i => $lq_line ) {
		if ( 0 === strpos( $lq_line, 'RewriteCond' ) && 0 !== strpos( $lq_lines[ $lq_i + 1 ] ?? '', 'RewriteRule' ) ) {
			$lq_shape = false;
		}
		if ( preg_match( '/^Rewrite(?:Rule|Cond %\{REQUEST_URI\} !?)\s*(\S+)/', $lq_line, $lq_m ) && false === @preg_match( '#' . $lq_m[1] . '#', '' ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$lq_shape = false;
		}
		if ( ! preg_match( '/^(<IfModule mod_rewrite\.c>|<\/IfModule>|RewriteEngine On|RewriteCond %\{REQUEST_URI\} \S+|RewriteRule \S+ - \[F\])$/', $lq_line ) ) {
			$lq_shape = false;
		}
	}
	lq_check( $lq_shape, "$lq_label: every line is a known directive with a valid pattern" );

	foreach ( $lq_denied as $lq_p ) {
		lq_check( lq_forbidden( $lq_lines, $lq_site[1] . $lq_p, $lq_site[1] ), "$lq_label: 403 for /$lq_p" );
	}
	foreach ( $lq_allowed as $lq_p ) {
		lq_check( ! lq_forbidden( $lq_lines, $lq_site[1] . $lq_p, $lq_site[1] ), "$lq_label: open /$lq_p" );
	}
}

// A folder name with a regex metacharacter is quoted, so its own paths match.
$lq_pfx = lq_sec_rewrite_prefix( '/srv/wp.6/', '/srv/' );
lq_check( 'wp\\.6/' === $lq_pfx && 1 === preg_match( '#^' . $lq_pfx . 'wp-includes/.+\\.php$#', 'wp.6/wp-includes/version.php' ) && 0 === preg_match( '#^' . $lq_pfx . 'wp-includes/.+\\.php$#', 'wpx6/wp-includes/version.php' ), 'a dot in a folder name is escaped, and the prefix matches that folder only' );

// WordPress in its own folder below the .htaccess (wp/), themes beside it.
$GLOBALS['lq_theme_root'] = '/srv/site/wp-content/themes';
$lq_split = lq_sec_files_htaccess_lines( '/srv/' );
lq_check( (bool) preg_grep( '#\^site/wp-includes/#', $lq_split ) && (bool) preg_grep( '#\^site/wp-content/themes/lailatulqadar/#', $lq_split ), 'core and the theme below the .htaccess folder get their prefix' );
lq_check( lq_forbidden( $lq_split, '/wp-config.php', '/' ) && lq_forbidden( $lq_split, '/site/wp-config.php', '/' ), 'wp-config.php is refused in the core folder and in the folder above it' );
lq_check( lq_forbidden( $lq_split, '/site/wp-includes/version.php', '/' ) && ! lq_forbidden( $lq_split, '/site/wp-includes/js/tinymce/wp-tinymce.php', '/' ), 'the prefixed rules refuse and allow as at the root' );

// The theme folder's own .htaccess, which Apache reads before WordPress runs.
$lq_ht   = (string) file_get_contents( dirname( __DIR__ ) . '/.htaccess' );
$lq_own  = preg_match_all( '/^RedirectMatch 403 (\S+)$/m', $lq_ht, $lq_m ) ? $lq_m[1] : array();
$lq_rw   = preg_match_all( '/^RewriteRule (\S+) - \[F\]$/m', $lq_ht, $lq_m ) ? $lq_m[1] : array();
$lq_hits = function ( $patterns, $subject ) {
	foreach ( $patterns as $p ) {
		if ( preg_match( '#' . $p . '#', $subject ) ) {
			return true;
		}
	}
	return false;
};
lq_check( 4 === count( $lq_own ), "the theme's .htaccess has its 4 RedirectMatch lines" );
lq_check( 4 === count( $lq_rw ), "the theme's .htaccess has its 4 RewriteRule lines" );
foreach ( array( '.git/config', '.github/workflows/content.yml', '.gitignore', '.claude/CLAUDE.md', 'assets/.env', 'tests/no-blog-test.php', 'tests', 'bin/build-error-pages.php', 'docs/seo.md', 'docs/', 'docs', 'docs/settings.md/x', 'functions.php', 'inc/security.php/x' ) as $lq_p ) {
	lq_check( $lq_own && $lq_hits( $lq_own, '/wp-content/themes/lailatulqadar/' . $lq_p ), "theme .htaccess (mod_alias): 403 for $lq_p" );
	lq_check( $lq_hits( $lq_rw, $lq_p ), "theme .htaccess (mod_rewrite): 403 for $lq_p" );
}
foreach ( array( 'style.css', 'readme.txt', 'docs/settings.md', 'css/admin.css', 'js/admin.js', 'fonts/kfgqpc/kfgqpc-hafs-uthmanic.woff2', 'images/book-open.webp', 'errors/403.html', 'docsx.css', 'testsuite.css', 'binary.css', '.well-known/x' ) as $lq_p ) {
	lq_check( ! $lq_hits( $lq_own, '/wp-content/themes/lailatulqadar/' . $lq_p ) && ! $lq_hits( $lq_rw, $lq_p ), "theme .htaccess: open $lq_p" );
}

// Markdown files by their own name, with no module beyond the core.
$lq_md = preg_match( '/<FilesMatch "([^"]*md[^"]*)">\s*<IfModule mod_authz_core\.c>\s*Require all denied/', $lq_ht, $lq_m ) ? $lq_m[1] : '';
lq_check( '' !== $lq_md, 'the theme .htaccess refuses Markdown files with a FilesMatch' );
foreach ( array( 'CHANGELOG.md', 'SSOT.md', 'UPGRADING.md', 'seo.md', 'notes.MD', 'x-settings.md' ) as $lq_p ) {
	lq_check( '' !== $lq_md && 1 === preg_match( '#' . $lq_md . '#', $lq_p ), "theme .htaccess FilesMatch: 403 for $lq_p" );
}
foreach ( array( 'settings.md', 'style.css', 'readme.txt', 'main.md.css' ) as $lq_p ) {
	lq_check( '' === $lq_md || 0 === preg_match( '#' . $lq_md . '#', $lq_p ), "theme .htaccess FilesMatch: open $lq_p" );
}

// tests/ and bin/ refuse every request into them; docs/ all but settings.md.
foreach ( array( 'tests', 'bin', 'docs' ) as $lq_d ) {
	$lq_dir = (string) @file_get_contents( dirname( __DIR__ ) . "/$lq_d/.htaccess" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	lq_check( false !== strpos( $lq_dir, 'Require all denied' ) && false !== strpos( $lq_dir, 'Deny from all' ), "$lq_d/.htaccess refuses requests, on Apache 2.4 and 2.2" );
}
$lq_docs = (string) @file_get_contents( dirname( __DIR__ ) . '/docs/.htaccess' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
lq_check( 2 === substr_count( $lq_docs, '<Files "settings.md">' ) && false !== strpos( $lq_docs, 'Require all granted' ) && false !== strpos( $lq_docs, 'Allow from all' ), 'docs/.htaccess leaves settings.md open, on Apache 2.4 and 2.2' );
lq_check( is_file( dirname( __DIR__ ) . '/docs/settings.md' ), 'docs/settings.md, the file the footer links to, exists' );

// The xmlrpc.php block, for Apache 2.4 and 2.2.
$lq_x = lq_sec_htaccess_lines();
lq_check( '<Files xmlrpc.php>' === $lq_x[0] && '</Files>' === end( $lq_x ) && in_array( 'Require all denied', $lq_x, true ) && in_array( 'Deny from all', $lq_x, true ), 'the xmlrpc.php block denies on Apache 2.4 and 2.2' );

echo $lq_fail ? "$lq_fail failed\n" : "all passed\n";
exit( $lq_fail ? 1 : 0 );
