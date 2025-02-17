<?php
session_name("user_session");
session_start();

require '../../includes/db.php';

//if not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

class ViewPost
{
    private $conn;
    private $post_id;

    public function __construct($conn, $post_id)
    {
        $this->conn = $conn;
        $this->post_id = $post_id;
    }

    public function getPostDetails()
    {
        $query = "SELECT p.id, p.user_id, p.image_url, p.content, p.created_at, u.username 
                  FROM posts p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLikesCount()
    {
        $query = "SELECT COUNT(*) AS likes FROM likes WHERE post_id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['likes'];
    }

    public function getImage()
    {
        $query = "SELECT image_url FROM posts WHERE id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['image_url'];
    }

    public function getComments()
    {
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

    public function addComment($user_id, $content)
    {
        $query = "INSERT INTO comments (post_id, user_id, content, created_at) 
                  VALUES (:post_id, :user_id, :content, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function getUserLikeStatus()
    {
        $query = "SELECT COUNT(*) AS liked FROM likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $this->post_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['liked'] > 0;
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
$post = new ViewPost($conn, $post_id, $_SESSION['user_id'] ?? null);
$likes = $post->getLikesCount();
$userLiked = $post->getUserLikeStatus();
$heartEmoji = $userLiked ? "❤️" : "🤍";
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
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .post-image {
            flex: 1 1 300px;
            text-align: center;
        }

        .post-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .post-details {
            flex: 2 1 500px;
        }

        .post-comments {
            margin-top: 20px;
            max-height: 300px;
            /* Adjust the height as needed */
            overflow-y: auto;
        }

        .comment-form textarea {
            resize: none;
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container mt-5">
        <div class="post-container">
            <div class="post-image">
                <img src="../../uploads/<?php echo $post->getImage(); ?>" alt="Post image">
            </div>
            <div class="post-details">
                <h5><a style="text-decoration: none; color: black;" href="view_profile.php?user_id=<?php echo $post_details['user_id']; ?>">@<?php echo ($post_details['username']); ?></a></h5>
                <p><?php echo (($post_details['content'])); ?></p>
                <div>
                    <button id="like-btn" class="btn btn-light" onclick="toggleLike(<?php echo $post_id; ?>)">
                        <span id="like-icon"><?php echo $heartEmoji; ?></span> <span id="like-count"><?php echo $likes; ?></span>
                    </button>
                </div>

                <p><strong>Posted on:</strong> <?php echo $post_details['created_at']; ?></p>

                <div class="post-comments">
                    <h4>Comments</h4>
                    <ul class="list-group">
                        <?php
                        $commentsToShow = array_slice($comments, 0, 4); // Show only the first 4 comments
                        foreach ($commentsToShow as $comment) { ?>
                            <li class="list-group-item">
                                <strong><a style="text-decoration: none; color: black;" href="view_profile.php?user_id=<?php echo $post_details['user_id']; ?>">@<?php echo ($post_details['username']); ?></a></strong>
                                <?php echo (($comment['content'])); ?>
                                <br>
                                <small><?php echo $comment['created_at']; ?></small>
                            </li>
                        <?php } ?>
                    </ul>
                    <?php if (count($comments) > 4) { ?>
                        <button class="btn btn-link" onclick="showAllComments()">Show all comments</button>
                    <?php } ?>
                </div>

                <div class="comment-form mt-4">
                    <h4>Add a Comment</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <textarea name="comment" class="form-control" rows="3" required></textarea>
                        <button type="submit" class="btn btn-primary mt-2">Comment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleLike(postId) {
            let likeBtn = document.getElementById("like-btn");
            let likeIcon = document.getElementById("like-icon");
            let likeCount = document.getElementById("like-count");

            let formData = new FormData();
            formData.append("action", "toggle_like");
            formData.append("post_id", postId);

            fetch("../../views/user/likes.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        likeIcon.innerHTML = data.liked ? "❤️" : "🤍";
                        likeCount.innerText = data.count;
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error("Error:", error));
        }

        function showAllComments() {
            let commentsList = document.querySelector('.post-comments ul');
            commentsList.innerHTML = `<?php foreach ($comments as $comment) { ?>
                <li class="list-group-item">
                    <strong><a style="text-decoration: none; color: black;" href="view_profile.php?user_id=<?php echo $post_details['user_id']; ?>">@<?php echo ($post_details['username']); ?></a></strong>
                    <?php echo (($comment['content'])); ?>
                    <br>
                    <small><?php echo $comment['created_at']; ?></small>
                </li>
            <?php } ?>`;
            document.querySelector('.post-comments button').style.display = 'none';
        }
    </script>

</body>

</html>