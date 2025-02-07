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

    public function toggleLike($post_id)
    {
        if (!$this->user_id) {
            return json_encode(['status' => 'error', 'message' => 'Not logged in']);
        }

        $stmt = $this->conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $this->user_id]);
        $existing_like = $stmt->fetch();

        if ($existing_like) {
            $stmt = $this->conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $this->user_id]);
            $liked = false;
        } else {
            $stmt = $this->conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $this->user_id]);
            $liked = true;
        }

        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $count = $stmt->fetchColumn();

        return json_encode(['status' => 'success', 'liked' => $liked, 'count' => $count]);
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
            SELECT p.*, u.username,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                   EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment($post_id, $content)
    {
        if (!$this->user_id || empty(trim($content))) {
            return json_encode(['status' => 'error', 'message' => 'Invalid comment']);
        }

        $stmt = $this->conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $this->user_id, $content]);

        $comment_id = $this->conn->lastInsertId();
        $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $username = $stmt->fetchColumn();

        return json_encode([
            'status' => 'success',
            'comment_id' => $comment_id,
            'username' => $username,
            'content' => htmlspecialchars($content)
        ]);
    }

    public function getComments($post_id)
    {
        $stmt = $this->conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$post_id]);
        return json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function editComment($comment_id, $content)
    {
        if (!$this->user_id || empty(trim($content))) {
            return json_encode(['status' => 'error', 'message' => 'Invalid comment']);
        }

        $stmt = $this->conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $comment_id, $this->user_id]);

        return json_encode(['status' => 'success', 'content' => htmlspecialchars($content)]);
    }

    public function deleteComment($comment_id)
    {
        if (!$this->user_id) {
            return json_encode(['status' => 'error', 'message' => 'Not logged in']);
        }

        $stmt = $this->conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $this->user_id]);

        return json_encode(['status' => 'success']);
    }
}

$dashboard = new Dashboard($conn);

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'toggle_like') {
        echo $dashboard->toggleLike($_POST['post_id']);
    } elseif ($_POST['action'] == 'add_comment') {
        echo $dashboard->addComment($_POST['post_id'], $_POST['content']);
    } elseif ($_POST['action'] == 'get_comments') {
        echo $dashboard->getComments($_POST['post_id']);
    } elseif ($_POST['action'] == 'edit_comment') {
        echo $dashboard->editComment($_POST['comment_id'], $_POST['content']);
    } elseif ($_POST['action'] == 'delete_comment') {
        echo $dashboard->deleteComment($_POST['comment_id']);
    }
    exit;
}

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
            height: 200px;
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
            font-size: 16px; /* Reduced size */
        }

        .comment-edit-input {
            width: 100%;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h3 class="text-center mb-4">Welcome, <?= htmlspecialchars($user['username']) ?></h3>
        <div class="row">
            <?php foreach ($posts as $post): ?>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <?php if ($post['image_url']): ?>
                            <img src="../../uploads/<?= htmlspecialchars($post['image_url']) ?>" class="post-image card-img-top" alt="Post image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h6><?= htmlspecialchars($post['username']) ?></h6>
                            <p><?= htmlspecialchars($post['content']) ?></p>
                            <button class="like-btn" data-post-id="<?= $post['id'] ?>">
                                <?= $post['user_liked'] ? '❤️' : '🤍' ?>
                            </button>
                            <span class="like-count"><?= $post['like_count'] ?> likes</span>

                            <button class="btn btn-link view-comments" data-post-id="<?= $post['id'] ?>">View Comments</button>
                            <div class="comments-section" data-post-id="<?= $post['id'] ?>">
                                <div class="comments-list"></div>
                                <input type="text" class="form-control comment-input" placeholder="Write a comment...">
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
                $.post("", {
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

                $.post("", {
                    action: 'get_comments',
                    post_id: postId
                }, function(response) {
                    const comments = JSON.parse(response); // Parse JSON
                    let commentsHtml = "";

                    comments.forEach(comment => {
                        commentsHtml += `
                            <div class="comment" data-comment-id="${comment.id}">
                                <p><strong>${comment.username}:</strong> <span class="comment-content">${comment.content}</span></p>
                                <div class="comment-actions">
                                    <span class="edit-comment">✏️</span>
                                    <span class="delete-comment">🗑️</span>
                                </div>
                            </div>
                        `;
                    });

                    section.find('.comments-list').html(commentsHtml); // Display formatted comments
                });
            });

            $('.add-comment').click(function() {
                const input = $(this).siblings('.comment-input');
                const postId = $(this).data('post-id');
                $.post("", {
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

                $.post("", {
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
                    $.post("", {
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