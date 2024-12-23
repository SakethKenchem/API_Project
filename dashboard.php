<?php


session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="text-center">
        <h1>Welcome to Your Dashboard</h1>
    </div>
    <div class="d-flex justify-content-center mt-5">
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>
</body>
</html>
