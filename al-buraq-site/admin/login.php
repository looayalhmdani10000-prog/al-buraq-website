<?php
session_start();
require_once __DIR__ . '/config.php';
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: bookings.php'); exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ($user === ADMIN_USER && password_verify($pass, ADMIN_PASS_HASH)) {
        $_SESSION['admin'] = true;
        header('Location: bookings.php'); exit;
    }
    $err = 'Invalid credentials';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Admin Login</title></head><body>
<h1>Admin Login</h1>
<?php if ($err): ?><p style="color:red"><?php echo htmlspecialchars($err); ?></p><?php endif; ?>
<form method="post">
    <label>Username: <input name="username" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <button type="submit">Login</button>
</form>
</body></html>