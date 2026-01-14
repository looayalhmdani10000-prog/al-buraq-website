<?php
require_once __DIR__ . '/db.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// Debug only on localhost
$APP_DEBUG = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true);

// Ensure logs folder exists (optional)
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/app.log';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

$fields = [
    'name','email','phone','pickup_date','pickup_time','pickup_location','dropoff_location',
    'shipment_type','weight','dimensions','quantity','declared_value','notes'
];

$data = [];
foreach ($fields as $f) {
    $data[$f] = isset($_POST[$f]) ? trim((string)$_POST[$f]) : null;
}

// Log attempt metadata (safe)
$meta = [
    'ip'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'ua'   => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'ajax' => !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
];
@error_log("[" . date('Y-m-d H:i:s') . "] SAVE_ATTEMPT: " . json_encode($meta) . PHP_EOL, 3, $logFile);

// Detect AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Validate required fields
$required = ['name','email','phone','pickup_date','pickup_location','dropoff_location','weight'];
$errors = [];
foreach ($required as $r) {
    if (empty($data[$r])) $errors[] = $r;
}
if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email_invalid';
}

if ($errors) {
    @error_log("[" . date('Y-m-d H:i:s') . "] VALIDATION_FAILED: " . json_encode($errors) . PHP_EOL, 3, $logFile);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'errors' => $errors]);
        exit;
    }

    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Invalid submission</title></head>
    <body>
      <h1>Invalid submission</h1>
      <p>Missing or invalid fields: <?php echo htmlspecialchars(implode(', ', $errors)); ?></p>
      <p><a href="booking.html">Return to booking form</a></p>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Normalize numeric fields
    $weight = is_numeric($data['weight']) ? (float)$data['weight'] : null;
    $quantity = (is_numeric($data['quantity']) && (int)$data['quantity'] > 0) ? (int)$data['quantity'] : 1;
    $declared_value = (is_numeric($data['declared_value'])) ? (float)$data['declared_value'] : null;

    $sql = "INSERT INTO bookings
      (name,email,phone,pickup_date,pickup_time,pickup_location,dropoff_location,shipment_type,weight,dimensions,quantity,declared_value,notes)
      VALUES
      (:name,:email,:phone,:pickup_date,:pickup_time,:pickup_location,:dropoff_location,:shipment_type,:weight,:dimensions,:quantity,:declared_value,:notes)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':name'           => $data['name'],
        ':email'          => $data['email'],
        ':phone'          => $data['phone'],
        ':pickup_date'    => $data['pickup_date'],
        ':pickup_time'    => ($data['pickup_time'] ?: null),
        ':pickup_location'=> $data['pickup_location'],
        ':dropoff_location'=> $data['dropoff_location'],
        ':shipment_type'  => ($data['shipment_type'] ?: null),
        ':weight'         => $weight,
        ':dimensions'     => ($data['dimensions'] ?: null),
        ':quantity'       => $quantity,
        ':declared_value' => $declared_value,
        ':notes'          => ($data['notes'] ?: null),
    ]);

} catch (PDOException $e) {
    @error_log("[" . date('Y-m-d H:i:s') . "] SAVE_BOOKING_ERROR: " . $e->getMessage() . PHP_EOL, 3, $logFile);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $APP_DEBUG ? $e->getMessage() : 'Server error: please try again later.'
        ]);
        exit;
    }

    http_response_code(500);
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Error</title></head>
    <body>
      <h1>Server error</h1>
      <p>Could not save booking.</p>

      <?php if ($APP_DEBUG): ?>
        <pre style="white-space:pre-wrap;"><?php echo htmlspecialchars($e->getMessage()); ?></pre>
      <?php else: ?>
        <p>Please try again later.</p>
      <?php endif; ?>

      <p><a href="booking.html">Return to booking form</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Success
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'message' => 'Booking saved successfully']);
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo 'Booking saved successfully';
exit;