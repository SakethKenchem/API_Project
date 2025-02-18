<?php
require_once '../../includes/db.php'; 
require('../../assets/fpdf/fpdf.php'); 
include '../admin/admin_navbar.php'; 

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

    public function getTotalComments() {
        $stmt = $this->conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM Comments GROUP BY date ORDER BY date");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCommentsData() {
        $stmt = $this->conn->query("SELECT post_id, COUNT(*) as count FROM Comments GROUP BY post_id ORDER BY count DESC LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsers() {
        $stmt = $this->conn->query("SELECT id, username, email, created_at FROM Users ORDER BY created_at");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPosts() {
        $stmt = $this->conn->query("SELECT id, content, image_url, created_at FROM Posts ORDER BY created_at");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getComments() {
        $stmt = $this->conn->query("SELECT id, post_id, content, created_at FROM Comments ORDER BY created_at");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$stats = new AdminStats($conn);

$data = [
    'userGrowth' => $stats->getUserGrowth(),
    'postGrowth' => $stats->getPostGrowth(),
    'likesData' => $stats->getLikesData(),
    'totalComments' => $stats->getTotalComments(),
    'commentsData' => $stats->getCommentsData(),
    'users' => $stats->getUsers(),
    'posts' => $stats->getPosts(),
    'comments' => $stats->getComments()
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_end_clean(); // Clean the output buffer to prevent any previous output
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'Admin Statistics Report', 0, 1, 'C');
            $this->Ln(10);
        }

        function ChapterTitle($title) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, $title, 0, 1, 'L');
            $this->Ln(5);
        }

        function ChapterBody($body) {
            $this->SetFont('Arial', '', 12);
            $this->MultiCell(0, 10, $body);
            $this->Ln();
        }
    }

    $pdf = new PDF();
    $pdf->AddPage();

    $pdf->ChapterTitle('Users');
    foreach ($data['users'] as $row) {
        $pdf->ChapterBody("ID: " . $row['id'] . " - Username: " . $row['username'] . " - Email: " . $row['email'] . " - Created At: " . $row['created_at']);
    }

    $pdf->ChapterTitle('User Growth');
    foreach ($data['userGrowth'] as $row) {
        $pdf->ChapterBody("Date: " . $row['date'] . " - Count: " . $row['count']);
    }

    $pdf->AddPage();
    $pdf->ChapterTitle('Post Growth');
    foreach ($data['postGrowth'] as $row) {
        $pdf->ChapterBody("Date: " . $row['date'] . " - Count: " . $row['count']);
    }

    $pdf->AddPage();
    $pdf->ChapterTitle('Top 10 Liked Posts');
    foreach ($data['likesData'] as $row) {
        $pdf->ChapterBody("Post ID: " . $row['post_id'] . " - Likes: " . $row['count']);
    }

    $pdf->AddPage();
    $pdf->ChapterTitle('Total Comments');
    foreach ($data['totalComments'] as $row) {
        $pdf->ChapterBody("Date: " . $row['date'] . " - Count: " . $row['count']);
    }

    $pdf->AddPage();
    $pdf->ChapterTitle('Top 10 Commented Posts');
    foreach ($data['commentsData'] as $row) {
        $pdf->ChapterBody("Post ID: " . $row['post_id'] . " - Comments: " . $row['count']);
    }

    $pdf->Output('D', 'admin_stats_report.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Stats</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        canvas {
            margin-top: 20px;
            float: left;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4 text-center">Admin Statistics</h2>
        <form method="post" action="">
            <button type="submit" class="btn btn-primary mb-4">Download Report as PDF</button>
        </form>
        <canvas class="userGrowthChart" id="userGrowthChart" width="400" height="200"></canvas>
        <canvas class="postGrowthChart" id="postGrowthChart" width="400" height="200"></canvas>
        <canvas class="likesChart" id="likesChart" width="400" height="200"></canvas>
        <canvas class="commentsChart" id="commentsChart" width="400" height="200"></canvas>
        <canvas class="topCommentsChart" id="topCommentsChart" width="400" height="200"></canvas>
    </div>
    <script>
        const userGrowthData = <?php echo json_encode($data['userGrowth']); ?>;
        const postGrowthData = <?php echo json_encode($data['postGrowth']); ?>;
        const likesData = <?php echo json_encode($data['likesData']); ?>;
        const totalCommentsData = <?php echo json_encode($data['totalComments']); ?>;
        const commentsData = <?php echo json_encode($data['commentsData']); ?>;

        const userGrowthLabels = userGrowthData.map(item => item.date);
        const userGrowthCounts = userGrowthData.map(item => item.count);

        const postGrowthLabels = postGrowthData.map(item => item.date);
        const postGrowthCounts = postGrowthData.map(item => item.count);

        const likesLabels = likesData.map(item => `Post ID: ${item.post_id}`);
        const likesCounts = likesData.map(item => item.count);

        const totalCommentsLabels = totalCommentsData.map(item => item.date);
        const totalCommentsCounts = totalCommentsData.map(item => item.count);

        const commentsLabels = commentsData.map(item => `Post ID: ${item.post_id}`);
        const commentsCounts = commentsData.map(item => item.count);

        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: userGrowthLabels,
                datasets: [{
                    label: 'User Growth',
                    data: userGrowthCounts,
                    borderColor: 'rgb(9, 228, 228)',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { display: true },
                    y: { display: true }
                }
            }
        });

        new Chart(document.getElementById('postGrowthChart'), {
            type: 'line',
            data: {
                labels: postGrowthLabels,
                datasets: [{
                    label: 'Post Growth',
                    data: postGrowthCounts,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { display: true },
                    y: { display: true }
                }
            }
        });

        new Chart(document.getElementById('likesChart'), {
            type: 'bar',
            data: {
                labels: likesLabels,
                datasets: [{
                    label: 'Top 10 Liked Posts',
                    data: likesCounts,
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderColor: 'rgb(210, 114, 17)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { display: true },
                    y: { display: true }
                }
            }
        });

        new Chart(document.getElementById('commentsChart'), {
            type: 'line',
            data: {
                labels: totalCommentsLabels,
                datasets: [{
                    label: 'Total Comments',
                    data: totalCommentsCounts,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { display: true },
                    y: { display: true }
                }
            }
        });

        new Chart(document.getElementById('topCommentsChart'), {
            type: 'bar',
            data: {
                labels: commentsLabels,
                datasets: [{
                    label: 'Top 10 Commented Posts',
                    data: commentsCounts,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(179, 40, 70)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { display: true },
                    y: { display: true }
                }
            }
        });
    </script>
</body>
</html>