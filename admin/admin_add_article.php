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

$error = "";

// Fetch logged-in user info
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$author = $user['name'] ?? 'Admin';
$stmt->close();

if (isset($_POST['submit']) || isset($_POST['draft'])) {
    // Get POST values safely
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    $date_posted = $_POST['date_posted'];
    $status = isset($_POST['draft']) ? 'draft' : 'published';

    // Validate author_id properly
    if (empty($_POST['author_id']) || !is_numeric($_POST['author_id'])) {
        $error = "Please select a valid author.";
    } else {
        $author_id = intval($_POST['author_id']);
    }

    // Validate category and main image
    if ($category_id <= 0) {
        $error = "Please select a valid category.";
    } elseif (empty($_FILES["image"]["name"])) {
        $error = "Main image is required.";
    }

    if (empty($error)) {
        // Upload main image
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_name = time() . '_' . basename($_FILES["image"]["name"]);
        $upload_path = $target_dir . $image_name;
        $db_path = 'admin/uploads/' . $image_name;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $upload_path)) {
            $error = "Error uploading main image. Check folder permissions.";
        } else {
            // Additional images
            $img2_url = $img2_caption = null;
            $img3_url = $img3_caption = null;

            for ($i = 2; $i <= 3; $i++) {
                $field = "image{$i}";
                $cap_field = "caption{$i}";
                $imgVarUrl = "img{$i}_url";
                $imgVarCap = "img{$i}_caption";

                $$imgVarUrl = null;
                $$imgVarCap = null;

                if (!empty($_FILES[$field]["name"])) {
                    $extra_name = time() . "_{$i}_" . basename($_FILES[$field]["name"]);
                    $extra_path = $target_dir . $extra_name;
                    $extra_db_path = "admin/uploads/" . $extra_name;

                    if (move_uploaded_file($_FILES[$field]["tmp_name"], $extra_path)) {
                        $$imgVarUrl = $extra_db_path;
                    }
                }

                if (!empty($_POST[$cap_field])) {
                    $$imgVarCap = trim($_POST[$cap_field]);
                }
            }

            // Ensure $author_id is correct
            $author_id = intval($_POST['author_id']);

            // Fetch author name correctly
            // Fetch author name correctly using prepared statement
            $stmtAuthor = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmtAuthor->bind_param("i", $author_id);
            $stmtAuthor->execute();
            $authorData = $stmtAuthor->get_result()->fetch_assoc();
            $author = $authorData['name'] ?? 'Unknown';
            $stmtAuthor->close();

            // Fetch category name
            $stmtCat = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmtCat->bind_param("i", $category_id);
            $stmtCat->execute();
            $catData = $stmtCat->get_result()->fetch_assoc();
            $category_name = $catData['name'] ?? 'Uncategorized';
            $stmtCat->close();

            // Now insert the article with author name
            $stmt = $conn->prepare("
                INSERT INTO articles 
                (category_id, category, title, author, author_id, date_posted, image_url, 
                image2_url, image2_caption, image3_url, image3_caption,
                body, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssissssssss",
                $category_id,
                $category_name,
                $title,
                $author, // <- this now has the proper author name
                $author_id,
                $date_posted,
                $db_path,
                $img2_url,
                $img2_caption,
                $img3_url,
                $img3_caption,
                $body,
                $status
            );


            if ($stmt->execute()) {
                $redirect = $status === 'draft' ? "draft_saved=1" : "success=1";
                $redirect .= "&category_id={$category_id}&author_id={$author_id}";
                header("Location: admin_add_article.php?$redirect");
                exit();
            } else {
                $error = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}



// Preselect values after redirect
$selected_category = $_GET['category_id'] ?? '';
$selected_author = $_GET['author_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Article</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../adminstyles/admin_add_article.css">
    <link rel="stylesheet" href="../adminstyles/admin-style.css">
    <style>
        .image-preview img {
            max-width: 150px;
            margin-top: 8px;
            border-radius: 6px;
            display: block;
        }
    </style>
</head>
<body>
<button id="hamburgerBtn" class="hamburger-btn">
    <i class="fa-solid fa-bars"></i>
</button>


<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="form-container">
            <a href="admin-articles.php" class="add-back"><i class="fa-solid fa-arrow-left"></i> Back to Articles</a>
            <h1>Add New Article</h1>

            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">✅ Article published successfully!</div>
            <?php elseif (isset($_GET['draft_saved'])): ?>
                <div class="success-message">📝 Draft saved successfully!</div>
            <?php elseif (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="post" enctype="multipart/form-data">
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">--Select Category--</option>
                    <?php
                    $catQuery = "SELECT id, name FROM categories 
                                 ORDER BY FIELD(name,'NEWS','EDITORIAL','OPINION','FEATURE','LITERARY','DEVCOMM','SPORTS','ENTERTAINMENT')";
                    $catResult = $conn->query($catQuery);
                    while ($cat = $catResult->fetch_assoc()) {
                        $isSelected = ($cat['id'] == $selected_category) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($cat['id']) . '" ' . $isSelected . '>' . htmlspecialchars($cat['name']) . '</option>';
                    }
                    ?>
                </select>

                <label>Title:</label>
                <input type="text" name="title" required>

                <label>Author:</label>
                <select name="author_id" required>
                    <option value="">-- Select Author (Staff or Executive) --</option>
                    <?php
                    $authorQuery = "SELECT id, name, role FROM users WHERE role IN ('Staff', 'Executive') ORDER BY role, name ASC";
                    $authorResult = $conn->query($authorQuery);
                   while ($authorRow = $authorResult->fetch_assoc()) {
    $roleLabel = htmlspecialchars($authorRow['role']);
    $isSelected = ($authorRow['id'] == $selected_author) ? 'selected' : '';
    echo "<option value='{$authorRow['id']}' $isSelected>" . htmlspecialchars($authorRow['name']) . " ({$roleLabel})</option>";
}

                    ?>
                </select>

                <label>Date:</label>
                <input type="date" name="date_posted" required 
                    value="<?= date('Y-m-d') ?>" 
                    max="<?= date('Y-m-d') ?>">

                <label>Body:</label>
                <textarea name="body" rows="8" required></textarea>

                <label>Main Image (required):</label>
                <input type="file" name="image" accept="image/*" required onchange="previewImage(event, 'imagePreview')">
                <div class="image-preview" id="imagePreview"></div>

                <hr>
                <h3>Additional Images (optional)</h3>

                <?php for ($i = 2; $i <= 3; $i++): ?>
                    <label>Image <?= $i - 1 ?>:</label>
                    <input type="file" name="image<?= $i ?>" accept="image/*" onchange="previewImage(event, 'preview<?= $i ?>')">
                    <div class="image-preview" id="preview<?= $i ?>"></div>

                    <label>Caption <?= $i - 1 ?> (optional):</label>
                    <input type="text" name="caption<?= $i ?>" placeholder="Enter caption (optional)">
                    <hr>
                <?php endfor; ?>

                <div class="button-group">
                    <input type="submit" name="submit" value="Publish Article" class="publish-btn">
                    <input type="submit" name="draft" value="Save as Draft" class="draft-btn">
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function previewImage(event, previewId) {
    if (!previewId) previewId = 'imagePreview';
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function() {
        document.getElementById(previewId).innerHTML = '<img src="' + reader.result + '" alt="Preview">';
    };
    reader.readAsDataURL(file);
}
</script>
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
