<?php
/**
 * Self-test for the JSON-LD graph (inc/schema.php, inc/schema-content.php).
 * Run: php tests/schema-graph-test.php
 *
 * Renders the graph of every guide page in both languages from the shipped
 * articles, with WordPress replaced by small stubs, once on its own and
 * once merged into a sample Rank Math graph. Each page runs in its own PHP
 * process, because the theme caches the language role per request.
 *
 * Checks: the JSON parses; every @id is unique; every reference points to
 * a node in the graph (a link to the other language's page excepted);
 * each node type carries its required properties; the expected nodes are
 * there (FAQPage on the FAQ, HowTo on the pages with numbered steps, Event
 * where Ramadan dates show, citations, quotations, tables, images, the
 * translation link); and, merged, the page keeps one graph with one
 * WebSite, one WebPage and one Article. When the schema.org vocabulary is
 * at $LQ_SCHEMA_VOCAB, every type and property is checked against it.
 *
 * Exit code 0 when every check passes.
 *
 * @package Lailatulqadar
 */

$root = dirname( __DIR__ );

if ( isset( $argv[1] ) && 'render' === $argv[1] ) {
	lq_test_render( $root, $argv[2], $argv[3], ! empty( $argv[4] ) );
	exit( 0 );
}

$defs  = json_decode( (string) file_get_contents( $root . '/inc/data/pages.json' ), true );
$fail  = 0;
$pages = 0;
$seen  = array();
$vocab = lq_test_vocab();

foreach ( array( 'primary', 'secondary' ) as $role ) {
	foreach ( array_keys( $defs ) as $key ) {
		foreach ( array( false, true ) as $rank_math ) {
			$cmd = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' render ' . escapeshellarg( $key ) . ' ' . $role . ( $rank_math ? ' 1' : '' ) . ' 2>&1';
			$out = shell_exec( $cmd );
			$tag = $key . '/' . $role . ( $rank_math ? ' (Rank Math)' : '' );
			$doc = json_decode( (string) $out, true );
			++$pages;
			if ( ! is_array( $doc ) || empty( $doc['@graph'] ) ) {
				++$fail;
				echo "INVALID JSON: $tag\n" . substr( (string) $out, 0, 2000 ) . "\n";
				continue;
			}
			$errors = lq_test_check( $doc['@graph'], $key, $role, $rank_math, $vocab );
			foreach ( $errors as $e ) {
				++$fail;
				echo "FAIL $tag: $e\n";
			}
			foreach ( $doc['@graph'] as $node ) {
				foreach ( (array) $node['@type'] as $t ) {
					$seen[ $t ] = true;
				}
			}
		}
	}
}

ksort( $seen );
echo $pages . ' graphs checked. Types: ' . implode( ', ', array_keys( $seen ) ) . "\n";
echo $fail ? "$fail failure(s)\n" : "All checks passed\n";
exit( $fail ? 1 : 0 );

/**
 * Check one graph.
 *
 * @param array  $graph     Nodes.
 * @param string $key       Page key.
 * @param string $role      Language role.
 * @param bool   $rank_math Merged into the Rank Math sample.
 * @param array  $vocab     Known terms, or empty.
 * @return string[]
 */
function lq_test_check( $graph, $key, $role, $rank_math, $vocab ) {
	$errors = array();
	$ids    = array();
	$types  = array();
	foreach ( $graph as $i => $node ) {
		if ( empty( $node['@type'] ) ) {
			$errors[] = "node $i has no @type";
			continue;
		}
		if ( empty( $node['@id'] ) ) {
			$errors[] = "node $i (" . implode( ',', (array) $node['@type'] ) . ') has no @id';
			continue;
		}
		if ( isset( $ids[ $node['@id'] ] ) ) {
			$errors[] = 'duplicate @id ' . $node['@id'];
		}
		$ids[ $node['@id'] ] = true;
		foreach ( (array) $node['@type'] as $t ) {
			$types[ $t ][] = $node;
		}
	}

	// References resolve, except the link to the page in the other language.
	$walk = function ( $value, $prop ) use ( &$walk, &$errors, $ids ) {
		if ( ! is_array( $value ) ) {
			return;
		}
		if ( 1 === count( $value ) && isset( $value['@id'] ) && ! isset( $ids[ $value['@id'] ] ) && ! in_array( $prop, array( 'workTranslation', 'translationOfWork' ), true ) ) {
			$errors[] = "dangling reference in $prop: " . $value['@id'];
		}
		foreach ( $value as $k => $v ) {
			$walk( $v, is_string( $k ) ? $k : $prop );
		}
	};
	foreach ( $graph as $node ) {
		foreach ( $node as $prop => $v ) {
			$walk( $v, $prop );
		}
	}

	$required = array(
		'WebSite'               => array( 'name', 'url' ),
		'Organization'          => array( 'name', 'url' ),
		'WebPage'               => array( 'url', 'name', 'isPartOf', 'inLanguage' ),
		'Article'               => array( 'headline', 'datePublished', 'author', 'image', 'publisher', 'inLanguage' ),
		'BreadcrumbList'        => array( 'itemListElement' ),
		'Question'              => array( 'name', 'acceptedAnswer' ),
		'HowTo'                 => array( 'name', 'step' ),
		'Event'                 => array( 'name', 'startDate', 'endDate' ),
		'ImageObject'           => array( 'contentUrl', 'creditText', 'creator', 'acquireLicensePage' ),
		'Quotation'             => array( 'text', 'inLanguage' ),
		'Table'                 => array( 'name' ),
		'SiteNavigationElement' => array( 'name', 'hasPart' ),
		'Book'                  => array( 'name' ),
		'Chapter'               => array( 'name', 'position', 'isPartOf' ),
		'CreativeWork'          => array( 'name' ),
	);
	foreach ( $required as $type => $props ) {
		foreach ( isset( $types[ $type ] ) ? $types[ $type ] : array() as $node ) {
			if ( 'ImageObject' === $type && ! empty( $node['encodingFormat'] ) && 'image/svg+xml' === $node['encodingFormat'] ) {
				$props = array( 'name', 'creator' );
			}
			foreach ( $props as $p ) {
				if ( ! isset( $node[ $p ] ) || '' === $node[ $p ] || array() === $node[ $p ] ) {
					$errors[] = "$type " . $node['@id'] . " lacks $p";
				}
			}
		}
	}
	// Questions sit in the FAQPage's mainEntity.
	foreach ( isset( $types['FAQPage'] ) ? $types['FAQPage'] : array() as $faq ) {
		$questions = isset( $faq['mainEntity'] ) ? (array) $faq['mainEntity'] : array();
		if ( count( $questions ) < 3 ) {
			$errors[] = 'FAQPage with fewer than three questions';
		}
		foreach ( $questions as $q ) {
			if ( empty( $q['@type'] ) || 'Question' !== $q['@type'] || empty( $q['name'] ) || empty( $q['acceptedAnswer']['text'] ) ) {
				$errors[] = 'FAQPage question without name or answer text';
			}
		}
	}
	foreach ( isset( $types['Question'] ) ? $types['Question'] : array() as $q ) {
		if ( empty( $q['acceptedAnswer']['text'] ) ) {
			$errors[] = 'Question ' . $q['@id'] . ' has no answer text';
		}
	}
	foreach ( isset( $types['Event'] ) ? $types['Event'] : array() as $e ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $e['startDate'] ) || $e['endDate'] < $e['startDate'] ) {
			$errors[] = 'Event ' . $e['@id'] . ' has bad dates';
		}
	}

	$one = array( 'WebSite' => 1, 'WebPage' => 1 );
	foreach ( $one as $type => $n ) {
		$count = isset( $types[ $type ] ) ? count( $types[ $type ] ) : 0;
		if ( $count !== $n ) {
			$errors[] = "expected $n $type, found $count";
		}
	}
	$articles = 0;
	foreach ( array( 'Article', 'BlogPosting' ) as $t ) {
		$articles += isset( $types[ $t ] ) ? count( $types[ $t ] ) : 0;
	}
	$is_article = in_array( $key, array( 'start', 'what', 'surah', 'when', 'last10', 'signs', 'worship', 'dua', 'faq', 'sources' ), true );
	if ( $articles !== ( $is_article ? 1 : 0 ) ) {
		$errors[] = "expected " . ( $is_article ? 1 : 0 ) . " article node(s), found $articles";
	}

	// What each page is expected to carry.
	$expect = array(
		'faq'     => array( 'FAQPage', 'Quotation', 'CreativeWork' ),
		'worship' => array( 'HowTo', 'Table', 'Quotation' ),
		'start'   => array( 'HowTo', 'ImageObject' ),
		'when'    => array( 'Event', 'ItemList', 'Table' ),
		'last10'  => array( 'Event', 'ItemList' ),
		'planner' => array( 'Event' ),
		'surah'   => array( 'Quotation', 'Chapter', 'Book' ),
		'what'    => array( 'Quotation', 'Book', 'CreativeWork', 'ImageObject' ),
		'contact' => array( 'ContactPage' ),
		'about'   => array( 'AboutPage', 'ProfilePage' ),
	);
	foreach ( isset( $expect[ $key ] ) ? $expect[ $key ] : array() as $type ) {
		if ( empty( $types[ $type ] ) ) {
			$errors[] = "expected a $type node";
		}
	}
	foreach ( array( 'WPHeader', 'WPFooter', 'SiteNavigationElement' ) as $type ) {
		if ( empty( $types[ $type ] ) ) {
			$errors[] = "expected a $type node";
		}
	}
	if ( 'faq' !== $key && ! empty( $types['FAQPage'] ) ) {
		$errors[] = 'FAQPage on a page that is not a page of questions';
	}
	if ( in_array( $key, array( 'dua', 'signs', 'sources', 'surah', 'what', 'faq' ), true ) && ! empty( $types['HowTo'] ) ) {
		$errors[] = 'HowTo on a page with no numbered steps';
	}
	if ( in_array( $key, array( 'dua', 'signs', 'sources', 'surah', 'what', 'faq', 'about' ), true ) && ! empty( $types['Event'] ) ) {
		$errors[] = 'Event on a page that shows no Ramadan dates';
	}
	if ( $is_article ) {
		$main = $types['Article'][0];
		$prop = 'secondary' === $role ? 'translationOfWork' : 'workTranslation';
		if ( empty( $main[ $prop ]['@id'] ) ) {
			$errors[] = "article lacks $prop";
		}
	}

	if ( $rank_math ) {
		$page = $types['WebPage'][0];
		if ( $is_article && false === strpos( $types['Article'][0]['@id'], '#richSnippet' ) ) {
			$errors[] = 'theme article not folded into the Rank Math article';
		}
		if ( empty( $page['description'] ) || 'Rank Math description' !== $page['description'] ) {
			$errors[] = 'Rank Math value overwritten on the WebPage';
		}
		$json = wp_json_encode_test( $graph );
		if ( $is_article && false !== strpos( $json, '#article"' ) ) {
			$errors[] = 'reference to the theme article @id left after the merge';
		}
	}

	if ( $vocab ) {
		$check = function ( $node ) use ( &$check, &$errors, $vocab ) {
			if ( ! is_array( $node ) ) {
				return;
			}
			foreach ( $node as $k => $v ) {
				if ( '@type' === $k ) {
					foreach ( (array) $v as $t ) {
						if ( ! isset( $vocab[ $t ] ) ) {
							$errors[] = "type $t is not in the schema.org vocabulary";
						}
					}
				} elseif ( is_string( $k ) && '@' !== $k[0] && ! isset( $vocab[ $k ] ) ) {
					$errors[] = "property $k is not in the schema.org vocabulary";
				}
				$check( $v );
			}
		};
		foreach ( $graph as $node ) {
			$check( $node );
		}
	}
	return array_values( array_unique( $errors ) );
}

/**
 * JSON for string checks.
 *
 * @param mixed $v Value.
 * @return string
 */
function wp_json_encode_test( $v ) {
	return (string) json_encode( $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Terms of the schema.org vocabulary file named by $LQ_SCHEMA_VOCAB.
 *
 * @return array
 */
function lq_test_vocab() {
	$file = getenv( 'LQ_SCHEMA_VOCAB' );
	if ( ! $file || ! is_readable( $file ) ) {
		echo "Vocabulary check skipped (set LQ_SCHEMA_VOCAB to schemaorg-current-https.jsonld).\n";
		return array();
	}
	$data  = json_decode( (string) file_get_contents( $file ), true );
	$terms = array();
	foreach ( isset( $data['@graph'] ) ? $data['@graph'] : array() as $node ) {
		if ( isset( $node['@id'] ) && 0 === strpos( $node['@id'], 'schema:' ) ) {
			$terms[ substr( $node['@id'], 7 ) ] = true;
		}
	}
	return $terms;
}

/**
 * Child process: build the stub site, render one page's graph, print JSON.
 *
 * @param string $root      Theme folder.
 * @param string $key       Page key.
 * @param string $role      primary or secondary.
 * @param bool   $rank_math Merge into a sample Rank Math graph.
 */
function lq_test_render( $root, $key, $role, $rank_math ) {
	define( 'ABSPATH', $root . '/' );
	define( 'LQ_DIR', $root );
	define( 'LQ_URI', 'https://lailatulqadar.test/wp-content/themes/lailatulqadar' );
	if ( $rank_math ) {
		define( 'RANK_MATH_VERSION', '1.0.0' );
	}
	require __DIR__ . '/stubs/wordpress.php';
	lq_stub_site( $root );
	foreach ( array( 'helpers', 'images', 'ramadan', 'seo', 'image-sitemap', 'setup', 'schema-content', 'schema' ) as $file ) {
		require $root . '/inc/' . $file . '.php';
	}
	lq_stub_view( $key, $role );

	$graph = lq_schema_graph();
	if ( $rank_math ) {
		$url  = $GLOBALS['lq_stub']['view']['url'];
		$home = 'https://lailatulqadar.test/';
		$data = array(
			'publisher'   => array(
				'@type' => 'Organization',
				'@id'   => $home . '#organization',
				'name'  => 'Lailatulqadar',
			),
			'WebSite'     => array(
				'@type'     => 'WebSite',
				'@id'       => $home . '#website',
				'url'       => $home,
				'name'      => 'Lailatulqadar',
				'publisher' => array( '@id' => $home . '#organization' ),
			),
			'WebPage'     => array(
				'@type'       => 'WebPage',
				'@id'         => $url . '#webpage',
				'url'         => $url,
				'name'        => 'Rank Math name',
				'description' => 'Rank Math description',
				'isPartOf'    => array( '@id' => $home . '#website' ),
				'inLanguage'  => 'en-US',
			),
			'BreadcrumbList' => array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => array(
					array(
						'@type'    => 'ListItem',
						'position' => 1,
						'item'     => array(
							'@id'  => $home,
							'name' => 'Home',
						),
					),
				),
			),
		);
		if ( in_array( $key, lq_article_keys(), true ) ) {
			$data['richSnippet'] = array(
				'@type'            => 'Article',
				'@id'              => $url . '#richSnippet',
				'headline'         => 'Rank Math headline',
				'datePublished'    => '2026-01-01T00:00:00+00:00',
				'author'           => array(
					'@type' => 'Person',
					'name'  => 'Editor',
				),
				'publisher'        => array( '@id' => $home . '#organization' ),
				'isPartOf'         => array( '@id' => $url . '#webpage' ),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'inLanguage'       => 'en-US',
			);
		}
		$graph = array_values( lq_rank_math_json_ld( $data, null ) );
	}
	echo json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
}
