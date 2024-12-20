<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.php');
    exit;
}

require 'connection.php'; // Include the database connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = htmlspecialchars(strip_tags(trim($_POST['otp'])));
    $userId = $_SESSION['user_id'];

    // Verify OTP
    $stmt = $pdo->prepare('SELECT otp_code, expiry_time FROM otp_codes WHERE user_id = ?');
    $stmt->execute([$userId]);
    $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($otpData) {
        if ($otp === $otpData['otp_code'] && strtotime($otpData['expiry_time']) > time()) {
            // OTP is valid, update user status to verified
            $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
            $stmt->execute([$userId]);

            // Redirect to dashboard
            header('Location: homepage.php');
            exit;
        } else {
            $message = 'Invalid or expired OTP.';
        }
    } else {
        $message = 'No OTP found.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h4>Verify OTP</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert <?= strpos($message, 'successful') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?= $message; ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Enter OTP</label>
                            <input type="text" name="otp" id="otp" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Verify</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
