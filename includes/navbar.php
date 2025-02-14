<?php
require_once 'db.php';
session_start();

class Navbar {
    private $db;
    private $username;
    private $profileLink;
    private $profilePic;

    public function __construct($db) {
        $this->db = $db;
        $this->username = "Guest";
        $this->profileLink = "#";
        $this->profilePic = 'default.png';
        $this->loadUser();
    }

    private function loadUser() {
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("SELECT username, profile_pic FROM users WHERE id = :user_id");
                $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->execute();

                if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->username = htmlspecialchars($result['username']);
                    $this->profileLink = "myprofile.php?user_id=" . htmlspecialchars($_SESSION['user_id']);
                    $this->profilePic = htmlspecialchars($result['profile_pic'] ?: 'default.png');
                }
            } catch (PDOException $e) {
                error_log("Error: " . $e->getMessage());
            }
        }
    }

    public function getUsername() {
        return $this->username;
    }

    public function getProfileLink() {
        return $this->profileLink;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getProfilePic() {
        return $this->profilePic;
    }
}

$user = new Navbar($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Not Instagram</title>

    <!-- Adjust these paths if needed -->
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* Navbar profile picture */
        .navbar-profile-pic {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 5px;
            object-fit: cover;
        }
        /* Search container */
        .search-container {
            position: relative;
            min-width: 250px;
        }
        /* Search dropdown */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            display: none;
            max-height: 300px;
            overflow-y: auto;
        }
        .search-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-decoration: none;
            color: inherit;
            transition: background-color 0.2s ease;
        }
        .search-item:last-child {
            border-bottom: none;
        }
        .search-item:hover {
            background-color: #f8f9fa;
        }
        .search-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
        }
        .search-loading,
        .search-error,
        .search-empty {
            text-align: center;
            padding: 1rem;
            color: #666;
        }
        .search-error {
            color: #dc3545;
        }
        .search-empty {
            color: #6c757d;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Not Instagram</a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Nav Items -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="explore.php">Explore</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_post.php">Create Post</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Nav Items + Search -->
            <div class="d-flex align-items-center">
                <form class="search-container me-3" role="search" onsubmit="return false;">
                    <input
                        type="text"
                        class="form-control"
                        id="searchInput"
                        placeholder="Search users..."
                        autocomplete="off"
                    >
                    <div id="searchResults" class="search-results">
                        <div class="search-loading" style="display: none;">Searching...</div>
                    </div>
                </form>

                <ul class="navbar-nav mb-2 mb-lg-0">
                    <?php if ($user->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $user->getProfileLink(); ?>">
                                <img src="<?= $user->getProfilePic(); ?>"
                                     alt="Profile"
                                     class="navbar-profile-pic">
                                <?= $user->getUsername(); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">Logout</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
$(document).ready(function() {
    let searchTimeout;
    const $searchInput = $('#searchInput');
    const $searchResults = $('#searchResults');
    const $loadingIndicator = $('.search-loading');

    $searchInput.on('input', function() {
        const query = $(this).val().trim();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            // Hide dropdown if too few characters
            $searchResults.slideUp();
            return;
        }

        // Show dropdown & loading
        $searchResults.show();
        $loadingIndicator.show();

        // Debounce for 300ms
        searchTimeout = setTimeout(() => {
            $.ajax({
                url: 'search.php',
                method: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function(response) {
                    $loadingIndicator.hide();
                    let html = '';

                    if (response.status === 'success') {
                        // Build results
                        response.results.forEach(user => {
                            const profilePic = user.profile_pic && user.profile_pic.trim() !== ''
                                ? user.profile_pic
                                : 'default.png';
                            html += `
                                <a href="myprofile.php?user_id=${user.id}" class="search-item">
                                    <img src="${profilePic}" alt="Profile" class="search-profile-pic">
                                    <span>${user.username}</span>
                                </a>
                            `;
                        });
                    }
                    else if (response.status === 'empty') {
                        html = '<div class="search-empty">No users found</div>';
                    }
                    else {
                        // 'error' or any other status
                        html = `<div class="search-error">${response.message}</div>`;
                    }

                    $searchResults.html(html).slideDown();
                },
                error: function(xhr, status, error) {
                    $loadingIndicator.hide();
                    $searchResults.html(`
                        <div class="search-error">
                            An error occurred: ${xhr.status} ${xhr.statusText}. Please try again.
                        </div>
                    `).slideDown();
                }
            });
        }, 300);
    });

    // Hide dropdown if clicked outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-container').length) {
            $searchResults.slideUp();
        }
    });
});
</script>

<script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>