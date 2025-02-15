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

    public function fetchPosts()
    {
        $query = "SELECT id, content, image_url FROM posts";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePost($id)
    {
        $query = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}

$dashboard = new AdminDashboard($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    
    if ($_POST['action'] === 'delete_user') {
        $dashboard->deleteUser($id);
        echo json_encode(['success' => true]);
    } elseif ($_POST['action'] === 'delete_post') {
        $dashboard->deletePost($id);
        echo json_encode(['success' => true]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $users = $dashboard->fetchUsers($search);
    echo json_encode($users);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'posts') {
    $posts = $dashboard->fetchPosts();
    echo json_encode($posts);
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

        .post-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .post-image:hover {
            cursor: pointer;
        }

        .scrollable-table {
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="background-color: #343a40; padding: 1rem;">
        <div class="container">
            <a class="navbar-brand" href="#" style="color: #fff;">Admin Dashboard</a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto"></ul>

                
                <!-- stats -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="admin_stats.php" class="nav-link" style="color: #fff;">Stats</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="admin_logout.php" class="nav-link" style="color: #fff;">Logout</a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['admin_username']); ?>!</h3>
        <!-- <a href="admin_logout.php" class="btn btn-secondary mb-4">Logout</a> -->

        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Search by username or email">
        </div>
        
        <h3>Users</h3>
        <div class="scrollable-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="admin-table"></tbody>
            </table>
        </div>

        <h3 class="mt-5">Posts</h3>
        <div class="scrollable-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="posts-table"></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="postModalLabel">Post Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="postContent"></p>
                    <img id="postImage" src="" alt="Post image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function fetchAdmins(search = '') {
        $.ajax({
            url: 'admin_dashboard.php',
            type: 'GET',
            data: { search: search },
            success: function(data) {
                const admins = JSON.parse(data);
                const adminTable = $('#admin-table');
                adminTable.empty();
                admins.slice(0, 3).forEach(admin => {
                    adminTable.append(`
                        <tr>
                            <td>${admin.id}</td>
                            <td>${admin.username}</td>
                            <td>${admin.email}</td>
                            <td><span class="delete-btn" data-id="${admin.id}" data-type="user">Delete</span></td>
                        </tr>
                    `);
                });
            }
        });
    }

    function fetchPosts() {
        $.ajax({
            url: 'admin_dashboard.php',
            type: 'GET',
            data: { fetch: 'posts' },
            success: function(data) {
                const posts = JSON.parse(data);
                const postsTable = $('#posts-table');
                postsTable.empty();
                posts.slice(0, 3).forEach(post => {
                    let imageUrl = `../../uploads/${post.image_url}`;
                    postsTable.append(`
                        <tr>
                            <td>${post.id}</td>
                            <td>${post.content}</td>
                            <td>
                                ${post.image_url ? `<img src="${imageUrl}" class="post-image" alt="Post image" data-content="${post.content}" data-image-url="${imageUrl}">` : ''}
                            </td>
                            <td><span class="delete-btn" data-id="${post.id}" data-type="post">Delete</span></td>
                        </tr>
                    `);
                });
            }
        });
    }

    fetchAdmins();
    fetchPosts();

    $('#search').on('input', function() {
        fetchAdmins($(this).val());
    });

    $(document).on('click', '.delete-btn', function() {
        if (confirm('Are you sure you want to delete this?')) {
            const id = $(this).data('id');
            const type = $(this).data('type');
            $.ajax({
                url: 'admin_dashboard.php',
                type: 'POST',
                data: { action: type === 'user' ? 'delete_user' : 'delete_post', id: id },
                success: function() {
                    if (type === 'user') fetchAdmins();
                    else fetchPosts();
                }
            });
        }
    });

    $(document).on('click', '.post-image', function() {
        $('#postContent').text($(this).data('content'));
        $('#postImage').attr('src', $(this).data('image-url'));
        $('#postModal').modal('show');
    });
    </script>
</body>
</html>