<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php'); exit;
}
$log = __DIR__ . '/../logs/app.log';
$lines = [];
if (file_exists($log)) {
    $lines = array_slice(file($log), -500);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Logs</title></head><body>
<h1>Application logs (last 500 lines)</h1>
<p><a href="bookings.php">Back</a></p>
<pre style="white-space:pre-wrap; background:#f9f9f9; padding:10px; border:1px solid #ddd;">
<?php echo htmlspecialchars(implode('', $lines)); ?></pre>
</body></html>