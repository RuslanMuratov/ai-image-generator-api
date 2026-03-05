<?php
// Version: 1.0.0 — 2026-03-05
// File: app/core/pdo.php
// uses: env.DB_DSN, env.DB_USER, env.DB_PASS

// === PDO init START ===
$dsn  = (string)($config['env']['DB_DSN'] ?? '');
$user = (string)($config['env']['DB_USER'] ?? '');
$pass = (string)($config['env']['DB_PASS'] ?? '');

if ($dsn === '') {
  respond(500, [
    'ok' => false,
    'error_code' => 'server_error',
    'message' => 'DB_DSN is not configured.'
  ]);
}

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  respond(500, [
    'ok' => false,
    'error_code' => 'server_error',
    'message' => 'Failed to connect to database.'
  ]);
}
// === PDO init END ===