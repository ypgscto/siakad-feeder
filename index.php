<?php

/**
 * Redirect jika Apache tidak memproses .htaccess (akses tanpa /public).
 */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base = rtrim($scriptDir, '/');
$target = ($base === '' ? '' : $base).'/public/';

header('Location: '.$target, true, 301);
exit;
