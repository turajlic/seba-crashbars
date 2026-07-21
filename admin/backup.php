<?php
declare(strict_types=1);
require __DIR__ . '/../inc/functions.php';
require_login();

$file = (string)($_GET['file'] ?? '');

if ($file === 'current') {
    $path = SEBA_CONTENT;
    $downloadName = 'content-' . date('Ymd-His') . '.json';
} else {
    $path = seba_backup_path($file);
    $downloadName = $file;
}

if ($path === null || !is_file($path)) {
    http_response_code(404);
    exit('Backup nije pronađen.');
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string)filesize($path));
header('Cache-Control: no-store');
readfile($path);
