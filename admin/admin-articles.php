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
if (!empty($_GET['category'])) {
    $category_id = (int) $_GET['category']; // cast to integer for safety
    $categoryFilter = " AND a.category_id = $category_id";
}


// Handle author filter
$authorFilter = '';
if (!empty($_GET['author'])) {
    $author_id = (int) $_GET['author']; // cast to integer
    $authorFilter = " AND a.author_id = $author_id";
}

// Fetch all articles + interaction counts, join with categories table
$query = "
    SELECT 
        a.*, 
        c.name AS category_name,
        (SELECT COUNT(*) FROM article_likes l WHERE l.article_id = a.id) AS likes,
        (SELECT COUNT(*) FROM article_views v WHERE v.article_id = a.id) AS views,
        (SELECT COUNT(*) FROM article_shares s WHERE s.article_id = a.id) AS shares,
        (SELECT COUNT(*) FROM comments c2 WHERE c2.article_id = a.id) AS comments,
        (SELECT COUNT(*) FROM article_saves s2 WHERE s2.article_id = a.id) AS saves
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' $categoryFilter $authorFilter
    ORDER BY a.date_posted DESC
";






$articlesResult = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Articles</title>
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
                <h1>All Articles</h1>
                <a href="admin_add_article.php" class="add-btn">
                    <i class="fa-solid fa-plus"></i> Add Article
                </a>
            </div>  

           <!-- Filters Row -->
            <div class="category-filter-row" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap; margin-bottom:20px;">
                <!-- Category Filter -->
                <div class="filter-wrapper">
                    <label for="categoryFilter">Filter by:</label>
                    <select id="categoryFilter">
                        <option value="">Categories</option>
                        <?php
                        $catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                        while ($cat = $catResult->fetch_assoc()) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                            echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

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

                <!-- Edit Categories Button -->
                <button type="button" id="editCategoriesBtn" class="edit-categories-btn">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Categories
                </button>
            </div>




            <!-- Edit Categories Modal -->
            <div id="editCategoriesModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <h2>Edit Categories</h2>
                    <div class="categories-list" id="categoriesList">
                        <?php
                        $catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                        while ($cat = $catResult->fetch_assoc()) {
                            echo '<div class="category-item" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:5px;">';
                            echo '<span>' . htmlspecialchars($cat['name']) . '</span>';
                            echo '<button type="button" class="delete-category-btn" data-id="' . $cat['id'] . '" style="cursor:pointer;">
                                <i class="fa-solid fa-trash"></i>
                            </button>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    <button id="addCategoryModalBtn" style="margin-top:10px; cursor:pointer;">+ Add Category</button>
                </div>
            </div>


            <!-- Messages -->
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
                        <th>Published</th>
                        <th>Interactions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($articlesResult && $articlesResult->num_rows > 0): ?>
                        <?php while($article = $articlesResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $article['id']; ?></td>
                                <td>
                                    <?php if(!empty($article['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($article['image_url']); ?>" alt="Thumb" class="article-thumb">
                                    <?php else: ?> -
                                    <?php endif; ?>
                                </td>
                                <td>
                                <a href="../article.php?id=<?php echo $article['id']; ?>" 
                                    target="_blank" 
                                    style="color:#000000; text-decoration:none; font-weight:500;">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a>
                                </td>
                                <td><?php echo htmlspecialchars($article['author'] ?? 'Admin'); ?></td>
                                <td><?php echo htmlspecialchars($article['category']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($article['date_posted'])); ?></td>
                                
                                <!-- New column for likes/views/shares/comments -->
                                <td>
                                <div class="interaction-counts">
                                    <span class="view-likes" data-article-id="<?php echo $article['id']; ?>" title="Likes" style="cursor:pointer;">
                                    <i class="fa-solid fa-heart"></i> <?php echo $article['likes']; ?>
                                    </span>

                                    <span class="view-views" data-article-id="<?php echo $article['id']; ?>" title="Views" style="cursor:pointer;">
                                    <i class="fa-solid fa-eye"></i> <?php echo $article['views']; ?>
                                    </span>

                                    <span class="view-shares" data-article-id="<?php echo $article['id']; ?>" title="Shares" style="cursor:pointer;">
                                    <i class="fa-solid fa-share"></i> <?php echo $article['shares']; ?>
                                    </span>

                                    <span class="view-comments" data-article-id="<?php echo $article['id']; ?>" title="Comments" style="cursor:pointer;">
                                    <i class="fa-solid fa-comment"></i> <?php echo $article['comments']; ?>
                                    </span>

                                    <span class="view-saves" data-article-id="<?php echo $article['id']; ?>" title="Saves" style="cursor:pointer;">
                                    <i class="fa-solid fa-bookmark"></i> <?php echo $article['saves']; ?>
                                    </span>
                                </div>
                                </td>


                               <td class="action-buttons" style="display: flex; flex-direction: column; gap: 5px;">
                                    <a href="../admin/admin_edit_article.php?id=<?php echo $article['id']; ?>" class="edit-btn">Edit</a>
                                    <a href="../admin/delete_article.php?id=<?php echo $article['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>
                                    
                                    <?php if ($article['status'] === 'published'): ?>
                                        <a href="../admin/unpublish_article.php?id=<?php echo $article['id']; ?>" 
                                        class="unpublish-btn"
                                        onclick="return confirm('Are you sure you want to unpublish this article?');">
                                        Unpublish
                                        </a>
                                    <?php else: ?>
                                        <span class="unpublished-label">Unpublished</span>
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

    <!-- Interaction Modal -->
<div id="interactionModal" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2 id="modalTitle">Interactions</h2>
    <div id="interactionList"></div>
  </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('interactionModal');
  const closeBtn = document.querySelector('.close-btn');
  const modalTitle = document.getElementById('modalTitle');
  const interactionList = document.getElementById('interactionList');

  // When admin clicks heart, share, or save icons
  document.querySelectorAll('.view-likes, .view-shares, .view-saves, .view-comments, .view-views').forEach(icon => {
    icon.addEventListener('click', function() {
      const articleId = this.dataset.articleId;
      let type = '';
      if (this.classList.contains('view-likes')) type = 'likes';
      else if (this.classList.contains('view-shares')) type = 'shares';
      else if (this.classList.contains('view-saves')) type = 'saves';
      else if (this.classList.contains('view-comments')) type = 'comments';
      else if (this.classList.contains('view-views')) type = 'views';

      // Set modal title
      if (type === 'likes') modalTitle.textContent = 'Who Liked This Article';
      if (type === 'shares') modalTitle.textContent = 'Who Shared This Article';
      if (type === 'saves') modalTitle.textContent = 'Who Saved This Article';
      if (type === 'comments') modalTitle.textContent = 'Who Commented on This Article';
      if (type === 'views') modalTitle.textContent = 'Who Saw This Article';

      interactionList.innerHTML = '<p>Loading...</p>';
      modal.style.display = 'block';

      // Fetch data
      fetch(`../admin/admin_fetch_interactions.php?type=${type}&article_id=${articleId}`)
  .then(async res => {
    const text = await res.text();
    console.log('Raw response:', text); // <-- inspect this in Console
    try {
      const data = JSON.parse(text);
      if (data && data.error) {
        // server returned an error field
        interactionList.innerHTML = `<p style="color:red;">${data.error}</p>`;
        return;
      }
      if (!Array.isArray(data)) {
        interactionList.innerHTML = '<p style="color:red;">Invalid server response.</p>';
        return;
      }
      if (data.length === 0) {
        interactionList.innerHTML = '<p>No interactions yet.</p>';
        return;
      }
      interactionList.innerHTML = data.map(user => `
        <div class="user-entry">
          <img src="../${user.profile_pic}" alt="User" class="user-img">
          <div class="user-info">
            <strong>${user.name}</strong>
            <span>${user.date}</span>
          </div>
        </div>
      `).join('');
    } catch (err) {
      console.error('JSON parse error', err);
      interactionList.innerHTML = '<p style="color:red;">Server returned invalid JSON. Check console for details.</p>';
    }
  })
  .catch(err => {
    interactionList.innerHTML = `<p style="color:red;">Fetch error: ${err}</p>`;
  });

    });
  });

  closeBtn.onclick = () => modal.style.display = 'none';
  window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
});

</script>





<script>
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
            // Add to dropdown
            let select = document.getElementById('category');
            let option = document.createElement('option');
            option.value = data.id; // Use inserted ID
            option.text = newCategory;
            select.add(option);
            select.value = data.id; // auto-select
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => alert('Fetch error: ' + err));
});
</script>





<!-- Modal JS -->
<script>
// Filter dropdown
document.getElementById('categoryFilter').addEventListener('change', function() {
    const value = this.value;
    window.location = value ? '?category=' + value : '?';
});

// Open modal
const editBtn = document.getElementById('editCategoriesBtn');
const modal = document.getElementById('editCategoriesModal');
const closeBtn = modal.querySelector('.close-btn');

editBtn.onclick = () => modal.style.display = 'block';
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => { if(e.target == modal) modal.style.display = 'none'; };

// Add category in modal
document.getElementById('addCategoryModalBtn').addEventListener('click', function() {
    let newCategory = prompt("Enter new category name:");
    if(!newCategory) return;

    fetch('admin_add_category.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'category=' + encodeURIComponent(newCategory)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('Category added!');
            location.reload(); // refresh modal and dropdown
        } else alert('Error: ' + data.error);
    })
    .catch(err => alert('Fetch error: ' + err));
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('editCategoriesModal');
    const editBtn = document.getElementById('editCategoriesBtn');
    const closeBtn = modal.querySelector('.close-btn');

    // Open modal
    editBtn.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    // Close modal via X
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close modal if clicked outside modal-content
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Delete category buttons using event delegation
    const categoriesList = document.getElementById('categoriesList');
    categoriesList.addEventListener('click', function(e) {
        if(e.target && e.target.matches('.delete-category-btn')){
            const id = e.target.dataset.id;
            if(!confirm("Are you sure you want to delete this category?")) return;

            fetch('admin_delete_category.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:'id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    alert('Category deleted!');
                    location.reload();
                } else alert('Error: ' + data.error);
            })
            .catch(err => alert('Fetch error: ' + err));
        }
    });
});

</script>

<script>
document.getElementById('categoryFilter').addEventListener('change', function() {
    const category = this.value;
    const author = document.getElementById('authorFilter').value;
    let url = '?';
    if(category) url += 'category=' + category + '&';
    if(author) url += 'author=' + author;
    window.location = url;
});

document.getElementById('authorFilter').addEventListener('change', function() {
    const author = this.value;
    const category = document.getElementById('categoryFilter').value;
    let url = '?';
    if(category) url += 'category=' + category + '&';
    if(author) url += 'author=' + author;
    window.location = url;
});

</script>

</html>
