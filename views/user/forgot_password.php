<?php
session_start();
require '../../includes/db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ForgotPassword {
    private $conn;
    private $email;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->email = $_POST['email'];
            $user = $this->getUserByEmail();
            if ($user) {
                $otp = $this->generateOtp();
                $this->saveOtp($user['id'], $otp);
                $this->sendOtpEmail($user['id'], $otp);
                $_SESSION['success'] = "OTP sent to your email.";
                header('Location: forgot_password.php');
            } else {
                $_SESSION['error'] = "Email not found.";
            }
        }
    }

    private function getUserByEmail() {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function generateOtp() {
        return rand(100000, 999999);
    }

    private function saveOtp($user_id, $otp) {
        $stmt = $this->conn->prepare("INSERT INTO otp_codes (user_id, otp_code, created_at) VALUES (:user_id, :otp_code, NOW())");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':otp_code', $otp);
        $stmt->execute();
    }

    private function sendOtpEmail($user_id, $otp) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 's.kenchem@gmail.com';
            $mail->Password = 'lnwh csma yqir zuva';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('noreply@yourdomain.com', 'Password Reset Request');
            $mail->addAddress($this->email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
            A password reset request was made for your account.<br>
            Your OTP Code is: <strong>$otp</strong>. Please use this code to reset your password. <br>
            <a href='http://localhost/API_Project/views/user/reset_password.php?user_id=$user_id&otp=$otp'>Click here to reset your password.</a> <br>
            
            If you did not request a password reset, please ignore this email.
            ";

            $mail->send();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error sending email: " . $mail->ErrorInfo;
        }
    }
}

$forgotPassword = new ForgotPassword($conn);
$forgotPassword->handleRequest();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="col-md-6">
            <h2>Not Instagram</h2>
            <h2 class="mt-5">Forgot Password</h2>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['success'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                unset($_SESSION['success']);
            }
            ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Enter your email address:</label>
                    <input type="email" class="form-control mb-3" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>