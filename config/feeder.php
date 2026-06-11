<?php

return [

    'ws_url' => (string) env('FEEDER_WS_URL', 'http://103.167.35.204:8100/ws/live2.php'),

    'username' => (string) env('FEEDER_USERNAME', ''),

    'password' => (string) env('FEEDER_PASSWORD', ''),

    'timeout' => (int) env('FEEDER_TIMEOUT', 180),

    'token_ttl_seconds' => (int) env('FEEDER_TOKEN_TTL', 300),

    'id_perguruan_tinggi' => (string) env('FEEDER_ID_PERGURUAN_TINGGI', ''),

    'default_id_wilayah' => (string) env('FEEDER_DEFAULT_ID_WILAYAH', '070000'),

];
