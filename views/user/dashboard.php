<?php
session_name("user_session");
session_start();

require '../../config.php';
include '../../includes/navbar.php'; // Correct path for navbar.php

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../views/user/login.php');
    exit();
}

require '../../includes/db.php';

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // If user not found, log out and redirect to login page
    header('Location: ../../views/user/logout.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <!-- No need for another include here, as navbar.php is already included above -->
    <div class="container mt-5">
        <div class="text-center">
            <h1>Welcome to Your Dashboard, <?php echo ($user['username']); ?>!</h1>
        </div>

    </div>
</body>

</html>
