<?php
require_once __DIR__ . '/../_internals/init.php';

$db = get_db();

$p = intval($_GET['p'] ?? '1');
if ($p === 0) $p = 1;
$offset = ($p - 1) * 100;

$stmt = $db->prepare('SELECT r.id, o.organization_name, r.date_range_start, r.date_range_end FROM tls_rpt r JOIN tls_rpt_report_organization o ON r.tls_rpt_report_organization_id = o.id ORDER BY r.date_range_end DESC LIMIT 100 OFFSET ?');
$stmt->bind_param('i', $offset);
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
                            <th>Org Name</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($res as $r) : ?>
                        <tr>
                            <th>
                                <a href="show.php?id=<?= htmlentities($r['id']) ?>">
                                    <?= htmlentities($r['id']) ?>
                                </a>
                            </th>
                            <th><?= htmlentities($r['organization_name']) ?></th>
                            <th><?= htmlentities($r['date_range_start']) ?></th>
                            <th><?= htmlentities($r['date_range_end']) ?></th>
                            <?php
                            $stmt = $db->prepare('SELECT SUM(summary_total_successful_sessions) AS total_successful, SUM(summary_total_failed_sessions) AS total_failed FROM tls_rpt_policy WHERE tls_rpt_id = ?');
                            $stmt->bind_param('i', $r['id']);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if ($res === false) {
                                $res = 'Unknown: ERR';
                            } else {
                                $res = $res->fetch_assoc();
                                $res = 'Success: ' . $res['total_successful'] . ', Failure: ' . $res['total_failed'];
                            }
                            ?>
                            <th>
                                <a href="show.php?id=<?= htmlentities($r['id']) ?>">
                                    <?= htmlentities($res) ?>
                                </a>
                            </th>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>