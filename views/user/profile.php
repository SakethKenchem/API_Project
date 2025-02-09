<?php
session_name("user_session");
session_start();
require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

class UserProfile {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function getUserDetails() {
        $stmt = $this->conn->prepare("SELECT username, profile_pic, bio FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserPosts() {
        $stmt = $this->conn->prepare("SELECT id, image_url FROM posts WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProfilePic($profile_pic) {
        if (!empty($profile_pic) && file_exists(__DIR__ . "/../../uploads/" . $profile_pic)) {
            return "../../uploads/" . $profile_pic;
        } else {
            return "../../assets/default_profile.jpg";
        }
    }
}

$user_id = $_SESSION['user_id'];
$userProfile = new UserProfile($conn, $user_id);
$user = $userProfile->getUserDetails();
$posts = $userProfile->getUserPosts();
$profile_pic = $userProfile->getProfilePic($user['profile_pic']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../../assets/bootstrap/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .profile-card {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
        }

        .bio {
            font-size: 16px;
            color: #555;
            margin-top: 10px;
        }

        .posts-container {
            max-width: 900px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            padding: 10px;
        }

        .post-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            transition: transform 0.3s ease;
        }

        .post-item img:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <!-- Profile Section -->
    <div class="profile-card">
        <h4>Profile</h4>
        <img src="<?php echo ($user['profile_pic'] ?: 'default.png'); ?>" alt="Profile Picture" class="profile-pic">
        <h4><?php echo ($user['username']); ?></h4>
        <p class="bio"><?php echo nl2br(($user['bio'])); ?></p>
        <a href="mysettings.php" style="font-size: small;">Settings</a>

        <hr style="margin-bottom: -19px;">

        <!-- User Posts -->
        <div class="posts-container">
            <?php foreach ($posts as $post): ?>
                <div class="post-item">
                    <a href="view_post.php?id=<?php echo $post['id']; ?>">
                        <img src="../../uploads/<?php echo ($post['image_url']); ?>" alt="Post Image">
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="../../assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>

</html>