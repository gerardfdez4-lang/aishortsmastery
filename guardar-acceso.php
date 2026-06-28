<?php
header('Content-Type: application/json; charset=utf-8');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'email_invalido']);
  exit;
}

$file = __DIR__ . '/compras.csv';
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
  'NO',                       // marca aquí "SI" cuando lo invites en Notion
  isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
]);
fclose($fh);

echo json_encode(['ok' => true]);
