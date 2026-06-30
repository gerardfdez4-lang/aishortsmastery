<?php
// Importa leads.csv -> Google Sheets POR LOTES (evita timeouts/503). Protegido con ?k=.
// En navegador se auto-encadena (meta refresh). Ejecutar hasta que diga FIN.
header('Content-Type: text/html; charset=utf-8');
@set_time_limit(120);
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';

$kf = '';
foreach (['/panel.key', '/panel.key.txt', '/panel.txt'] as $c) { if (is_file($base . $c)) { $kf = $base . $c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
$given = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $given !== $key) { http_response_code(403); echo 'restringido'; exit; }

$hook = @file_get_contents($base . '/sheet.url');
$hook = $hook ? trim($hook) : '';
if ($hook === '') { echo 'Falta asm-data/sheet.url'; exit; }

$file = $base . '/leads.csv';
if (!is_file($file)) { $file = __DIR__ . '/leads.csv'; }
if (!is_file($file)) { echo 'No hay leads.csv'; exit; }

$rows = [];
if (($h = fopen($file, 'r')) !== false) {
  fgetcsv($h); // cabecera
  while (($r = fgetcsv($h)) !== false) { if (!empty($r[1])) $rows[] = $r; }
  fclose($h);
}
$total = count($rows);
$CHUNK = 10;
$start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
$end = min($start + $CHUNK, $total);

for ($i = $start; $i < $end; $i++) {
  $r = $rows[$i];
  $ch = curl_init($hook);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['fecha'=>isset($r[0])?$r[0]:'', 'email'=>$r[1], 'telefono'=>isset($r[2])?$r[2]:'']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_SSL_VERIFYPEER => true,
  ]);
  curl_exec($ch); curl_close($ch);
}

if ($end < $total) {
  $next = 'sync-leads.php?k=' . urlencode($given) . '&start=' . $end;
  echo "PARCIAL $end $total\n";
  echo "<html><head><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($next) . "'></head>"
     . "<body style='font-family:sans-serif;background:#0a0a0f;color:#fff;padding:30px'>"
     . "Importando… <b>$end / $total</b>. Si no avanza solo, <a style='color:#ff8a00' href='" . htmlspecialchars($next) . "'>haz clic aquí</a>.</body></html>";
} else {
  echo "FIN $total\n";
  echo "<body style='font-family:sans-serif;background:#0a0a0f;color:#fff;padding:30px'>"
     . "✅ <b>Terminado.</b> $total leads enviados a Google Sheets (duplicados por email omitidos). "
     . "Borra las filas de prueba de la hoja.</body>";
}
