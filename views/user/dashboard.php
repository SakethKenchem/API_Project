<?php
session_name("user_session");
session_start();

include '../../includes/navbar.php';
require '../../config.php';
require '../../includes/db.php';

class Dashboard
{
    private $conn;
    private $user_id;
    private $user;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->user_id = $_SESSION['user_id'] ?? null;

        if (!$this->user_id) {
            header('Location: ../../views/user/login.php');
            exit();
        }

        $this->loadUserData();
    }


    public function getUserId()
    {
        return $this->user_id;
    }

    private function loadUserData()
    {
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $this->user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$this->user) {
            header('Location: ../../views/user/logout.php');
            exit();
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getPosts()
    {
        $stmt = $this->conn->prepare("
            SELECT posts.*, 
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS like_count,
                   (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND user_id = ?) AS user_liked
            FROM posts
            ORDER BY created_at DESC
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$dashboard = new Dashboard($conn);
$user = $dashboard->getUser();
$posts = $dashboard->getPosts();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="container mt-5 d-flex flex-column align-items-center">
        <h3 class="text-center mb-4">Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h3>

        <?php foreach ($posts as $post): ?>
            <div class="card mb-3 shadow-sm" style="width: 400px; border-radius: 10px;">
                <?php if ($post['image_url']): ?>
                    <img src="../../uploads/<?php echo $post['image_url']; ?>" class="card-img-top" alt="Post Image" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                <?php endif; ?>

                <div class="card-body text-center">
                    <h6 class="text-muted"><?php echo $user['username']; ?></h6>
                    <p class="card-text"><?php echo $post['content']; ?></p>

                    <?php
                    // Check if the user has liked the post
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND user_id = ?");
                    $stmt->execute([$post['id'], $_SESSION['user_id']]);
                    $isLiked = $stmt->fetchColumn() > 0;
                    ?>

                    <form action="../../includes/toggle_like.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post['id']; ?>">
                        <button type="submit" class="like-btn" style="border: none; background: none; font-size: 24px; cursor: pointer;">
                            <?= $isLiked ? '❤️' : '🤍'; ?> <!-- Filled or empty heart -->
                        </button>
                    </form>


                    <!-- Like Count -->
                    <small class="text-muted">
                        <?php
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
                        $stmt->execute([$post['id']]);
                        echo $stmt->fetchColumn() . " Likes";
                        ?>
                    </small>
                </div>

                <div class="card-footer text-center text-muted small">
                    Posted on <?php echo date("F j, Y", strtotime($post['created_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">


    </div>

    <script>
        $(document).ready(function() {
            $(".like-btn").click(function() {
                var button = $(this);
                var postId = button.data("post-id");

                $.ajax({
                    url: "../../includes/toggle_like.php",
                    type: "POST",
                    data: {
                        post_id: postId
                    },
                    success: function(response) {
                        console.log(response); // Log the response
                        var data = JSON.parse(response);
                        if (data.status === "success") {
                            button.text(data.liked ? "Unlike" : "Like");
                            button.siblings(".like-count").text(data.like_count + " Likes");
                        } else {
                            alert("Error: " + data.message);
                        }
                    },
                    error: function() {
                        alert("Failed to send request.");
                    }
                });
            });
        });
    </script>
</body>

</html>