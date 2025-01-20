<?php
session_start();
require '../../includes/db.php';

class PasswordReset {
    private $conn;
    private $user_id;
    private $otp_code;

    public function __construct($conn, $user_id, $otp_code) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->otp_code = $otp_code;
    }

    public function handleRequest() {
        if (isset($_POST['otp'], $_POST['password'])) {
            $this->resetPassword($_POST['otp'], $_POST['password']);
        }
    }

    private function resetPassword($input_otp, $new_password) {
        $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("SELECT * FROM otp_codes WHERE user_id = :user_id AND otp_code = :otp_code ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':otp_code', $input_otp);
        $stmt->execute();
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otp_record) {
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = :password WHERE id = :user_id");
            $stmt->bindParam(':password', $new_password_hashed);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();
            $_SESSION['success'] = "Password has been reset successfully.";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error'] = "Invalid OTP.";
        }
    }
}

if (isset($_GET['user_id'], $_GET['otp'])) {
    $passwordReset = new PasswordReset($conn, $_GET['user_id'], $_GET['otp']);
    $passwordReset->handleRequest();
} else {
    $_SESSION['error'] = "Invalid or expired link.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="col-md-6">
    <h2>Not Instagram</h2>
        <h2 class="mt-5">Reset Password</h2>

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

        <form action="reset_password.php?user_id=<?php echo ($_GET['user_id']); ?>&otp=<?php echo ($_GET['otp']); ?>" method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" class="form-control mb-3" id="otp" name="otp" required>
            </div>
            <div class="form-group">
                <label for="password">Enter New Password</label>
                <input type="password" class="form-control mb-3" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    </div>
</div>
</body>
</html>