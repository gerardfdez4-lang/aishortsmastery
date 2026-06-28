<?php
header('Content-Type: application/json; charset=utf-8');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'email_invalido']);
  exit;
}

// Guardar FUERA de la carpeta web (sobrevive a los despliegues de Git y no es accesible por web)
$dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$file = (is_dir($dir) && is_writable($dir)) ? $dir . '/compras.csv' : __DIR__ . '/compras.csv';

$nuevo = !file_exists($file);
$fh = fopen($file, 'a');
if ($fh === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'no_escritura']);
  exit;
}

if ($nuevo) {
  fwrite($fh, "\xEF\xBB\xBF");
  fputcsv($fh, ['fecha', 'email_notion', 'invitado', 'ip']);
}

fputcsv($fh, [
  date('Y-m-d H:i:s'),
  $email,
  'NO',
  isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
]);
fclose($fh);

echo json_encode(['ok' => true]);
