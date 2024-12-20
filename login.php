<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php'); // Redirect to the dashboard if the user is already logged in
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'connection.php'; // Include database connection file

    $email = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email']))) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate inputs
    if ($email && $password) {
        try {
            // Check if the user exists
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Credentials are correct, generate OTP
                $otp_code = random_int(100000, 999999);
                $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Insert OTP into the database
                $stmt = $pdo->prepare('INSERT INTO otp_codes (user_id, otp_code, expiry_time) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $otp_code, $expiry_time]);

                // Send OTP via email
                require 'vendor/autoload.php'; // Load PHPMailer

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 's.kenchem@gmail.com'; // Replace with your email
                $mail->Password = 'jncj pmsd ljkk savt'; // Replace with your email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('your_email@gmail.com', '2FA System');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Your 2FA Code';
                $mail->Body = "Your OTP code is <strong>$otp_code</strong>. It is valid for 1 hour.";

                $mail->send();

                // Redirect to OTP verification page
                $_SESSION['user_id'] = $user['id'];
                header('Location: verify_otp.php');
                exit;
            } else {
                $message = 'Invalid email or password.';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Login</h2>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>

        <p class="mt-3">Don't have an account? <a href="signup.php">Sign up</a></p>
    </form>
</div>

</body>
</html>
