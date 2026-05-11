<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../_internals/init.php';
require_once __DIR__ . '/../_internals/dmarc_add.php';

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

if (str_ends_with($file, '.xml.gz')) {
    $content = gzdecode($content);
    if ($content === false) {
        die('Failed to decompress');
    }
} else if (str_ends_with($file, '.zip')) {
    // For google.
    $zip = new ZipArchive();
    $res = $zip->open($file, ZipArchive::RDONLY);
    if ($res !== true) {
        die('Failed to open zip: ' . $file);
    }
    $first_item_name = $zip->getNameIndex(0);
    if (!str_ends_with($first_item_name, '.xml')) {
        die('First item in zip is not xml: ' . $first_item_name);
    }
    $content = $zip->getFromIndex(0);
    $zip->close();
}

$rpt = new SimpleXMLElement($content);
$db = get_db();

try {
    $res = dmarc_add($db, $rpt);
    if ($res === false) {
        die('Failed to add dmarc report');
    }
} catch (\Throwable $t) {
    \Sentry\captureException($t);
    die('Exception: ' . $t);
}
