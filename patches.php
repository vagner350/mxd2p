<?php
// Ajustes aplicados na cópia da loja (texto original da amazole.store -> MXD20).
// Cada item: [arquivo, 'str'|'re', procurar, substituir]. Aplicados ANTES do mapa global.
const MX_IMG = 'https://cdn.zyrosite.com/cdn-ecommerce/store_01M61B38SQHHHJ5MY4E72HDENH/assets/684bcd3a-b7f0-482f-b8bd-57ada4398a11.png';
const MX_IMG300 = 'https://cdn.zyrosite.com/cdn-cgi/image/format=auto,w=300,fit=scale-down/cdn-ecommerce/store_01M61B38SQHHHJ5MY4E72HDENH/assets/684bcd3a-b7f0-482f-b8bd-57ada4398a11.png';
const MX_IMG1200 = 'https://cdn.zyrosite.com/cdn-cgi/image/format=auto,w=1200,fit=scale-down/cdn-ecommerce/store_01M61B38SQHHHJ5MY4E72HDENH/assets/684bcd3a-b7f0-482f-b8bd-57ada4398a11.png';
const MX_CHANNEL = 'scha_01M61B38TG9Y250JW4V4PNZEK1';
const MX_SLUG = 'tonico-mxd20-barbudom-30ml';

return [
  // ---------- assets/loja.js ----------
  ['assets/loja.js', 'str', 'const BRINDES = { "variant_01M5J97DPEKVZ0J6AK154YK8KB": "Sabonete em Pasta Clareador 150g" };', 'const BRINDES = {};'],
  ['assets/loja.js', 're', '/const BUMPS = \{.*?\n\};\nfunction bumpKind\(lines\)\{.*?\n\}\n/s',
    "const BUMPS = {\n  esf: { price: 6700, from: 13700, slug: \"" . MX_SLUG . "\", short: \"+1 Tônico MXD20 30ml\",\n         title: \"Sim, quero +1 Tônico MXD20!\", text: \"Leve mais um frasco de 30ml e não interrompa a sua rotina.\" }\n};\nfunction bumpKind(lines){ return \"esf\"; }\n"],
  ['assets/loja.js', 'str', 'const PIXEL_ID = "1768974181217383";', 'const PIXEL_ID = ""; /* coloque aqui o ID do Pixel da MXD20 */'],
  ['assets/loja.js', 'str', "fbq('init', PIXEL_ID);\nfbq('track', 'PageView');", "if (PIXEL_ID) { fbq('init', PIXEL_ID); fbq('track', 'PageView'); }"],
  ['assets/loja.js', 'str', 'function px(ev, data, opt){ try {', 'function px(ev, data, opt){ if (!PIXEL_ID) return; try {'],
  ['assets/loja.js', 're', '/function bonusHTML\(p\)\{.*?\n\}\n/s', "function bonusHTML(p){ return \"\"; }\n"],
  ['assets/loja.js', 'str', "      <a href=\"/#produtos\" \${active===\"home\"?'aria-current=\"page\"':\"\"}>Produtos</a>\n      <a href=\"/?c=kits#produtos\">Kits</a>\n      <a href=\"/?c=esfoliantes#produtos\">Esfoliantes</a>\n",
    "      <a href=\"/produto/" . MX_SLUG . "/\" \${active===\"produto\"?'aria-current=\"page\"':\"\"}>Tônico MXD20</a>\n      <a href=\"/#kits\">Kits</a>\n"],
  ['assets/loja.js', 'str', '<a href="/#resultados">Resultados</a>' . "\n" . '      <a href="/pedido/"', '<a href="/#resultados">Resultados</a>' . "\n" . '      <a href="/#duvidas">Dúvidas</a>' . "\n" . '      <a href="/pedido/"'],
  ['assets/loja.js', 'str', '<p>Cosméticos para clarear e cuidar da sua pele, com ativos inspirados na Amazônia.</p>', '<p>Tônico MXD20 Barbudom: a força da Amazônia para barba, cabelo e sobrancelhas mais fortes e preenchidos.</p>'],
  ['assets/loja.js', 'str', '<li><a href="${LOJA.instagram}" target="_blank" rel="noopener">Instagram @amazoleon</a></li>', ''],
  ['assets/loja.js', 'str', '<li><a href="/atacado/"><b>Compre no atacado</b></a></li>', ''],
  ['assets/loja.js', 'str', 'function chrome(active){', "function chrome(active){\n  if (!document.querySelector('link[href*=\"mxd20.css\"]')) { const l = document.createElement(\"link\"); l.rel = \"stylesheet\"; l.href = \"/assets/mxd20.css?v=1\"; document.head.appendChild(l); }"],
  ['assets/loja.js', 'str', 'Olá! Tenho uma dúvida sobre os produtos Amazolé.', 'Olá! Tenho uma dúvida sobre o Tônico MXD20.'],

  // ---------- api/_lib.php ----------
  ['api/_lib.php', 'str', "\$ch = cfg()['hostinger_channel'] ?? 'scha_01M50JEQZNPZS0ZZT4J0WGQ9GJ';", "\$ch = '" . MX_CHANNEL . "';"],
  ['api/_lib.php', 're', "/\n\s*\. '<div style=\"border:2px dashed #14AEB8.*?<\/div>'/s", ''],
  ['api/_lib.php', 'str', "const BRINDES = ['variant_01M5J97DPEKVZ0J6AK154YK8KB' => 'Sabonete em Pasta Clareador Amazolé 150g'];", 'const BRINDES = [];'],
  ['api/_lib.php', 're', '/const BUMPS = \[.*?\n\];/s', "const BUMPS = [\n  'esf' => ['sku' => 'BUMP-MXD20', 'name' => 'Tônico MXD20 Barbudom 30ml (oferta do checkout)', 'unit' => 6700],\n];"],
  ['api/_lib.php', 're', '/function bump_kind\(\$lines\) \{.*?\n\}/s', "function bump_kind(\$lines) { return 'esf'; }"],
  ['api/_lib.php', 'str', '<h2 style="color:#E2087D;margin:0 0 8px">', '<h2 style="color:#0B0B0B;margin:0 0 8px">'],
  ['api/_lib.php', 'str', '<h2 style="color:#E2087D">', '<h2 style="color:#0B0B0B">'],
  ['api/_lib.php', 'str', 'background:#E2087D;color:#fff;', 'background:#FFC400;color:#0B0B0B;'],
  ['api/_lib.php', 'str', "\$esf ='esfoliante-clareador-amazole-200g'; \$ser = 'serum-intimo-clareador-amazole-30ml';", "\$esf = '" . MX_SLUG . "'; \$ser = \$esf;"],
  ['api/_lib.php', 'str', "if (\$sku === 'BUMP-ESF200') \$out[] = \$esf;", "if (\$sku === 'BUMP-MXD20') \$out[] = \$esf;"],
  ['api/_lib.php', 'str', "elseif (\$sku === 'UPS-SER30') \$out[] = \$ser;", "elseif (\$sku === 'UPS-MXD20') \$out[] = \$ser;"],

  // ---------- api/_lib_noout.php ----------
  ['api/_lib_noout.php', 'str', "if (!defined('BRINDES_SITE')) define('BRINDES_SITE', ['variant_01M5J97DPEKVZ0J6AK154YK8KB' => 'Sabonete em Pasta Clareador 150g']);", "if (!defined('BRINDES_SITE')) define('BRINDES_SITE', []);"],
  ['api/_lib_noout.php', 'str', "'image' => 'https://amazole.store/assets/kit-3.jpg',", "'image' => '" . MX_IMG1200 . "',"],
  ['api/_lib_noout.php', 'str', "'description' => 'Loja oficial Amazolé: cosméticos clareadores para axilas, virilha, melasma e manchas. Produzido na Amazônia.',", "'description' => 'Loja oficial MXD20: Tônico Barbudom para barba, cabelo e sobrancelhas, com ativos da Amazônia.',"],
  ['api/_lib_noout.php', 'str', "    'sameAs' => ['https://www.instagram.com/amazoleon/'],\n", ''],

  // ---------- api/checkout.php / upsell.php ----------
  ['api/checkout.php', 'str', "cfg()['soft_descriptor'] ?? 'AMAZOLE'", "'MXD20'"],
  ['api/upsell.php', 'str', "const UPSELL = ['sku' => 'UPS-SER30', 'name' => 'Sérum Íntimo Clareador Amazolé 30ml (oferta pós-compra)', 'quantity' => 1, 'unit_value' => 4700, 'type' => 'physical'];",
    "const UPSELL = ['sku' => 'UPS-MXD20', 'name' => 'Tônico MXD20 Barbudom 30ml (oferta pós-compra)', 'quantity' => 1, 'unit_value' => 6700, 'type' => 'physical'];"],

  // ---------- api/produto_page.php ----------
  ['api/produto_page.php', 'str', "str_contains(\$p['slug'], 'esfoliante')", "str_contains(\$p['slug'], 'mxd20')"],
  ['api/produto_page.php', 're', "/\\\$bonus = preg_match\(.*?\n/", "\$bonus = '';\n"],
  ['api/produto_page.php', 'str', "['Em quanto tempo vejo resultado?', 'Muitas clientes notam a pele mais lisa e uniforme nas primeiras semanas de uso contínuo. O tempo varia de pessoa para pessoa.'],",
    "['Em quanto tempo vejo resultado?', 'Com o uso contínuo, duas vezes ao dia, você nota fios mais resistentes e áreas mais preenchidas. O tempo varia de pessoa para pessoa.'],"],

  // ---------- produto/index.html ----------
  ['produto/index.html', 're', '/\n\s*<section class="band soft" id="resultados">.*?<\/section>\n/s', "\n"],
  ['produto/index.html', 're', '/\n\s*<section class="band soft"><div class="wrap">\s*<div class="head"><h2>Você também pode gostar<\/h2><\/div>.*?<\/section>/s', ''],
  ['produto/index.html', 'str', 'const r=document.getElementById("related"); r.innerHTML=rel.map(cardHTML).join(""); bindAdds(r);', 'const r=document.getElementById("related"); if(r){ r.innerHTML=rel.map(cardHTML).join(""); bindAdds(r); }'],
  ['produto/index.html', 'str', '<details><summary>Em quanto tempo vejo resultado?</summary><p>Muitas clientes notam a pele mais lisa e uniforme nas primeiras semanas de uso contínuo. O tempo varia de pessoa para pessoa.</p></details>',
    '<details><summary>Em quanto tempo vejo resultado?</summary><p>Com o uso contínuo, duas vezes ao dia, você nota fios mais resistentes e áreas mais preenchidas. O tempo varia de pessoa para pessoa.</p></details>'],
  ['produto/index.html', 'str', 'Não testado em animais</div>', 'Ativos da Amazônia</div>'],

  // ---------- checkout ----------
  ['checkout/index.html', 'str', '<span id="bumpTxt"><span class="t">Sim, quero +1 Esfoliante Clareador!</span><br><span class="d">Leve mais um pote de 200g de <s>R$ 97,00</s> por <b>R$ 57,00</b> e não interrompa o tratamento. Vai no mesmo pacote, com frete grátis.</span></span>', '<span id="bumpTxt"></span>'],
  ['checkout/index.html', 'str', 'let BUMP = 5700, bumpK = "esf"', 'let BUMP = 6700, bumpK = "esf"'],
  ['checkout/index.html', 'str', 'const BUMP_SKU = { esf: "BUMP-ESF200", sab: "BUMP-SAB150", ser: "BUMP-SER30" };', 'const BUMP_SKU = { esf: "BUMP-MXD20" };'],
  ['checkout/index.html', 'str', '(baseLines.some(l => !/col[aá]geno/i.test(l.product.title)) ?', '(false ?'],
  ['checkout/sucesso/index.html', 're', '/\n\s*<a class="bonus-dl".*?<\/a>/s', ''],
  ['checkout/sucesso/index.html', 'str', '<h2>Leve o <em>Sérum Íntimo Clareador</em> por só R$ 47,00</h2>', '<h2>Leve <em>+1 Tônico MXD20</em> por só R$ 67,00</h2>'],
  ['checkout/sucesso/index.html', 're', '/<img src="https:\/\/cdn\.zyrosite\.com\/cdn-cgi\/image\/format=auto,w=300,fit=scale-down\/cdn-ecommerce\/store_01M50JEQYV1Q2NTETM2JVQSH4E\/assets\/88ec6cc7[^"]*" alt="[^"]*">/', '<img src="' . MX_IMG300 . '" alt="Tônico MXD20 Barbudom 30ml">'],
  ['checkout/sucesso/index.html', 'str', '<div><p>Completa o tratamento da virilha e região íntima: textura leve, absorve rápido e ajuda a uniformizar o tom.</p>', '<div><p>Garanta mais um frasco e não interrompa a rotina: barba, cabelo e sobrancelha com força total.</p>'],
  ['checkout/sucesso/index.html', 'str', '<p class="pr"><s>R$ 97,00</s> <b>R$ 47,00</b></p>', '<p class="pr"><s>R$ 137,00</s> <b>R$ 67,00</b></p>'],
  ['checkout/sucesso/index.html', 'str', '{value:47,currency:"BRL",content_ids:["UPS-SER30"]', '{value:67,currency:"BRL",content_ids:["UPS-MXD20"]'],
  ['checkout/sucesso/index.html', 'str', 'O Sérum Íntimo vai junto no seu pedido. Obrigado!', 'O frasco extra vai junto no seu pedido. Obrigado!'],

  // ---------- .htaccess ----------
  ['.htaccess', 'str', "RewriteEngine On\n", "RewriteEngine On\nRewriteRule ^_deploy/(?!instalar\\.php$) - [F,L]\n"],
];
