<?php
$servername = "localhost";
$username = "root";   
$password = "";       
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get category name from URL parameter (e.g., ?category=NEWS)
$categoryToShow = isset($_GET['category']) ? strtoupper($_GET['category']) : '';

if (empty($categoryToShow)) {
    die("<p style='text-align:center; margin-top:50px;'>No category selected.</p>");
}

// Secure query (prevents SQL injection)
$stmt = $conn->prepare("SELECT * FROM articles WHERE category = ? AND status = 'published' ORDER BY date_posted DESC");
$stmt->bind_param("s", $categoryToShow);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($categoryToShow); ?> | TSH</title>
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
                            <span class="text-s"><?php echo htmlspecialchars($categoryToShow); ?></span>
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
            <p style="text-align:center;">No articles found for this category.</p>
        <?php endif; ?>

        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
