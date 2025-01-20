<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
include '../../includes/navbar.php';
require_once '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../views/user/login.php");
    exit();
}

class Post {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createPost($user_id, $content, $image_url = null) {
        try {
            $query = "INSERT INTO posts (user_id, content, image_url) VALUES (:user_id, :content, :image_url)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":image_url", $image_url);
            return $stmt->execute();
        } catch (PDOException $e) {
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
        $target_dir = "../../uploads/";
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $image_name;
            } else {
                $message = "<div class='alert alert-danger'>Error uploading the image.</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.</div>";
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
</head>
<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Create a Post</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)) echo $message; ?>
                        <form action="create_post.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <textarea class="form-control" name="content" rows="3" placeholder="What's on your mind?" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image" class="form-label">Upload Image (Optional)</label>
                                <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Post</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
