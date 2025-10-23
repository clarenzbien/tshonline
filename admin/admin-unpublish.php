<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
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

// ✅ If a publish action is triggered
if (isset($_GET['publish_id'])) {
    $id = intval($_GET['publish_id']);
    $sql = "UPDATE articles SET status='published' WHERE id=$id";
    
    if ($conn->query($sql) === TRUE) {
        header("Location: admin-unpublish.php?success=Article published successfully");
        exit();
    } else {
        header("Location: admin-unpublish.php?error=Failed to publish article");
        exit();
    }
}

// Fetch unpublished articles
$articlesResult = $conn->query("
    SELECT id, category, title, author, date_posted, image_url, status
    FROM articles
    WHERE status = 'unpublished'
    ORDER BY date_posted DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unpublished Articles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../adminstyles/admin-style.css">
    <link rel="stylesheet" href="../adminstyles/admin-articles.css">
</head>
<body>
    <button id="hamburgerBtn" class="hamburger-btn">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="articles-header">
                <h1>Unpublished Articles</h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <table class="articles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Date Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articlesResult->num_rows > 0): ?>
                        <?php while($article = $articlesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $article['id']; ?></td>
                                <td>
                                    <?php if(!empty($article['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($article['image_url']); ?>" alt="Thumb" class="article-thumb">
                                    <?php else: ?> -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($article['title']); ?></td>
                                <td><?php echo htmlspecialchars($article['author'] ?? 'Admin'); ?></td>
                                <td><?php echo htmlspecialchars($article['category']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($article['date_posted'])); ?></td>
                                <td class="action-buttons" style="display: flex; flex-direction: column; gap: 5px;">
                                    <a href="admin_edit_unpublish.php?id=<?php echo $article['id']; ?>" class="edit-btn">Edit</a>
                                    <a href="delete_article.php?id=<?php echo $article['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No unpublished articles found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');

hamburgerBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});

// Optional: close sidebar when clicking outside
document.addEventListener('click', e => {
    if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
</script>
</body>
</html>
