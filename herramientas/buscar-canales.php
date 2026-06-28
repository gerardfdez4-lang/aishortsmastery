<?php
header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'sin_query']); exit; }

// La API key se guarda FUERA de la web (no en el repo, no descargable)
$keyfile = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data/youtube.key';
$key = is_file($keyfile) ? trim(file_get_contents($keyfile)) : '';
if ($key === '') { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'falta_api_key']); exit; }

function fetch_json($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>true]);
  $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if ($code !== 200) return null;
  return json_decode($res, true);
}

// 1) Buscar vídeos por el nicho/keyword, ordenados por visitas
$search = 'https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&maxResults=15&order=viewCount&q=' . urlencode($q) . '&key=' . $key;
$sr = fetch_json($search);
if (!$sr || !isset($sr['items'])) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'youtube']); exit; }

$ids = [];
foreach ($sr['items'] as $it) { if (isset($it['id']['videoId'])) $ids[] = $it['id']['videoId']; }
if (!$ids) { echo json_encode(['ok'=>true,'items'=>[]]); exit; }

// 2) Estadísticas (visitas) de esos vídeos
$stats = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id=' . implode(',', $ids) . '&key=' . $key;
$vr = fetch_json($stats);
$out = [];
if ($vr && isset($vr['items'])) {
  foreach ($vr['items'] as $it) {
    $out[] = [
      'title'   => $it['snippet']['title'],
      'channel' => $it['snippet']['channelTitle'],
      'views'   => isset($it['statistics']['viewCount']) ? (int)$it['statistics']['viewCount'] : 0,
      'id'      => $it['id'],
      'thumb'   => isset($it['snippet']['thumbnails']['medium']['url']) ? $it['snippet']['thumbnails']['medium']['url'] : '',
    ];
  }
}
usort($out, function($a, $b){ return $b['views'] - $a['views']; });
echo json_encode(['ok'=>true, 'items'=>$out]);
