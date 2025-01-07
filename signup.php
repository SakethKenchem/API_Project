<?php
require 'db.php';
require 'send_email.php';

class User {
    private $conn;
    private $username;
    private $email;
    private $password;

    public function __construct($conn, $username, $email, $password) {
        $this->conn = $conn;
        $this->username = trim($username);
        $this->email = trim($email);
        $this->password = $password;
    }

    public function validate() {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return '<div class="text-danger">Invalid email format.</div>';
        } elseif (strlen($this->username) < 3 || strlen($this->username) > 20) {
            return '<div class="text-danger">Username must be between 3 and 20 characters.</div>';
        } elseif (strlen($this->password) < 8) {
            return '<div class="text-danger">Password must be at least 8 characters long.</div>';
        }
        return '';
    }

    public function userExists() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$this->username, $this->email]);
        return $stmt->fetchColumn() > 0;
    }

    public function save() {
        $passwordHash = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$this->username, $this->email, $passwordHash]);
        return $this->conn->lastInsertId();
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($conn, $_POST['username'], $_POST['email'], $_POST['password']);
    $message = $user->validate();

    if (empty($message)) {
        if ($user->userExists()) {
            $message = '<div class="text-danger">Username or email already in use. Please choose a different one.</div>';
        } else {
            $user_id = $user->save();
            
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script>
        function validateForm() {
            let isValid = true;

            // Validate email
            const email = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailPattern.test(email.value)) {
                emailError.textContent = 'Please enter a valid email address.';
                isValid = false;
            } else {
                emailError.textContent = '';
            }

            // Validate username
            const username = document.getElementById('username');
            const usernameError = document.getElementById('usernameError');
            if (username.value.length < 3 || username.value.length > 20) {
                usernameError.textContent = 'Username must be between 3 and 20 characters.';
                isValid = false;
            } else {
                usernameError.textContent = '';
            }

            // Validate password
            const password = document.getElementById('password');
            const passwordError = document.getElementById('passwordError');
            if (password.value.length < 8) {
                passwordError.textContent = 'Password must be at least 8 characters long.';
                isValid = false;
            } else {
                passwordError.textContent = '';
            }

            return isValid;
        }
    </script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 20px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(206, 7, 7, 0.1);
            background-color: #fff;
            border-radius: 8px;
        }

        .form-control {
            margin-bottom: 15px;
            height: 45px;
        }

        .form-group label {
            margin-bottom: 5px;
        }

        .btn-primary {
            width: 100%;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .text-danger {
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <h2>Not Instagram</h2>
    <div class="container">
        <div class="form-wrapper">
            <h2>Sign Up</h2>
            <form onsubmit="return validateForm()" method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <div id="usernameError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div id="emailError" class="text-danger"></div>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div id="passwordError" class="text-danger"></div>
                </div>
                <button type="submit" class="btn btn-primary">Sign Up</button>
                <p class="mt-3 text-center">Already have an account? <a href="login.php">Login</a></p>
            </form>
            <p class="mt-3"><?php echo $message; ?></p>
        </div>
    </div>
</body>

</html>