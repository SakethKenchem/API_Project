<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

// Check if user ID is provided in the GET request
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo "Invalid user ID.";
    exit;
}

$user_id = (int)$_GET['user_id']; // Sanitize user input

try {
    // Fetch user details
    $stmt = $conn->prepare("SELECT username, profile_pic, bio FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.";
        exit;
    }

    // Determine the profile picture path
    $profile_pic = '../../uploads/' . ($user['profile_pic']);
    if (empty($user['profile_pic']) || !file_exists($profile_pic)) {
        $profile_pic = '../assets/default_profile.jpg'; // Corrected path
    }

    // Fetch user posts
    $stmt = $conn->prepare("SELECT id, image_url FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css"> <!-- Corrected path -->
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

    <div class="container mt-5">
        <div class="profile-card">
        <img src="<?php echo ($user['profile_pic'] ?: 'default.png'); ?>" alt="Profile Picture" class="profile-pic">
            <h2><?php echo ($user['username']); ?></h2>
            <p class="bio"><?php echo (($user['bio'])); ?></p>
        </div>

        <div class="posts-container">
            <?php if (empty($posts)): ?>
                <p>This user has not posted anything yet.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-item">
                        <a href="view_post.php?id=<?php echo ($post['id']); ?>">
                            <img src="../../uploads/<?php echo ($post['image_url']); ?>" alt="Post Image"> <!-- Corrected path -->
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/bootstrap/bootstrap.bundle.min.js"></script> <!-- Corrected path-->
</body>
</html>
