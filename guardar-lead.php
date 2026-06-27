<?php
header('Content-Type: application/json; charset=utf-8');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$tel   = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';

// validación mínima
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'email_invalido']);
  exit;
}

$file = __DIR__ . '/leads.csv';
$nuevo = !file_exists($file);

$fh = fopen($file, 'a');
if ($fh === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'no_escritura']);
  exit;
}

// cabecera + BOM (para que Excel muestre bien los acentos) la primera vez
if ($nuevo) {
  fwrite($fh, "\xEF\xBB\xBF");
  fputcsv($fh, ['fecha', 'email', 'telefono', 'ip', 'navegador']);
}

fputcsv($fh, [
  date('Y-m-d H:i:s'),
  $email,
  $tel,
  isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
  isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 200) : '',
]);
fclose($fh);

echo json_encode(['ok' => true]);
