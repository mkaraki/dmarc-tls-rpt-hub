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
    p.id, p.policy_type, d.domain_name AS policy_domain,
    p.summary_total_successful_sessions, p.summary_total_failed_sessions,
    r.date_range_start, r.date_range_end,
    r.id AS report_id, o.organization_name
FROM 
    tls_rpt_policy p
    JOIN general_domain d ON p.policy_domain_id = d.id
    JOIN tls_rpt r ON p.tls_rpt_id = r.id
    JOIN tls_rpt_report_organization o ON r.tls_rpt_report_organization_id = o.id
WHERE
    p.summary_total_failed_sessions > 0
' . ($domain_mode ? ' AND p.policy_domain_id = ?' : '') . '
ORDER BY
    r.date_range_end DESC
LIMIT 100
');
if ($domain_mode) {
    $stmt_policy->bind_param('i', $domain_id);
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
    <title>Recent Fail - TLS RPT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col">
            <h1>
               Recent Fail Policies - TLS RPT
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
                    <th>Type</th>
                    <th>Domain</th>
                    <th>Range</th>
                    <th>Success</th>
                    <th>Failure</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($res_policy as $policy) : ?>
                    <tr>
                        <td>
                            <a href="show.php?id=<?= htmlentities($policy['report_id']) ?>">
                                <?= htmlentities($policy['report_id']) ?>
                            </a>
                        </td>
                        <td>
                            <?= htmlentities($policy['organization_name']) ?>
                        </td>
                        <td><?= htmlentities($policy['policy_type']) ?></td>
                        <td>
                            <a href="domain.php?domain=<?= htmlentities($policy['policy_domain']) ?>">
                                <?= htmlentities($policy['policy_domain']) ?>
                            </a>

                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#mx_modal_<?= $policy['id'] ?>">
                                MX Patterns
                            </button>
                        </td>
                        <td>
                            <?= htmlentities($policy['date_range_start']) ?> - <?= htmlentities($policy['date_range_end']) ?>
                        </td>
                        <td><?= htmlentities($policy['summary_total_successful_sessions']) ?></td>
                        <td>
                            <a href="fail.php?id=<?= htmlentities($policy['id']) ?>">
                                <?= htmlentities($policy['summary_total_failed_sessions']) ?>
                            </a>
                        </td>

                        <div class="modal fade" id="mx_modal_<?= $policy['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5">MX Host Patterns of <?= htmlentities($policy['policy_domain']) ?></h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <ul>
                                            <?php
                                            $stmt = $db->prepare('SELECT a.pattern FROM tls_rpt_policy_mx_host_pattern_assign b JOIN tls_rpt_mx_host_pattern a ON b.tls_rpt_mx_host_pattern_id = a.id WHERE b.tls_rpt_policy_id = ? ORDER BY a.pattern ASC');
                                            $stmt->bind_param('i', $policy['id']);
                                            $stmt->execute();
                                            $res = $stmt->get_result();
                                            if ($res === false) {
                                                die('Unable to get');
                                            }
                                            $res = $res->fetch_all(MYSQLI_ASSOC);
                                            ?>
                                            <?php foreach ($res as $mx) : ?>
                                                <li>
                                                    <?= htmlentities($mx['pattern']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
