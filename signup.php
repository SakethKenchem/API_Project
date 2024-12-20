<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
require 'connection.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = isset($_POST['username']) ? htmlspecialchars(strip_tags(trim($_POST['username']))) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email']))) : '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : '';

    
    if ($username && $email && $password) {
        //
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $password]);

            // Get the last inserted user ID
            $userId = $pdo->lastInsertId();

            // Generate a 6-digit OTP code
            $otp_code = random_int(100000, 999999);
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));

            
            $stmt = $pdo->prepare('INSERT INTO otp_codes (user_id, otp_code, expiry_time) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $otp_code, $expiry_time]);

            
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 's.kenchem@gmail.com'; 
            $mail->Password = 'jncj pmsd ljkk savt'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your_email@gmail.com', '2FA System');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your 2FA Code';
            $mail->Body = "Your OTP code is <strong>$otp_code</strong>. It is valid for 1 hour.";

            $mail->send();

            // Redirect to OTP verification page
            session_start();
            $_SESSION['user_id'] = $userId;
            header('Location: verify_otp.php');
            exit;
        } catch (Exception $e) {
            $message = "Error: {$e->getMessage()}";
        }
    } else {
        $message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h4>Sign Up</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger"><?= $message; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Sign Up</button>
                        </div>
                        <div>
                            <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
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
