<?php
// Detecta el país del visitante y dice si es LATAM. Cachea por IP 30 días.
// Prueba manual: ?cc=MX
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cc = '';
if (isset($_GET['cc'])) { $cc = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $_GET['cc']), 0, 2)); }
if ($cc === '' && !empty($_SERVER['HTTP_CF_IPCOUNTRY'])) { $cc = strtoupper($_SERVER['HTTP_CF_IPCOUNTRY']); }

function client_ip() {
  foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
    if (!empty($_SERVER[$k])) {
      $ip = trim(explode(',', $_SERVER[$k])[0]);
      if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
  }
  return '';
}

if ($cc === '' && ($cc === '' || strlen($cc) !== 2)) {
  $ip = client_ip();
  if ($ip !== '') {
    $dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data/geocache';
    @mkdir($dir, 0775, true);
    $cf = $dir . '/' . md5($ip) . '.txt';
    if (is_file($cf) && (time() - filemtime($cf) < 30 * 86400)) {
      $cc = trim((string)file_get_contents($cf));
    } elseif (function_exists('curl_init')) {
      $ch = curl_init('https://ipwho.is/' . $ip . '?fields=country_code');
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_SSL_VERIFYPEER => true]);
      $r = curl_exec($ch); curl_close($ch);
      $j = json_decode($r, true);
      if (is_array($j) && !empty($j['country_code'])) { $cc = strtoupper($j['country_code']); @file_put_contents($cf, $cc); }
    }
  }
}

$latam = ['MX','CO','AR','PE','CL','EC','VE','GT','BO','PY','UY','DO','CR','PA','HN','SV','NI','CU'];
echo json_encode(['cc' => $cc, 'latam' => in_array($cc, $latam, true)]);
