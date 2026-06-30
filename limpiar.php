<?php
// Limpieza única de filas de PRUEBA en eventos.csv. Protegido con ?k= (panel.key).
// OJO: borra eventos de tipo compra/ping/selftest. No ejecutar cuando ya haya ventas reales.
header('Content-Type: text/plain; charset=utf-8');
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
$kf = '';
foreach (['/panel.key','/panel.key.txt','/panel.txt'] as $c) { if (is_file($base.$c)) { $kf = $base.$c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/','', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
$g = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $g !== $key) { http_response_code(403); echo '403 — clave incorrecta'; exit; }

$file = $base . '/eventos.csv';
if (!is_file($file)) { echo 'No existe eventos.csv'; exit; }

$remove = ['selftest','hotmart_ping','compra_toolkit','compra_curso'];
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
copy($file, $base . '/eventos.bak.csv'); // backup

$out = []; $removed = 0; $head = true;
foreach ($lines as $ln) {
  if ($head) { $out[] = $ln; $head = false; continue; }
  $f = str_getcsv($ln);
  if (isset($f[1]) && in_array($f[1], $remove, true)) { $removed++; continue; }
  $out[] = $ln;
}
file_put_contents($file, implode("\n", $out) . "\n");
echo "OK. Eliminadas $removed filas de prueba (" . implode(', ', $remove) . ").\n";
echo "Backup en asm-data/eventos.bak.csv\n";
echo "El panel ahora marca 0 compras (la realidad).";
