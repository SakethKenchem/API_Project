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
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$this->user_id]);
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
                                <img src="<?= htmlspecialchars($post['profile_pic']) ?: '../../default.png' ?>" alt="Profile Picture" class="post-profile-pic">
                                <h6><a href="../../views/user/view_profile.php?user_id=<?= $post['user_id'] ?>" style="text-decoration: none; color: black;">
                                    <?= htmlspecialchars($post['username']) ?></a></h6>
                            </div>
                            <p><?= ($post['content']) ?></p>
                            <button class="like-btn" data-post-id="<?= $post['id'] ?>">
                                <?= $post['user_liked'] ? '❤️' : '🤍' ?>
                            </button>
                            <span class="like-count"><?= $post['like_count'] ?> likes</span>

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

            $('.view-comments').click(function() {
                const section = $(this).siblings('.comments-section');
                section.toggle();
                const postId = $(this).data('post-id');

                $.post("comments.php", { 
                    action: 'get_comments',
                    post_id: postId
                }, function(response) {
                    const comments = JSON.parse(response); 
                    let commentsHtml = "";
                    comments.forEach(comment => {
                        commentsHtml += `
                            <div class="comment" data-comment-id="${comment.id}">
                                <p><strong><a href="../../views/user/view_profile.php?user_id=${comment.user_id}" style="text-decoration: none; color: black;">${comment.username}</a>:</strong> <span class="comment-content">${comment.content}</span></p>
                                <div class="comment-actions">
                                    <span class="edit-comment">✏️</span>
                                    <span class="delete-comment">🗑️</span>
                                </div>
                            </div>
                        `;
                    });

                    section.find('.comments-list').html(commentsHtml); 
                });
            });

            $('.add-comment').click(function() {
                const input = $(this).siblings('.comment-input');
                const postId = $(this).data('post-id');
                $.post("comments.php", { 
                    action: 'add_comment',
                    post_id: postId,
                    content: input.val()
                }, function(response) {
                    const data = JSON.parse(response);
                    input.val('');
                    input.siblings('.comments-list').append(`
                        <div class="comment" data-comment-id="${data.comment_id}">
                            <p><strong>${data.username}:</strong> <span class="comment-content">${data.content}</span></p>
                            <div class="comment-actions">
                                <span class="edit-comment">✏️</span>
                                <span class="delete-comment">🗑️</span>
                            </div>
                        </div>
                    `);
                });
            });

            $(document).on('click', '.edit-comment', function() {
                const commentDiv = $(this).closest('.comment');
                const commentId = commentDiv.data('comment-id');
                const contentSpan = commentDiv.find('.comment-content');
                const currentContent = contentSpan.text();

                contentSpan.replaceWith(`<input type="text" class="form-control comment-edit-input" value="${currentContent}">`);
                $(this).replaceWith(`<button class="save-comment btn btn-sm btn-success">Save</button>`);
            });

            $(document).on('click', '.save-comment', function() {
                const commentDiv = $(this).closest('.comment');
                const commentId = commentDiv.data('comment-id');
                const input = commentDiv.find('.comment-edit-input');
                const newContent = input.val();

                $.post("comments.php", { 
                    action: 'edit_comment',
                    comment_id: commentId,
                    content: newContent
                }, function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        input.replaceWith(`<span class="comment-content">${data.content}</span>`);
                        $('.save-comment').replaceWith(`<span class="edit-comment">✏️</span>`);
                    }
                });
            });

            $(document).on('click', '.delete-comment', function() {
                const commentDiv = $(this).closest('.comment');
                const commentId = commentDiv.data('comment-id');

                if (confirm('Are you sure you want to delete this comment?')) {
                    $.post("comments.php", { 
                        action: 'delete_comment',
                        comment_id: commentId
                    }, function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            commentDiv.remove();
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>
