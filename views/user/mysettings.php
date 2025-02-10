<?php
session_name("user_session");
session_start();

require_once '../../includes/db.php';
require '../../config.php';
include '../../includes/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

class UserProfileSettings
{
    private $db;
    private $id;
    private $data;

    public function __construct($db, $userId)
    {
        $this->db = $db;
        $this->id = $userId;
        $this->loadUser();
    }

    private function loadUser()
    {
        $stmt = $this->db->prepare("SELECT username, email, created_at, profile_pic FROM users WHERE id = :user_id");
        $stmt->execute(['user_id' => $this->id]);
        $this->data = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUsername($newUsername)
    {
        $stmt = $this->db->prepare("UPDATE users SET username = :username WHERE id = :user_id");
        return $stmt->execute([
            'username' => $newUsername,
            'user_id' => $this->id
        ]);
    }

    public function getData()
    {
        return $this->data;
    }

    public function getComments()
    {
        $stmt = $this->db->prepare("
            SELECT c.*, p.content AS post_content
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            WHERE c.user_id = :user_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['user_id' => $this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateComment($commentId, $newContent)
    {
        $stmt = $this->db->prepare("UPDATE comments SET content = :content WHERE id = :comment_id AND user_id = :user_id");
        return $stmt->execute([
            'content' => $newContent,
            'comment_id' => $commentId,
            'user_id' => $this->id
        ]);
    }

    public function deleteComment($commentId)
    {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = :comment_id AND user_id = :user_id");
        return $stmt->execute([
            'comment_id' => $commentId,
            'user_id' => $this->id
        ]);
    }

    public function updateProfilePic($imagePath)
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_pic = :profile_pic WHERE id = :user_id");
        return $stmt->execute([
            'profile_pic' => $imagePath,
            'user_id' => $this->id
        ]);
    }

}

$message = '';
$userProfile = new UserProfileSettings($conn, $_SESSION['user_id']);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $targetDir = "../../uploads/";  
    $targetFile = $targetDir . basename($_FILES["profile_pic"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));


    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if ($check === false) {
        $message = "File is not an image.";
        $uploadOk = 0;
    }

    if ($_FILES["profile_pic"]["size"] > 500000) {
        $message = "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
        $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        $message = "Sorry, your file was not uploaded.";
        
    } else {
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
            
            if ($userProfile->updateProfilePic($targetFile)) {
                $message = "Profile picture updated successfully!";
                
                $userProfile = new UserProfile($conn, $_SESSION['user_id']);
            } else {
                $message = "Failed to update profile picture path in the database.";
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
        }
    }
} if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_username'])) {
        if ($userProfile->updateUsername($_POST['new_username'])) {
            $message = "Username updated successfully!";
            $userProfile = new UserProfile($conn, $_SESSION['user_id']);
        }
    }
}

$userData = $userProfile->getData();
$comments = $userProfile->getComments(); // Retrieve the comments
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Not Instagram</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .comments-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .comment-actions {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-top: 5px;
        }

        .comment-actions button,
        .comment-actions a {
            padding: 5px 10px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .comment-actions button:hover,
        .comment-actions a:hover {
            background-color: #0056b3;
        }

        .liked-posts-container {
            max-height: 300px; /* Adjust this value to your desired height */
            overflow-y: auto; /* This enables the vertical scrollbar */
        }
    </style>
</head>

<body>
<div class="container mt-5">
    <?php if ($message): ?>
        <div class="alert alert-info"><?= ($message) ?></div>
    <?php endif; ?>

    <!-- Profile Information Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">Profile Information</h3>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Account Details</h5>
                        <p><strong>Email:</strong> <?= ($userData['email']) ?></p>
                        <p><strong>Member Since:</strong> <?= (date('F j, Y', strtotime($userData['created_at']))) ?></p>
                    </div>
                    <div class="mb-4">
                        <h5>Update Username</h5>
                        <form method="POST" class="mt-3">
                            <div class="mb-3">
                                <label for="current_username" class="form-label">Current Username</label>
                                <input type="text" class="form-control" id="current_username"
                                       value="<?= ($userData['username']) ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="new_username" class="form-label">New Username</label>
                                <input type="text" class="form-control" id="new_username" name="new_username"
                                       required minlength="3" maxlength="30">
                                <div class="form-text">Username must be 3-30 characters long.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Username</button>
                        </form>
                    </div>

                    <!-- Profile Picture Update Form -->
                    <div class="mb-4">
                        <h5>Update Profile Picture</h5>
                        <form id="profile-pic-form" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Upload Profile Picture</label>
                                <input type="file" class="form-control" id="profile_pic" name="profile_pic"
                                       accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </form>
                        <img src="<?= ($userData['profile_pic'] ?: 'default.png') ?>" class="rounded-circle"
                             width="150" height="150" alt="Profile Picture">
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Comments and Liked Posts Section -->
    <div class="row justify-content-center mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Your Comments</h3>
                </div>
                <div class="card-body comments-container">
                    <?php if (empty($comments)): ?>
                        <p>You haven't made any comments yet.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($comments as $comment): ?>
                                <li class="list-group-item">
                                    <p>
                                        <strong>On post:</strong> <?= ($comment['post_content']) ?>
                                        <br>
                                        <strong>Comment:</strong>
                                        <span id="comment-content-<?= $comment['id'] ?>">
                                            <?= ($comment['content']) ?>
                                        </span>
                                    </p>
                                    <small>Posted on: <?= (date('F j, Y, g:i a', strtotime($comment['created_at']))) ?></small>
                                    <div class="comment-actions">
                                        <button onclick="editComment(<?= $comment['id'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteComment(<?= $comment['id'] ?>)"
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                    <div id="edit-form-<?= $comment['id'] ?>" style="display:none;">
                                        <div class="form-group">
                                            <label for="comment-content">Edit Comment:</label>
                                            <textarea class="form-control" id="comment-edit-input-<?= $comment['id'] ?>"
                                                      rows="3"><?= ($comment['content']) ?></textarea>
                                        </div>
                                        <button onclick="saveComment(<?= $comment['id'] ?>)"
                                                class="btn btn-primary btn-sm">Save
                                        </button>
                                        <button onclick="cancelEdit(<?= $comment['id'] ?>)"
                                                class="btn btn-secondary btn-sm">Cancel
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <?php include 'view_likes.php'; ?>
        </div>
    </div>
</div>

<script>
    function editComment(commentId) {
        document.getElementById('comment-content-' + commentId).style.display = 'none';
        document.getElementById('edit-form-' + commentId).style.display = 'block';
    }

    function cancelEdit(commentId) {
        document.getElementById('comment-content-' + commentId).style.display = 'inline';
        document.getElementById('edit-form-' + commentId).style.display = 'none';
    }

    function saveComment(commentId) {
        const newContent = document.getElementById('comment-edit-input-' + commentId).value;

        $.post("profile_actions.php", {
                action: 'edit_comment',
                comment_id: commentId,
                content: newContent
            },
            function (response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    document.getElementById('comment-content-' + commentId).innerText = data.content;
                    cancelEdit(commentId);
                } else {
                    alert('Failed to update comment: ' + data.message);
                }
            })
            .fail(function () {
                alert('An error occurred while updating the comment.');
            });
    }

    function deleteComment(commentId) {
        if (confirm('Are you sure you want to delete this comment?')) {
            $.post("profile_actions.php", {
                    action: 'delete_comment',
                    comment_id: commentId
                },
                function (response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        
                        const commentElement = document.querySelector(`.list-group-item [data-comment-id="${commentId}"]`);
                        if (commentElement) {
                            commentElement.remove();
                        }
                        location.reload();
                    } else {
                        alert('Failed to delete comment: ' + data.message);
                    }
                })
                .fail(function () {
                    alert('An error occurred while deleting the comment.');
                });
        }
    }

</script>
</body>

</html>
