<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$authorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch author details
$author = $conn->query("SELECT name, profile_pic, position_title, email, bio FROM users WHERE id = $authorId LIMIT 1")->fetch_assoc();

// Fetch author articles (published only)
$stmt = $conn->prepare("SELECT * FROM articles WHERE author_id = ? AND status = 'published' ORDER BY date_posted DESC");
$stmt->bind_param("i", $authorId);
$stmt->execute();
$articles = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($author['name']); ?> - Articles | TSH</title>
<link rel="stylesheet" href="./styles/style.css"> <!-- Reuse the same style file -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="main-container">
    <div class="left-icon" onclick="openSidebar()">
        <?php include 'hamburger-menu.php'; ?>
    </div>
</div>

<div class="content-wrapper" style="margin-top: 100px;">

    <div class="author-page-container" style="max-width:900px; margin:40px auto; display:flex; gap:30px; align-items:flex-start;">
        <!-- Author Image -->
        <div class="author-image">
            <img src="<?php echo htmlspecialchars($author['profile_pic']); ?>" 
                alt="<?php echo htmlspecialchars($author['name']); ?>" 
                style="width:200px; height:200px; border-radius:50%; object-fit:cover; border:4px solid #001c0c;">
        </div>

        <!-- Author Details -->
        <div class="author-details" style="flex:1; display:flex; flex-direction:column; gap:8px;">
            <h1 style="font-family:Nexa, var(--default-font-family); font-weight:900; text-transform:uppercase; color:#001c0c; margin:0;">
                <?php echo htmlspecialchars($author['name']); ?>
            </h1>
            <span style="font-weight:600; color:#555;">
                <?php echo htmlspecialchars($author['position_title'] ?? 'Author'); ?>
            </span>
            <div style="display:flex; align-items:center; gap:6px; color:#555;">
                <i class="fa-solid fa-envelope"></i>
                <span><?php echo htmlspecialchars($author['email'] ?? 'email@example.com'); ?></span>
            </div>
            <p style="color:#333; line-height:1.5; margin-top:5px; font-size: 15px;">
                <?php echo nl2br(htmlspecialchars($author['bio'] ?? 'No bio available.')); ?>
            </p>
        </div>
    </div>


    <div class="section-container">
        <div class="section-articles">
            <div class="section-header">
                <div class="sectionH">
                    <span class="text-s">ARTICLES BY <?php echo htmlspecialchars(strtoupper($author['name'])); ?></span>
                </div>
            </div>
        </div>

        <?php if ($articles->num_rows > 0): ?>
            <div class="article-container">
                <?php while ($row = $articles->fetch_assoc()): ?>
                    <div class="article-img" style="background-image: url('<?php echo htmlspecialchars($row['image_url']); ?>');">
                        <div class="article-text">
                            <a class="title1" href="article.php?id=<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                            <span class="date1"><?php echo date("F d, Y", strtotime($row['date_posted'])); ?></span>
                            <span class="author1">By <?php echo htmlspecialchars($author['name']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center; margin-top:40px;">No published articles found for this author.</p>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</div>
</body>
</html>

<?php 
$conn->close();
?>
