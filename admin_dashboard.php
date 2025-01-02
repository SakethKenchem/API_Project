<?php
session_name("admin_session");
session_start();
require_once 'db.php';

class User
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    public function fetchUsers($search = "")
    {
        try {
            $query = "SELECT id, username, email, created_at FROM users";
            if ($search) {
                $query .= " WHERE id LIKE :search OR username LIKE :search OR email LIKE :search";
            }
            $stmt = $this->conn->prepare($query);
            if ($search) {
                $search = "%$search%";
                $stmt->bindParam(':search', $search, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function deleteUser($id)
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}

$user = new User($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        if ($user->deleteUser($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
        }
        exit;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : "";
$users = $user->fetchUsers($search);

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    renderTableRows($users);
    exit;
}

function renderTableRows($users)
{
    if (count($users) === 0) {
        echo "<tr><td colspan='5' class='text-center'>No results found</td></tr>";
    } else {
        foreach ($users as $user) {
            echo "<tr>
                <td>" . htmlspecialchars($user['id']) . "</td>
                <td>" . htmlspecialchars($user['username']) . "</td>
                <td>" . htmlspecialchars($user['email']) . "</td>
                <td>" . htmlspecialchars($user['created_at']) . "</td>
                <td><button class='btn btn-danger btn-sm delete-btn' data-id='" . htmlspecialchars($user['id']) . "'>Delete</button></td>
            </tr>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2>User Dashboard</h2>
        <div class="form-group">
            <input type="text" id="search" class="form-control" placeholder="Search users by ID, username, or email">
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTable">
                <?php renderTableRows($users); ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            // Search functionality
            $('#search').on('keyup', function() {
                var searchValue = $(this).val();
                $.ajax({
                    url: 'admin_dashboard.php',
                    method: 'GET',
                    data: {
                        search: searchValue,
                        ajax: '1'
                    },
                    success: function(data) {
                        $('#userTable').html(data);
                    }
                });
            });

            // Delete functionality
            $(document).on('click', '.delete-btn', function() {
                var userId = $(this).data('id');
                if (confirm('Are you sure you want to delete this user?')) {
                    $.ajax({
                        url: 'admin_dashboard.php',
                        method: 'POST',
                        data: {
                            action: 'delete',
                            id: userId
                        },
                        dataType: 'json',
                        success: function(response) {

                            if (response.success) {
                                alert('User deleted successfully');
                                $('#search').trigger('keyup');
                            } else {
                                alert('Error: ' + (response.error || 'Failed to delete user'));
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('An error occurred while trying to delete the user.');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>