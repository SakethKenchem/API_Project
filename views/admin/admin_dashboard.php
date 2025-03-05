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

    public function fetchPosts($search = '')
{
    $query = "SELECT p.*, u.username 
             FROM posts p 
             LEFT JOIN users u ON p.user_id = u.id";
    if ($search) {
        $query .= " WHERE p.content LIKE :search 
                   OR u.username LIKE :search 
                   OR DATE_FORMAT(p.created_at, '%Y-%m-%d') LIKE :search
                   OR p.user_id LIKE :search";
    }
    $query .= " ORDER BY p.created_at DESC";

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

    public function deletePost($id)
    {
        $query = "DELETE FROM posts WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function countUsers()
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }

    public function countPosts()
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM posts");
        return $stmt->fetchColumn();
    }

    //count daily login using otp codes generated
    public function countDailyLogins()
    {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM otp_codes WHERE DATE(created_at) = CURDATE()");
        return $stmt->fetchColumn();
    }

    public function fetchUserDetails($userId)
    {
        $query = "SELECT u.id, u.username, u.email, 
                         (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count,
                         (SELECT COUNT(*) FROM comments WHERE user_id = u.id) AS comment_count,
                         (SELECT COUNT(*) FROM likes WHERE user_id = u.id) AS like_count
                  FROM users u
                  WHERE u.id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
    } elseif ($_POST['action'] === 'fetch_user_details') {
        $userDetails = $dashboard->fetchUserDetails($id);
        echo json_encode($userDetails);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['search_users'])) {
        $search = trim($_GET['search_users']);
        $users = $dashboard->fetchUsers($search);
        echo json_encode($users);
        exit;
    } elseif (isset($_GET['search_posts'])) {
        $search = trim($_GET['search_posts']);
        $posts = $dashboard->fetchPosts($search);
        echo json_encode($posts);
        exit;
    }
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
            max-height: 400px;
            overflow-y: auto;
        }

        .scrollable-table tbody {
            max-height: 200px;
            /* Adjust the height as needed */
            overflow-y: auto;
            display: block;
        }

        .scrollable-table thead,
        .scrollable-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        body {
            background-color: #f8f9fa;
            margin-bottom: 50px;
            padding-bottom: 50px;
        }

        .table th {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
        }

        .table th,
        .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 26%;
        }

        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 15%;
        }
    </style>
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5">
        <h3 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['admin_username']); ?>!</h3>

        <div class="row">
            <div class="col-md-6">
                <h3>Users</h3>
                <!--box with user count-->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-header">Users</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $dashboard->countUsers(); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-header">Posts</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $dashboard->countPosts(); ?></h5>
                            </div>
                        </div>
                    </div>
                    <!--box with daily login count-->
                    <div class="col-md-6">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-header">Daily Logins</div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $dashboard->countDailyLogins(); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="search-container mb-3">
                    <input type="text" id="search-users" class="form-control" placeholder="Search users...">
                    <span class="search-icon">
                        <img src="../../assets/images/loading.gif" class="loading-spinner" id="users-loading">
                        <i class="bi bi-search"></i>
                    </span>
                </div>
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
                        <tbody id="users-table"></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-6">
                <h3>Posts</h3>
                <div class="search-container mb-3">
                <input type="text" id="search-posts" class="form-control" placeholder="Search posts by content, username, date (YYYY-MM-DD), or user ID">
                    <span class="search-icon">
                        <img src="../../assets/images/loading.gif" class="loading-spinner" id="posts-loading">
                        <i class="bi bi-search"></i>
                    </span>
                </div>
                <div class="scrollable-table">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Content</th>
                                <th>Image</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="posts-table"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="width: fit-content;">
                    <p style="width: fit-content;"><strong>Username:</strong> <span id="modalUsername"></span></p>
                    <p style="width: fit-content; word-break: break-all;"><strong>Email:</strong> <span id="modalEmail"></span></p>
                    <p style="width: fit-content;"><strong>Posts:</strong> <span id="modalPostCount"></span></p>
                    <p style="width: fit-content;"><strong>Comments:</strong> <span id="modalCommentCount"></span></p>
                    <p><strong>Likes:</strong> <span id="modalLikeCount"></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Modal -->
    <div class="modal fade" id="postModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Post Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p id="postContent" class="mb-3"></p>
                    <img id="postImage" src="" alt="Post image" class="img-fluid">
                    <div class="mt-3">
                        <p id="postInfo" class="text-muted"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let usersSearchTimeout;
        let postsSearchTimeout;

        function fetchUsers(search = '') {
            $('#users-loading').show();
            $.ajax({
                url: 'admin_dashboard.php',
                type: 'GET',
                data: {
                    search_users: search
                },
                success: function(data) {
                    const users = JSON.parse(data);
                    const usersTable = $('#users-table');
                    usersTable.empty();
                    users.forEach(user => {
                        usersTable.append(`
                        <tr>
                            <td>${user.id}</td>
                            <td><span class="username-link" data-id="${user.id}">${user.username}</span></td>
                            <td>${user.email}</td>
                            <td><span class="delete-btn" data-id="${user.id}" data-type="user">Delete</span></td>
                        </tr>
                    `);
                    });
                },
                complete: function() {
                    $('#users-loading').hide();
                }
            });
        }

        function fetchPosts(search = '') {
            $('#posts-loading').show();
            $.ajax({
                url: 'admin_dashboard.php',
                type: 'GET',
                data: {
                    search_posts: search
                },
                success: function(data) {
                    const posts = JSON.parse(data);
                    const postsTable = $('#posts-table');
                    postsTable.empty();
                    posts.forEach(post => {
                        const imageUrl = post.image_url ? `../../uploads/${post.image_url}` : '';
                        const truncatedContent = post.content.length > 50 ?
                            post.content.substring(0, 50) + '...' :
                            post.content;

                        postsTable.append(`
                        <tr>
                            <td>${post.id}</td>
                            <td>${post.username || 'Unknown'}</td>
                            <td>${truncatedContent}</td>
                            <td>
                                ${post.image_url ? 
                                    `<img src="${imageUrl}" class="post-image" 
                                          data-content="${post.content.split(' ').slice(0, 9).join(' ')}..."
                                          data-image-url="${imageUrl}"
                                          data-username="${post.username || 'Unknown'}"
                                          data-created="${post.created_at}"
                                          alt="Post image">` : 
                                    'No image'}
                            </td>
                            <td>${new Date(post.created_at).toLocaleString()}</td>
                            <td><span class="delete-btn" data-id="${post.id}" data-type="post">Delete</span></td>
                        </tr>
                    `);
                    });
                },
                complete: function() {
                    $('#posts-loading').hide();
                }
            });
        }

        // Initial load
        fetchUsers();
        fetchPosts();

        // Search handlers with debouncing
        $('#search-users').on('input', function() {
            clearTimeout(usersSearchTimeout);
            usersSearchTimeout = setTimeout(() => {
                fetchUsers($(this).val());
            }, 300);
        });

        $('#search-posts').on('input', function() {
            clearTimeout(postsSearchTimeout);
            postsSearchTimeout = setTimeout(() => {
                fetchPosts($(this).val());
            }, 300);
        });

        // Delete handlers
        $(document).on('click', '.delete-btn', function() {
            if (confirm('Are you sure you want to delete this?')) {
                const id = $(this).data('id');
                const type = $(this).data('type');
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: {
                        action: type === 'user' ? 'delete_user' : 'delete_post',
                        id: id
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            if (type === 'user') {
                                fetchUsers($('#search-users').val());
                            } else {
                                fetchPosts($('#search-posts').val());
                            }
                        }
                    }
                });
            }
        });

        // Post image modal handler
        $(document).on('click', '.post-image', function() {
            const $this = $(this);
            $('#postContent').text($this.data('content'));
            $('#postImage').attr('src', $this.data('image-url'));
            $('#postInfo').html(`
            Posted by: ${$this.data('username')}<br>
            Created: ${new Date($this.data('created')).toLocaleString()}
            
        `);
            $('#postModal').modal('show');
        });

        // Username click handler
        $(document).on('click', '.username-link', function() {
            const userId = $(this).data('id');
            $.ajax({
                url: 'admin_dashboard.php',
                type: 'POST',
                data: {
                    action: 'fetch_user_details',
                    id: userId
                },
                success: function(response) {
                    const userDetails = JSON.parse(response);
                    $('#modalUsername').text(userDetails.username);
                    $('#modalEmail').text(userDetails.email);
                    $('#modalPostCount').text(userDetails.post_count);
                    $('#modalCommentCount').text(userDetails.comment_count);
                    $('#modalLikeCount').text(userDetails.like_count);
                    $('#userDetailsModal').modal('show');
                }
            });
        });
    </script>
</body>

</html>