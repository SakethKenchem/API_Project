<?php

function my_autoloader($className) {
    // Define base directories for user and admin classes
    $baseDirs = [
        __DIR__ . '/includes/',  // For classes in the 'includes' folder
        __DIR__ . '/views/user/', // For user-related classes in 'user' folder
        __DIR__ . '/views/admin/', // For admin-related classes in 'admin' folder
    ];

    // Class map with class names and their corresponding file paths
    $classMap = [
        'OTPVerification' => 'verify_otp.php',
        'User' => 'user.php',
        'Post' => 'Post.php',
        'LoginOtp' => 'verify_login_otp.php',
        'Login' => 'login.php',
        'UserProfile' => 'profile.php',
        'LoginOTPVerification' => 'verify_login_otp.php',
        'Dashboard' => 'dashboard.php',
        'UserSignup' => 'signup.php',
        'ForgotPassword' => 'forgot_password.php',
        'PasswordReset' => 'reset_password.php',
        'CreatePost' => 'create_post.php',
        'Navbar' => 'navbar.php',
        'AdminDashboard' => 'admin_dashboard.php',
        'AdminLogin' => 'admin_login.php',
    ];

    // Check if the class exists in the class map and the corresponding file path
    if (isset($classMap[$className])) {
        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $classMap[$className];
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    } else {
        // If class name is not in the map, try to load from any of the directories
        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }

    // If class not found in any of the directories, throw an error
    echo "Error: Class '$className' not found.";
}

spl_autoload_register('my_autoloader');
?>
