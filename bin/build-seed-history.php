<?php
if ( PHP_SAPI !== 'cli' ) { exit; } // Command line only; usage: php bin/build-seed-history.php <repo> > inc/data/seed-history.json
// Build inc/data/seed-history.json: md5 of every shipped version of each article.
$repo = $argv[1]; chdir($repo);
$commits = array_filter(explode("\n", shell_exec('git rev-list HEAD -- content/')));
$out = array();
$norm = function($c){ return md5(trim(str_replace(array("\r\n","\r"),"\n",$c))); };
foreach ($commits as $c) {
  $files = array_filter(explode("\n", shell_exec("git ls-tree -r --name-only $c content/")));
  foreach ($files as $f) {
    if (preg_match('#^content/([a-z]{2})/([a-z0-9]+)\.json$#',$f,$m)) {
      $d = json_decode(shell_exec("git show $c:".escapeshellarg($f)), true);
      if (!is_array($d) || !isset($d['content'])) continue; $body=$d['content'];
    } elseif (preg_match('#^content/([a-z]{2})/([a-z0-9]+)\.html$#',$f,$m)) {
      $body = shell_exec("git show $c:".escapeshellarg($f));
    } else continue;
    $out[$m[1]][$m[2]][] = $norm($body);
  }
}
ksort($out);
foreach ($out as &$l){ ksort($l); foreach($l as &$h){ $h=array_values(array_unique($h)); sort($h);} }
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
