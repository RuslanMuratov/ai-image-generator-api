<?php
// Version: 1.0.0 — 2026-03-05
// File: app/core/api_guard.php
// uses: none

// === API guard START ===
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'OPTIONS') {
  header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  header('Access-Control-Max-Age: 600');
  http_response_code(204);
  exit;
}

header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// === API guard END ===