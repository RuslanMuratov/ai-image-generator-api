<?php
// Version: 1.0.0 — 2026-03-05
// File: app/core/auth.php
// uses: env.API_AUTH_TOKEN, env.REQUIRE_AUTH

// === Auth START ===
$requireAuth = !empty($features['require_auth']);
$expected = (string)($config['env']['API_AUTH_TOKEN'] ?? '');

$GLOBALS['api_user_id'] = 0;

if (!$requireAuth) {
  $GLOBALS['api_user_id'] = 1;
  return;
}

$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (is_string($auth) && stripos($auth, 'Bearer ') === 0) {
  $token = trim(substr($auth, 7));
}

if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
  respond(401, [
    'ok' => false,
    'error_code' => 'unauthorized',
    'message' => 'Missing or invalid API token.'
  ]);
}

$GLOBALS['api_user_id'] = 1;
// === Auth END ===