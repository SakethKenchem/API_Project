<?php
session_name("user_session");
session_start();

if (!isset($_POST['action'])) {
    include '../../includes/navbar.php';
}
require '../../config.php';
require '../../includes/db.php';

class Dashboard
{
    private $conn;
    private $user_id;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getUserInfo()
    {
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPosts()
    {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.username, u.profile_pic,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN followers f ON f.following_id = p.user_id AND f.follower_id = ?
            WHERE p.user_id = ? OR f.follower_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$this->user_id, $this->user_id, $this->user_id, $this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$dashboard = new Dashboard($conn);

if (!$dashboard->getUserId()) {
    header('Location: login.php');
    exit;
}

$user = $dashboard->getUserInfo();
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
    <style>
        .like-btn {
            border: none;
            background: none;
            cursor: pointer;
            font-size: 24px;
        }

        .post-image {
            width: 100%;
            height: auto;
            object-fit: cover;
        }

        .comments-section {
            display: none;
        }

        .comment-actions {
            display: flex;
            justify-content: space-between;
        }

        .comment-actions .edit-comment,
        .comment-actions .delete-comment {
            cursor: pointer;
            margin-left: 10px;
            font-size: 16px;
        }

        .comment-edit-input {
            width: 100%;
            margin-top: 5px;
        }

        .post-profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 5px;
        }

        .post-header {
            display: flex;
            align-items: center;
        }

        /* Added Flyout CSS for Comments (as requested) */
        .flyout {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            text-align: center;
            z-index: 1000;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 900;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h3 class="text-center mb-4">Welcome, <?= ($user['username']) ?></h3>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-3 mb-4">
                    <div class="card" style="width: fit-content;">
                        <?php if ($post['image_url']): ?>
                            <a href="../../views/user/view_post.php?post_id=<?= $post['id'] ?>">
                                <img src="../../uploads/<?= ($post['image_url']) ?>" class="post-image card-img-top" alt="Post image">
                            </a>
                        <?php endif; ?>
                        <div class="card-body">
                            <p style="font-size: xx-small;"><?= date('M d, Y h:i A', strtotime($post['created_at'])) ?></p>
                            <div class="post-header">
                                <img src="<?= ($post['profile_pic']) ?: '../../default.png' ?>" alt="Profile Picture" class="post-profile-pic">
                                <h6>
                                    <a href="../../views/user/view_profile.php?user_id=<?= $post['user_id'] ?>" style="text-decoration: none; color: black;">
                                        <?= ($post['username']) ?>
                                    </a>
                                </h6>
                            </div>
                            <p><?= ($post['content']) ?></p>
                            <button class="like-btn" data-post-id="<?= $post['id'] ?>">
                                <?= $post['user_liked'] ? '❤️' : '🤍' ?>
                            </button>
                            <span class="like-count"><?= $post['like_count'] ?> likes</span>

                            <!-- Existing inline comments section remains unchanged -->
                            <button class="btn btn-link view-comments" data-post-id="<?= $post['id'] ?>">View Comments</button>
                            <div class="comments-section" data-post-id="<?= $post['id'] ?>">
                                <div class="comments-list"></div>
                                <input type="text" class="form-control comment-input" placeholder="Write a comment..." required>
                                <button class="btn btn-primary btn-sm add-comment" data-post-id="<?= $post['id'] ?>">Post</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Added Flyout HTML for Comments -->
    <div class="overlay" id="overlay" onclick="closeFlyout()"></div>
    <div class="flyout" id="flyout">
        <h5 id="flyout-title">Comments</h5>
        <ul id="flyout-list" style="list-style: none; padding: 0;"></ul>
    </div>

    <script>
        $(document).ready(function() {
            $('.like-btn').click(function() {
                const btn = $(this);
                $.post("likes.php", { 
                    action: 'toggle_like',
                    post_id: btn.data('post-id')
                }, function(response) {
                    const data = JSON.parse(response);
                    btn.html(data.liked ? '❤️' : '🤍');
                    btn.siblings('.like-count').text(data.count + ' likes');
                });
            });

            // Override the view-comments click event to open the flyout
            $('.view-comments').off('click').on('click', function() {
                const postId = $(this).data('post-id');
                let flyoutList = document.getElementById('flyout-list');
                flyoutList.innerHTML = '';
                
                // Fetch comments for the flyout via AJAX
                $.post("comments.php", { 
                    action: 'get_comments',
                    post_id: postId
                }, function(response) {
                    let comments = JSON.parse(response);
                    comments.forEach(comment => {
                        let li = document.createElement('li');
                        li.setAttribute('data-comment-id', comment.id);
                        li.innerHTML = `<strong><a href="../../views/user/view_profile.php?user_id=${comment.user_id}" style="text-decoration: none; color: black;">${comment.username}</a>:</strong> <span class="comment-content">${comment.content}</span> 
                            <button class="edit-comment btn btn-sm btn-link">Edit</button>
                            <button class="delete-comment btn btn-sm btn-link">Delete</button>`;
                        flyoutList.appendChild(li);
                    });
                });

                document.getElementById('overlay').style.display = 'block';
                document.getElementById('flyout').style.display = 'block';
            });
        });

        function closeFlyout() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('flyout').style.display = 'none';
        }

        // AJAX-based Edit and Delete functions for flyout comments
        $(document).on('click', '.flyout .edit-comment', function() {
            const li = $(this).closest('li');
            const commentId = li.data('comment-id');
            const contentSpan = li.find('.comment-content');
            const currentContent = contentSpan.text();
            contentSpan.replaceWith(`<input type="text" class="comment-edit-input" value="${currentContent}">`);
            $(this).text('Save').removeClass('edit-comment').addClass('save-comment');
        });

        $(document).on('click', '.flyout .save-comment', function() {
            const li = $(this).closest('li');
            const commentId = li.data('comment-id');
            const newContent = li.find('.comment-edit-input').val();
            $.post("comments.php", { 
                action: 'edit_comment',
                comment_id: commentId,
                content: newContent
            }, function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    li.find('.comment-edit-input').replaceWith(`<span class="comment-content">${data.content}</span>`);
                    li.find('.save-comment').text('Edit').removeClass('save-comment').addClass('edit-comment');
                }
            });
        });

        $(document).on('click', '.flyout .delete-comment', function() {
            const li = $(this).closest('li');
            const commentId = li.data('comment-id');
            if (confirm('Are you sure you want to delete this comment?')) {
                $.post("comments.php", { 
                    action: 'delete_comment',
                    comment_id: commentId
                }, function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        li.remove();
                    }
                });
            }
        });
    </script>
</body>

</html>