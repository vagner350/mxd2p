<?php
// Roda pelo cron da Hostinger a cada 30 min.
// 1) Carrinhos abandonados: manda um e-mail de lembrete para o cliente (1x) e avisa a loja
//    com o link pronto de WhatsApp para recuperar a venda.
// 2) Pix/boleto gerados e não pagos: mesmo aviso para a loja.
// 3) Acompanhamento do cliente no 7º e 21º dia e pedido de avaliação no 10º dia.
if (php_sapi_name() !== 'cli') { http_response_code(404); exit; }
$_SERVER['REQUEST_METHOD'] = 'CLI';
require __DIR__ . '/_lib.php';
header_remove();
const SITE = 'https://maxd20.com.br';
const SLUG = 'tonico-mxd20-barbudom-30ml';
const WA_LOJA = '5592985647581';

function mx_mail($to, $subject, $html) {
  $from = cfg()['notify_from'] ?? 'pedidos@amazole.me';
  return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html,
    "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: MXD20 <$from>\r\nReply-To: vosnegocios@gmail.com\r\n", "-f$from");
}
function mx_box($title, $body, $btnUrl, $btnTxt) {
  return '<div style="font-family:Arial,sans-serif;font-size:15px;color:#0B0B0B;max-width:540px">'
    . '<div style="background:#0B0B0B;padding:14px 18px;border-radius:12px 12px 0 0"><b style="color:#fff;font-size:22px;letter-spacing:.5px">MXD<span style="color:#FFC400">20</span></b></div>'
    . '<div style="border:1px solid #E9E4D4;border-top:0;border-radius:0 0 12px 12px;padding:18px">'
    . '<h2 style="color:#0B0B0B;margin:0 0 10px">' . $title . '</h2>' . $body
    . '<p style="margin:18px 0"><a href="' . esc_html($btnUrl) . '" style="display:inline-block;background:#FFC400;color:#0B0B0B;padding:13px 24px;border-radius:99px;text-decoration:none;font-weight:bold">' . $btnTxt . '</a></p>'
    . '<p style="color:#5C5C5C;font-size:13px">Dúvidas? É só responder este e-mail ou chamar no WhatsApp (92) 98564-7581.</p></div></div>';
}

// ---------- 1. carrinhos abandonados ----------
$list = json_update('leads', function (&$leads) {
  $out = [];
  foreach ($leads as $id => &$l) {
    if (!empty($l['converted']) || !empty($l['notified'])) continue;
    if (($l['updated'] ?? time()) > time() - 30 * 60) continue;   // espera 30 min
    if (strlen($l['phone'] ?? '') < 10 && empty($l['email'])) continue;
    $l['notified'] = time();
    $out[] = $l;
  }
  return $out;
});
// ignora quem acabou comprando com o mesmo telefone/e-mail
$orders = json_load('orders');
$bought = [];
foreach ($orders as $o) { if (!empty($o['phone'])) $bought[digits($o['phone'])] = 1; if (!empty($o['email'])) $bought[$o['email']] = 1; }
$list = array_values(array_filter($list, fn($l) => empty($bought[$l['phone'] ?? '-']) && empty($bought[$l['email'] ?? '-'])));

// lembrete automático por e-mail para o cliente
foreach ($list as $l) {
  if (empty($l['email'])) continue;
  $first = esc_html(explode(' ', trim($l['name'] ?: ''))[0]);
  $prod = esc_html(implode(', ', array_map(fn($i) => $i['quantity'] . 'x ' . $i['name'], $l['items'] ?? [])) ?: 'Tônico MXD20');
  $body = '<p>Vimos que você separou <b>' . $prod . '</b> e não finalizou. Seu carrinho continua guardado.</p>'
    . '<ul style="padding-left:18px"><li>Frete grátis para todo o Brasil</li><li>Pix com aprovação na hora ou até 12x no cartão</li><li>7 dias para devolver</li></ul>'
    . '<p>Quanto antes você começa, antes vê a barba, o cabelo e a sobrancelha mais preenchidos.</p>';
  mx_mail($l['email'], ($first ? "$first, " : '') . 'seu MXD20 ficou esperando', mx_box(($first ? "$first, seu" : 'Seu') . ' carrinho está guardado', $body, SITE . '/carrinho/', 'Finalizar meu pedido'));
  log_line('abandono_email', ['email' => $l['email']]);
}

// ---------- 2. Pix e boleto gerados e não pagos ----------
$pend = json_update('orders', function (&$orders) {
  $out = [];
  foreach ($orders as $id => &$o) {
    if (!empty($o['paid']) || !empty($o['reminded']) || !in_array($o['method'] ?? '', ['pix', 'boleto'], true)) continue;
    $age = time() - strtotime($o['created'] ?? 'now');
    $wait = ($o['method'] === 'pix') ? 30 * 60 : 24 * 3600;
    if ($age < $wait || $age > 7 * 86400) continue;
    $o['reminded'] = time();
    $out[] = $o;
  }
  return $out;
});
$pendRows = '';
foreach ($pend as $o) {
  [$c, $j] = appmax('GET', '/v1/orders/' . (int)$o['order_id']);
  $st = $j['data']['order']['status'] ?? '';
  if (is_paid_status($st)) { notify_paid((int)$o['order_id'], 'conferência automática'); continue; }
  if (in_array(mb_strtolower($st), ['cancelado', 'estornado'], true)) continue;
  $first = explode(' ', trim($o['name'] ?: 'tudo bem'))[0];
  $prod = implode(', ', array_map(fn($i) => $i['quantity'] . 'x ' . $i['name'], $o['items'] ?? []));
  $msg = $o['method'] === 'pix'
    ? "Oi $first! Aqui é da MXD20 💛 Vi que o Pix do seu pedido #{$o['order_id']} ($prod) ainda não foi pago. Quer que eu te mande o código de novo ou ficou alguma dúvida? Se o Pix expirou, é só refazer em " . SITE . "/carrinho/"
    : "Oi $first! Aqui é da MXD20 💛 Seu boleto do pedido #{$o['order_id']} ($prod) ainda não foi pago. Posso te ajudar? Se preferir, dá pra pagar com Pix na hora em " . SITE . "/carrinho/";
  $link = 'https://wa.me/55' . digits($o['phone'] ?? '') . '?text=' . rawurlencode($msg);
  $pendRows .= '<tr><td style="padding:8px;border-bottom:1px solid #ddd"><b>#' . (int)$o['order_id'] . ' · ' . esc_html($o['name'] ?? '-') . '</b><br>' . esc_html($o['phone'] ?? '') . '<br>' . esc_html(strtoupper($o['method'])) . ' gerado em ' . esc_html(date('d/m H:i', strtotime($o['created']))) . '</td><td style="padding:8px;border-bottom:1px solid #ddd">' . esc_html($prod) . '<br><b>' . brl($o['total'] ?? 0) . '</b></td><td style="padding:8px;border-bottom:1px solid #ddd"><a href="' . esc_html($link) . '" style="background:#25D366;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none">Chamar no WhatsApp</a></td></tr>';
}
if ($pendRows) notify_email('MXD20: Pix/boleto não pagos para recuperar', '<div style="font-family:Arial,sans-serif;font-size:14px"><h2 style="color:#0B0B0B">Pagamentos pendentes · MXD20</h2><p>Esses clientes geraram Pix (há mais de 30 min) ou boleto (há mais de 1 dia) e ainda não pagaram.</p><table style="border-collapse:collapse;width:100%">' . $pendRows . '</table></div>');

// ---------- 3. acompanhamento no 7º e 21º dia após o pagamento ----------
$FOLLOW = [
  7 => ['f7', 'Como está a sua primeira semana com o MXD20?', 'Uma semana de rotina, %s!',
        '<p>Você está no começo, e é aqui que a constância faz diferença. Algumas dicas para esta fase:</p><ul style="padding-left:18px"><li>Aplique 2x ao dia, de manhã e à noite, sempre com a pele limpa e seca.</li><li>Massageie por 1 minuto para o tônico chegar à raiz. Não precisa enxaguar.</li><li>Tire hoje uma foto do "antes", com a mesma luz e o mesmo ângulo. Ela vai mostrar sua evolução.</li></ul>'],
  21 => ['f21', 'Três semanas de MXD20: hora de manter o ritmo', 'Três semanas de rotina, %s!',
        '<p>Agora é manter a constância, que é o que traz resultado.</p><ul style="padding-left:18px"><li>Compare a foto de hoje com a do começo.</li><li>Continue com 2 aplicações por dia, sem pular.</li><li>Seu frasco está acabando? Para não interromper a rotina, garanta o próximo na loja.</li></ul><p>Mande sua foto de antes e depois pra gente no WhatsApp. Adoramos ver a evolução!</p>'],
];
foreach ($FOLLOW as $days => [$flag, $subject, $title, $body]) {
  $toSend = json_update('orders', function (&$orders) use ($days, $flag) {
    $out = [];
    foreach ($orders as $id => &$o) {
      if (empty($o['paid']) || !empty($o[$flag]) || empty($o['email'])) continue;
      $paidAt = strtotime($o['notified'] ?? $o['created'] ?? 'now');
      if (time() - $paidAt < $days * 86400) continue;
      if (time() - $paidAt > ($days + 5) * 86400) { $o[$flag] = 'skip'; continue; }
      $o[$flag] = time(); $out[] = $o;
    }
    return $out;
  });
  foreach ($toSend as $o) {
    $first = explode(' ', trim($o['name'] ?: ''))[0];
    $wa = 'https://wa.me/' . WA_LOJA . '?text=' . rawurlencode('Olá! Sou cliente MXD20 (pedido #' . $o['order_id'] . ') e tenho uma dúvida sobre o tônico.');
    $btn = $days === 21 ? [SITE . '/produto/' . SLUG . '/#ofertas', 'Garantir o próximo frasco'] : [$wa, 'Tirar dúvida no WhatsApp'];
    mx_mail($o['email'], $subject, mx_box(esc_html(sprintf($title, $first)), $body . '<p style="color:#5C5C5C;font-size:12px">Resultados variam de pessoa para pessoa.</p>', $btn[0], $btn[1]));
    log_line('follow_' . $days, ['order' => $o['order_id']]);
  }
}

// ---------- pedido de avaliação 10 dias após o pagamento ----------
$toReview = json_update('orders', function (&$orders) {
  $out = [];
  foreach ($orders as $id => &$o) {
    if (empty($o['paid']) || !empty($o['review_asked']) || empty($o['email'])) continue;
    $paidAt = strtotime($o['notified'] ?? $o['created'] ?? 'now');
    if (time() - $paidAt < 10 * 86400) continue;
    $o['review_asked'] = time();
    $out[] = $o;
  }
  return $out;
});
foreach ($toReview as $o) {
  $first = esc_html(explode(' ', trim($o['name'] ?: ''))[0]);
  $url = SITE . '/avaliar/?pedido=' . (int)$o['order_id'] . '&email=' . rawurlencode($o['email']);
  mx_mail($o['email'], 'Como está sendo o seu MXD20? Avalie seu pedido', mx_box("Como estão seus fios, $first?",
    '<p>Já faz uns dias que seu MXD20 chegou. Conta pra gente como está sendo? Sua opinião ajuda outras pessoas a decidir.</p><p style="color:#5C5C5C;font-size:13px">Leva 1 minuto. Se quiser, mande uma foto do antes e depois.</p>', $url, 'Avaliar meu pedido'));
  log_line('review_request', ['order' => $o['order_id']]);
}

// ---------- aviso para a loja com os carrinhos abandonados ----------
$list = array_values(array_filter($list, fn($l) => strlen($l['phone'] ?? '') >= 10));
if (!$list) exit(0);
$rows = '';
foreach ($list as $l) {
  $first = explode(' ', trim($l['name'] ?: 'tudo bem'))[0];
  $prod = implode(', ', array_map(fn($i) => $i['quantity'] . 'x ' . $i['name'], $l['items'] ?? []));
  $msg = "Oi $first! Aqui é da MXD20 💛 Vi que você separou " . ($prod ?: 'o Tônico MXD20') . " no site e não finalizou. Ficou alguma dúvida? Posso te ajudar a escolher o kit. Seu carrinho continua guardado aqui: " . SITE . "/carrinho/";
  $link = 'https://wa.me/55' . $l['phone'] . '?text=' . rawurlencode($msg);
  $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #ddd"><b>' . esc_html($l['name'] ?: '-') . '</b><br>' . esc_html($l['phone']) . '<br>' . esc_html($l['email'] ?? '') . '</td><td style="padding:8px;border-bottom:1px solid #ddd">' . esc_html($prod) . '<br><b>' . brl($l['total'] ?? 0) . '</b></td><td style="padding:8px;border-bottom:1px solid #ddd"><a href="' . esc_html($link) . '" style="background:#25D366;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none">Chamar no WhatsApp</a></td></tr>';
}
notify_email('MXD20: ' . count($list) . ' carrinho(s) abandonado(s) para recuperar', '<div style="font-family:Arial,sans-serif;font-size:14px"><h2 style="color:#0B0B0B">Carrinhos abandonados · MXD20</h2><p>Essas pessoas preencheram os dados no checkout há mais de 30 minutos e não pagaram. Toque no botão para mandar a mensagem pronta no WhatsApp.</p><table style="border-collapse:collapse;width:100%">' . $rows . '</table></div>');
