<?php
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Redirect based on role
if (isset($_SESSION['is_admin'], $_SESSION['role'])) {
    if ($_SESSION['is_admin'] == 1 && $_SESSION['role'] === 'Executive') {
        header("Location: admin-dashboard.php");
        exit();
    } else {
        header("Location: ../maindashboard.php");
        exit();
    }
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Total counts
$readsResult = $conn->query("SELECT SUM(views) as total_reads FROM articles");
$totalReads = $readsResult->fetch_assoc()['total_reads'] ?? 0;

$savesResult = $conn->query("SELECT COUNT(*) as total_saves FROM article_saves");
$totalSaves = $savesResult->fetch_assoc()['total_saves'] ?? 0;

$likesResult = $conn->query("SELECT COUNT(*) as total_likes FROM article_likes");
$totalLikes = $likesResult->fetch_assoc()['total_likes'] ?? 0;

$sharesResult = $conn->query("SELECT COUNT(*) as total_shares FROM article_shares");
$totalShares = $sharesResult->fetch_assoc()['total_shares'] ?? 0;

// Weekly data (last 7 days)
$weeklyLabels = [];
$weeklyReads = [];
$weeklySaves = [];
$weeklyLikes = [];
$weeklyShares = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weeklyLabels[] = date('D', strtotime($date));

    $r = $conn->query("SELECT COUNT(*) as daily_reads FROM article_views WHERE DATE(viewed_at)='$date'");
    $weeklyReads[] = $r->fetch_assoc()['daily_reads'] ?? 0;

    $s = $conn->query("SELECT COUNT(*) as daily_saves FROM article_saves WHERE DATE(saved_at)='$date'");
    $weeklySaves[] = $s->fetch_assoc()['daily_saves'] ?? 0;

    $l = $conn->query("SELECT COUNT(*) as daily_likes FROM article_likes WHERE DATE(created_at)='$date'");
    $weeklyLikes[] = $l->fetch_assoc()['daily_likes'] ?? 0;

    $sh = $conn->query("SELECT COUNT(*) as daily_shares FROM article_shares WHERE DATE(created_at)='$date'");
    $weeklyShares[] = $sh->fetch_assoc()['daily_shares'] ?? 0;

    // Most Read Article
    $mostReadRes = $conn->query("
        SELECT id, title, views, image_url 
        FROM articles 
        ORDER BY views DESC 
        LIMIT 1
    ");
    $mostRead = $mostReadRes->fetch_assoc();

    // Most Saved Article
    $mostSavedRes = $conn->query("
        SELECT a.id, a.title, a.image_url, COUNT(s.id) as save_count 
        FROM articles a
        LEFT JOIN article_saves s ON a.id = s.article_id
        GROUP BY a.id
        ORDER BY save_count DESC
        LIMIT 1
    ");
    $mostSaved = $mostSavedRes->fetch_assoc();

    // Most Liked Article
    $mostLikedRes = $conn->query("
        SELECT a.id, a.title, a.image_url, COUNT(l.id) as like_count 
        FROM articles a
        LEFT JOIN article_likes l ON a.id = l.article_id
        GROUP BY a.id
        ORDER BY like_count DESC
        LIMIT 1
    ");
    $mostLiked = $mostLikedRes->fetch_assoc();

    // Most Shared Article
    $mostSharedRes = $conn->query("
        SELECT a.id, a.title, a.image_url, COUNT(l.id) as share_count 
        FROM articles a
        LEFT JOIN article_shares l ON a.id = l.article_id
        GROUP BY a.id
        ORDER BY share_count DESC
        LIMIT 1
    ");
    $mostShared = $mostSharedRes->fetch_assoc();
}

// Monthly data (last 12 months)
$monthlyLabels = [];
$monthlyReads = [];
$monthlySaves = [];
$monthlyLikes = [];
$monthlyShares = [];

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[] = date('M Y', strtotime($month . '-01'));

    $r = $conn->query("SELECT COUNT(*) as monthly_reads FROM article_views WHERE DATE_FORMAT(viewed_at,'%Y-%m')='$month'");
    $monthlyReads[] = $r->fetch_assoc()['monthly_reads'] ?? 0;

    $s = $conn->query("SELECT COUNT(*) as monthly_saves FROM article_saves WHERE DATE_FORMAT(saved_at,'%Y-%m')='$month'");
    $monthlySaves[] = $s->fetch_assoc()['monthly_saves'] ?? 0;

    $l = $conn->query("SELECT COUNT(*) as monthly_likes FROM article_likes WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'");
    $monthlyLikes[] = $l->fetch_assoc()['monthly_likes'] ?? 0;

    $sh = $conn->query("SELECT COUNT(*) as monthly_shares FROM article_shares WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'");
    $monthlyShares[] = $sh->fetch_assoc()['monthly_shares'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../adminstyles/admin-style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Analytics Card Responsiveness */
.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}

.cards-container .card {
    flex: 1 1 calc(25% - 15px);
    min-width: 220px;
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
}

.cards-container .card i {
    font-size: 28px;
    color: #004102;
    margin-bottom: 10px;
}

.cards-container .card h2 {
    font-size: 1.4em;
    margin: 5px 0;
}

.cards-container .card p {
    font-size: 0.9em;
    color: #555;
    margin-bottom: 15px;
    text-align: center;
}

.chart-container {
    width: 100%;
    height: 150px;
}

@media (max-width: 1024px) {
    .cards-container .card { flex: 1 1 calc(50% - 15px); }
    .chart-container { height: 130px; }
}

@media (max-width: 768px) {
    .cards-container {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 10px;
    }
    .cards-container .card { flex: 0 0 220px; min-width: 220px; }
    .chart-container { height: 120px; }
}

@media (max-width: 480px) {
    .cards-container {
        flex-direction: column;
        overflow-x: hidden;
        gap: 15px;
    }
    .cards-container .card { width: 100%; min-width: unset; }
    .chart-container { height: 100px; }
}

/* Monthly chart container */
.monthly-chart-container {
    width: 100%;
    max-width: 1200px;
    margin: 20px auto;
    height: 300px;
}

@media (max-width: 768px) { .monthly-chart-container { height: 250px; } }
@media (max-width: 480px) { .monthly-chart-container { height: 220px; } }
</style>
</head>
<body>
<button id="hamburgerBtn" class="hamburger-btn">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="dashboard-container">
<?php include 'sidebar.php'; ?>

<main class="main-content">
<h1>Welcome, Admin!</h1>
<p>Check your analytics here!</p>

<div class="cards-container">
    <div class="card">
        <i class="fa-solid fa-eye"></i>
        <h2><?php echo $totalReads; ?></h2>
        <p>Total Reads</p>
        <div class="chart-container"><canvas id="readsChart"></canvas></div>
    </div>

    <div class="card">
        <i class="fa-solid fa-bookmark"></i>
        <h2><?php echo $totalSaves; ?></h2>
        <p>Total Saves</p>
        <div class="chart-container"><canvas id="savesChart"></canvas></div>
    </div>

    <div class="card">
        <i class="fa-solid fa-star"></i>
        <h2><?php echo $totalLikes; ?></h2>
        <p>Total Stars</p>
        <div class="chart-container"><canvas id="likesChart"></canvas></div>
    </div>

    <div class="card">
        <i class="fa-solid fa-share-alt"></i>
        <h2><?php echo $totalShares; ?></h2>
        <p>Total Shares</p>
        <div class="chart-container"><canvas id="sharesChart"></canvas></div>
    </div>
</div>

<h2 style="margin-top: 20px;">Top Articles</h2>
<div class="cards-container">
    <!-- Most Read -->
    <div class="card">
        <i class="fa-solid fa-eye"></i>
        <?php if (!empty($mostRead['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($mostRead['image_url']); ?>" alt="Article Image" class="article-thumb">
        <?php endif; ?>
        <h3>
            <a href="../article.php?id=<?php echo $mostRead['id']; ?>">
                <?php echo htmlspecialchars($mostRead['title']); ?>
            </a>
        </h3>
        <p>Most Read: <?php echo $mostRead['views']; ?> views</p>
    </div>

    <!-- Most Saved -->
    <div class="card">
        <i class="fa-solid fa-bookmark"></i>
        <?php if (!empty($mostSaved['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($mostSaved['image_url']); ?>" alt="Article Image" class="article-thumb">
        <?php endif; ?>
        <h3>
            <a href="../article.php?id=<?php echo $mostSaved['id']; ?>">
                <?php echo htmlspecialchars($mostSaved['title']); ?>
            </a>
        </h3>
        <p>Most Saved: <?php echo $mostSaved['save_count']; ?> saves</p>
    </div>

    <!-- Most Liked -->
    <div class="card">
        <i class="fa-solid fa-star"></i>
        <?php if (!empty($mostLiked['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($mostLiked['image_url']); ?>" alt="Article Image" class="article-thumb">
        <?php endif; ?>
        <h3>
            <a href="../article.php?id=<?php echo $mostLiked['id']; ?>">
                <?php echo htmlspecialchars($mostLiked['title']); ?>
            </a>
        </h3>
        <p>Most Starred: <?php echo $mostLiked['like_count']; ?> likes</p>
    </div>

    <!-- Most Shared -->
    <div class="card">
        <i class="fa-solid fa-share-alt"></i>
        <?php if (!empty($mostShared['image_url'])): ?>
            <img src="../<?php echo htmlspecialchars($mostShared['image_url']); ?>" alt="Article Image" class="article-thumb">
        <?php endif; ?>
        <h3>
            <a href="../article.php?id=<?php echo $mostShared['id']; ?>">
                <?php echo htmlspecialchars($mostShared['title']); ?>
            </a>
        </h3>
        <p>Most Shared: <?php echo $mostShared['share_count']; ?> shares</p>
    </div>
</div>

<h2 style="margin-top: 30px;">Monthly Analytics</h2>
<div class="card">
    <div class="monthly-chart-container">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

</main>
</div>

<script>
const labels = <?php echo json_encode($weeklyLabels); ?>;
const readsData = { labels, datasets:[{ label:'Reads', data:<?php echo json_encode($weeklyReads); ?>, borderColor:'#4CAF50', backgroundColor:'rgba(76, 175, 80, 0.2)', tension:0.4 }]};
const savesData = { labels, datasets:[{ label:'Saves', data:<?php echo json_encode($weeklySaves); ?>, borderColor:'#2196F3', backgroundColor:'rgba(33, 150, 243, 0.2)', tension:0.4 }]};
const likesData = { labels, datasets:[{ label:'Stars', data:<?php echo json_encode($weeklyLikes); ?>, borderColor:'#FF9800', backgroundColor:'rgba(255, 152, 0, 0.2)', tension:0.4 }]};
const sharesData = { labels, datasets:[{ label:'Shares', data:<?php echo json_encode($weeklyShares); ?>, borderColor:'#9C27B0', backgroundColor:'rgba(156, 39, 176, 0.2)', tension:0.4 }]};

const options = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } };

new Chart(document.getElementById('readsChart'), { type:'line', data:readsData, options });
new Chart(document.getElementById('savesChart'), { type:'line', data:savesData, options });
new Chart(document.getElementById('likesChart'), { type:'line', data:likesData, options });
new Chart(document.getElementById('sharesChart'), { type:'line', data:sharesData, options });

// Monthly chart
const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
const monthlyData = {
    labels: monthlyLabels,
    datasets:[
        { label:'Reads', data:<?php echo json_encode($monthlyReads); ?>, backgroundColor:'rgba(76, 175, 80, 0.7)' },
        { label:'Saves', data:<?php echo json_encode($monthlySaves); ?>, backgroundColor:'rgba(33, 150, 243, 0.7)' },
        { label:'Stars', data:<?php echo json_encode($monthlyLikes); ?>, backgroundColor:'rgba(255, 152, 0, 0.7)' },
        { label:'Shares', data:<?php echo json_encode($monthlyShares); ?>, backgroundColor:'rgba(156, 39, 176, 0.7)' }
    ]
};
const monthlyOptions = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'top' }, title:{ display:true, text:'Monthly Article Analytics' } }, scales:{ y:{ beginAtZero:true } } };
new Chart(document.getElementById('monthlyChart'), { type:'bar', data:monthlyData, options:monthlyOptions });

const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');
hamburgerBtn.addEventListener('click',()=>{ sidebar.classList.toggle('show'); });
document.addEventListener('click',e=>{ if(!sidebar.contains(e.target)&&!hamburgerBtn.contains(e.target)){ sidebar.classList.remove('show'); }});
</script>
</body>
</html>
