<?php
require_once __DIR__ . '/../_internals/init.php';

$db = get_db();

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID is required');
}

$stmt_fail = $db->prepare('SELECT
    f.id,
    f.result_type,
    si.ip_address AS sending_mta_ip,
    md.domain_name AS receiving_mx_hostname,
    gh.helo_string AS receiving_mx_helo,
    ri.ip_address AS receiving_ip,
    f.failed_session_count,
    a.additional_information,
    fr.failure_reason_code
FROM
    tls_rpt_policy_failure_details f
LEFT JOIN
    general_ip si ON f.sending_mta_ip_id = si.id
LEFT JOIN
    general_domain md ON f.receiving_mx_hostname_id = md.id
LEFT JOIN
    general_helo gh ON f.receiving_mx_helo_id = gh.id
LEFT JOIN
    general_ip ri ON f.receiving_ip_id = ri.id
LEFT JOIN
    tls_rpt_policy_additional_information a ON f.additional_information_id = a.id
LEFT JOIN
    tls_rpt_policy_failure_reason_code fr ON f.failure_reason_code_id = fr.id
WHERE
    f.tls_rpt_policy_id = ?
');
$stmt_fail->bind_param('i', $_GET['id']);
$stmt_fail->execute();
$res_fail = $stmt_fail->get_result();
if ($res_fail === false) {
    die('Unable to get');
}
$res_fail = $res_fail->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TLS RPT List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col">
            <h1>TLS RPT List</h1>
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
                    <th>Result</th>
                    <th>Sending MTA IP</th>
                    <th>Receiving MX Hostname</th>
                    <th>Receiving MX HELO</th>
                    <th>Receiving IP</th>
                    <th>Failed Session Count</th>
                    <th>Failure Reason Code</th>
                    <th>Additional Information</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($res_fail as $f) : ?>
                <tr>
                    <th><?= htmlentities($f['id']) ?></th>
                    <th><?= htmlentities($f['result_type']) ?></th>
                    <th><?= htmlentities($f['sending_mta_ip'] ?? '') ?></th>
                    <th><?= htmlentities($f['receiving_mx_hostname'] ?? '') ?></th>
                    <th><?= htmlentities($f['receiving_mx_helo'] ?? '') ?></th>
                    <th><?= htmlentities($f['receiving_ip'] ?? '') ?></th>
                    <th><?= htmlentities($f['failed_session_count'] ?? '') ?></th>
                    <th><?= htmlentities($f['failure_reason_code'] ?? '') ?></th>
                    <th><?= htmlentities($f['additional_information'] ?? '') ?></th>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
