<?php
require_once '../../includes/db.php';

class PostManager {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function getPosts() {
        $stmt = $this->conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deletePost($post_id) {
        $stmt = $this->conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $this->user_id]);
    }
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$postManager = new PostManager($conn, $user_id);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['post_id'];
    $postManager->deletePost($post_id);
    header('Location: view_my_posts.php');
    exit();
}

// Fetch posts
$posts = $postManager->getPosts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Posts</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .post-card { margin-bottom: 20px; }
        .post-image { height: 200px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>My Posts</h2>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-4 post-card">
                    <div class="card">
                        <img src="../../uploads/<?= htmlspecialchars($post['image_url']) ?>" 
                             class="card-img-top post-image" 
                             alt="Post image">
                        <div class="card-body">
                            <p class="card-text"><?= htmlspecialchars($post['content']) ?></p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" name="delete_post" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>