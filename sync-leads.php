<?php
// Importa de una vez TODOS los leads existentes de leads.csv a Google Sheets (vía el webhook de Apps Script).
// Protegido con ?k=  (misma clave que el panel: asm-data/panel.key). Ejecútalo UNA vez.
header('Content-Type: text/plain; charset=utf-8');
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';

$kf = '';
foreach (['/panel.key', '/panel.key.txt', '/panel.txt'] as $c) { if (is_file($base . $c)) { $kf = $base . $c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
$given = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $given !== $key) { http_response_code(403); echo 'Acceso restringido. Usa ?k=TU_CLAVE.'; exit; }

$hook = @file_get_contents($base . '/sheet.url');
$hook = $hook ? trim($hook) : '';
if ($hook === '') { echo 'Falta crear asm-data/sheet.url con la URL del Apps Script.'; exit; }

$file = $base . '/leads.csv';
if (!is_file($file)) { $file = __DIR__ . '/leads.csv'; }
if (!is_file($file)) { echo 'No encuentro leads.csv.'; exit; }

$n = 0;
if (($h = fopen($file, 'r')) !== false) {
  fgetcsv($h); // saltar cabecera
  while (($r = fgetcsv($h)) !== false) {
    if (empty($r[1])) continue; // sin email
    $ch = curl_init($hook);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => http_build_query(['fecha'=>isset($r[0])?$r[0]:'', 'email'=>$r[1], 'telefono'=>isset($r[2])?$r[2]:'']),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 6,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch); curl_close($ch);
    $n++;
  }
  fclose($h);
}
echo "Enviados $n leads a Google Sheets. Los duplicados (mismo email) se omiten en la hoja. Puedes borrar este archivo cuando acabes.";
