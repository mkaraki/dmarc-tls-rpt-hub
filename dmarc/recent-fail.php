<?php
require_once __DIR__ . '/../_internals/init.php';

$db = get_db();

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

$stmt_policy = $db->prepare('
SELECT
    r.id AS report_id,
    r.date_range_begin, r.date_range_end,
    o.org_name,
    \'spf\' AS auth_type,
    rsi.ip_address,
    rc.row_count,
    rsd.domain_name AS domain_name,
    rs.spf_result AS result
FROM
    dmarc_spf_result rs
    JOIN dmarc_record rc ON rs.dmarc_record_id = rc.id
    JOIN dmarc_report r ON rc.dmarc_report_id = r.id
    JOIN dmarc_org o ON r.metadata_org_id = o.id
    JOIN general_domain rsd ON rs.domain_id = rsd.id
    JOIN general_ip rsi ON rc.row_source_ip_id = rsi.id
WHERE
    spf_result != "pass"
    ' . ($domain_mode ? 'AND (rc.identifiers_envelope_to_id = ? OR rc.identifiers_header_from_id = ? OR rs.domain_id = ?)' : '') .  '
    
UNION ALL

SELECT
    r.id AS report_id,
    r.date_range_begin, r.date_range_end,
    o.org_name,
    \'dkim\' AS auth_type,
    rsi.ip_address,
    rc.row_count,
    rsd.domain_name AS domain_name,
    rs.dkim_result AS result
FROM
    dmarc_dkim_result rs
    JOIN dmarc_record rc ON rs.dmarc_record_id = rc.id
    JOIN dmarc_report r ON rc.dmarc_report_id = r.id
    JOIN dmarc_org o ON r.metadata_org_id = o.id
    JOIN general_domain rsd ON rs.domain_id = rsd.id
    JOIN general_ip rsi ON rc.row_source_ip_id = rsi.id
WHERE
    dkim_result != "pass"
    ' . ($domain_mode ? 'AND (rc.identifiers_envelope_to_id = ? OR rc.identifiers_header_from_id = ? OR rs.domain_id = ?)' : '') .  '
    
    
ORDER BY
    date_range_end DESC
LIMIT 100
');
if ($domain_mode) {
    $stmt_policy->bind_param('iiiiii', $domain_id, $domain_id, $domain_id, $domain_id, $domain_id, $domain_id);
}
$stmt_policy->execute();
$res_policy = $stmt_policy->get_result();
if ($res_policy === false) {
    die('Unable to get');
}
$res_policy = $res_policy->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recent Fail - DMARC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col">
            <h1>
               Recent Fail Auth - DMARC
            </h1>
        </div>
        <hr />
    </div>
</div>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <table class="table">
                <thead>
                <tr>
                    <th>Rpt. ID</th>
                    <th>Reporter</th>
                    <th>IP Address</th>
                    <th>Message Cnt.</th>
                    <th>Domain</th>
                    <th>Range</th>
                    <th>Result</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($res_policy as $policy) : ?>
                    <tr>
                        <td>
                            <?= htmlentities($policy['report_id']) ?>
                        </td>
                        <td>
                            <?= htmlentities($policy['org_name']) ?>
                        </td>
                        <td>
                            <?= htmlentities($policy['ip_address']) ?>
                        </td>
                        <td>
                            <?= htmlentities($policy['row_count']) ?>
                        </td>
                        <td>
                            <a href="list.php?domain=<?= htmlentities($policy['domain_name']) ?>">
                                <?= htmlentities($policy['domain_name']) ?>
                            </a>
                        </td>
                        <td>
                            <?= htmlentities($policy['date_range_begin']) ?> - <?= htmlentities($policy['date_range_end']) ?>
                        </td>
                        <td><?= htmlentities($policy['auth_type']) ?>: <?= htmlentities($policy['result']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/js/bootstrap.min.js" integrity="sha512-nKXmKvJyiGQy343jatQlzDprflyB5c+tKCzGP3Uq67v+lmzfnZUi/ZT+fc6ITZfSC5HhaBKUIvr/nTLCV+7F+Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>
</html>
