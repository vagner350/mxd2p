<?php
// Teste completo do site (roda pela tarefa agendada): php teste_site.php
// Grava o resultado em _blog/teste-site.txt (pasta bloqueada para o público).
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
@set_time_limit(600);
const BASE = 'https://maxd20.com.br';
$out = []; $falhas = [];
function req($url, $post = null, $head = false) {
  $ch = curl_init($url);
  $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => false, CURLOPT_USERAGENT => 'MXD20-teste/1.0', CURLOPT_HEADER => false];
  if ($post !== null) { $o[CURLOPT_POST] = true; $o[CURLOPT_POSTFIELDS] = json_encode($post); $o[CURLOPT_HTTPHEADER] = ['Content-Type: application/json']; }
  if ($head) $o[CURLOPT_NOBODY] = true;
  curl_setopt_array($ch, $o);
  $t = microtime(true); $b = (string)curl_exec($ch);
  $r = ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'ms' => (int)((microtime(true) - $t) * 1000), 'body' => $b, 'loc' => curl_getinfo($ch, CURLINFO_REDIRECT_URL), 'type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE)];
  curl_close($ch); return $r;
}
$ruins = ['amazol', 'esfoliante', 'clarea', 'clarear', 'axila', 'virilha', 'sabonete', 'sérum', 'serum', 'colágeno', 'colageno', 'melasma', '/quiz/', '/promocao/', '/colageno/', 'manchas', 'amiga'];
$paginas = ['/', '/produto/tonico-mxd20-barbudom-30ml/', '/carrinho/', '/checkout/', '/checkout/sucesso/', '/pedido/', '/avaliar/', '/atacado/', '/comprar/',
  '/politicas/trocas/', '/politicas/entrega/', '/politicas/privacidade/', '/politicas/termos/', '/blog/', '/blog/feed.xml', '/sitemap.xml', '/robots.txt',
  '/painel/', '/painel/entrar/', '/painel/creators/', '/creator/', '/404.html', '/pagina-que-nao-existe/', '/blog/post-que-nao-existe/',
  '/_deploy/patches.php', '/_blog/temas.json', '/api/appmax-config.php', '/c/TESTE/'];
// posts do blog
foreach (glob(dirname(__DIR__) . '/_blog/posts/*.json') as $f) { $p = json_decode(file_get_contents($f), true); if ($p && strtotime($p['publicar_em']) <= time()) $paginas[] = '/blog/' . $p['slug'] . '/'; }
$links = [];
$out[] = "== PÁGINAS ==";
foreach ($paginas as $u) {
  $r = req(BASE . $u);
  preg_match('#<title>(.*?)</title>#s', $r['body'], $m);
  $low = mb_strtolower(strip_tags(preg_replace('#<script\b[^>]*>.*?</script>#si', '', $r['body'])));
  $achou = []; foreach ($ruins as $w) if (($n = substr_count($low, $w))) $achou[] = "$w×$n";
  $out[] = sprintf('%d %5dms %-62s %s%s%s', $r['code'], $r['ms'], $u, trim(html_entity_decode($m[1] ?? '')), $r['loc'] ? ' -> ' . $r['loc'] : '', $achou ? '  [TEXTO: ' . implode(', ', $achou) . ']' : '');
  $esperado = in_array($u, ['/pagina-que-nao-existe/', '/blog/post-que-nao-existe/'], true) ? 404 : (in_array($u, ['/_deploy/patches.php', '/_blog/temas.json', '/api/appmax-config.php'], true) ? 403 : null);
  if ($esperado && $r['code'] !== $esperado && !($esperado === 403 && in_array($r['code'], [403, 404], true))) $falhas[] = "$u devolveu {$r['code']} (esperado $esperado)";
  if (!$esperado && $u !== '/c/TESTE/' && $r['code'] !== 200) $falhas[] = "$u devolveu {$r['code']}";
  if ($r['code'] === 200 && str_contains((string)$r['type'], 'html')) {
    preg_match_all('#(?:href|src)="(/[^"\#?]*)(?:[?\#][^"]*)?"#', $r['body'], $mm);
    foreach ($mm[1] as $l) if (!str_starts_with($l, '//')) $links[$l][] = $u;
    // texto visível só em HTML (scripts incluídos em verificação separada)
    preg_match_all('#<script\b[^>]*>(.*?)</script>#si', $r['body'], $sc);
    foreach ($sc[1] as $s) foreach (['amazol', 'sabonete', 'esfoliante', 'sérum'] as $w) if (stripos($s, $w) !== false) { $out[] = "      script com '$w' em $u"; break; }
  }
}
$out[] = ""; $out[] = "== LINKS E ARQUIVOS INTERNOS (" . count($links) . ") ==";
foreach ($links as $l => $origens) {
  if (preg_match('#^/(api/(checkout|upsell|cupom|lead|avaliar|inscrever|atacado|auth|admin|creators|creator|painel|logs|t)\.php)#', $l)) continue;
  $r = req(BASE . $l, null, true);
  if ($r['code'] >= 400) { $out[] = "QUEBRADO {$r['code']} $l  (em " . implode(', ', array_slice(array_unique($origens), 0, 3)) . ')'; $falhas[] = "link quebrado $l"; }
}
$out[] = "(links com erro listados acima; se não houver nenhum, todos responderam)";

$out[] = ""; $out[] = "== APIs ==";
$api = [
  ['GET', '/api/ping.php', null, 200], ['GET', '/api/config.php', null, 200], ['GET', '/api/installments.php?total=19700', null, 200],
  ['GET', '/api/avaliacoes.php?slug=tonico-mxd20-barbudom-30ml', null, 200], ['GET', '/api/recentes.php', null, 200],
  ['POST', '/api/cupom.php', ['code' => 'PRIMEIRA10', 'email' => 'teste-' . time() . '@example.com', 'cpf' => ''], 200],
  ['POST', '/api/cupom.php', ['code' => 'NAOEXISTE', 'email' => '', 'cpf' => ''], 422],
  ['POST', '/api/checkout.php', [], 422],
  ['POST', '/api/checkout.php', ['items' => [['variant_id' => 'variant_inexistente', 'quantity' => 1]], 'method' => 'pix', 'customer' => ['name' => 'Teste Teste', 'email' => 'teste@example.com', 'phone' => '92999999999', 'cpf' => '52998224725', 'cep' => '69000000', 'street' => 'Rua A', 'number' => '1', 'district' => 'Centro', 'city' => 'Manaus', 'state' => 'AM']], 422],
  ['POST', '/api/lead.php', ['lead_id' => 'x'], 422],
  ['GET', '/api/status.php?order=0', null, 422], ['GET', '/api/pedido.php?pedido=0', null, null],
  ['GET', '/api/admin.php', null, 401], ['GET', '/api/painel.php', null, 401], ['GET', '/api/logs.php', null, 401], ['GET', '/api/creators.php', null, 401],
];
foreach ($api as [$m, $u, $body, $esp]) {
  $r = $m === 'POST' ? req(BASE . $u, $body) : req(BASE . $u);
  $ok = $esp === null || $r['code'] === $esp || ($esp === 401 && in_array($r['code'], [401, 403, 405], true));
  $out[] = sprintf('%s %d %-55s %s', $ok ? 'ok ' : 'ERRO', $r['code'], "$m $u", mb_substr(preg_replace('/\s+/', ' ', $r['body']), 0, 150));
  if (!$ok) $falhas[] = "$m $u devolveu {$r['code']} (esperado $esp)";
}

$out[] = ""; $out[] = "== CATÁLOGO (Hostinger) ==";
$r = req('https://api-ecommerce.hostinger.com/v2/channels/scha_01M61B38TG9Y250JW4V4PNZEK1/products?limit=100');
$j = json_decode($r['body'], true);
foreach (($j['data'] ?? []) as $p) foreach ($p['variants'] ?? [] as $v) { $pr = $v['prices'][0] ?? []; $out[] = '  ' . $p['title'] . ' – ' . $v['title'] . ' | R$ ' . number_format(($pr['sale_amount'] ?? $pr['amount']) / 100, 2, ',', '.') . ($pr['sale_amount'] ? ' (de ' . number_format($pr['amount'] / 100, 2, ',', '.') . ')' : '') . ' | ' . $v['id']; }
if (empty($j['data'])) $falhas[] = 'catálogo vazio';

$out[] = ""; $out[] = "== SERVIDOR ==";
$cfg = dirname(__DIR__, 2) . '/appmax-config.php';
$out[] = 'appmax-config fora do site: ' . (is_file($cfg) ? 'sim' : 'NÃO');
$out[] = 'pasta de dados: ' . (is_dir(dirname(__DIR__, 2) . '/amazole-data') ? 'sim' : 'não');
$out[] = 'posts no blog: ' . count(glob(dirname(__DIR__) . '/_blog/posts/*.json'));
$out[] = 'pull-log: ' . (is_file(dirname(__DIR__) . '/_blog/pull-log.txt') ? trim(file_get_contents(dirname(__DIR__) . '/_blog/pull-log.txt')) . ' @ ' . date('c', filemtime(dirname(__DIR__) . '/_blog/pull-log.txt')) : 'não existe');
$out[] = 'exec disponível: ' . (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true) ? 'sim' : 'não');

$out = array_merge(['Teste em ' . date('c') . ' | FALHAS: ' . count($falhas)], array_map(fn($f) => '  - ' . $f, $falhas), [''], $out);
file_put_contents(dirname(__DIR__) . '/_blog/teste-site.txt', implode("\n", $out) . "\n");
