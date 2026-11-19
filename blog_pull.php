<?php
// Roda pela tarefa agendada da hospedagem a cada 10 min: puxa os posts novos do branch blog-posts.
// php blog_pull.php        -> git pull
// php blog_pull.php teste  -> também testa as páginas do blog e grava o resultado em _blog/teste.txt
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$dir = dirname(__DIR__) . '/_blog';
$out = [];
exec('cd ' . escapeshellarg($dir) . ' && git pull -q origin blog-posts 2>&1', $out, $rc);
file_put_contents($dir . '/pull-log.txt', date('c') . " rc=$rc\n" . implode("\n", $out) . "\n");
if (($argv[1] ?? '') === 'teste') {
  $r = [];
  foreach (['/blog/', '/blog/barba-falhada-por-que-acontece-e-o-que-ajuda/', '/blog/feed.xml', '/sitemap.xml', '/checkout/', '/', '/promocao/', '/quiz/'] as $u) {
    $ch = curl_init('https://maxd20.com.br' . $u); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $b = (string)curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    preg_match('#<title>(.*?)</title>#s', $b, $m);
    $r[] = "$code $u | " . trim($m[1] ?? '') . ' | ' . strlen($b) . ' bytes | cards=' . substr_count($b, 'class="bl-card"') . ' | blog-links=' . substr_count($b, '/blog/') . ' | amazole=' . substr_count(strtolower($b), 'amazol') . ' | sabonete=' . substr_count(strtolower($b), 'sabonete');
  }
  file_put_contents($dir . '/teste.txt', implode("\n", $r) . "\n");
}
