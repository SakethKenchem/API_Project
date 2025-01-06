<?php
session_name("user_session");
session_start();
require 'db.php'; 
require 'send_email.php'; 

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_entered = $_POST['otp'];
    $user_id = $_SESSION['user_id'];

    try {
        // Verify OTP from the database
        $stmt = $conn->prepare("SELECT otp_code FROM otp_codes WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if OTP matches
        if ($otp_record && $otp_record['otp_code'] === $otp_entered) {
            //If OTP is matched, login successful
            $_SESSION['is_logged_in'] = true;
            header("Location: dashboard.php");
            exit();
        } else {
            $message = 'Invalid OTP. Please try again.';
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        .container {
            max-width: 500px;
        }
    </style>
    
</head>
<body>
<div class="container mt-5">
    <h2>Verify OTP</h2>
    <form method="POST" action="verify_login_otp.php">
        <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP sent to your email</label>
            <input type="text" class="form-control" id="otp" name="otp" required>
        </div>
        <button type="submit" class="btn btn-primary">Verify OTP</button>
    </form>
    <?php if ($message): ?>
        <div class="alert alert-danger mt-3">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
