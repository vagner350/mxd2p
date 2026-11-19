<?php
// Tarefa agendada (a cada 10 min): baixa os posts do branch blog-posts do GitHub para _blog/posts.
// Funciona sem git no servidor. O repositório precisa estar público (ou configure GITHUB_TOKEN abaixo).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
const REPO = 'vagner350/mxd2p';
const BRANCH = 'blog-posts';
$dir = dirname(__DIR__) . '/_blog';
$token = (string)(@include dirname(__DIR__, 2) . '/github-token.php');   // opcional: arquivo fora do site que devolve o token
$log = [date('c')];
$ch = curl_init('https://codeload.github.com/' . REPO . '/zip/refs/heads/' . BRANCH);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'MXD20-blog-sync',
  CURLOPT_HTTPHEADER => $token && $token !== '1' ? ['Authorization: Bearer ' . $token] : []]);
$zip = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
if ($code !== 200 || !$zip) { $log[] = "erro ao baixar ($code)"; file_put_contents("$dir/sync-log.txt", implode("\n", $log) . "\n"); exit(1); }
$tmp = tempnam(sys_get_temp_dir(), 'blog'); file_put_contents($tmp, $zip);
$z = new ZipArchive();
if ($z->open($tmp) !== true) { $log[] = 'zip inválido'; file_put_contents("$dir/sync-log.txt", implode("\n", $log) . "\n"); @unlink($tmp); exit(1); }
@mkdir("$dir/posts", 0755, true);
$novos = 0; $vistos = [];
for ($i = 0; $i < $z->numFiles; $i++) {
  $n = $z->getNameIndex($i);
  $rel = preg_replace('#^[^/]+/#', '', $n);                       // tira a pasta raiz do zip
  if (!preg_match('#^(posts/[A-Za-z0-9._-]+\.json|GUIA-BLOG\.md|temas\.json)$#', $rel)) continue;
  $data = $z->getFromIndex($i);
  if (str_ends_with($rel, '.json') && json_decode($data, true) === null) { $log[] = "JSON inválido ignorado: $rel"; continue; }
  $dst = "$dir/$rel"; $vistos[$rel] = 1;
  if (!is_file($dst) || md5_file($dst) !== md5($data)) { file_put_contents($dst, $data); if (str_starts_with($rel, 'posts/')) $novos++; }
}
$z->close(); @unlink($tmp);
// remove do site posts apagados no GitHub
foreach (glob("$dir/posts/*.json") as $f) { $rel = 'posts/' . basename($f); if (!isset($vistos[$rel])) { @unlink($f); $log[] = "removido: $rel"; } }
$log[] = "ok · posts novos/alterados: $novos · total: " . count(glob("$dir/posts/*.json"));
file_put_contents("$dir/sync-log.txt", implode("\n", $log) . "\n");
