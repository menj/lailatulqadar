<?php
if ( PHP_SAPI !== 'cli' ) { exit; } // Command line only; usage: php bin/build-seed-history.php [repo] > inc/data/seed-history.json.new
// Build inc/data/seed-history.json: md5 of every shipped version of each article.
// The hashes already in the file are kept: a version that left the git history
// (a rebase or squash) may still be on a live site, and dropping its hash
// would make setup treat that unedited page as edited. Write to a new file
// and move it into place; redirecting onto the file itself truncates it
// before it is read.
$repo = isset( $argv[1] ) ? $argv[1] : dirname( __DIR__ );
chdir( $repo );
$commits = array_filter( explode( "\n", (string) shell_exec( 'git rev-list HEAD -- content/' ) ) );
$norm    = function ( $c ) { return md5( trim( str_replace( array( "\r\n", "\r" ), "\n", $c ) ) ); };
$known   = is_readable( 'inc/data/seed-history.json' ) ? json_decode( (string) file_get_contents( 'inc/data/seed-history.json' ), true ) : array();
$out     = is_array( $known ) ? $known : array();
foreach ( $commits as $c ) {
	$files = array_filter( explode( "\n", (string) shell_exec( 'git ls-tree -r --name-only ' . escapeshellarg( $c ) . ' content/' ) ) );
	foreach ( $files as $f ) {
		if ( preg_match( '#^content/([a-z]{2})/([a-z0-9]+)\.json$#', $f, $m ) ) {
			$d = json_decode( (string) shell_exec( 'git show ' . escapeshellarg( $c . ':' . $f ) ), true );
			if ( ! is_array( $d ) || ! isset( $d['content'] ) ) {
				continue;
			}
			$body = $d['content'];
		} elseif ( preg_match( '#^content/([a-z]{2})/([a-z0-9]+)\.html$#', $f, $m ) ) {
			$body = (string) shell_exec( 'git show ' . escapeshellarg( $c . ':' . $f ) );
		} else {
			continue;
		}
		$out[ $m[1] ][ $m[2] ][] = $norm( $body );
	}
}
ksort( $out );
foreach ( $out as &$l ) {
	ksort( $l );
	foreach ( $l as &$h ) {
		$h = array_values( array_unique( $h ) );
		sort( $h );
	}
	unset( $h );
}
unset( $l );
echo json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
