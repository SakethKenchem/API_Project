<?php
session_start();
require '../../includes/db.php';


if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

class AdminDashboard
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }


    public function fetchUsers($search = '')
    {
        $query = "SELECT id, username, email FROM users";
        if ($search) {
            $query .= " WHERE username LIKE :search OR email LIKE :search";
        }
        $stmt = $this->conn->prepare($query);
        if ($search) {
            $searchTerm = '%' . $search . '%';
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function deleteUser($id)
    {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

$dashboard = new AdminDashboard($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id']);
    $dashboard->deleteUser($id);
    echo json_encode(['success' => true]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $users = $dashboard->fetchUsers($search);
    echo json_encode($users);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Dashboard</title>
    <style>
        .delete-btn {
            cursor: pointer;
            color: red;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['admin_username']); ?>!</h3>
        <a href="admin_logout.php" class="btn btn-secondary mb-4">Logout</a>

        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Search by username or email">
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-table">

            </tbody>
        </table>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchAdmins(search = '') {
            $.ajax({
                url: 'admin_dashboard.php',
                type: 'GET',
                data: {
                    search: search
                },
                success: function(data) {
                    const admins = JSON.parse(data);
                    const adminTable = $('#admin-table');
                    adminTable.empty();

                    if (admins.length === 0) {
                        adminTable.append('<tr><td colspan="4" class="text-center">No records found</td></tr>');
                    } else {
                        admins.forEach(admin => {
                            adminTable.append(`
                            <tr>
                                <td>${admin.id}</td>
                                <td>${admin.username}</td>
                                <td>${admin.email}</td>
                                <td><span class="delete-btn" data-id="${admin.id}">Delete</span></td>
                            </tr>
                        `);
                        });
                    }
                }
            });
        }


        fetchAdmins();


        $('#search').on('input', function() {
            const search = $(this).val();
            fetchAdmins(search);
        });


        $(document).on('click', '.delete-btn', function() {
            if (confirm('Are you sure you want to delete this user?')) {
                const id = $(this).data('id');
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: id
                    },
                    success: function() {
                        fetchAdmins();
                    }
                });
            }
        });
    </script>
</body>

</html>