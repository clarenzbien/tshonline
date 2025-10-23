<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function recordShare($conn, $articleId, $userId = null, $type = 'copy') {
    $stmt = $conn->prepare("INSERT INTO article_shares (article_id, user_id, action_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $articleId, $userId, $type);
    $stmt->execute();
    $stmt->close();
}


// Get current article ID
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Increment views
if ($articleId > 0) {
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) { 
        // Check if user already viewed today
        $today = date('Y-m-d');
        $check = $conn->query("SELECT 1 FROM article_views WHERE article_id=$articleId AND user_id=$userId AND DATE(viewed_at)='$today'");
        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO article_views (article_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $articleId, $userId);
            $stmt->execute();
            $stmt->close();
            $conn->query("UPDATE articles SET views = views + 1 WHERE id = $articleId");
        }
    } else {
        // For guests without login, just increment
        $conn->query("UPDATE articles SET views = views + 1 WHERE id = $articleId");
    }
}



// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_article']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check = $conn->query("SELECT * FROM article_likes WHERE article_id=$articleId AND user_id=$user_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM article_likes WHERE article_id=$articleId AND user_id=$user_id");
    } else {
        $conn->query("INSERT INTO article_likes (article_id, user_id) VALUES ($articleId, $user_id)");
    }
    header("Location: article.php?id=$articleId");
    exit();
}

// Handle save action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_article']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $checkSave = $conn->query("SELECT * FROM article_saves WHERE article_id=$articleId AND user_id=$user_id");
    if ($checkSave->num_rows > 0) {
        // Unsave
        $conn->query("DELETE FROM article_saves WHERE article_id=$articleId AND user_id=$user_id");
    } else {
        // Save
        $conn->query("INSERT INTO article_saves (article_id, user_id) VALUES ($articleId, $user_id)");
    }
    header("Location: article.php?id=$articleId");
    exit();
}

// Check if user saved
$userSaved = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $checkUserSave = $conn->query("SELECT 1 FROM article_saves WHERE article_id=$articleId AND user_id=$uid");
    $userSaved = $checkUserSave->num_rows > 0;
}


// Fetch current article
$articleRes = $conn->query("SELECT * FROM articles WHERE id=$articleId LIMIT 1");
$article = $articleRes->num_rows > 0 ? $articleRes->fetch_assoc() : null;

// Count likes
$likeCountRes = $conn->query("SELECT COUNT(*) AS total FROM article_likes WHERE article_id=$articleId");
$likeCount = $likeCountRes->fetch_assoc()['total'];

// Check if user liked
$userLiked = false;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $checkUserLike = $conn->query("SELECT 1 FROM article_likes WHERE article_id=$articleId AND user_id=$uid");
    $userLiked = $checkUserLike->num_rows > 0;
}

// Fetch all articles for recent and most read sections
$allArticlesRes = $conn->query("SELECT * FROM articles ORDER BY date_posted DESC");
$recentArticles = [];
$allArticles = [];
if ($allArticlesRes->num_rows > 0) {
    while($row = $allArticlesRes->fetch_assoc()) {
        $recentArticles[] = $row;
        $allArticles[] = $row;
    }
}

// Fetch most read articles (top 6 views, excluding current)
$mostReadArticles = array_filter($allArticles, fn($a) => $a['id'] != $articleId);
usort($mostReadArticles, fn($a, $b) => $b['views'] <=> $a['views']);
$mostReadArticles = array_slice($mostReadArticles, 0, 6);

// Fetch recent articles (latest 6, excluding current)
$recentArticlesToShow = array_filter($recentArticles, fn($a) => $a['id'] != $articleId);
$recentArticlesToShow = array_slice($recentArticlesToShow, 0, 6);

// Handle comment submission
// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text']) && isset($_SESSION['user_id'])) {
    $comment_text = trim($_POST['comment_text']);
    $user_id = $_SESSION['user_id'];
    $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : null;

    if (!empty($comment_text)) {
        if ($comment_id) {
            // Update existing comment without updated_at
            $update = $conn->prepare("UPDATE comments SET comment_text = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("sii", $comment_text, $comment_id, $user_id);
            $update->execute();
            $update->close();
        } else {
            // Insert new comment (avoid rapid duplicates)
            $stmt = $conn->prepare("SELECT 1 FROM comments WHERE article_id = ? AND user_id = ? AND comment_text = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1");
            $stmt->bind_param("iis", $articleId, $user_id, $comment_text);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 0) {
                $insert = $conn->prepare("INSERT INTO comments (article_id, user_id, comment_text) VALUES (?, ?, ?)");
                $insert->bind_param("iis", $articleId, $user_id, $comment_text);
                $insert->execute();
                $insert->close();
            }
            $stmt->close();
        }
    }

    header("Location: article.php?id=$articleId");
    exit();
}



// Fetch comments
$comments = $conn->query("
    SELECT c.id, c.user_id, c.comment_text, c.created_at, u.name, u.profile_pic
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.article_id = $articleId
    ORDER BY c.created_at DESC
");

$categoryId = null;
$categoryName = '';
if ($article && !empty($article['category_id'])) {
    $catRes = $conn->query("SELECT id, name FROM categories WHERE id=" . intval($article['category_id']) . " LIMIT 1");
    if ($catRes && $catRes->num_rows > 0) {
        $catRow = $catRes->fetch_assoc();
        $categoryId = $catRow['id'];
        $categoryName = $catRow['name'];
    }
}




$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?php echo $article ? htmlspecialchars($article['title']) : "Article Not Found"; ?></title>
<link rel="stylesheet" href="./styles/article.css">
<link rel="stylesheet" href="./styles/footer.css"> <!-- Add this -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<div class="main-container">
    <div class="left-icon" onclick="openSidebar()">
        <?php include 'hamburger-menu.php'; ?>
    </div>
</div>

<div class="content-wrapper">

<?php if($article): ?>
    <div class="article-page-container">
        <?php if(!empty($article['image_url'])): ?>
            <div class="article-image-top">
                <img src="<?php echo htmlspecialchars($article['image_url']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" />
            </div>
        <?php endif; ?>

        <div class="article-header">
            <h1><?php echo htmlspecialchars($article['title']); ?></h1>
          <p class="article-meta">
            By <a href="author.php?id=<?php echo $article['author_id']; ?>" class="author-link">
                <?php echo htmlspecialchars($article['author']); ?></a> |
            <?php echo date("F d, Y", strtotime($article['date_posted'])); ?>
            <?php if(!empty($categoryName)): ?>
                | <span class="article-category"><?php echo htmlspecialchars($categoryName); ?></span>
            <?php endif; ?>
        </p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="article-actions">
                        <!-- Like Button -->
                        <form method="POST" class="action-btn-form">
                            <input type="hidden" name="like_article" value="1">
                            <button type="submit" class="action-btn like-btn <?php echo $userLiked ? 'liked' : ''; ?>">
                                <i class="fa-solid fa-star"></i> <?php echo $userLiked ? 'Starred' : 'Star'; ?>
                                <span class="count">(<?php echo $likeCount; ?>)</span>
                            </button>
                        </form>

                        <!-- Save Button -->
                        <form method="POST" class="action-btn-form">
                            <input type="hidden" name="save_article" value="1">
                            <button type="submit" class="action-btn save-btn <?php echo $userSaved ? 'saved' : ''; ?>">
                                <i class="fa-solid fa-bookmark"></i> <?php echo $userSaved ? 'Saved' : 'Save'; ?>
                            </button>
                        </form>

                        <!-- Share Button -->
                        <button type="button" class="action-btn share-btn" onclick="shareArticle()">
                            <i class="fa-solid fa-share-nodes"></i> Share
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="article-actions">
                        <a href="login.php?redirect=article.php?id=<?php echo $articleId; ?>" class="action-btn"><i class="fa-regular fa-star"></i> Star</a>
                        <a href="login.php?redirect=article.php?id=<?php echo $articleId; ?>" class="action-btn"><i class="fa-regular fa-bookmark"></i> Save</a>
                        <button type="button" class="action-btn share-btn" onclick="shareArticle()"><i class="fa-solid fa-share-nodes"></i> Share</button>
                    </div>
                    <?php endif; ?>


                
            </p>
            
        </div>

        <div class="article-content">
            <?php echo nl2br(htmlspecialchars($article['body'])); ?>
        </div>
    </div>

    
<?php if (!empty($article['image2_url']) || !empty($article['image3_url'])): ?>
    <div class="extra-images-section">
        <?php for ($i = 2; $i <= 3; $i++): ?>
            <?php 
                $urlKey = "image{$i}_url";
                $captionKey = "image{$i}_caption";
                if (!empty($article[$urlKey])): 
            ?>
                <div class="extra-image-container">
                    <img src="<?php echo htmlspecialchars($article[$urlKey]); ?>" 
                         alt="Additional Image <?php echo $i - 1; ?>" class="extra-article-image">
                    <?php if (!empty($article[$captionKey])): ?>
                        <div class="image-caption">
                            <?php echo htmlspecialchars($article[$captionKey]); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>
<?php else: ?>
    <p>Article not found.</p>
<?php endif; ?>



<!-- Comments Section -->
<div class="comments-section">
    <div class="comments-header">
        <h2>Comments <span class="comments-count">(<?php echo $comments->num_rows; ?>)</span></h2>
    </div>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" class="comment-form">
            <textarea name="comment_text" rows="3" placeholder="Write your comment..." required></textarea>
            <button type="submit">Post Comment</button>
        </form>
    <?php else: ?>
        <p><a href="login.php?redirect=article.php?id=<?php echo $articleId; ?>">Log in</a> to post a comment.</p>
    <?php endif; ?>

    <?php if ($comments->num_rows > 0): ?>
        <?php while ($row = $comments->fetch_assoc()): ?>
            <div class="comment-box">
                <div class="comment-user-pic">
                    <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="User Pic">
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                    <p class="comment-user"><strong><?php echo htmlspecialchars($row['name']); ?></strong></p>


                   <?php
                    $isAdmin = (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) 
           || !empty($_SESSION['admin_id']);

                    $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $row['user_id'];

                    // Show dropdown only if admin OR owner
                    if ($isOwner || $isAdmin):
                    ?>
                        <div class="comment-menu">
                            <i class="fa-solid fa-ellipsis-vertical menu-icon"></i>
                            <div class="dropdown-menu" style="display: none;">
                               <?php if ($isOwner): ?>
                                    <form method="POST" class="edit-comment-form" style="display:none;">
                                        <input type="hidden" name="comment_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
                                        <textarea name="comment_text"><?php echo htmlspecialchars($row['comment_text']); ?></textarea>
                                        <button type="submit">Save</button>
                                    </form>
                                    <button class="edit-comment-btn" 
                                        onclick="toggleEditForm(<?php echo $row['id']; ?>, '<?php echo addslashes($row['comment_text']); ?>')">
                                        Edit
                                    </button>

                                    <?php endif; ?>


                                <!-- Admins and owners can delete -->
                                <form method="GET" action="delete_comment.php" style="margin:0;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
                                    <button type="submit" onclick="return confirm('Delete this comment?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>


                    </div>

                    <p class="comment-date"><?php echo date("F d, Y h:i A", strtotime($row['created_at'])); ?></p>
                    <div class="comment-text">
                        <span class="comment-short"><?php echo nl2br(htmlspecialchars(substr($row['comment_text'], 0, 200))); ?></span>
                        <?php if (strlen($row['comment_text']) > 200): ?>
                            <span class="comment-full" style="display:none;"><?php echo nl2br(htmlspecialchars($row['comment_text'])); ?></span>
                            <button class="view-more-btn">View more</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No comments yet. Be the first to comment!</p>
    <?php endif; ?>

    <!-- Edit Comment Modal -->
<div id="editCommentModal" class="modal">
  <div class="modal-content">
    <span class="close-modal">&times;</span>
    <h3>Edit Comment</h3>
    <form id="editCommentForm" method="POST">
      <input type="hidden" name="comment_id" id="modalCommentId">
      <input type="hidden" name="article_id" value="<?php echo $articleId; ?>">
      <textarea name="comment_text" id="modalCommentText" rows="5" placeholder="Edit your comment..." required></textarea>
      <button type="submit" class="btn-save">Save Changes</button>
    </form>
  </div>
</div>

</div>

<hr style="margin: 40px 0; border: none; border-top: 2px solid #ccc;">

<!-- Latest Articles Section -->
<div class="section-container">
    <div class="section-articles">
        <div class="section-header">
            <div class="sectionH"><span class="text-s">LATEST ARTICLES</span></div>
            <div class="wrapper"><a href="maindashboard.php" class="view-more-btn">Read More Articles</a></div>
        </div>
    </div>

    <div class="article-container">
        <?php foreach($recentArticlesToShow as $row): ?>
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

<!-- Most Read Articles Section -->
<div class="section-container">
    <div class="section-articles">
        <div class="section-header">
            <div class="sectionM"><span class="text-m">MOST READ ARTICLES</span></div>
            <div class="wrapper"><a href="maindashboard.php" class="view-more-btn">Read More Articles</a></div>
        </div>
    </div>

    <div class="article-container">
        <?php foreach($mostReadArticles as $row): ?>
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

<?php include 'footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".view-more-btn").forEach(function(button) {
        button.addEventListener("click", function() {
            let fullText = this.previousElementSibling; 
            let shortText = fullText.previousElementSibling;
            if (fullText.style.display === "none") {
                fullText.style.display = "inline";
                shortText.style.display = "none";
                this.textContent = "View less";
            } else {
                fullText.style.display = "none";
                shortText.style.display = "inline";
                this.textContent = "View more";
            }
        });
    });
});
</script>



<script>
function shareArticle() {
    const articleUrl = window.location.href;
    const articleTitle = document.querySelector('h1').innerText;

    if (navigator.share) {
        navigator.share({
            title: articleTitle,
            url: articleUrl
        })
        .then(() => alert('Shared successfully!'))
        .catch((error) => alert('Error sharing: ' + error));
    } else {
        alert('Sharing not supported on this device. Link copied instead.');
        navigator.clipboard.writeText(articleUrl)
            .then(() => console.log('Link copied to clipboard'))
            .catch(() => alert('Failed to copy link'));
    }
}

</script>


<script>function shareArticle() {
    const articleUrl = window.location.href;
    const articleTitle = document.querySelector('h1').innerText;

    if (navigator.share) {
        navigator.share({ title: articleTitle, url: articleUrl })
            .then(() => alert('Shared successfully!'))
            .catch(err => alert('Error sharing: ' + err));
    } else {
        navigator.clipboard.writeText(articleUrl)
            .then(() => alert('Link copied!'))
            .catch(() => alert('Failed to copy. You can manually copy: ' + articleUrl));

        // Record share via AJAX
        fetch('./admin/admin_record_share.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ article_id: <?php echo $articleId; ?>, action_type: 'copy' })
        });
    }
}

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.menu-icon').forEach(icon => {
    icon.addEventListener('click', (e) => {
      const menu = e.target.closest('.comment-menu').querySelector('.dropdown-menu');
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.comment-menu')) {
      document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
    }
  });
});
</script>
<script>function toggleEditForm(commentId, commentText) {
    const modal = document.getElementById('editCommentModal');
    const textarea = document.getElementById('modalCommentText');
    const commentIdInput = document.getElementById('modalCommentId');

    textarea.value = commentText;
    commentIdInput.value = commentId;

    modal.style.display = 'block';

    // Close functionality
    modal.querySelector('.close-modal').onclick = () => {
        modal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
}

</script>



</body>
</html>



<!-- okay na commentss -->