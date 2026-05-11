<?php
require_once __DIR__ . '/../_internals/init.php';

$db = get_db();

$p = intval($_GET['p'] ?? '1');
if ($p === 0) $p = 1;
$offset = ($p - 1) * 100;

if (empty($_GET['domain'])) {
    die('Domain is required');
}

$stmt_domain = $db->prepare('SELECT domain_name FROM general_domain WHERE domain_name = ?');
$stmt_domain->bind_param('s', $_GET['domain']);
$stmt_domain->execute();
$res_domain = $stmt_domain->get_result();
if ($res_domain === false) {
    die('Unable to get');
}
if ($res_domain->num_rows === 0) {
    die('Domain not found');
}
$res_domain = $res_domain->fetch_column(0);

$stmt_policy = $db->prepare('SELECT p.id, p.policy_type, d.domain_name AS policy_domain, p.summary_total_successful_sessions, p.summary_total_failed_sessions FROM tls_rpt_policy p JOIN general_domain d ON p.policy_domain_id = d.id WHERE p.policy_domain_id = ? ORDER BY p.id DESC LIMIT 100 OFFSET ?');
$stmt_policy->bind_param('ii', $res_domain, $offset);
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
    <title>TLS RPT for <?= htmlentities($res_domain) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap.min.css" integrity="sha512-2bBQCjcnw658Lho4nlXJcc6WkV/UxpE/sAokbXPxQNGqmNdQrWqtw26Ns9kFF/yG792pKR1Sx8/Y1Lf1XN4GKA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col">
            <h1>
                TLS RPT Policies for <code><?= htmlentities($res_domain) ?></code>
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
                    <th>Id</th>
                    <th>Type</th>
                    <th>Domain</th>
                    <th>Success</th>
                    <th>Failure</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($res_policy as $policy) : ?>
                    <tr>
                        <th><?= htmlentities($policy['id']) ?></th>
                        <th><?= htmlentities($policy['policy_type']) ?></th>
                        <th>
                            <?= htmlentities($policy['policy_domain']) ?>
                        </th>
                        <th><?= htmlentities($policy['summary_total_successful_sessions']) ?></th>
                        <th>
                            <a href="fail.php?id=<?= htmlentities($policy['id']) ?>">
                                <?= htmlentities($policy['summary_total_failed_sessions']) ?>
                            </a>
                        </th>
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
