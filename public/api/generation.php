<?php
// Version: 1.0.4 — 2026-03-05
// File: public/api/generation.php
// uses: env.API_AUTH_TOKEN, env.DB_DSN, env.DB_USER, env.DB_PASS, env.STORAGE_PUBLIC_BASE_URL, env.POLLINATIONS_BASE_URL, env.POLLINATIONS_API_KEY, env.POLLINATIONS_PRIVACY_MODE, env.BILLING_ENABLED, env.REQUIRE_AUTH, env.SAVE_FILES, env.SAVE_DB, generator.models, generator.default_model_id, generator.aspect_ratios, generator.default_aspect_ratio_id

require __DIR__ . '/../../app/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond($httpCode, $payload) {
  http_response_code($httpCode);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Simple local API guard (generation) ---
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, [
    'ok' => false,
    'error_code' => 'method_not_allowed',
    'message' => 'Only POST is allowed'
  ]);
}
// --- End simple guard ---

// Global API guard: blocks direct access, cross-site requests, and bots
require __DIR__ . '/../../app/core/api_guard.php';
// End global API guard

$configPath = __DIR__ . '/../../config/config.php';
$config = require $configPath;

$gen = $config['generator'] ?? [];
$api = $config['api']['pollinations'] ?? [];
$features = $config['features'] ?? [];

// Auth (optional)
require __DIR__ . '/../../app/core/auth.php';

// DB (optional)
$pdo = null;
if (!empty($features['save_db'])) {
  require __DIR__ . '/../../app/core/pdo.php';
}

// Billing (optional)
$userId = (int)($GLOBALS['api_user_id'] ?? 0);

if (!empty($features['billing_enabled']) && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $balance = (is_array($row) && isset($row['balance'])) ? (int)$row['balance'] : 0;

    if ($balance < 1) {
      respond(402, [
        'ok' => false,
        'error_code' => 'insufficient_balance',
        'message' => 'Insufficient balance.'
      ]);
    }
  } catch (Throwable $e) {
    respond(500, [
      'ok' => false,
      'error_code' => 'server_error',
      'message' => 'Failed to check balance.'
    ]);
  }
}

function s($v) { return is_string($v) ? trim($v) : ''; }

function is_yes_no($v) {
  $v = is_string($v) ? strtolower(trim($v)) : '';
  return ($v === 'yes' || $v === 'no') ? $v : null;
}

function ensure_dir($dir) {
  if (is_dir($dir)) return true;
  return @mkdir($dir, 0775, true);
}

function uuid_v4() {
  $bytes = random_bytes(16);
  $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
  $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
  $hex = bin2hex($bytes);
  return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
}

function http_get_binary($url, $headers = []) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_TIMEOUT, 120);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

  $caPath = __DIR__ . '/../../config/cacert.pem';
  if (is_file($caPath)) {
    curl_setopt($ch, CURLOPT_CAINFO, $caPath);
  }

  if (!empty($headers)) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  }

  $body = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);

  return [$code, $ct, $body, $err];
}

/**
 * Read JSON body
 */
$raw = file_get_contents('php://input');
if (!is_string($raw) || trim($raw) === '') {
  respond(400, [
    'ok' => false,
    'error_code' => 'invalid_input',
    'message' => 'Empty JSON body'
  ]);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  respond(400, [
    'ok' => false,
    'error_code' => 'invalid_input',
    'message' => 'Invalid JSON'
  ]);
}

/**
 * Validate input by contract
 */
$prompt = s($data['prompt'] ?? '');
if ($prompt === '') {
  respond(400, [
    'ok' => false,
    'error_code' => 'invalid_input',
    'message' => 'prompt is required'
  ]);
}

/**
 * Provider config
 */
$pollinationsBase = (isset($api['base_url']) && is_string($api['base_url']) && trim($api['base_url']) !== '')
  ? rtrim(trim($api['base_url']), '/')
  : 'https://gen.pollinations.ai';

$apiKey = (isset($api['key']) && is_string($api['key'])) ? trim($api['key']) : '';

$privacyMode = isset($api['privacy_mode']) && is_string($api['privacy_mode']) ? strtolower(trim($api['privacy_mode'])) : 'no';
if ($privacyMode !== 'yes' && $privacyMode !== 'no') $privacyMode = 'no';

/**
 * Closed list: models (from config)
 */
$modelsArr = $api['models'] ?? [];

$defaultModelId = isset($api['default_model_id']) ? (string)$api['default_model_id'] : '';
if ($defaultModelId === '' && isset($gen['default_model_id'])) {
  $defaultModelId = (string)$gen['default_model_id'];
}

$allowedModels = [];
if (is_array($modelsArr)) {
  foreach ($modelsArr as $m) {
    if (!is_array($m)) continue;
    $id = isset($m['id']) ? (string)$m['id'] : '';
    if ($id !== '') $allowedModels[$id] = true;
  }
}

$model = s($data['model'] ?? '');
if ($model === '' || empty($allowedModels[$model])) {
  if ($defaultModelId !== '' && !empty($allowedModels[$defaultModelId])) {
    $model = $defaultModelId;
  } else {
    $model = 'flux';
  }
}

/**
 * Closed list: aspect ratios (from config)
 */
$ratioCfg = $gen['aspect_ratios'] ?? [];
$cfgDefaultRatio = isset($gen['default_aspect_ratio_id']) ? (string)$gen['default_aspect_ratio_id'] : '1:1';

$ratioMap = [];
if (is_array($ratioCfg)) {
  foreach ($ratioCfg as $r) {
    if (!is_array($r)) continue;
    $id = isset($r['id']) ? (string)$r['id'] : '';
    $w  = isset($r['w']) ? (int)$r['w'] : 0;
    $h  = isset($r['h']) ? (int)$r['h'] : 0;
    $label = isset($r['label']) ? (string)$r['label'] : '';
    if ($id === '' || $w <= 0 || $h <= 0) continue;
    $ratioMap[$id] = ['w' => $w, 'h' => $h, 'label' => $label];
  }
}

$aspectRatio = s($data['aspect_ratio'] ?? '');
if ($aspectRatio === '' || empty($ratioMap[$aspectRatio])) {
  $aspectRatio = (!empty($ratioMap[$cfgDefaultRatio])) ? $cfgDefaultRatio : '1:1';
}

$width  = $ratioMap[$aspectRatio]['w'] ?? 1024;
$height = $ratioMap[$aspectRatio]['h'] ?? 1024;

/**
 * Seed
 */
$seed = $data['seed'] ?? null;
if ($seed === null || $seed === '' || (is_string($seed) && trim($seed) === '')) {
  $seed = random_int(1, 2000000000);
} else {
  $seed = is_numeric($seed) ? (int)$seed : random_int(1, 2000000000);
  if ($seed <= 0) $seed = random_int(1, 2000000000);
}

/**
 * Privacy (contract requires yes/no)
 */
$isPrivate = is_yes_no($data['is_private'] ?? 'no');
if ($isPrivate === null) $isPrivate = 'no';
if ($privacyMode === 'yes') {
  $isPrivate = 'yes';
}

/**
 * Provider call (Pollinations Image API)
 */
$encodedPrompt = rawurlencode($prompt);

$q = http_build_query([
  'model'   => $model,
  'width'   => $width,
  'height'  => $height,
  'seed'    => $seed,
  'private' => ($isPrivate === 'yes') ? 'true' : 'false',
], '', '&', PHP_QUERY_RFC3986);

$providerUrl = $pollinationsBase . '/image/' . $encodedPrompt . '?' . $q;

$headers = [];
if ($apiKey !== '') {
  $headers[] = 'Authorization: Bearer ' . $apiKey;
}

list($httpCode, $contentType, $bin, $curlErr) = http_get_binary($providerUrl, $headers);

if ($bin === false || $bin === null || $bin === '' || $httpCode < 200 || $httpCode >= 300) {
  $msg = 'Provider error (HTTP ' . $httpCode . ')';
  if (is_string($curlErr) && $curlErr !== '') $msg .= ': ' . $curlErr;

  if ($httpCode === 401 || $httpCode === 403) {
    respond(502, [
      'ok' => false,
      'error_code' => 'auth_failed',
      'message' => 'Provider authorization failed. Check API key.'
    ]);
  }

  if ($httpCode === 429) {
    respond(429, [
      'ok' => false,
      'error_code' => 'rate_limit',
      'message' => 'Rate limit. Please wait and retry.',
      'retry_after' => 15
    ]);
  }

  respond(502, [
    'ok' => false,
    'error_code' => 'provider_error',
    'message' => $msg
  ]);
}

$ctLower = strtolower((string)$contentType);
if ($ctLower !== '' && strpos($ctLower, 'image/') === false) {
  respond(502, [
    'ok' => false,
    'error_code' => 'provider_error',
    'message' => 'Provider returned non-image response'
  ]);
}

/**
 * Save files (optional)
 */
$imageId = 0;
$uuid = uuid_v4();
$relPath = '';
$relOriginalPath = '';
$fileSize = 0;

if (!empty($features['save_files'])) {
  if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp') || !function_exists('imagejpeg') || !function_exists('imagecreatetruecolor')) {
    respond(500, [
      'ok' => false,
      'error_code' => 'server_error',
      'message' => 'GD extension with WebP + JPG support is required'
    ]);
  }

  $im = @imagecreatefromstring($bin);
  if (!$im) {
    respond(500, [
      'ok' => false,
      'error_code' => 'generation_failed',
      'message' => 'Failed to decode provider image'
    ]);
  }

  $yyyy = date('Y');
  $mm   = date('m');

  $relDir = '/uploads/ai/' . $yyyy . '/' . $mm;

  $fileNameWebp = 'ai-' . $uuid . '.webp';
  $fileNameJpg  = 'ai-' . $uuid . '.jpg';

  $relPath = $relDir . '/' . $fileNameWebp;
  $relOriginalPath = $relDir . '/' . $fileNameJpg;

  $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
  if ($docRoot === '') {
    imagedestroy($im);
    respond(500, [
      'ok' => false,
      'error_code' => 'server_error',
      'message' => 'DOCUMENT_ROOT is not set'
    ]);
  }

  $absDir  = $docRoot . $relDir;
  $absPath = $docRoot . $relPath;
  $absOriginalPath = $docRoot . $relOriginalPath;

  if (!ensure_dir($absDir)) {
    imagedestroy($im);
    respond(500, [
      'ok' => false,
      'error_code' => 'server_error',
      'message' => 'Cannot create uploads directory'
    ]);
  }

  $qualityWebp = 80;
  $okSaveWebp = @imagewebp($im, $absPath, $qualityWebp);

  if (!$okSaveWebp || !is_file($absPath) || filesize($absPath) <= 0) {
    imagedestroy($im);
    respond(500, [
      'ok' => false,
      'error_code' => 'generation_failed',
      'message' => 'Failed to save WebP file'
    ]);
  }

  $w = imagesx($im);
  $h = imagesy($im);

  $bg = imagecreatetruecolor($w, $h);
  $white = imagecolorallocate($bg, 255, 255, 255);
  imagefilledrectangle($bg, 0, 0, $w, $h, $white);
  imagecopy($bg, $im, 0, 0, 0, 0, $w, $h);

  $qualityJpg = 100;
  $okSaveJpg = @imagejpeg($bg, $absOriginalPath, $qualityJpg);

  imagedestroy($bg);
  imagedestroy($im);

  if (!$okSaveJpg || !is_file($absOriginalPath) || filesize($absOriginalPath) <= 0) {
    if (is_file($absPath)) @unlink($absPath);
    respond(500, [
      'ok' => false,
      'error_code' => 'generation_failed',
      'message' => 'Failed to save original JPG file'
    ]);
  }

  $fileSize = (int)filesize($absPath);
}

/**
 * DB insert (optional)
 */
if (!empty($features['save_db']) && $pdo instanceof PDO) {
  $userIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

  $absSavedWebp = '';
  $absSavedJpg  = '';

  if (!empty($features['save_files'])) {
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $absSavedWebp = ($docRoot !== '' && $relPath !== '') ? ($docRoot . $relPath) : '';
    $absSavedJpg  = ($docRoot !== '' && $relOriginalPath !== '') ? ($docRoot . $relOriginalPath) : '';
  }

  $stmt = $pdo->prepare("
    INSERT INTO ai_images (
      uuid,
      user_id,
      user_ip,
      provider,
      provider_model,
      prompt,
      negative_prompt,
      seed,
      width,
      height,
      aspect_ratio,
      file_path,
      original_file_path,
      original_file_url,
      file_url,
      file_size,
      is_private,
      status
    ) VALUES (
      :uuid,
      :user_id,
      :user_ip,
      :provider,
      :provider_model,
      :prompt,
      :negative_prompt,
      :seed,
      :width,
      :height,
      :aspect_ratio,
      :file_path,
      :original_file_path,
      :original_file_url,
      :file_url,
      :file_size,
      :is_private,
      'generated'
    )
  ");

  $stmt->execute([
    ':uuid'               => $uuid,
    ':user_id'            => $userId,
    ':user_ip'            => $userIp,
    ':provider'           => 'pollinations',
    ':provider_model'     => $model,
    ':prompt'             => $prompt,
    ':negative_prompt'    => null,
    ':seed'               => $seed,
    ':width'              => $width,
    ':height'             => $height,
    ':aspect_ratio'       => $aspectRatio,
    ':file_path'          => $absSavedWebp,
    ':original_file_path' => $absSavedJpg,
    ':original_file_url'  => $relOriginalPath,
    ':file_url'           => $relPath,
    ':file_size'          => $fileSize,
    ':is_private'         => $isPrivate,
  ]);

  $imageId = (int)$pdo->lastInsertId();
}

/**
 * Debit balance (optional)
 */
if (!empty($features['billing_enabled']) && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare("
      UPDATE users
      SET balance = balance - 1
      WHERE id = :id AND balance >= 1
      LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);

    if ($stmt->rowCount() !== 1) {
      respond(402, [
        'ok' => false,
        'error_code' => 'insufficient_balance',
        'message' => 'Insufficient balance.'
      ]);
    }
  } catch (Throwable $e) {
    respond(500, [
      'ok' => false,
      'error_code' => 'server_error',
      'message' => 'Failed to debit balance.'
    ]);
  }
}

respond(200, [
  'ok' => true,
  'image_id' => $imageId,
  'image_uuid' => $uuid,
  'image_url' => $relPath,
  'meta' => [
    'model' => $model,
    'width' => $width,
    'height' => $height,
    'aspect_ratio' => $aspectRatio,
    'seed' => $seed
  ]
]);