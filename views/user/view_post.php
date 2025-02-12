<?php
session_name("user_session");
session_start();

require '../../includes/db.php';

class ViewPost {
    private $conn;
    private $post_id;

    public function __construct($conn, $post_id) {
        $this->conn = $conn;
        $this->post_id = $post_id;
    }

    public function getPostDetails() {
        $query = "SELECT p.id, p.user_id, p.image_url, p.content, p.created_at, u.username 
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLikesCount() {
        $query = "SELECT COUNT(*) AS likes FROM likes WHERE post_id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['likes'];
    }

    public function getImage() {
        $query = "SELECT image_url FROM posts WHERE id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['image_url'];
    }

    public function getComments() {
        $query = "SELECT c.content, c.created_at, u.username 
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.post_id = :post_id 
                  ORDER BY c.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment($user_id, $content) {
        $query = "INSERT INTO comments (post_id, user_id, content, created_at) 
                  VALUES (:post_id, :user_id, :content, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        return $stmt->execute();
    }
}

// Check if post ID is provided
if (!isset($_GET['post_id']) || empty($_GET['post_id'])) {
    die('Invalid Post ID');
}

$post_id = intval($_GET['post_id']);
$post = new ViewPost($conn, $post_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session
    $content = $_POST['comment'];
    $post->addComment($user_id, $content);
    header("Location: view_post.php?post_id=$post_id");
    exit();
}

$post_details = $post->getPostDetails();
if (!$post_details) {
    die('Post not found');
}

$likes = $post->getLikesCount();
$comments = $post->getComments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Post</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <style>
        .post-container {
            height: auto;
            width: auto;
            display: flex;
            gap: 20px;
        }
        .post-image {
            flex: 2;
        }
        .post-comments {
            flex: 1;
            margin-top: 50px;
            margin-right: 120px;
            
        }

        .post-image img {
            width: 300px;
            height: auto;
            object-fit: cover;
            margin-left: 250px;
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container mt-5">
        <div class="post-container">
            <div class="post-image">
                <img src="../../uploads/<?php echo $post->getImage(); ?>" class="post-image card-img-top" alt="Post image">
            </div>
            <div class="post-comments">
                <h5>@<?php echo ($post_details['username']); ?></h5>
                <p><?php echo nl2br(($post_details['content'])); ?></p>
                <p><strong>Likes:</strong> <?php echo $likes; ?></p>
                
                <p><strong>Posted on:</strong> <?php echo $post_details['created_at']; ?></p>
                
                <h4 class="mt-4">Comments</h4>
                <ul class="list-group">
                    <?php foreach ($comments as $comment) { ?>
                        <li class="list-group-item">
                            <strong>@<?php echo ($comment['username']); ?>:</strong>
                            <?php echo nl2br(($comment['content'])); ?>
                            <br>
                            <small><?php echo $comment['created_at']; ?></small>
                        </li>
                    <?php } ?>
                </ul>
                
                <h4 class="mt-4">Add a Comment</h4>
                <form action="" method="POST">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <textarea name="comment" class="form-control" required></textarea>
                    <button type="submit" class="btn btn-primary mt-2">Comment</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
