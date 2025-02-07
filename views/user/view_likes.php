<?php
if (!isset($_SESSION['user_id'])) {
    echo '<p class="text-center">Please log in to view liked posts.</p>';
    exit();
}

require_once '../../includes/db.php';

class LikedPosts {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    public function getLikedPosts() {
        $stmt = $this->conn->prepare("
            SELECT p.*, l.id AS liked_id 
            FROM posts p
            JOIN likes l ON p.id = l.post_id
            WHERE l.user_id = ?
            ORDER BY l.id DESC
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$likedPostsObj = new LikedPosts($conn, $_SESSION['user_id']);
$likedPosts = $likedPostsObj->getLikedPosts();
?>

<!-- Liked Posts Section with Border and Bottom Margin -->
<div class="card mb-5"> <!-- Added mb-5 class for bottom margin -->
    <div class="card-header">
        <h3 class="mb-0">Liked Posts</h3>
    </div>
    <div class="card-body">
        <?php if(count($likedPosts) > 0): ?>
            <div class="row g-3">
                <?php foreach($likedPosts as $post): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 liked-post-card">
                            <?php if (!empty($post['image_url'])): ?>
                                <img 
                                    src="../../uploads/<?= htmlspecialchars($post['image_url']) ?>" 
                                    alt="Post Image" 
                                    class="card-img-top"
                                    style="height: 200px; object-fit: cover;"
                                >
                            <?php endif; ?>
                            <div class="card-body">
                                <p class="card-text text-truncate">
                                    <?= htmlspecialchars($post['content']) ?>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent text-center">
                                <small class="text-muted">Liked</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center mb-0">You haven't liked any posts yet.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.liked-post-card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.liked-post-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.card-img-top {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}
</style>