<?php
session_name("user_session");
session_start();
require '../../includes/db.php';
require '../../config.php';

class Explore
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function fetchPosts($offset = 0, $limit = 10)
    {
        $query = "SELECT id, user_id, content, image_url, created_at FROM posts ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$explore = new Explore($conn);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch']) && $_GET['fetch'] === 'posts') {
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $posts = $explore->fetchPosts($offset);
    echo json_encode($posts);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <title>Explore</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .explore-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
            padding: 20px;
        }
        .post-item {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .post-item img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        .post-item:hover img {
            transform: scale(1.05);
        }
        .like-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 50px;
            color: red;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }
        .show-like {
            transform: translate(-50%, -50%) scale(1);
        }
    </style>
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container">
        <h3 class="mt-4">Explore</h3>
        <div class="explore-container" id="explore-container"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    let offset = 0;
    function fetchPosts() {
        $.ajax({
            url: 'explore.php',
            type: 'GET',
            data: { fetch: 'posts', offset: offset },
            success: function(data) {
                const posts = JSON.parse(data);
                const container = $('#explore-container');
                posts.forEach(post => {
                    container.append(`
                        <div class="post-item" data-id="${post.id}">
                            <a href="../../views/user/view_post.php?post_id=${post.id}">
                                <img src="../../uploads/${post.image_url}" class="post-image card-img-top" alt="Post image">
                            </a>
                            <div class="like-overlay">❤️</div>
                        </div>
                    `);
                });
                offset += posts.length;
            }
        });
    }
    
    $(document).ready(function() {
        fetchPosts();

        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
                fetchPosts();
            }
        });
        
        $(document).on('dblclick', '.post-item', function() {
            const likeOverlay = $(this).find('.like-overlay');
            likeOverlay.addClass('show-like');
            setTimeout(() => likeOverlay.removeClass('show-like'), 600);
        });
        
        $(document).on('click', '.post-item', function() {
            const postId = $(this).data('id');
            window.location.href = `view_post.php?id=${postId}`;
        });
    });
    </script>
</body>
</html>