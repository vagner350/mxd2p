<?php
// Blog MXD20: lê os posts de /_blog/posts/*.json (repositório vagner350/mxd2p, branch blog-posts)
// e publica cada um quando chega o horário de "publicar_em". Rotas (.htaccess):
//   /blog/            lista (?c=categoria, ?p=página)
//   /blog/<slug>/     post
//   /blog/feed.xml    RSS
require_once __DIR__ . '/_lib_noout.php';
date_default_timezone_set('America/Sao_Paulo');
const SITE = 'https://maxd20.com.br';
const PROD = '/produto/tonico-mxd20-barbudom-30ml/';
const IMG = 'https://cdn.zyrosite.com/cdn-cgi/image/format=auto,w=600,fit=scale-down/cdn-ecommerce/store_01M61B38SQHHHJ5MY4E72HDENH/assets/684bcd3a-b7f0-482f-b8bd-57ada4398a11.png';
const IMG_OG = 'https://cdn.zyrosite.com/cdn-cgi/image/format=auto,w=1200,fit=scale-down/cdn-ecommerce/store_01M61B38SQHHHJ5MY4E72HDENH/assets/684bcd3a-b7f0-482f-b8bd-57ada4398a11.png';
const POR_PAGINA = 12;

function posts_all() {
  static $all = null; if ($all !== null) return $all;
  $all = [];
  foreach (glob(dirname(__DIR__) . '/_blog/posts/*.json') ?: [] as $f) {
    $p = json_decode((string)file_get_contents($f), true);
    if (!is_array($p) || empty($p['slug']) || empty($p['titulo']) || empty($p['conteudo_html'])) continue;
    $t = strtotime($p['publicar_em'] ?? '') ?: filemtime($f);
    if ($t > time()) continue;                                   // ainda não chegou a hora
    $p['slug'] = preg_replace('/[^a-z0-9-]/', '', strtolower($p['slug']));
    $p['_t'] = $t;
    $p['_min'] = max(2, (int)round(str_word_count(strip_tags($p['conteudo_html'])) / 200));
    $all[$p['slug']] = $p;
  }
  uasort($all, fn($a, $b) => $b['_t'] <=> $a['_t']);
  return $all;
}
function data_br($t) { $m = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']; return date('j', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . date('Y', $t); }
function cat_slug($c) { $c = strtr(mb_strtolower(trim((string)$c)), ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']); return trim(preg_replace('/[^a-z0-9]+/', '-', $c), '-'); }
// limpa o HTML do post: só tags de texto, sem scripts/estilos/atributos perigosos
function limpa_html($h) {
  $h = strip_tags((string)$h, '<h2><h3><h4><p><ul><ol><li><strong><b><em><i><a><blockquote><br><table><thead><tbody><tr><th><td>');
  $h = preg_replace('/\s(on\w+|style|class|id)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $h);
  $h = preg_replace_callback('/<a\s+[^>]*href\s*=\s*"([^"]*)"[^>]*>/i', function ($m) {
    $u = trim($m[1]);
    if (!preg_match('#^(https?://|/)#i', $u)) return '<a>';
    $ext = !preg_match('#^(/|https?://(www\.)?maxd20\.com\.br)#i', $u);
    return '<a href="' . h($u) . '"' . ($ext ? ' target="_blank" rel="noopener nofollow"' : '') . '>';
  }, $h);
  return $h;
}
// CTA no meio do texto (depois do 3º parágrafo)
function cta_meio() {
  return '<aside class="bl-cta"><img src="' . IMG . '" alt="Tônico MXD20 Barbudom 30ml" width="120" height="120" loading="lazy"><div><b>Tônico MXD20 Barbudom</b><p>Ativos da Amazônia para barba, cabelo e sobrancelha. Frete grátis e bônus Guia de 30 dias.</p><a class="btn btn-pink" href="' . PROD . '#ofertas">Ver os kits</a></div></aside>';
}
function insere_cta($html) {
  $n = 0;
  return preg_replace_callback('#</p>#i', function ($m) use (&$n) { $n++; return $n === 3 ? '</p>' . cta_meio() : '</p>'; }, $html);
}

$todos = posts_all();
$base = SITE;
if (!empty($_GET['feed'])) {
  header('Content-Type: application/rss+xml; charset=utf-8'); header('Cache-Control: public, max-age=600');
  echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Blog MXD20</title><link>' . $base . '/blog/</link><description>Dicas de barba, cabelo e sobrancelha da MXD20</description><language>pt-BR</language>';
  foreach (array_slice($todos, 0, 30) as $p) echo '<item><title>' . h($p['titulo']) . '</title><link>' . $base . '/blog/' . $p['slug'] . '/</link><guid>' . $base . '/blog/' . $p['slug'] . '/</guid><pubDate>' . date(DATE_RSS, $p['_t']) . '</pubDate><description>' . h($p['descricao'] ?? '') . '</description></item>';
  echo '</channel></rss>'; exit;
}

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['slug'] ?? '')));
$post = $slug ? ($todos[$slug] ?? null) : null;
if ($slug && !$post) { http_response_code(404); }

// ---------- conteúdo ----------
$head = ''; $body = '';
$card = function ($p) {
  return '<article class="bl-card"><span class="bl-cat">' . h($p['categoria'] ?? 'Dicas') . '</span><h3><a href="/blog/' . $p['slug'] . '/">' . h($p['titulo']) . '</a></h3><p>' . h($p['descricao'] ?? '') . '</p><span class="bl-meta">' . data_br($p['_t']) . ' · ' . $p['_min'] . ' min de leitura</span></article>';
};

if ($post) {
  $url = $base . '/blog/' . $post['slug'] . '/';
  $title = ($post['titulo_seo'] ?? $post['titulo']) . ' | Blog MXD20';
  $desc = $post['descricao'] ?? mb_substr(trim(strip_tags($post['conteudo_html'])), 0, 155);
  $faq = array_values(array_filter((array)($post['faq'] ?? []), fn($f) => is_array($f) && count($f) >= 2));
  $ld = ld(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post['titulo'], 'description' => $desc, 'image' => [IMG_OG],
      'datePublished' => date('c', $post['_t']), 'dateModified' => date('c', $post['_t']), 'mainEntityOfPage' => $url, 'inLanguage' => 'pt-BR',
      'author' => ['@type' => 'Organization', 'name' => 'Equipe MXD20', 'url' => $base . '/'], 'publisher' => ['@type' => 'Organization', 'name' => 'MXD20', 'logo' => ['@type' => 'ImageObject', 'url' => $base . '/assets/icon-512.png']]])
    . ld(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $base . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $base . '/blog/'],
      ['@type' => 'ListItem', 'position' => 3, 'name' => $post['titulo'], 'item' => $url]]])
    . ($faq ? ld(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => strip_tags($f[0]), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f[1])]], $faq)]) : '');
  $head = '<title>' . h($title) . '</title><meta name="description" content="' . h($desc) . '"><link rel="canonical" href="' . h($url) . '">'
    . '<meta property="og:type" content="article"><meta property="og:title" content="' . h($post['titulo']) . '"><meta property="og:description" content="' . h($desc) . '"><meta property="og:url" content="' . h($url) . '"><meta property="og:image" content="' . IMG_OG . '"><meta property="og:site_name" content="MXD20"><meta property="og:locale" content="pt_BR"><meta name="twitter:card" content="summary_large_image">'
    . '<meta property="article:published_time" content="' . date('c', $post['_t']) . '">' . $ld;
  $rel = array_slice(array_values(array_filter($todos, fn($x) => $x['slug'] !== $post['slug'] && cat_slug($x['categoria'] ?? '') === cat_slug($post['categoria'] ?? ''))), 0, 3);
  if (count($rel) < 3) $rel = array_slice(array_merge($rel, array_values(array_filter($todos, fn($x) => $x['slug'] !== $post['slug'] && !in_array($x, $rel, true)))), 0, 3);
  $body = '<div class="wrap crumbs"><a href="/">Início</a> › <a href="/blog/">Blog</a> › <span>' . h($post['titulo']) . '</span></div>'
    . '<article class="bl-post wrap"><header class="bl-head"><a class="bl-cat" href="/blog/?c=' . cat_slug($post['categoria'] ?? '') . '">' . h($post['categoria'] ?? 'Dicas') . '</a><h1>' . h($post['titulo']) . '</h1>'
    . '<p class="bl-lead">' . h($desc) . '</p><span class="bl-meta">Equipe MXD20 · ' . data_br($post['_t']) . ' · ' . $post['_min'] . ' min de leitura</span></header>'
    . '<div class="bl-body">' . insere_cta(limpa_html($post['conteudo_html'])) . '</div>'
    . ($faq ? '<section class="bl-faq"><h2>Perguntas frequentes</h2>' . implode('', array_map(fn($f) => '<details><summary>' . h(strip_tags($f[0])) . '</summary><p>' . h(strip_tags($f[1])) . '</p></details>', $faq)) . '</section>' : '')
    . '<section class="bl-end"><img src="' . IMG . '" alt="Tônico MXD20 Barbudom" width="220" height="220" loading="lazy"><div><span class="tag">Strong Hair · Barbudom</span><h2>Barba, cabelo e sobrancelha mais preenchidos</h2><p>O Tônico MXD20 Barbudom leva ativos da Amazônia direto na raiz. Leve, não oleoso e sem enxágue. Frete grátis e Pix ou até 12x.</p><a class="btn btn-pink" href="' . PROD . '#ofertas">Escolher meu kit</a></div></section>'
    . '<p class="bl-aviso">Conteúdo informativo. Não substitui a avaliação de um médico ou dermatologista. Resultados variam de pessoa para pessoa.</p>'
    . '</article>'
    . ($rel ? '<section class="band soft"><div class="wrap"><h2 class="bl-h2">Leia também</h2><div class="bl-grid">' . implode('', array_map($card, $rel)) . '</div></div></section>' : '');
} elseif (!$slug) {
  $cats = []; foreach ($todos as $p) { $c = $p['categoria'] ?? 'Dicas'; $cats[cat_slug($c)] = $c; }
  $c = cat_slug($_GET['c'] ?? '');
  $lista = $c ? array_values(array_filter($todos, fn($p) => cat_slug($p['categoria'] ?? 'Dicas') === $c)) : array_values($todos);
  $pg = max(1, (int)($_GET['p'] ?? 1)); $tot = max(1, (int)ceil(count($lista) / POR_PAGINA)); $pg = min($pg, $tot);
  $vis = array_slice($lista, ($pg - 1) * POR_PAGINA, POR_PAGINA);
  $url = $base . '/blog/' . ($c ? '?c=' . $c : '') . ($pg > 1 ? ($c ? '&' : '?') . 'p=' . $pg : '');
  $head = '<title>Blog MXD20 | Dicas de barba, cabelo e sobrancelha' . ($pg > 1 ? ' – página ' . $pg : '') . '</title><meta name="description" content="Dicas práticas para ter barba mais cheia, cabelo mais forte e sobrancelhas preenchidas: rotina, cuidados, mitos e verdades. Blog oficial MXD20.">'
    . '<link rel="canonical" href="' . h($url) . '"><link rel="alternate" type="application/rss+xml" title="Blog MXD20" href="' . $base . '/blog/feed.xml">'
    . '<meta property="og:type" content="website"><meta property="og:title" content="Blog MXD20"><meta property="og:url" content="' . h($url) . '"><meta property="og:image" content="' . IMG_OG . '">'
    . ld(['@context' => 'https://schema.org', '@type' => 'Blog', 'name' => 'Blog MXD20', 'url' => $base . '/blog/', 'inLanguage' => 'pt-BR', 'publisher' => ['@type' => 'Organization', 'name' => 'MXD20']]);
  $nav = '';
  if ($tot > 1) { $q = fn($n) => '/blog/' . ($c ? '?c=' . $c . ($n > 1 ? '&p=' . $n : '') : ($n > 1 ? '?p=' . $n : '')); $nav = '<nav class="bl-pag">' . ($pg > 1 ? '<a class="btn btn-line" href="' . $q($pg - 1) . '">← Mais recentes</a>' : '<span></span>') . '<span>Página ' . $pg . ' de ' . $tot . '</span>' . ($pg < $tot ? '<a class="btn btn-line" href="' . $q($pg + 1) . '">Mais antigos →</a>' : '<span></span>') . '</nav>'; }
  $body = '<section class="mx-pain bl-top"><div class="wrap"><h1>Blog <em>MXD20</em></h1><p>Dicas práticas para barba mais cheia, cabelo mais forte e sobrancelhas preenchidas. Posts novos todos os dias.</p></div></section>'
    . '<section class="band"><div class="wrap">'
    . (count($cats) > 1 ? '<div class="filters" style="justify-content:center;margin-bottom:24px"><a class="' . ($c ? '' : 'on') . '" href="/blog/">Todos</a>' . implode('', array_map(fn($k, $v) => '<a class="' . ($c === $k ? 'on' : '') . '" href="/blog/?c=' . $k . '">' . h($v) . '</a>', array_keys($cats), $cats)) . '</div>' : '')
    . ($vis ? '<div class="bl-grid">' . implode('', array_map($card, $vis)) . '</div>' . $nav : '<p class="note">Os primeiros posts chegam em breve.</p>')
    . '</div></section>'
    . '<section class="bl-end wrap" style="margin-bottom:56px"><img src="' . IMG . '" alt="Tônico MXD20 Barbudom" width="220" height="220" loading="lazy"><div><span class="tag">Strong Hair · Barbudom</span><h2>Conheça o Tônico MXD20</h2><p>Ativos da Amazônia para barba, cabelo e sobrancelha. Frete grátis, Pix ou até 12x e bônus Guia de 30 dias.</p><a class="btn btn-pink" href="' . PROD . '">Conhecer o tônico</a></div></section>';
} else {
  $head = '<title>Post não encontrado | Blog MXD20</title><meta name="robots" content="noindex">';
  $body = '<section class="status"><div class="wrap"><h1>Post não encontrado</h1><p>Esse post não existe ou ainda não foi publicado.</p><a class="btn btn-pink" href="/blog/">Ver todos os posts</a></div></section>';
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=300');
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/icon-192.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<meta name="theme-color" content="#0B0B0B">
<link rel="preload" href="/assets/fonts/montserrat-latin-900-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/loja.css?v=20">
<link rel="stylesheet" href="/assets/mxd20.css?v=1">
<link rel="stylesheet" href="/assets/blog.css?v=1">
<?= $head ?>
</head>
<body>
<div class="announce"><b>Frete grátis</b> para todo o Brasil · Parcele em até 12x</div>
<header class="top"><div class="wrap">
  <a class="logo" href="/" aria-label="MXD20, início"><img src="/assets/logo.png" alt="MXD20" width="571" height="119"></a>
  <nav class="cats" aria-label="Menu"><a href="/produto/tonico-mxd20-barbudom-30ml/">Tônico MXD20</a><a href="/#kits">Kits</a><a href="/blog/" aria-current="page">Blog</a><a href="/#duvidas">Dúvidas</a><a href="/pedido/">Acompanhar pedido</a></nav>
  <button class="cart-btn" id="openCart" aria-label="Abrir carrinho"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg><span class="t">Carrinho</span><span class="n" id="cartN">0</span></button>
</div></header>
<main><?= $body ?></main>
<script src="/assets/loja.js?v=mx3"></script>
<script>chrome("blog");</script>
</body>
</html>
