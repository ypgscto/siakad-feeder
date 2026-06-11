<?php

return [

    'ws_url' => (string) env('FEEDER_WS_URL', 'http://103.167.35.204:8100/ws/live2.php'),

  /*
   * Neo Feeder Windows sering hang pada Content-Type application/xml.
   * JSON (default) lebih stabil — sama seperti aplikasi SiFeeder lama.
   */
    'prefer_json' => filter_var(env('FEEDER_PREFER_JSON', true), FILTER_VALIDATE_BOOL),

    'username' => (string) env('FEEDER_USERNAME', ''),

    'password' => (string) env('FEEDER_PASSWORD', ''),

    'connect_timeout' => (int) env('FEEDER_CONNECT_TIMEOUT', 15),

    'token_timeout' => (int) env('FEEDER_TOKEN_TIMEOUT', 45),

    'timeout' => (int) env('FEEDER_TIMEOUT', 120),

    'write_timeout' => (int) env('FEEDER_WRITE_TIMEOUT', 45),

    'write_retry_attempts' => (int) env('FEEDER_WRITE_RETRY_ATTEMPTS', 2),

    'retry_attempts' => (int) env('FEEDER_RETRY_ATTEMPTS', 3),

    'retry_delay_ms' => (int) env('FEEDER_RETRY_DELAY_MS', 1000),

    'token_ttl_seconds' => (int) env('FEEDER_TOKEN_TTL', 300),

    'id_perguruan_tinggi' => (string) env('FEEDER_ID_PERGURUAN_TINGGI', ''),

    'default_id_wilayah' => (string) env('FEEDER_DEFAULT_ID_WILAYAH', '070000'),

];
