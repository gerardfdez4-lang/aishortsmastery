<?php
// Tracker first-party, cookieless, sin IP. Registra eventos del embudo en asm-data/eventos.csv
header('Access-Control-Allow-Origin: *');
$dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data';
if (!is_dir($dir)) { $dir = __DIR__; }
$file = $dir . '/eventos.csv';

function val($k){ return isset($_POST[$k]) ? $_POST[$k] : (isset($_GET[$k]) ? $_GET[$k] : ''); }

$e = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', val('e')), 0, 40);
if ($e === '') { http_response_code(204); exit; }

$row = [
  date('c'),
  $e,
  substr(val('p'), 0, 120),   // página
  substr(val('s'), 0, 40),    // sesión (sessionStorage, no cookie)
  substr(val('m'), 0, 60),    // meta
  substr(val('r'), 0, 180),   // referrer
];
$row = array_map(function($x){ return '"' . str_replace('"', '""', $x) . '"'; }, $row);

$new = !file_exists($file);
$fp = @fopen($file, 'a');
if ($fp) {
  if ($new) { fwrite($fp, "fecha,evento,pagina,sesion,meta,referrer\n"); }
  fwrite($fp, implode(',', $row) . "\n");
  fclose($fp);
}
http_response_code(204);
