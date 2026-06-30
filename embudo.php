<?php
// Panel del embudo. Protegido con ?k=CLAVE (la clave está en asm-data/panel.key)
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
$cands = ['/panel.key', '/panel.key.txt', '/panel.txt'];
$keyfile = ''; $key = '';
foreach ($cands as $c) { if (is_file($base . $c)) { $keyfile = $base . $c; break; } }
if ($keyfile) {
  $raw = file_get_contents($keyfile);
  $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);       // quita BOM
  $key = trim($raw, " \t\n\r\0\x0B\"'");                  // quita espacios y comillas
}
$given = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $given !== $key) {
  http_response_code(403);
  header('Content-Type: text/html; charset=utf-8');
  echo '<body style="font-family:sans-serif;background:#0a0a0f;color:#fff;padding:40px;line-height:1.6">';
  echo '<h2>🔒 Panel restringido</h2><p>Accede con <code>?k=TU_CLAVE</code>.</p>';
  if (!$keyfile) {
    echo '<p style="color:#ff8a00">No encuentro <code>asm-data/panel.key</code>. Créalo dentro de <code>asm-data</code> con tu clave. <b>Importante:</b> que se llame exactamente <code>panel.key</code> (no <code>panel.key.txt</code>).</p>';
  } elseif ($given !== '') {
    echo '<p style="color:#ff8a00">Clave incorrecta. Archivo detectado: <code>' . htmlspecialchars(basename($keyfile)) . '</code> (' . strlen($key) . ' caracteres). Revisa que escribes la misma clave que hay dentro, sin espacios.</p>';
  }
  echo '</body>'; exit;
}

$file = $base . '/eventos.csv';
$days = isset($_GET['d']) ? max(1, (int)$_GET['d']) : 0; // 0 = todo
$since = $days ? (time() - $days*86400) : 0;

$counts = []; $total = 0;
if (is_file($file) && ($h = fopen($file, 'r'))) {
  fgetcsv($h);
  while (($r = fgetcsv($h)) !== false) {
    if (count($r) < 2) continue;
    if ($since) { $t = strtotime($r[0]); if ($t && $t < $since) continue; }
    $ev = $r[1]; if ($ev === '') continue;
    $counts[$ev] = ($counts[$ev] ?? 0) + 1;
    $total++;
  }
  fclose($h);
}
function c($counts, $k){ return $counts[$k] ?? 0; }
function pct($a, $b){ return $b > 0 ? round($a / $b * 100) : 0; }

// Pasos del embudo
$landing = c($counts,'landing');
$lead    = c($counts,'lead');
$vfin    = c($counts,'video_fin');
$ventas  = c($counts,'ventas');
$ckH     = c($counts,'checkout_herramientas');
$ckC     = c($counts,'checkout_curso');
$checkout= $ckH + $ckC;
$compraH = c($counts,'compra_toolkit');
$compraC = c($counts,'compra_curso');
$compras = $compraH + $compraC;

$steps = [
  ['👀 Landing (clase gratis)', $landing, $landing],
  ['📩 Lead (desbloquean vídeo)', $lead, $landing],
  ['🎬 Terminan el vídeo', $vfin, $lead],
  ['🛒 Página de ventas', $ventas, $lead],
  ['💳 Clic en checkout', $checkout, $ventas],
  ['✅ Compra', $compras, $checkout],
];
$max = max(1, $landing);
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Embudo · AI Shorts Mastery</title>
<style>
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0a0a0f;color:#e7e9f2;margin:0;padding:34px 22px 60px}
  .wrap{max-width:760px;margin:0 auto}
  h1{font-size:26px;margin:0 0 4px}.sub{color:#9aa0b4;font-size:14px;margin-bottom:22px}
  .grad{background:linear-gradient(90deg,#ff3d3d,#ff8a00);-webkit-background-clip:text;background-clip:text;color:transparent}
  .filters{margin-bottom:20px}.filters a{color:#9aa0b4;text-decoration:none;border:1px solid #262633;border-radius:999px;padding:6px 13px;font-size:13px;margin-right:6px}
  .filters a.on{background:linear-gradient(90deg,#ff3d3d,#ff8a00);color:#0a0a0f;border-color:transparent;font-weight:700}
  .row{margin-bottom:14px}
  .row .lab{display:flex;justify-content:space-between;font-size:15px;margin-bottom:5px}
  .row .lab b{font-weight:800}.row .lab .cv{color:#39d98a;font-weight:700;font-size:13px}
  .track{background:#16161f;border:1px solid #262633;border-radius:10px;height:34px;overflow:hidden}
  .fill{height:100%;background:linear-gradient(90deg,#ff3d3d,#ff8a00);display:flex;align-items:center;padding-left:12px;font-weight:800;color:#0a0a0f;font-size:14px;min-width:fit-content;white-space:nowrap}
  .cards{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:24px 0}
  .card{background:#16161f;border:1px solid #262633;border-radius:12px;padding:16px}
  .card .n{font-size:26px;font-weight:900}.card .l{color:#9aa0b4;font-size:13px}
  .note{color:#6b7185;font-size:12px;margin-top:24px;line-height:1.6}
</style></head><body><div class="wrap">
<h1>Embudo <span class="grad">AI Shorts Mastery</span></h1>
<div class="sub">Datos propios, sin cookies · <?= $total ?> eventos registrados</div>

<div class="filters">
  <a href="?k=<?= htmlspecialchars($given) ?>&d=0" class="<?= $days==0?'on':'' ?>">Todo</a>
  <a href="?k=<?= htmlspecialchars($given) ?>&d=7" class="<?= $days==7?'on':'' ?>">7 días</a>
  <a href="?k=<?= htmlspecialchars($given) ?>&d=1" class="<?= $days==1?'on':'' ?>">Hoy</a>
</div>

<?php foreach ($steps as $st): list($lab,$n,$prev)=$st; $w=round($n/$max*100); ?>
<div class="row">
  <div class="lab"><b><?= $lab ?></b><span><?= $n ?> <span class="cv"><?= $prev>0 ? '· '.pct($n,$prev).'% del paso anterior' : '' ?></span></span></div>
  <div class="track"><div class="fill" style="width:<?= max($w,6) ?>%"><?= $n ?></div></div>
</div>
<?php endforeach; ?>

<h2 style="font-size:18px;margin:28px 0 12px">📺 Retención del vídeo</h2>
<?php
$vplay=c($counts,'video_play'); $v25=c($counts,'video_25'); $v50=c($counts,'video_50'); $v75=c($counts,'video_75');
$ret=[['▶️ Le dan al play',$vplay],['25%',$v25],['50%',$v50],['75%',$v75],['🏁 Terminan (100%)',$vfin]];
$maxr=max(1,$lead,$vplay);
foreach($ret as $rr){ list($rl,$rn)=$rr; $rw=round($rn/$maxr*100); ?>
<div class="row"><div class="lab"><b><?= $rl ?></b><span><?= $rn ?> <span class="cv"><?= $vplay>0 ? '· '.pct($rn,$vplay).'% de los que reproducen' : '' ?></span></span></div>
<div class="track"><div class="fill" style="width:<?= max($rw,6) ?>%"><?= $rn ?></div></div></div>
<?php } ?>
<p style="color:#6b7185;font-size:12px;margin:8px 0 0">Si caen entre el play y el 50% → el vídeo es muy largo o el arranque no engancha. La oferta ya está visible bajo el vídeo, así que no dependes de que terminen.</p>

<div class="cards">
  <div class="card"><div class="n"><?= $compraH ?></div><div class="l">Compras Toolkit (29€)</div></div>
  <div class="card"><div class="n"><?= $compraC ?></div><div class="l">Compras Curso (197€)</div></div>
  <div class="card"><div class="n"><?= c($counts,'cta_toolkit') ?></div><div class="l">Clics CTA toolkit (fin de vídeo)</div></div>
  <div class="card"><div class="n"><?= pct($lead,$landing) ?>%</div><div class="l">Tasa de captación (landing→lead)</div></div>
</div>

<div class="note">
  <b>Cómo leerlo:</b> mira en qué paso cae el % más fuerte — ahí está tu cuello de botella.<br>
  · Si caen en <b>landing→lead</b>: el muro o la promesa no convencen.<br>
  · Si pocos <b>terminan el vídeo</b>: el vídeo es largo o pierde ritmo.<br>
  · Si llegan a <b>ventas</b> pero no hay <b>checkout</b>: precio/oferta/copy.<br>
  · Si hay <b>checkout</b> pero no <b>compra</b>: fricción en el pago.<br>
  La "compra" se cuenta al llegar a la página de gracias (redirección de Stripe tras pagar).
</div>
</div></body></html>
