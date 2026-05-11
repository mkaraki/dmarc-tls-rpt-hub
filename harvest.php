<!DOCTYPE html>
<html lang="en">
<head>
	<title>Harvest Task</title>
</head>
<body>
	<header>
		<h1>Harvest Task</h1>
	</header
	<div>Started at <?= date('c') ?></div>
	<hr />
	<div>TASK: Harvest DMARC</div>
<pre><?php require __DIR__ . '/scripts/dmarc.php'; ?></pre>
	<hr />
	<div>TASK: Harvest TLS-RPT</div>
<pre><?php require __DIR__ . '/scripts/tls-rpt.php'; ?></pre>
	<hr />
	<div>Ended at <?= date('c') ?></div>
</body>
</html>