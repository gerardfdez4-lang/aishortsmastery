<?php
// Limpieza puntual: borra filas de diagnóstico (contienen "diag"). Protegido con ?k=.
header('Content-Type: text/plain; charset=utf-8');
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
$kf = '';
foreach (['/panel.key','/panel.key.txt','/panel.txt'] as $c) { if (is_file($base.$c)) { $kf = $base.$c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/','', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
$g = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $g !== $key) { http_response_code(403); echo '403'; exit; }

$file = $base . '/eventos.csv';
if (!is_file($file)) { echo 'no hay eventos.csv'; exit; }
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
copy($file, $base . '/eventos.bak.csv');
$out = []; $removed = 0; $head = true;
foreach ($lines as $ln) {
  if ($head) { $out[] = $ln; $head = false; continue; }
  if (stripos($ln, 'diag') !== false) { $removed++; continue; }
  $out[] = $ln;
}
file_put_contents($file, implode("\n", $out) . "\n");
echo "OK. Eliminadas $removed filas de diagnóstico. Panel a 0.";
