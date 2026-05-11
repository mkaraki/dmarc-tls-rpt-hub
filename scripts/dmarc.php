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

$transactionContext = \Sentry\Tracing\TransactionContext::make()
    ->setName('DMARC harvest command')
    ->setOp('cli.dmarc');
$transaction = \Sentry\startTransaction($transactionContext);
\Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

function get_dmarc_file($parts, $mbox, $msgno, $parentsection = ""): array|false{
    return get_mbox_file_with_ext(
        ['.xml', '.xml.gz', '.zip'],
        $parts,
        $mbox,
        $msgno,
        $parentsection
    );
}


try {
    $mbox = imap_open(DMARC_IMAP_URL, DMARC_IMAP_USER, DMARC_IMAP_PASS);

    if ($mbox === false) {
        $errors = imap_last_error();
        $errors = $errors === false ? 'NO ERROR' : $errors;
        die('Failed to open mailbox: ' . $errors);
    }
    
    $mbox_len = imap_num_msg($mbox);
    if ($mbox_len === false) {
        $errors = imap_last_error();
        $errors = $errors === false ? 'NO ERROR' : $errors;
        die('Failed to get mailbox length: ' . $errors);
    }

    // This is verbose
    //if ($mbox_len < 1) {
    //    print('No messages found');
    //    exit();
    //}
    
    for ($i = 0; $i < $mbox_len; $i++) {
        $mail = imap_fetchstructure($mbox, $i + 1);
        if ($mail === false) {
            $errors = imap_last_error();
            $errors = $errors === false ? 'NO ERROR' : $errors;
            print('Failed to fetch message ' . $i+1 . ': ' . $errors);
            continue;
        }
        
        if (!isset($mail->parts) || !$mail->parts) {
            //print('Message ' . $i+1 . ' has no parts');
            continue;
        }
        
        $rpt = get_dmarc_file($mail->parts, $mbox, $i + 1, "");
        if ($rpt === false) {
            //print('Message ' . $i+1 . ' has no DMARC attachments');
            continue;
        }
        
        print("Processing: " . $rpt[0] . "\n");
        
        if (str_ends_with($rpt[0], '.xml.gz')) {
            $rpt[1] =  gzdecode(base64_decode($rpt[1]));
            if ($rpt[1] === false) {
                print('Failed to decompress');
                continue;
            }
        } else if (str_ends_with($rpt[0], '.zip')) {
            // For google.
            $temp_file = tempnam(sys_get_temp_dir(), strval(random_int(PHP_INT_MIN, PHP_INT_MAX)));
            try {
                file_put_contents($temp_file, base64_decode($rpt[1]));
                
                $zip = new ZipArchive();
                $res = $zip->open($temp_file, ZipArchive::RDONLY);
                if ($res !== true) {
                    print('Failed to open zip: ' . $temp_file);
                    continue;
                }
                $first_item_name = $zip->getNameIndex(0);
                if (!str_ends_with($first_item_name, '.xml')) {
                    //print('First item in zip is not xml: ' . $first_item_name);
                    continue;
                }
                $rpt[1] = $zip->getFromIndex(0);
                $zip->close();
            } finally {
                unlink($temp_file);
            }
        }

        $rpt = new SimpleXMLElement($rpt[1]);
        $db = get_db();
        
        $res = dmarc_add($db, $rpt);
        if ($res === false) {
            print('Failed to add dmarc report');
            continue;
        }
        
        if (PURGE_AFTER_HARVEST_DMARC) {
            $_ = imap_delete($mbox, $i + 1);
        }
    }
} catch (\Throwable $exception) {
    \Sentry\captureException($exception);
    throw $exception;
} finally {
    if ($mbox !== false) {
        $_ = imap_close($mbox, CL_EXPUNGE);
    }

    $transaction->finish();
}