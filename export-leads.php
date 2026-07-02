<?php
// Exporta leads.csv en CSV limpio para MailerLite (email,phone · sin BOM · sin duplicados · emails válidos).
// Protegido con ?k= (panel.key). Borrar tras usar (contiene datos personales).
$base = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
$kf = '';
foreach (['/panel.key','/panel.key.txt','/panel.txt'] as $c) { if (is_file($base.$c)) { $kf = $base.$c; break; } }
$key = $kf ? trim(preg_replace('/^\xEF\xBB\xBF/','', file_get_contents($kf)), " \t\n\r\0\x0B\"'") : '';
$g = isset($_GET['k']) ? trim($_GET['k']) : '';
if ($key === '' || $g !== $key) { http_response_code(403); header('Content-Type:text/plain'); echo '403'; exit; }

$file = $base . '/leads.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads-mailerlite.csv"');
echo "email,phone\n";
if (!is_file($file)) exit;

$seen = [];
if (($h = fopen($file, 'r')) !== false) {
  while (($r = fgetcsv($h)) !== false) {
    if (count($r) < 2) continue;
    // columnas: 0=fecha, 1=email, 2=telefono, 3=ip, 4=navegador
    $email = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $r[1])));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue; // salta cabecera y basura
    if (isset($seen[$email])) continue;                       // sin duplicados
    $seen[$email] = 1;
    $phone = isset($r[2]) ? trim($r[2]) : '';
    echo '"' . str_replace('"','""',$email) . '","' . str_replace('"','""',$phone) . "\"\n";
  }
  fclose($h);
}
