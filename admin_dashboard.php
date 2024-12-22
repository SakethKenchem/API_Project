<?php
session_start();
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all users
$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Admin Dashboard</h2>
        <a href="admin_logout.php" class="btn btn-danger mt-3">Logout</a>
        <form class="mt-3">
            <input type="text" id="search" class="form-control" placeholder="Search Users">
        </form>

        <table class="table mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody id="user_list">
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['username']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo $user['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    // AJAX search functionality
    $('#search').on('keyup', function() {
        var query = $(this).val();

        $.ajax({
            url: "search_users.php",
            method: "POST",
            data: { query: query },
            success: function(response) {
                $('#user_list').html(response);
            }
        });
    });
    </script>
</body>
</html>
