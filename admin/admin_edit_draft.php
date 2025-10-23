<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
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

// Get article ID
if (!isset($_GET['id'])) {
    header("Location: admin-articles.php");
    exit();
}

$article_id = intval($_GET['id']);

// Fetch existing article
$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: admin-articles.php");
    exit();
}
$article = $result->fetch_assoc();
$stmt->close();

// Get current user's role
$userStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$userStmt->bind_param("i", $_SESSION['admin_id']);
$userStmt->execute();
$userRow = $userStmt->get_result()->fetch_assoc();
$userStmt->close();
$role = strtolower($userRow['role'] ?? '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $title = $_POST['title'] ?? '';
    $author_id = isset($_POST['author_id']) ? intval($_POST['author_id']) : null;
    $date_posted = $_POST['date_posted'] ?? date('Y-m-d');
    $body = $_POST['body'] ?? '';

    $image_path = $article['image_url'] ?? '';
    $img2_url = $article['image2_url'] ?? '';
    $img2_caption = $article['image2_caption'] ?? '';
    $img3_url = $article['image3_url'] ?? '';
    $img3_caption = $article['image3_caption'] ?? '';

    // Resolve author name
    if ($author_id) {
        $stmtAuthor = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmtAuthor->bind_param("i", $author_id);
        $stmtAuthor->execute();
        $authorData = $stmtAuthor->get_result()->fetch_assoc();
        $author = $authorData['name'] ?? 'Unknown';
        $stmtAuthor->close();
    } else {
        $author = $article['author'] ?? 'Unknown';
    }

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    // Handle main image upload
    if (!empty($_FILES["image"]["name"])) {
        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $upload_path = $target_dir . $image_name;
        $db_path = 'admin/uploads/' . $image_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $upload_path)) {
            $image_path = $db_path;
        } else {
            $error = "Error uploading main image.";
        }
    }

    // Handle additional images
    for ($i = 2; $i <= 3; $i++) {
        $fileField = "image{$i}";
        $capField = "caption{$i}";
        $imgVarUrl = "img{$i}_url";
        $imgVarCap = "img{$i}_caption";

        if (!empty($_FILES[$fileField]["name"])) {
            $extra_name = time() . "_{$i}_" . basename($_FILES[$fileField]["name"]);
            $extra_path = $target_dir . $extra_name;
            $extra_db_path = "admin/uploads/" . $extra_name;
            if (move_uploaded_file($_FILES[$fileField]["tmp_name"], $extra_path)) {
                $$imgVarUrl = $extra_db_path;
            }
        }

        if (isset($_POST[$capField])) {
            $$imgVarCap = trim($_POST[$capField]);
        }
    }

    if (!isset($error)) {
        // ----------------- UPDATE (draft) -----------------
        if (isset($_POST['draft'])) {
            $sql = "UPDATE articles SET category=?, title=?, author=?, author_id=?, date_posted=?, image_url=?, 
                    image2_url=?, image2_caption=?, image3_url=?, image3_caption=?, body=?, status='draft', edit_status='draft'
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssisssssssi",
                $category,
                $title,
                $author,
                $author_id,
                $date_posted,
                $image_path,
                $img2_url,
                $img2_caption,
                $img3_url,
                $img3_caption,
                $body,
                $article_id
            );
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin-drafts.php?success=Article updated as draft");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
                $stmt->close();
            }
        }

        // ----------------- APPROVE (Section Head / Executive) -----------------
        elseif (isset($_POST['approve']) && in_array($role, ['section head', 'executive'])) {
            $sql = "UPDATE articles SET category=?, title=?, author=?, author_id=?, date_posted=?, image_url=?, 
                    image2_url=?, image2_caption=?, image3_url=?, image3_caption=?, body=?, edit_status='approved'
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssisssssssi",
                $category,
                $title,
                $author,
                $author_id,
                $date_posted,
                $image_path,
                $img2_url,
                $img2_caption,
                $img3_url,
                $img3_caption,
                $body,
                $article_id
            );
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin-drafts.php?success=Article approved successfully");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Article</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../adminstyles/admin-style.css">
    <link rel="stylesheet" href="../adminstyles/admin_add_article.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="form-container">
            <a href="admin-drafts.php" class="add-back"><i class="fa-solid fa-arrow-left"></i> Back to Drafts</a>

            <h1>Edit Article</h1>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <label>Category:</label>
                <select name="category" required>
                    <option value="">--Select Category--</option>
                    <?php
                    $catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
                    if ($catResult && $catResult->num_rows > 0) {
                        while ($cat = $catResult->fetch_assoc()) {
                            $selected = ($article['category'] == $cat['name']) ? "selected" : "";
                            echo "<option value='" . htmlspecialchars($cat['name']) . "' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                        }
                    }
                    ?>
                </select>

                <label>Title:</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($article['title'] ?? '') ?>">

                <label>Author:</label>
                <select name="author_id" required>
                    <option value="">-- Select Author (Section Head, Staff, Executive) --</option>
                    <?php
                    $authorQuery = "SELECT id, name, role FROM users WHERE role IN ('Section Head','Staff','Executive') ORDER BY role, name ASC";
                    $authorResult = $conn->query($authorQuery);
                    while ($authorRow = $authorResult->fetch_assoc()) {
                        $roleLabel = htmlspecialchars($authorRow['role']);
                        $isSelected = (intval($article['author_id']) === intval($authorRow['id'])) ? 'selected' : '';
                        echo "<option value='{$authorRow['id']}' $isSelected>" . htmlspecialchars($authorRow['name']) . " ({$roleLabel})</option>";
                    }
                    ?>
                </select>

                <label>Date:</label>
                <input type="date" name="date_posted" required
                       value="<?= htmlspecialchars(isset($article['date_posted']) ? date('Y-m-d', strtotime($article['date_posted'])) : date('Y-m-d')) ?>"
                       max="<?= date('Y-m-d') ?>">

                <label>Body:</label>
                <textarea name="body" rows="8" required><?= htmlspecialchars($article['body'] ?? '') ?></textarea>

                <label>Image:</label>
                <input type="file" name="image" accept="image/*" onchange="previewImage(event)">
                <div class="image-preview" id="imagePreview">
                    <?php if (!empty($article['image_url'])): ?>
                        <img src="../<?= htmlspecialchars($article['image_url']) ?>" alt="Current Image">
                    <?php endif; ?>
                </div>

                <hr>
                <h3>Additional Images (optional)</h3>
                <?php for ($i = 2; $i <= 3; $i++):
                    $imgVarUrl = "image{$i}_url";
                    $imgVarCap = "image{$i}_caption";
                ?>
                    <label>Image <?= $i - 1 ?>:</label>
                    <input type="file" name="image<?= $i ?>" accept="image/*" onchange="previewImage(event, 'preview<?= $i ?>')">
                    <div class="image-preview" id="preview<?= $i ?>">
                        <?php if (!empty($article[$imgVarUrl])): ?>
                            <img src="../<?= htmlspecialchars($article[$imgVarUrl]) ?>" alt="Current Image <?= $i ?>">
                        <?php endif; ?>
                    </div>

                    <label>Caption <?= $i - 1 ?>:</label>
                    <input type="text" name="caption<?= $i ?>" placeholder="Enter caption" value="<?= htmlspecialchars($article[$imgVarCap] ?? '') ?>">
                    <hr>
                <?php endfor; ?>

                <div class="button-group">
                    <input type="submit" name="draft" value="Update" class="draft-btn">
                    <?php if (in_array($role, ['section head', 'executive'])): ?>
                        <input type="submit" name="approve" value="Approve" class="approve-btn" style="background:#28a745; color:white;">
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function previewImage(event, previewId = 'imagePreview') {
    const reader = new FileReader();
    reader.onload = function() {
        document.getElementById(previewId).innerHTML = '<img src="'+ reader.result +'" alt="Preview">';
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>
