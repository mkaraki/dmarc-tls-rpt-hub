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

$transactionContext = \Sentry\Tracing\TransactionContext::make()
    ->setName('TLS-RPT harvest command')
    ->setOp('cli.tls-rpt');
$transaction = \Sentry\startTransaction($transactionContext);
\Sentry\SentrySdk::getCurrentHub()->setSpan($transaction);

function get_rpt_file($parts, $mbox, $msgno, $parentsection = ""): array|false{
    return get_mbox_file_with_ext(
        ['.json', '.json.gz'],
        $parts,
        $mbox,
        $msgno,
        $parentsection
    );
}


try {
    $db = get_db();
    
    $mbox = imap_open(TLS_RPT_IMAP_URL, TLS_RPT_IMAP_USER, TLS_RPT_IMAP_PASS);

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
        
        $rpt = get_rpt_file($mail->parts, $mbox, $i + 1, "");
        if ($rpt === false) {
            //print('Message ' . $i+1 . ' has no TLS-RPT attachments');
            continue;
        }
        
        if (str_ends_with($rpt[0], '.json.gz')) {
            $rpt[1] = gzdecode(base64_decode($rpt[1]));
            if ($rpt[1] === false) {
                print('Failed to decompress attachment in message ' . $i+1);
                continue;
            }
        }
        
        $rpt = json_decode($rpt[1], true);
        
        $res = tls_rpt_add($db, $rpt);
        if ($res === false) {
            print('Failed to add tls rpt');
            continue;
        }
        
        if (PURGE_AFTER_HARVEST_TLS_RPT) {
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