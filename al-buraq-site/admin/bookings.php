<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php'); exit;
}
require_once __DIR__ . '/../db.php';
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: bookings.php'); exit;
}
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings.csv"');
    $out = fopen('php://output','w');
    $header = ['id','name','email','phone','pickup_date','pickup_time','pickup_location','dropoff_location','shipment_type','weight','dimensions','quantity','declared_value','notes','created_at'];
    fputcsv($out, $header);
    $stmt = $pdo->query('SELECT ' . implode(',', $header) . ' FROM bookings ORDER BY id DESC');
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($out, $row);
    }
    exit;
}
// Fetch bookings
$stmt = $pdo->query('SELECT id,name,email,phone,pickup_date,pickup_time,pickup_location,dropoff_location,shipment_type,weight,dimensions,quantity,declared_value,notes,created_at FROM bookings ORDER BY id DESC');
$bookings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Bookings Admin</title></head><body>
<h1>Bookings</h1>
<p><a href="logout.php">Logout</a> | <a href="bookings.php?export=csv">Export CSV</a></p>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Pickup</th><th>Dropoff</th><th>Weight</th><th>Created</th><th>Actions</th></tr>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?php echo htmlspecialchars($b['id']); ?></td>
    <td><?php echo htmlspecialchars($b['name']); ?></td>
    <td><?php echo htmlspecialchars($b['email']); ?></td>
    <td><?php echo htmlspecialchars($b['phone']); ?></td>
    <td><?php echo htmlspecialchars($b['pickup_date'] . ' ' . ($b['pickup_time'] ?? '')); ?></td>
    <td><?php echo htmlspecialchars($b['dropoff_location']); ?></td>
    <td><?php echo htmlspecialchars($b['weight']); ?></td>
    <td><?php echo htmlspecialchars($b['created_at']); ?></td>
    <td>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete booking?');">
            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($b['id']); ?>">
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
</body></html>