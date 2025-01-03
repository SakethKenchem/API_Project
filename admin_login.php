<?php
session_name("admin_session");
session_start();

require_once 'db.php';

class AdminLogin
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function login($email, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['role'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        }
        return "Invalid email or password!";
    }
}

$adminLogin = new AdminLogin($conn);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $adminLogin->login($_POST['email'], $_POST['password']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        h2 {
            text-align: center;
        }

        body {
            background-color:rgb(213, 222, 231);
        }
    </style>
</head>

<body>
    <h2>Not Instagram Admin </h2>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="row w-100">
            <div class="col-lg-4 col-md-6 mx-auto">

                <form method="POST" class="p-4 border rounded bg-light">
                    <h2 class="text-center mb-4">Admin Login</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>