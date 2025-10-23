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

// Handle category filter
$categoryFilter = '';
$selectedCategory = '';
if (!empty($_GET['category'])) {
    $selectedCategory = (int) $_GET['category'];
    $categoryFilter = " AND a.category_id = $selectedCategory";
}


// Handle author filter
$authorFilter = '';
if (!empty($_GET['author'])) {
    $author_id = (int) $_GET['author']; // cast to integer
    $authorFilter = " AND a.author_id = $author_id";
}

// ✅ Fetch drafts, including their category and status
$query = "
    SELECT 
        a.*, 
        c.name AS category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status IN ('draft', 'edited', 'approved')
    $categoryFilter $authorFilter
    ORDER BY a.date_posted DESC
";

$articlesResult = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Drafts</title>
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
                <h1>Drafts</h1>
                <a href="admin_add_article.php" class="add-btn">
                    <i class="fa-solid fa-plus"></i> Add Article
                </a>
            </div>
            
            <!-- Category Filter -->
            <form method="GET" action="" class="category-filter" style="margin-bottom: 15px;">
                <label for="category">Filter by:</label>
                <select name="category" id="category" onchange="this.form.submit()">
                    <option value="">Categories</option>
                    <?php
                    $catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                    while ($cat = $catResult->fetch_assoc()) {
                        $selected = ($selectedCategory == $cat['id']) ? 'selected' : '';
                        echo "<option value='" . $cat['id'] . "' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                    }
                    ?>
                </select>
                <!-- Author Filter -->
                <div class="filter-wrapper">
                    <select id="authorFilter">
                        <option value="">Authors</option>
                        <?php
                        $authorResult = $conn->query("SELECT id, name FROM users WHERE is_admin = 1 ORDER BY name ASC");
                        while ($user = $authorResult->fetch_assoc()) {
                            $selected = (isset($_GET['author']) && $_GET['author'] == $user['id']) ? 'selected' : '';
                            echo "<option value='{$user['id']}' $selected>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="button" class="add-category-btn" id="addCategoryBtn" style="margin-left:5px; cursor:pointer;">
                    <i class="fa-solid fa-plus"></i> Add Category
                </button>

                
            </form>

            

            <!-- Success / Error Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?= htmlspecialchars($_GET['success']); ?>
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <?= htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Articles Table -->
            <table class="articles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Status</th> <!-- ✅ Added -->
                        <th>Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articlesResult->num_rows > 0): ?>
                        <?php while($article = $articlesResult->fetch_assoc()): ?>
                            <tr style="<?= ($article['edit_status'] === 'approved') ? 'background-color: #d4e7d9ff;' : ''; ?>">

                                <td><?= $article['id']; ?></td>
                                <td>
                                    <?php if(!empty($article['image_url'])): ?>
                                        <img src="../<?= htmlspecialchars($article['image_url']); ?>" alt="Thumb" class="article-thumb">
                                    <?php else: ?> -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($article['title']); ?></td>
                                <td><?= htmlspecialchars($article['author'] ?? 'Admin'); ?></td>
                                <td><?= htmlspecialchars($article['category_name'] ?? '-'); ?></td>
                                <td>
                                    <?php 
                                        if ($article['edit_status'] === 'approved') {
                                            echo "<span style='color:green;font-weight:bold;'>Approved</span>";
                                        } elseif ($article['edit_status'] === 'edited') {
                                            echo "<span style='color:orange;font-weight:bold;'>Edited</span>";
                                        } else {
                                            echo "<span style='color:gray;'>Draft</span>";
                                        }
                                    ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($article['date_posted'])); ?></td>
                                <td class="action-buttons">
                                    <a href="../admin/admin_edit_draft.php?id=<?= $article['id']; ?>" class="edit-btn">Edit</a>
                                    <a href="../admin/delete_article.php?id=<?= $article['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>

                                    <!-- ✅ Publish Button for Approved Articles -->
                                    <?php if ($article['edit_status'] === 'approved'): ?>
                                        <a href="publish_article.php?id=<?= $article['id']; ?>" class="publish-btn"
                                            style="background: #2e8b57; color: #fff; padding: 5px 10px; border-radius: 5px;"
                                            onclick="return confirm('Publish this approved article?');">Publish</a>

                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No articles found.</td></tr>
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

    document.addEventListener('click', e => {
        if (!sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });

    document.getElementById('addCategoryBtn').addEventListener('click', function() {
        let newCategory = prompt("Enter new category name:");
        if (!newCategory) return;

        fetch('admin_add_category.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'category=' + encodeURIComponent(newCategory)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Category added successfully!');
                let select = document.getElementById('category');
                let option = document.createElement('option');
                option.value = data.id;
                option.text = newCategory;
                select.add(option);
                select.value = data.id;
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => alert('Fetch error: ' + err));
    });
    </script>
</body>


<script>
document.getElementById('category').addEventListener('change', function() {
    const category = this.value;
    const author = document.getElementById('authorFilter').value;
    let url = '?';
    if (category) url += 'category=' + category + '&';
    if (author) url += 'author=' + author;
    window.location = url;
});

document.getElementById('authorFilter').addEventListener('change', function() {
    const author = this.value;
    const category = document.getElementById('category').value;
    let url = '?';
    if (category) url += 'category=' + category + '&';
    if (author) url += 'author=' + author;
    window.location = url;
});
</script>

</html>
