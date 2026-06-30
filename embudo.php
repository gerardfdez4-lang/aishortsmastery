<?php
// Panel del embudo. Protegido con ?k=CLAVE (la clave está en asm-data/panel.key)
date_default_timezone_set('Europe/Madrid'); // todo se muestra en hora de España
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

$file  = $base . '/eventos.csv';
$days  = isset($_GET['d']) ? max(0, (int)$_GET['d']) : 0; // 0 = todo
$since = $days ? (time() - $days*86400) : 0;

// --- parse de una sola pasada ---
$counts = []; $total = 0; $last = 0; $rows = [];
$todayStart = strtotime('today');                 // medianoche de hoy (Madrid)
$hLand = array_fill(0,24,0); $hLead = array_fill(0,24,0);
$visitsToday = 0; $leadsToday = 0; $lastLead = 0; $sessToday = [];
$leadsTotal = 0;

if (is_file($file) && ($h = fopen($file, 'r'))) {
  fgetcsv($h);
  while (($r = fgetcsv($h)) !== false) {
    if (count($r) < 2) continue;
    $t = strtotime($r[0]); $ev = $r[1]; if ($ev === '') continue;
    $rows[] = [$t, $ev, $r[2] ?? '', $r[3] ?? '', $r[4] ?? '', $r[5] ?? ''];
    if ($t > $last) $last = $t;
    // funnel respeta el filtro de días
    if (!$since || $t >= $since) { $counts[$ev] = ($counts[$ev] ?? 0) + 1; $total++; }
    if ($ev === 'lead') $leadsTotal++;
    // métricas de HOY (siempre, independientes del filtro)
    if ($t && $t >= $todayStart) {
      $hr = (int)date('G', $t);
      if ($ev === 'landing') { $hLand[$hr]++; $visitsToday++; if ($r[3] ?? '') $sessToday[$r[3]] = 1; }
      if ($ev === 'lead')    { $hLead[$hr]++; $leadsToday++; if ($t > $lastLead) $lastLead = $t; }
    }
  }
  fclose($h);
}
function c($counts, $k){ return $counts[$k] ?? 0; }
function pct($a, $b){ return $b > 0 ? round($a / $b * 100) : 0; }
function hace($t){
  if (!$t) return 'nunca';
  $s = time() - $t;
  if ($s < 60) return 'hace ' . $s . 's';
  if ($s < 3600) return 'hace ' . floor($s/60) . ' min';
  if ($s < 86400) return 'hace ' . floor($s/3600) . 'h ' . floor(($s%3600)/60) . 'm';
  return 'hace ' . floor($s/86400) . ' días';
}

// salud
$age = $last ? time() - $last : PHP_INT_MAX;
if      ($age < 1800)  { $hcol = '#39d98a'; $hico = '🟢'; $htxt = 'Activo y recibiendo datos'; }
elseif  ($age < 10800) { $hcol = '#ffb020'; $hico = '🟡'; $htxt = 'Sin actividad reciente (normal si hay poco tráfico)'; }
else                   { $hcol = '#ff5470'; $hico = '🔴'; $htxt = 'Llevas rato sin eventos — revisa tráfico/anuncios'; }
$lastEv = $rows ? end($rows) : null;

// pasos del embudo
$landing = c($counts,'landing'); $lead = c($counts,'lead'); $vfin = c($counts,'video_fin');
$ventas  = c($counts,'ventas'); $ckH = c($counts,'checkout_herramientas'); $ckC = c($counts,'checkout_curso');
$checkout = $ckH + $ckC; $compraH = c($counts,'compra_toolkit'); $compraC = c($counts,'compra_curso'); $compras = $compraH + $compraC;
$steps = [
  ['👀 Landing (clase gratis)', $landing, $landing],
  ['📩 Lead (desbloquean vídeo)', $lead, $landing],
  ['🎬 Terminan el vídeo', $vfin, $lead],
  ['🛒 Página de ventas', $ventas, $lead],
  ['💳 Clic en checkout', $checkout, $ventas],
  ['✅ Compra', $compras, $checkout],
];
$max = max(1, $landing);

// etiquetas para el feed
$EVL = [
  'landing'=>'👀 Visita landing','lead'=>'📩 LEAD nuevo','video_play'=>'▶️ Play vídeo',
  'video_25'=>'⏩ 25% vídeo','video_50'=>'⏩ 50% vídeo','video_75'=>'⏩ 75% vídeo','video_fin'=>'🏁 Fin vídeo',
  'ventas'=>'🛒 Página ventas','checkout_herramientas'=>'💳 Checkout Toolkit','checkout_curso'=>'💳 Checkout Curso',
  'cta_toolkit'=>'👆 CTA toolkit','compra_toolkit'=>'✅ COMPRA Toolkit','compra_curso'=>'✅ COMPRA Curso',
  'hotmart_ping'=>'🔔 Hotmart ping','selftest'=>'🔧 Test interno',
];
$feed = array_slice($rows, -45); $feed = array_reverse($feed);
$maxH = max(1, max($hLand));
$nowHr = (int)date('G');
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<meta http-equiv="refresh" content="45">
<title>Embudo · AI Shorts Mastery</title>
<style>
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0a0a0f;color:#e7e9f2;margin:0;padding:30px 22px 70px}
  .wrap{max-width:820px;margin:0 auto}
  h1{font-size:25px;margin:0 0 4px}.sub{color:#9aa0b4;font-size:13px;margin-bottom:18px}
  h2{font-size:17px;margin:30px 0 12px}
  .grad{background:linear-gradient(90deg,#ff3d3d,#ff8a00);-webkit-background-clip:text;background-clip:text;color:transparent}
  .health{display:flex;align-items:center;gap:12px;background:#16161f;border:1px solid #262633;border-left:5px solid var(--hc);border-radius:12px;padding:14px 16px;margin-bottom:18px}
  .health .big{font-size:17px;font-weight:800}.health .sm{color:#9aa0b4;font-size:12.5px}
  .filters{margin-bottom:20px}.filters a{color:#9aa0b4;text-decoration:none;border:1px solid #262633;border-radius:999px;padding:6px 13px;font-size:13px;margin-right:6px}
  .filters a.on{background:linear-gradient(90deg,#ff3d3d,#ff8a00);color:#0a0a0f;border-color:transparent;font-weight:700}
  .cards{display:grid;grid-template-columns:repeat(4,1fr);gap:11px;margin:6px 0 4px}
  .card{background:#16161f;border:1px solid #262633;border-radius:12px;padding:14px}
  .card .n{font-size:24px;font-weight:900}.card .l{color:#9aa0b4;font-size:12px;margin-top:2px}
  .chart{background:#16161f;border:1px solid #262633;border-radius:12px;padding:16px 14px 10px}
  .bars{display:flex;align-items:flex-end;gap:3px;height:120px}
  .bcol{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:3px}
  .bar{width:100%;background:linear-gradient(180deg,#ff8a00,#ff3d3d);border-radius:3px 3px 0 0;min-height:2px}
  .bcol.now .bar{outline:2px solid #fff3}
  .bcol .ld{font-size:10px;color:#39d98a;font-weight:800;height:12px}
  .bcol .hh{font-size:9.5px;color:#6b7185;margin-top:4px}
  .row{margin-bottom:13px}
  .row .lab{display:flex;justify-content:space-between;font-size:14.5px;margin-bottom:5px}
  .row .lab b{font-weight:800}.row .lab .cv{color:#39d98a;font-weight:700;font-size:12.5px}
  .track{background:#16161f;border:1px solid #262633;border-radius:10px;height:32px;overflow:hidden}
  .fill{height:100%;background:linear-gradient(90deg,#ff3d3d,#ff8a00);display:flex;align-items:center;padding-left:11px;font-weight:800;color:#0a0a0f;font-size:13px;min-width:fit-content;white-space:nowrap}
  table{width:100%;border-collapse:collapse;font-size:13px}
  .feed{background:#16161f;border:1px solid #262633;border-radius:12px;overflow:hidden}
  .feed td{padding:8px 12px;border-top:1px solid #20202c}
  .feed tr:first-child td{border-top:0}
  .feed .tm{color:#6b7185;white-space:nowrap;font-variant-numeric:tabular-nums}
  .feed .ev{font-weight:700}.feed .mt{color:#9aa0b4;font-size:12px}
  .lead td{background:rgba(57,217,138,.07)}
  .buy td{background:rgba(255,138,0,.10)}
  .note{color:#6b7185;font-size:12px;margin-top:22px;line-height:1.6}
</style></head><body><div class="wrap" style="--hc:<?= $hcol ?>">
<h1>Embudo <span class="grad">AI Shorts Mastery</span></h1>
<div class="sub">Datos propios, sin cookies · <?= $total ?> eventos (filtro) · hora de España · se refresca solo cada 45s</div>

<div class="health">
  <div style="font-size:26px"><?= $hico ?></div>
  <div>
    <div class="big" style="color:<?= $hcol ?>"><?= $htxt ?></div>
    <div class="sm">Última actividad: <b><?= hace($last) ?></b><?= $lastEv ? ' · ' . htmlspecialchars($EVL[$lastEv[1]] ?? $lastEv[1]) . ' a las ' . date('H:i', $lastEv[0]) : '' ?> · Último lead: <b><?= hace($lastLead ?: 0) ?></b></div>
  </div>
</div>

<div class="cards">
  <div class="card"><div class="n"><?= $visitsToday ?></div><div class="l">Visitas hoy</div></div>
  <div class="card"><div class="n"><?= count($sessToday) ?></div><div class="l">Visitantes únicos hoy</div></div>
  <div class="card"><div class="n"><?= $leadsToday ?></div><div class="l">Leads hoy</div></div>
  <div class="card"><div class="n"><?= pct($leadsToday,$visitsToday) ?>%</div><div class="l">Conversión hoy</div></div>
</div>

<h2>📊 Actividad de hoy por horas <span style="color:#6b7185;font-weight:400;font-size:13px">· barras = visitas · número verde = leads</span></h2>
<div class="chart">
  <div class="bars">
    <?php for ($i=0;$i<24;$i++): $bh = round($hLand[$i]/$maxH*100); ?>
    <div class="bcol <?= $i==$nowHr?'now':'' ?>">
      <div class="ld"><?= $hLead[$i] ? $hLead[$i] : '' ?></div>
      <div class="bar" style="height:<?= $hLand[$i] ? max($bh,4) : 0 ?>%" title="<?= $i ?>:00 — <?= $hLand[$i] ?> visitas, <?= $hLead[$i] ?> leads"></div>
      <div class="hh"><?= $i % 2 == 0 ? $i : '' ?></div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<div class="filters" style="margin-top:24px">
  <a href="?k=<?= htmlspecialchars($given) ?>&d=0" class="<?= $days==0?'on':'' ?>">Todo</a>
  <a href="?k=<?= htmlspecialchars($given) ?>&d=7" class="<?= $days==7?'on':'' ?>">7 días</a>
  <a href="?k=<?= htmlspecialchars($given) ?>&d=1" class="<?= $days==1?'on':'' ?>">Últimas 24h</a>
</div>

<h2>🧭 Embudo de conversión</h2>
<?php foreach ($steps as $st): list($lab,$n,$prev)=$st; $w=round($n/$max*100); ?>
<div class="row">
  <div class="lab"><b><?= $lab ?></b><span><?= $n ?> <span class="cv"><?= $prev>0 ? '· '.pct($n,$prev).'% del paso anterior' : '' ?></span></span></div>
  <div class="track"><div class="fill" style="width:<?= max($w,6) ?>%"><?= $n ?></div></div>
</div>
<?php endforeach; ?>

<h2>📺 Retención del vídeo</h2>
<?php
$vplay=c($counts,'video_play'); $v25=c($counts,'video_25'); $v50=c($counts,'video_50'); $v75=c($counts,'video_75');
$ret=[['▶️ Le dan al play',$vplay],['25%',$v25],['50%',$v50],['75%',$v75],['🏁 Terminan (100%)',$vfin]];
$maxr=max(1,$lead,$vplay);
foreach($ret as $rr){ list($rl,$rn)=$rr; $rw=round($rn/$maxr*100); ?>
<div class="row"><div class="lab"><b><?= $rl ?></b><span><?= $rn ?> <span class="cv"><?= $vplay>0 ? '· '.pct($rn,$vplay).'% de los que reproducen' : '' ?></span></span></div>
<div class="track"><div class="fill" style="width:<?= max($rw,6) ?>%"><?= $rn ?></div></div></div>
<?php } ?>

<h2>🔴 En vivo — últimos eventos</h2>
<div class="feed"><table>
<?php if (!$feed): ?><tr><td style="color:#6b7185;padding:16px">Sin eventos todavía.</td></tr><?php endif; ?>
<?php foreach ($feed as $f): list($ft,$fe,$fp,$fs,$fm)=$f;
  $cls = $fe==='lead' ? 'lead' : (strpos($fe,'compra')===0 ? 'buy' : ''); ?>
<tr class="<?= $cls ?>">
  <td class="tm"><?= $ft ? date('d/m H:i:s', $ft) : '—' ?></td>
  <td class="ev"><?= htmlspecialchars($EVL[$fe] ?? $fe) ?></td>
  <td class="mt"><?= htmlspecialchars($fm ?: $fp) ?></td>
</tr>
<?php endforeach; ?>
</table></div>

<div class="note">
  <b>Cómo usar este panel para diagnosticar:</b><br>
  · <b>Semáforo verde</b> = el tracking funciona. Si está rojo pero la web carga, es que <b>no entra tráfico</b> (no que esté roto).<br>
  · <b>Gráfica por horas</b>: si las barras (visitas) también caen → es tráfico, no un fallo. Si hay visitas pero 0 leads en verde → problema de conversión en el muro.<br>
  · <b>Feed en vivo</b>: abre la web en otra pestaña, navega, y deberías verte aparecer aquí en segundos.<br>
  · Filtra el embudo por <b>24h / 7 días / todo</b> para ver el cuello de botella.<br>
  Las compras se registran vía el webhook de Hotmart al aprobarse el pago.
</div>
</div></body></html>
