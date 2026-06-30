<?php
// Diagnóstico de la conexión con Google Sheets. Protegido con ?k=. Borrar tras depurar.
header('Content-Type: text/plain; charset=utf-8');
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
$kf = '';
foreach (['/panel.key', '/panel.key.txt', '/panel.txt'] as $c) { if (is_file($base . $c)) { $kf = $base . $c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
if ($key === '' || (isset($_GET['k']) ? trim($_GET['k']) : '') !== $key) { http_response_code(403); echo 'restringido'; exit; }

$exists = is_file($base . '/sheet.url');
echo "sheet.url existe: " . ($exists ? 'SI' : 'NO') . "\n";
$hook = $exists ? trim(file_get_contents($base . '/sheet.url')) : '';
echo "longitud: " . strlen($hook) . "\n";
echo "empieza por https://script.google.com: " . (strpos($hook, 'https://script.google.com') === 0 ? 'SI' : 'NO') . "\n";
echo "termina en /exec: " . (substr($hook, -5) === '/exec' ? 'SI' : 'NO') . "\n";
if ($hook === '') { echo "URL VACIA — revisa el archivo sheet.url"; exit; }

$ch = curl_init($hook);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query(['fecha'=>date('Y-m-d H:i:s'), 'email'=>'probe-'.time().'@test.com', 'telefono'=>'612000000']),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 12,
  CURLOPT_SSL_VERIFYPEER => true,
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "HTTP: $code\n";
echo "curl_error: " . ($err ?: '(ninguno)') . "\n";
echo "respuesta (primeros 350): " . substr($res, 0, 350) . "\n";
