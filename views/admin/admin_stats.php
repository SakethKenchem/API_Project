<?php
require_once '../../includes/db.php'; // Database connection

class AdminStats {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getUserGrowth() {
        $stmt = $this->conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM Users GROUP BY date ORDER BY date");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPostGrowth() {
        $stmt = $this->conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM Posts GROUP BY date ORDER BY date");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLikesData() {
        $stmt = $this->conn->query("SELECT post_id, COUNT(*) as count FROM Likes GROUP BY post_id ORDER BY count DESC LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFollowersData() {
        $stmt = $this->conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM Followers GROUP BY date ORDER BY date");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCommentsData() {
        $stmt = $this->conn->query("SELECT post_id, COUNT(*) as count FROM Comments GROUP BY post_id ORDER BY count DESC LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$stats = new AdminStats($conn);

$data = [
    'userGrowth' => $stats->getUserGrowth(),
    'postGrowth' => $stats->getPostGrowth(),
    'likesData' => $stats->getLikesData(),
    'followersData' => $stats->getFollowersData(),
    'commentsData' => $stats->getCommentsData()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Stats</title>
    <script src="../../node_modules/chart.js/dist/chart.umd.js"></script>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #343a40;
        }
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .chart-container {
            position: relative;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4 text-center">Admin Statistics</h2>
        <div class="chart-grid">
            <div class="chart-container"><canvas id="userGrowthChart"></canvas></div>
            <div class="chart-container"><canvas id="postChart"></canvas></div>
            <div class="chart-container"><canvas id="likesChart"></canvas></div>
            <div class="chart-container"><canvas id="followersChart"></canvas></div>
            <div class="chart-container"><canvas id="commentsChart"></canvas></div>
        </div>
    </div>

    <script>
        const data = <?php echo json_encode($data); ?>;

        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: { labels: data.userGrowth.map(d => d.date), datasets: [{ label: 'Users', data: data.userGrowth.map(d => d.count), borderColor: 'blue', backgroundColor: 'rgba(0, 0, 255, 0.1)', fill: true }] },
        });

        new Chart(document.getElementById('postChart'), {
            type: 'line',
            data: { labels: data.postGrowth.map(d => d.date), datasets: [{ label: 'Posts', data: data.postGrowth.map(d => d.count), borderColor: 'green', backgroundColor: 'rgba(0, 128, 0, 0.1)', fill: true }] },
        });

        new Chart(document.getElementById('likesChart'), {
            type: 'bar',
            data: { labels: data.likesData.map(d => 'Post ' + d.post_id), datasets: [{ label: 'Likes', data: data.likesData.map(d => d.count), backgroundColor: 'red' }] },
        });

        new Chart(document.getElementById('followersChart'), {
            type: 'line',
            data: { labels: data.followersData.map(d => d.date), datasets: [{ label: 'New Followers', data: data.followersData.map(d => d.count), borderColor: 'purple', backgroundColor: 'rgba(128, 0, 128, 0.1)', fill: true }] },
        });

        new Chart(document.getElementById('commentsChart'), {
            type: 'bar',
            data: { labels: data.commentsData.map(d => 'Post ' + d.post_id), datasets: [{ label: 'Comments', data: data.commentsData.map(d => d.count), backgroundColor: 'orange' }] },
        });
    </script>
</body>
</html>
