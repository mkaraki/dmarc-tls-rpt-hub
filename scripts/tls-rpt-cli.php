<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../_internals/init.php';
require_once __DIR__ . '/../_internals/tls_rpt_add.php';

\Sentry\init([
    'dsn' => SENTRY_DSN,
    'send_default_pii' => false,
    'traces_sample_rate' => 1.0,
    'enable_logs' => true,
]);

$file = $argv[1] ?? '';

if (!is_file($file)) {
    die('File not found: ' . $file);
}

$content = file_get_contents($file);

if (str_ends_with($file, '.json.gz')) {
    $content = gzdecode($content);
    if ($content === false) {
        die('Failed to decompress');
    }
}

$rpt = json_decode($content, true);

$db = get_db();
try {
    tls_rpt_add($db, $rpt);
} catch (Throwable $e) {
    \Sentry\captureException($e);
    die('Exception: ' . $e);
}