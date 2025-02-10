<?php
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger'>You need to log in to view your posts.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'edit') {
        try {
            $stmt = $conn->prepare("UPDATE posts SET content = :content WHERE id = :post_id AND user_id = :user_id");
            $stmt->execute([
                'content' => $_POST['content'],
                'post_id' => $_POST['post_id'],
                'user_id' => $user_id,
            ]);

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    } elseif ($_POST['action'] === 'delete') {
        try {
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = :post_id AND user_id = :user_id");
            $stmt->execute([
                'post_id' => $_POST['post_id'],
                'user_id' => $user_id,
            ]);

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }
}

$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <style>
       .myposts-container {
            margin-top: 20px;
        }

        .myposts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .myposts-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
        }

        .myposts-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .myposts-text {
            margin-bottom: 10px;
        }

        .myposts-edit-form {
            display: none;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    

    <div class="container mt-4 myposts-container">
    <h3 class="mb-4">My Posts</h3>
    <div class="row myposts-grid" id="my-posts-grid">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-4 mb-4 myposts-card">
                <div class="card shadow-sm">
                    <div class="card-body">
                     <img class = "myposts-image" src="../../uploads/<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post Image">
                        <p class="card-text myposts-text" data-id="<?= htmlspecialchars($post['id']) ?>" >  <?= htmlspecialchars($post['content']) ?> </p>
                        <small class="text-muted">Posted on: <?= htmlspecialchars(date('F j, Y, g:i a', strtotime($post['created_at']))) ?></small>
                        <div class="mt-3 d-flex justify-content-between">
                            <button class="btn btn-warning btn-sm edit-btn" data-id="<?= htmlspecialchars($post['id']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= htmlspecialchars($post['id']) ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $('.edit-btn').click(function () {
        let postId = $(this).data('id');
        let newContent = prompt('Edit your post:', $(this).closest('.card-body').find('.myposts-text').text());
        if (newContent !== null) {
            $.post('view_my_posts.php', { action: 'edit', post_id: postId, content: newContent }, function (response) {
                try{
                let data = JSON.parse(response);
                if (data.status === 'success') {
                     location.reload();
                } else {
                    alert('Error updating post: ' + data.message);
                }
                }
                catch(e){
                    alert('Post was sucessfully edited, please refresh');
                     location.reload();
                }
            });
        }
    });

    $('.delete-btn').click(function () {
        if (confirm('Are you sure you want to delete this post?')) {
            let postId = $(this).data('id');
            $.post('view_my_posts.php', { action: 'delete', post_id: postId }, function (response) {
                   try{
                    let data = JSON.parse(response);
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error deleting post: ' + data.message);
                    }
                   } catch(e){
                    alert('Post was sucessfully deleted, please refresh');
                    location.reload();
                   }
            });
        }
    });
});
</script>
</body>
</html>
