<?php
session_name("user_session");
session_start();

require '../../config.php';
require '../../includes/db.php';
require '../../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../views/user/login.php");
    exit();
}

class Post
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createPost($user_id, $content, $image_url = null)
    {
        try {
            $query = "INSERT INTO posts (user_id, content, image_url) VALUES (:user_id, :content, :image_url)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":image_url", $image_url);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating post: " . $e->getMessage());
            return false;
        }
    }
}

$post = new Post($conn);
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $content = htmlspecialchars($_POST['content']);
    $image_url = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../../uploads/';
        $target_file = $upload_dir . basename($_FILES['image']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = basename($_FILES['image']['name']);
            } else {
                $message = "<div class='alert alert-danger'>Error uploading the image.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Invalid file type. Only JPG, MP4, JPEG, PNG, WEBP and GIF allowed.</div>";
        }
    }

    if (empty($message)) {
        if ($post->createPost($user_id, $content, $image_url)) {
            $message = "<div class='alert alert-success'>Post created successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to create post. Please try again.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
        .form-control, .btn {
            border-radius: 0.25rem;
        }
        .form-group label {
            font-weight: bold;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Create a Post</h1>
        <?php if (!empty($message)) echo $message; ?>

        <form action="create_post.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <textarea class="form-control" name="content" rows="3" placeholder="What's on your mind?" required></textarea>
            </div>
            <div class="form-group mt-3">
                <label for="image" class="form-label">Upload Image</label>
                <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-3">Post</button>
        </form>
    </div>

    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
