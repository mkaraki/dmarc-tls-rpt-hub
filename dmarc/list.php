<?php
require_once __DIR__ . '/../_internals/init.php';

$db = get_db();

$p = intval($_GET['p'] ?? '1');
if ($p < 1) $p = 1;
$offset = ($p - 1) * 100;

$domain_mode = false;
$domain_id = null;
if (!empty($_GET['domain'])) {
    $stmt_domain = $db->prepare('SELECT id FROM general_domain WHERE domain_name = ? LIMIT 1');
    $stmt_domain->bind_param('s', $_GET['domain']);
    $stmt_domain->execute();
    $res_domain = $stmt_domain->get_result();
    if ($res_domain === false) {
        die('Unable to get');
    }
    if ($res_domain->num_rows === 0) {
        die('Domain not found');
    }
    $domain_id = $res_domain->fetch_column(0);
    $domain_mode = true;
}

$stmt = $db->prepare('SELECT
    rc.id AS id,
    o.org_name AS org_name,
    rp.date_range_begin AS begin,
    rp.date_range_end AS end,
    rsi.ip_address AS row_source_ip,
    hfd.domain_name AS header_from,
    efd.domain_name AS envelope_from,
    rc.row_count AS row_count
FROM
    dmarc_record rc
    JOIN dmarc_report rp ON rc.dmarc_report_id = rp.id
    JOIN dmarc_org o ON rp.metadata_org_id = o.id
    JOIN general_ip rsi ON rc.row_source_ip_id = rsi.id
    JOIN general_domain hfd ON rc.identifiers_header_from_id = hfd.id
    JOIN general_domain efd ON rc.identifiers_envelope_to_id = efd.id
' .
($domain_mode ? ' WHERE rc.identifiers_header_from_id = ? OR rc.identifiers_envelope_to_id = ?' : '') .
' ORDER BY
    rp.date_range_end DESC
LIMIT 100
    OFFSET ?
');
if ($domain_mode) {
    $stmt->bind_param('iii', $domain_id, $domain_id, $offset);
} else {
    $stmt->bind_param('i', $offset);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res === false) {
    die('Unable to get');
}
$res = $res->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        DMARC Records
        <?php if ($domain_mode) : ?>
            for <?= htmlentities($_GET['domain']) ?>
        <?php endif; ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .spf-dkim-res-pass {
            color: darkgreen !important;
        }
        .spf-dkim-res-fail {
            color: darkred !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col">
                <h1>
                    DMARC Records
                    <?php if ($domain_mode) : ?>
                        for <code><?= htmlentities($_GET['domain']) ?></code>
                    <?php endif; ?>
                </h1>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Header From <small>/ Envelope From</small></th>
                            <th>Source IP</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Count</th>
                            <th>Org Name</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th>Domain</th>
                            <th colspan="5">Auth Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($res as $r) : ?>
                        <tr>
                            <th scope="row"><?= htmlentities($r['id']) ?></th>
                            <td>
                                <a href="?domain=<?= htmlentities($r['header_from']) ?>">
                                    <?= htmlentities($r['header_from']) ?>
                                </a>
                                <?php if ($r['envelope_from'] !== null) : ?>
                                    <br />
                                    <small>
                                        <a href="?domain=<?= htmlentities($r['envelope_from']) ?>">
                                            <?= htmlentities($r['envelope_from']) ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlentities($r['row_source_ip']) ?></td>
                            <td><?= htmlentities($r['begin']) ?></td>
                            <td><?= htmlentities($r['end']) ?></td>
                            <td><?= htmlentities($r['row_count']) ?></td>
                            <td><?= htmlentities($r['org_name']) ?></td>
                        </tr>
                        <?php
                        $stmt_spf = $db->prepare('SELECT d.domain_name, s.spf_result FROM dmarc_spf_result s JOIN general_domain d ON s.domain_id = d.id WHERE s.dmarc_record_id = ?');
                        $stmt_spf->bind_param('i', $r['id']);
                        $stmt_spf->execute();
                        $res_spf = $stmt_spf->get_result();
                        if ($res_spf === false)
                            $res_spf = [];
                        else
                            $res_spf = $res_spf->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <?php foreach ($res_spf as $spf_info) : ?>
                            <tr>
                                <td></td>
                                <th scope="row"><?= htmlentities($spf_info['domain_name']) ?></th>
                                <td colspan="5" class="spf-dkim-res-<?= htmlentities($spf_info['spf_result']) ?>">
                                    SPF: <?= htmlentities($spf_info['spf_result']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php
                        $stmt_dkim = $db->prepare('SELECT d.domain_name, k.dkim_result FROM dmarc_dkim_result k JOIN general_domain d ON k.domain_id = d.id WHERE k.dmarc_record_id = ?');
                        $stmt_dkim->bind_param('i', $r['id']);
                        $stmt_dkim->execute();
                        $res_dkim = $stmt_dkim->get_result();
                        if ($res_dkim === false)
                            $res_dkim = [];
                        else
                            $res_dkim = $res_dkim->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <?php foreach ($res_dkim as $dkim_info) : ?>
                            <tr>
                                <td></td>
                                <th scope="row"><?= htmlentities($dkim_info['domain_name']) ?></th>
                                <td colspan="5" class="spf-dkim-res-<?= htmlentities($dkim_info['dkim_result']) ?>">
                                    DKIM: <?= htmlentities($dkim_info['dkim_result']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>