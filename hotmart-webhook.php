<?php
// Recibe el webhook/postback de Hotmart y registra la compra del curso en eventos.csv (para el panel).
header('Content-Type: text/plain; charset=utf-8');
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// --- validación por token (hottok) si existe asm-data/hotmart.token ---
$tokfile = $base . '/hotmart.token';
$tok = is_file($tokfile) ? trim((string)file_get_contents($tokfile)) : '';
$hottok = '';
if (isset($_GET['hottok'])) $hottok = $_GET['hottok'];
elseif (isset($_SERVER['HTTP_X_HOTMART_HOTTOK'])) $hottok = $_SERVER['HTTP_X_HOTMART_HOTTOK'];
elseif (is_array($data) && isset($data['hottok'])) $hottok = $data['hottok'];
if ($tok !== '' && $hottok !== $tok) { http_response_code(403); echo 'token_invalido'; exit; }

// --- detectar compra aprobada (cubre formatos v1 y 2.0 de Hotmart) ---
$status = ''; $event = ''; $email = ''; $prod = '';
if (is_array($data)) {
  $event  = isset($data['event']) ? (string)$data['event'] : '';
  $status = isset($data['status']) ? (string)$data['status'] : '';
  if ($status === '' && isset($data['data']['purchase']['status'])) $status = (string)$data['data']['purchase']['status'];
  if (isset($data['email'])) $email = (string)$data['email'];
  elseif (isset($data['data']['buyer']['email'])) $email = (string)$data['data']['buyer']['email'];
  if (isset($data['prod_name'])) $prod = (string)$data['prod_name'];
  elseif (isset($data['data']['product']['name'])) $prod = (string)$data['data']['product']['name'];
  elseif (isset($data['product']['name'])) $prod = (string)$data['product']['name'];
}
$hay = $status . ' ' . $event;
$aprobada = (stripos($hay, 'APPROVED') !== false) || (stripos($hay, 'COMPLETE') !== false) || (stripos($hay, 'PURCHASE_APPROVED') !== false);
$es_toolkit = (stripos($prod, 'herramient') !== false) || (stripos($prod, 'toolkit') !== false);

// --- registrar en eventos.csv (mismo formato que track.php) ---
$file = $base . '/eventos.csv';
$ev = $aprobada ? ($es_toolkit ? 'compra_toolkit' : 'compra_curso') : 'hotmart_ping';
$row = [date('c'), $ev, '/hotmart', substr($email, 0, 40), substr($prod ?: ($status ?: $event), 0, 40), 'hotmart'];
$row = array_map(function ($x) { return '"' . str_replace('"', '""', $x) . '"'; }, $row);
$new = !file_exists($file);
$fp = @fopen($file, 'a');
if ($fp) { if ($new) fwrite($fp, "fecha,evento,pagina,sesion,meta,referrer\n"); fwrite($fp, implode(',', $row) . "\n"); fclose($fp); }

http_response_code(200);
echo 'ok';
