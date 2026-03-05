<?php
// Version: 1.0.0 — 2026-03-05
// File: config/config.php
// uses: env.API_AUTH_TOKEN, env.REQUIRE_AUTH, env.SAVE_FILES, env.SAVE_DB, env.BILLING_ENABLED, env.DB_DSN, env.DB_USER, env.DB_PASS, env.POLLINATIONS_BASE_URL, env.POLLINATIONS_API_KEY, env.POLLINATIONS_PRIVACY_MODE

// === Config START ===
$env = [
  'API_AUTH_TOKEN' => getenv('API_AUTH_TOKEN') !== false ? (string)getenv('API_AUTH_TOKEN') : '',
  'REQUIRE_AUTH'   => getenv('REQUIRE_AUTH') !== false ? (string)getenv('REQUIRE_AUTH') : 'yes',

  'SAVE_FILES'     => getenv('SAVE_FILES') !== false ? (string)getenv('SAVE_FILES') : 'yes',
  'SAVE_DB'        => getenv('SAVE_DB') !== false ? (string)getenv('SAVE_DB') : 'no',
  'BILLING_ENABLED'=> getenv('BILLING_ENABLED') !== false ? (string)getenv('BILLING_ENABLED') : 'no',

  'DB_DSN'         => getenv('DB_DSN') !== false ? (string)getenv('DB_DSN') : '',
  'DB_USER'        => getenv('DB_USER') !== false ? (string)getenv('DB_USER') : '',
  'DB_PASS'        => getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '',

  'POLLINATIONS_BASE_URL' => getenv('POLLINATIONS_BASE_URL') !== false ? (string)getenv('POLLINATIONS_BASE_URL') : 'https://gen.pollinations.ai',
  'POLLINATIONS_API_KEY'  => getenv('POLLINATIONS_API_KEY') !== false ? (string)getenv('POLLINATIONS_API_KEY') : '',
  'POLLINATIONS_PRIVACY_MODE' => getenv('POLLINATIONS_PRIVACY_MODE') !== false ? (string)getenv('POLLINATIONS_PRIVACY_MODE') : 'no',
];

$features = [
  'require_auth'    => (strtolower(trim($env['REQUIRE_AUTH'])) === 'yes'),
  'save_files'      => (strtolower(trim($env['SAVE_FILES'])) === 'yes'),
  'save_db'         => (strtolower(trim($env['SAVE_DB'])) === 'yes'),
  'billing_enabled' => (strtolower(trim($env['BILLING_ENABLED'])) === 'yes'),
];

return [
  'env' => $env,
  'features' => $features,
  'api' => [
    'pollinations' => [
      'base_url' => $env['POLLINATIONS_BASE_URL'],
      'key' => $env['POLLINATIONS_API_KEY'],
      'privacy_mode' => $env['POLLINATIONS_PRIVACY_MODE'],
      'default_model_id' => 'flux',
      'models' => [
        ['id' => 'flux', 'label' => 'Flux'],
      ],
    ],
  ],
  'generator' => [
    'default_model_id' => 'flux',
    'default_aspect_ratio_id' => '16:9',
    'aspect_ratios' => [
      ['id' => '1:1',  'label' => '1:1',  'w' => 1024, 'h' => 1024],
      ['id' => '16:9', 'label' => '16:9', 'w' => 1280, 'h' => 720],
      ['id' => '9:16', 'label' => '9:16', 'w' => 720,  'h' => 1280],
    ],
  ],
];
// === Config END ===