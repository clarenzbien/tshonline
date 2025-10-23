<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";   
$password = "";       
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];

// Fetch articles liked by this user
$sql = "
    SELECT a.*
    FROM articles a
    INNER JOIN article_likes al ON a.id = al.article_id
    WHERE al.user_id = ?
    ORDER BY a.date_posted DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>My Liked Articles</title>
<link rel="stylesheet" href="./styles/style.css" />
</head>
<body>
<div class="main-container">
    <div class="left-icon" onclick="openSidebar()">
        <?php include 'hamburger-menu.php'; ?>
    </div>
</div>

<div class="content-wrapper" style="margin-top: 80px;">
    <?php if(!empty($articles)): ?>
        <div class="section-container">
            <div class="section-articles">
                <div class="section-header">
                    <div class="sectionH">
                        <span class="text-s">My Starred Articles</span>
                    </div>
                </div>
            </div>

            <div class="article-container">
                <?php foreach($articles as $row): ?>
                    <div class="article-img" style="background-image: url('<?php echo htmlspecialchars($row['image_url']); ?>');">
                        <div class="article-text">
                            <a class="title1" href="article.php?id=<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                            <span class="date1"><?php echo date("F d, Y", strtotime($row['date_posted'])); ?></span>
                            <span class="author1">By <?php echo htmlspecialchars($row['author']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <p>No articles found.</p>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</div>
</body>
</html>
