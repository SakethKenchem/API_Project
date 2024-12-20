<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if user is not logged in
    exit;
}

require 'connection.php'; // Include database connection file
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = isset($_POST['otp_code']) ? $_POST['otp_code'] : '';
    if ($otp_code) {
        try {
            // Fetch the OTP from the database
            $stmt = $pdo->prepare('SELECT * FROM otp_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $otp = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($otp && $otp['otp_code'] == $otp_code && strtotime($otp['expiry_time']) > time()) {
                // OTP is correct and not expired, log in the user
                $_SESSION['logged_in'] = true;
                header('Location: homepage.php');
                exit;
            } else {
                $message = 'Invalid or expired OTP code.';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    } else {
        $message = 'Please enter the OTP code.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Verify OTP</h2>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message; ?></div>
    <?php endif; ?>

    <form action="verify_otp.php" method="POST">
        <div class="mb-3">
            <label for="otp_code" class="form-label">OTP Code</label>
            <input type="text" class="form-control" id="otp_code" name="otp_code" required>
        </div>
        <button type="submit" class="btn btn-primary">Verify OTP</button>
    </form>
</div>

</body>
</html>
