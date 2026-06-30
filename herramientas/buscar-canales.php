<?php
header('Content-Type: application/json; charset=utf-8');

$keyfile = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data/youtube.key';
$key = is_file($keyfile) ? trim(file_get_contents($keyfile)) : '';
if ($key === '') { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'falta_api_key']); exit; }

// --- Caché de respuestas (ahorra cuota de la API) ---
$cacheDir = dirname($_SERVER['DOCUMENT_ROOT']) . '/asm-data/cache';
$TTL = 21600; // 6 h
$ckey = sha1((isset($_GET['mode'])?$_GET['mode']:'niche').'|'.(isset($_GET['q'])?$_GET['q']:'').'|'.(isset($_GET['ref'])?$_GET['ref']:'').'|'.(isset($_GET['recent'])?$_GET['recent']:''));
$cfile = $cacheDir . '/' . $ckey . '.json';
if (is_file($cfile) && (time() - filemtime($cfile) < $TTL)) { echo file_get_contents($cfile); exit; }
function cache_out($cfile, $arr){
  $j = json_encode($arr);
  if (!empty($arr['ok'])) { @mkdir(dirname($cfile), 0775, true); @file_put_contents($cfile, $j); }
  echo $j; exit;
}

function yt($url){
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15, CURLOPT_SSL_VERIFYPEER=>true]);
  $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  return $c===200 ? json_decode($r,true) : null;
}
$API = 'https://www.googleapis.com/youtube/v3/';

function stats_for($ids, $key, $API){
  if(!$ids) return [];
  $vr = yt($API.'videos?part=snippet,statistics&id='.implode(',',$ids).'&key='.$key);
  $out=[];
  if($vr && isset($vr['items'])) foreach($vr['items'] as $it){
    $out[]=[
      'title'=>$it['snippet']['title'],
      'channel'=>$it['snippet']['channelTitle'],
      'views'=>isset($it['statistics']['viewCount'])?(int)$it['statistics']['viewCount']:0,
      'id'=>$it['id'],
      'thumb'=>isset($it['snippet']['thumbnails']['medium']['url'])?$it['snippet']['thumbnails']['medium']['url']:''
    ];
  }
  usort($out, function($a,$b){return $b['views']-$a['views'];});
  return $out;
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'niche';

if ($mode === 'channel') {
  $ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';
  if ($ref==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'sin_ref']); exit; }
  // Resolver channelId
  $cid='';
  if (preg_match('~(UC[0-9A-Za-z_\-]{22})~', $ref, $m)) { $cid=$m[1]; }
  else {
    $handle = $ref;
    if (preg_match('~@([A-Za-z0-9_\.\-]+)~', $ref, $m)) $handle='@'.$m[1];
    $cr = yt($API.'channels?part=id&forHandle='.urlencode(ltrim($handle,'@')).'&key='.$key);
    if ($cr && !empty($cr['items'])) $cid=$cr['items'][0]['id'];
    if ($cid==='') { // fallback: búsqueda de canal
      $sr = yt($API.'search?part=snippet&type=channel&maxResults=1&q='.urlencode($ref).'&key='.$key);
      if ($sr && !empty($sr['items'])) $cid=$sr['items'][0]['id']['channelId'];
    }
  }
  if ($cid==='') { echo json_encode(['ok'=>false,'error'=>'canal_no_encontrado']); exit; }
  // Info del canal
  $info = yt($API.'channels?part=snippet,statistics&id='.$cid.'&key='.$key);
  $chan=null;
  if ($info && !empty($info['items'])) {
    $c=$info['items'][0];
    $chan=['title'=>$c['snippet']['title'],
           'subs'=>isset($c['statistics']['subscriberCount'])?(int)$c['statistics']['subscriberCount']:0,
           'thumb'=>isset($c['snippet']['thumbnails']['default']['url'])?$c['snippet']['thumbnails']['default']['url']:''];
  }
  // Vídeos más populares del canal
  $sr = yt($API.'search?part=snippet&type=video&order=viewCount&maxResults=15&channelId='.$cid.'&key='.$key);
  $ids=[]; if($sr && isset($sr['items'])) foreach($sr['items'] as $it) if(isset($it['id']['videoId'])) $ids[]=$it['id']['videoId'];
  cache_out($cfile, ['ok'=>true,'channel'=>$chan,'items'=>stats_for($ids,$key,$API)]);
}

// mode = niche
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q==='') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'sin_query']); exit; }
$recent = !isset($_GET['recent']) || $_GET['recent']!=='0';
$url = $API.'search?part=snippet&type=video&order=viewCount&maxResults=15&q='.urlencode($q).'&key='.$key;
if ($recent) $url .= '&publishedAfter='.gmdate('Y-m-d\TH:i:s\Z', time()-30*86400);
$sr = yt($url);
if (!$sr || !isset($sr['items'])) { http_response_code(502); echo json_encode(['ok'=>false,'error'=>'youtube']); exit; }
$ids=[]; foreach($sr['items'] as $it) if(isset($it['id']['videoId'])) $ids[]=$it['id']['videoId'];
cache_out($cfile, ['ok'=>true,'items'=>stats_for($ids,$key,$API)]);
