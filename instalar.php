<?php
// Instalador da loja MXD20 (maxd20.com.br).
// Monta o site a partir da mesma base da amazole.store (mesma tecnologia, API e gateway Appmax),
// aplica a marca MXD20 (preto e amarelo) e coloca por cima os arquivos próprios da pasta site/.
// Uso: https://maxd20.com.br/_deploy/instalar.php?k=<chave>
// Também roda pela linha de comando (tarefa cron da hospedagem): php instalar.php
// Nesse modo só instala quando o repositório mudou desde a última instalação e grava o resultado em report.json.
const KEY_HASH = '6539e2a47bd1c54374c4045f19aa83d791f70a7440f45caf2b46709a53967cf4';
$CLI = PHP_SAPI === 'cli';
if ($CLI) {
  $rep = __DIR__ . '/report.json';
  $ver = @filemtime(__DIR__ . '/instalar.php') . '-' . @filemtime(__DIR__ . '/patches.php') . '-' . @md5_file(__DIR__ . '/site/index.html');
  if (is_file($rep) && (json_decode((string)file_get_contents($rep), true)['versao'] ?? '') === $ver && empty($argv[1])) exit(0);
  $_GET = [];
  ob_start();
  register_shutdown_function(function () use ($rep, $ver) {
    $out = ob_get_clean(); $j = json_decode((string)$out, true) ?: ['saida' => $out];
    $j['versao'] = $ver; $j['quando'] = date('c');
    file_put_contents($rep, json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  });
} else {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  if (!hash_equals(KEY_HASH, hash('sha256', (string)($_GET['k'] ?? '')))) { http_response_code(404); echo '{"error":"nao_encontrado"}'; exit; }
}
@set_time_limit(300);

$HOME = '/home/u616319980/domains';
$SRC = "$HOME/amazole.store/public_html";
$DST = dirname(__DIR__);                       // public_html da maxd20.com.br
$OLD = "$HOME/nexcash.com.br/public_html/mxd20site/assets";
$log = []; $miss = [];
$patches = require __DIR__ . '/patches.php';

function rrm($p) { if (is_link($p) || is_file($p)) return @unlink($p); if (!is_dir($p)) return true; foreach (scandir($p) as $f) if ($f !== '.' && $f !== '..') rrm("$p/$f"); return @rmdir($p); }
function rcopy($s, $d, $skip, $rel = '') {
  if (!is_dir($d)) @mkdir($d, 0755, true); $n = 0;
  foreach (scandir($s) as $f) {
    if ($f === '.' || $f === '..') continue;
    $r = ltrim("$rel/$f", '/');
    foreach ($skip as $pat) if (fnmatch($pat, $r)) continue 2;
    if (is_dir("$s/$f")) $n += rcopy("$s/$f", "$d/$f", $skip, $r);
    else { if (@copy("$s/$f", "$d/$f")) $n++; }
  }
  return $n;
}
function rfiles($d, $rel = '') { $o = []; foreach (scandir($d) as $f) { if ($f === '.' || $f === '..') continue; $r = ltrim("$rel/$f", '/'); if (is_dir("$d/$f")) $o = array_merge($o, rfiles("$d/$f", $r)); else $o[] = $r; } return $o; }

// 1. limpa o public_html (menos a pasta deste instalador)
if (!is_dir($SRC)) { http_response_code(500); echo json_encode(['error' => 'origem_nao_encontrada', 'src' => $SRC]); exit; }
foreach (scandir($DST) as $f) if (!in_array($f, ['.', '..', basename(__DIR__)], true)) rrm("$DST/$f");
$log[] = 'limpo';

// 2. copia a base da loja (sem vídeos e conteúdo exclusivo da Amazolé)
$skip = ['*.mp4', 'assets/produtos', 'assets/ativos', 'atacado', 'api/atacado.php', '3ae52b52bb28debbda9b0e359b836af1.txt', 'assets/guia-30-dias-amazole.pdf', 'api/appmax-config.php', 'appmax-config.php'];
$log[] = 'copiados: ' . rcopy($SRC, $DST, $skip);

// 3. ajustes pontuais
foreach ($patches as $i => [$file, $type, $find, $repl]) {
  $p = "$DST/$file"; if (!is_file($p)) { $miss[] = "#$i arquivo ausente: $file"; continue; }
  $t = file_get_contents($p);
  if ($type === 're') { $n2 = preg_replace($find, $repl, $t, -1, $c); if ($n2 === null) { $miss[] = "#$i regex invalida: $file"; continue; } }
  else { $c = substr_count($t, $find); $n2 = str_replace($find, $repl, $t); }
  if (!$c) { $miss[] = "#$i nao achou em $file: " . mb_substr(is_string($find) ? $find : '', 0, 70); continue; }
  file_put_contents($p, $n2);
}

// 4. troca global de marca, domínio, canal e cores
$map = [
  'scha_01M50JEQZNPZS0ZZT4J0WGQ9GJ' => MX_CHANNEL,
  'https://cdn.zyrosite.com/cdn-ecommerce/store_01M50JEQYV1Q2NTETM2JVQSH4E/assets/cd9990bd-eb0d-470e-a587-8a0e6f06bcd1.png' => MX_IMG,
  'Produtos Amazolé para clarear e cuidar da pele. Frete grátis.' => 'Tônico MXD20 Barbudom para barba, cabelo e sobrancelha. Frete grátis.',
  '<a href="/?c=kits#produtos">Kits</a><a href="/?c=esfoliantes#produtos">Esfoliantes</a>' => '<a href="/produto/' . MX_SLUG . '/">Tônico MXD20</a><a href="/#kits">Kits</a>',
  '<a href="/">Início</a> › <a href="/#produtos">Produtos</a> ›' => '<a href="/">Início</a> ›',
  '<a href="/#produtos">Produtos</a>' => '',
  '/#produtos' => '/#kits',
  'location.hash = "produtos"' => 'location.hash = "kits"',
  '/assets/logo.webp' => '/assets/logo.png',
  'width="605" height="138"' => 'width="571" height="119"',
  'loja.js?v=18' => 'loja.js?v=mx2', 'conteudo.js?v=12' => 'conteudo.js?v=mx2',
  'Amazol%C3%A9' => 'MXD20', 'AMAZOLÉ' => 'MXD20', 'Amazolé' => 'MXD20', 'amazole.store' => 'maxd20.com.br',
];
$colors = [
  '#14AEB8' => '#FFC400', '#08727A' => '#111111', '#E6F7F8' => '#FFF8DC', '#E2087D' => '#FFC400', '#BE0068' => '#111111', '#FDE8F3' => '#FFF4CC',
  '#13262B' => '#0B0B0B', '#5A6E73' => '#5C5C5C', '#DCE8EA' => '#E9E4D4', '#F5FAFA' => '#FAF8F1', '#F2FBFB' => '#FFFBEA', '#E2F4F5' => '#FFF4CC',
  '#0B8790' => '#111111', '#7FE3EA' => '#FFC400', '#FFF7FB' => '#FFFBEA', '#f6b8d8' => '#F3D98A', '#7a0a44' => '#5A4500', '#33474c' => '#333333',
  '#40555a' => '#444444', '#9fb3b7' => '#9A9A9A', '#B9CBCF' => '#BDBDBD', '#D8E2E4' => '#DDDDDD', '#2E9D57' => '#FFC400', '#9AAFB3' => '#9A9A9A',
  'rgba(20,174,184' => 'rgba(255,196,0', 'rgba(226,8,125' => 'rgba(255,196,0', 'rgba(19,38,43' => 'rgba(0,0,0', 'rgba(10,20,24' => 'rgba(0,0,0', '#0d1a1d' => '#000000',
];
$textExt = ['html', 'php', 'js', 'css', 'json', 'webmanifest', 'txt', 'htaccess', 'xml', 'svg'];
$changed = 0;
foreach (rfiles($DST) as $r) {
  if (str_starts_with($r, basename(__DIR__) . '/')) continue;
  $ext = strtolower(pathinfo($r, PATHINFO_EXTENSION) ?: ltrim(basename($r), '.'));
  if (!in_array($ext, $textExt, true)) continue;
  $p = "$DST/$r"; $t = file_get_contents($p); $o = $t;
  $t = strtr($t, $map);
  $t = str_ireplace(array_keys($colors), array_values($colors), $t);
  if ($ext === 'html' && !str_contains($t, 'mxd20.css')) $t = preg_replace('#(<link rel="stylesheet" href="/assets/loja\.css\?v=\d+">)#', "$1\n<link rel=\"stylesheet\" href=\"/assets/mxd20.css?v=1\">", $t, 1);
  if ($t !== $o) { file_put_contents($p, $t); $changed++; }
}
$log[] = "arquivos de texto ajustados: $changed";

// 5. arquivos próprios da MXD20 por cima
$log[] = 'arquivos MXD20: ' . rcopy(__DIR__ . '/site', $DST, []);

// 6. imagens do produto (servidor) -> assets/mxd
$mx = "$DST/assets/mxd"; @mkdir($mx, 0755, true);
function to_jpg($src, $dst, $max = 900) {
  if (!function_exists('imagecreatefromstring')) return @copy($src, $dst);
  $im = @imagecreatefromstring(@file_get_contents($src)); if (!$im) return @copy($src, $dst);
  $w = imagesx($im); $h = imagesy($im); $s = min(1, $max / $w); $nw = (int)round($w * $s); $nh = (int)round($h * $s);
  $c = imagecreatetruecolor($nw, $nh); imagefill($c, 0, 0, imagecolorallocate($c, 255, 255, 255));
  imagecopyresampled($c, $im, 0, 0, 0, 0, $nw, $nh, $w, $h); $ok = imagejpeg($c, $dst, 82); imagedestroy($im); imagedestroy($c); return $ok;
}
$img = [];
for ($i = 1; $i <= 5; $i++) if (is_file("$OLD/prova-social-$i.png")) $img[] = "prova-$i: " . (to_jpg("$OLD/prova-social-$i.png", "$mx/prova-$i.jpg") ? 'ok' : 'falhou');
if (is_file("$OLD/mxd20-produto.png")) $img[] = 'produto: ' . (@copy("$OLD/mxd20-produto.png", "$mx/produto.png") ? 'ok' : 'falhou');
// imagens da página de referência (kits 2 e 3 e um depoimento)
function fetch_img($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36',
    CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/png,image/*;q=0.8', 'Referer: https://minhaofertagarantida.com.br/']]);
  $b = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  return ($code === 200 && $b && @getimagesizefromstring($b)) ? $b : null;
}
foreach (['kit-2.png' => 'https://i.imgur.com/GocVxsS.png', 'kit-3.png' => 'https://i.imgur.com/pcFs5SG.png', '_dep.png' => 'https://i.imgur.com/uCSOl5d.png'] as $name => $url) {
  $b = fetch_img($url);
  if ($b) { file_put_contents("$mx/$name", $b); $img[] = "$name: ok"; } else $img[] = "$name: sem acesso";
}
// recorta o espaço vazio das fotos dos kits e deixa quadrada, com o produto ocupando a imagem
function trim_square($f, $pad = 0.06) {
  if (!function_exists('imagecreatefromstring') || !is_file($f)) return 'sem GD';
  $im = @imagecreatefromstring(file_get_contents($f)); if (!$im) return 'ilegivel';
  imagesavealpha($im, true);
  $w = imagesx($im); $h = imagesy($im); $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
  for ($y = 0; $y < $h; $y += 2) for ($x = 0; $x < $w; $x += 2) {
    $c = imagecolorat($im, $x, $y); $a = ($c >> 24) & 0x7F; $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
    if ($a < 100 && ($r < 235 || $g < 235 || $b < 235)) { if ($x < $x0) $x0 = $x; if ($x > $x1) $x1 = $x; if ($y < $y0) $y0 = $y; if ($y > $y1) $y1 = $y; }
  }
  if ($x1 < 0) return 'vazia';
  $cw = $x1 - $x0 + 1; $ch = $y1 - $y0 + 1; $side = (int)round(max($cw, $ch) * (1 + 2 * $pad)); $out = min(900, $side);
  $c = imagecreatetruecolor($out, $out); imagesavealpha($c, true); imagealphablending($c, false);
  imagefill($c, 0, 0, imagecolorallocatealpha($c, 255, 255, 255, 127)); imagealphablending($c, true);
  $s = $out / $side; $dw = (int)round($cw * $s); $dh = (int)round($ch * $s);
  imagecopyresampled($c, $im, (int)(($out - $dw) / 2), (int)(($out - $dh) / 2), $x0, $y0, $dw, $dh, $cw, $ch);
  imagesavealpha($c, true); imagepng($c, $f, 8); imagedestroy($im); imagedestroy($c);
  return "recortada ({$cw}x{$ch})";
}
foreach (['kit-2.png', 'kit-3.png'] as $k) if (is_file("$mx/$k")) $img[] = "$k " . trim_square("$mx/$k");
if (is_file("$mx/_dep.png")) { to_jpg("$mx/_dep.png", "$mx/prova-6.jpg"); @unlink("$mx/_dep.png"); }
$log[] = 'imagens: ' . implode(', ', $img);

// 7. credenciais da Appmax (fora do site), copiadas da amazole.store se ainda não existirem
$cfgDst = dirname($DST) . '/appmax-config.php';
if (!is_file($cfgDst) || !empty($_GET['cfg'])) $log[] = 'appmax-config: ' . (@copy("$HOME/amazole.store/appmax-config.php", $cfgDst) ? 'copiado' : 'falhou');
else $log[] = 'appmax-config: ja existe';
@chmod($cfgDst, 0600);
@unlink("$DST/default.php");
// cache do catálogo da loja nova
@unlink(dirname($DST) . '/amazole-data/catalog_cache.json');

// 8. palavras que ainda lembram a Amazolé (para revisão)
$words = ['clare', 'esfolia', 'manchas', 'axila', 'virilha', 'sérum', 'colágeno', 'melasma', 'sabonete', 'sozinha', 'amazole', 'Amazol'];
$left = [];
foreach (rfiles($DST) as $r) {
  if (str_starts_with($r, basename(__DIR__) . '/') || !preg_match('/\.(html|php|js|json)$/', $r)) continue;
  foreach (file("$DST/$r") as $ln => $line) foreach ($words as $w) if (stripos($line, $w) !== false) { $left[$r][] = ($ln + 1) . ': ' . mb_substr(trim($line), 0, 160); break; }
}
foreach ($left as $k => $v) $left[$k] = array_slice($v, 0, 12);
echo json_encode(['ok' => true, 'log' => $log, 'patches_nao_aplicados' => $miss, 'revisar' => $left], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
