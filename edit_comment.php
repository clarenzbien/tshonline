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

// Get comment ID, article ID, and new text
$commentId = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
$articleId = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0;
$newText = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

// Make sure user is logged in
$userId = $_SESSION['user_id'] ?? 0;
$isAdmin = $_SESSION['is_admin'] ?? 0;

if (!$userId) {
    die("Unauthorized access.");
}

// Check if comment exists
$check = $conn->query("SELECT user_id FROM comments WHERE id=$commentId LIMIT 1");
if ($check->num_rows === 0) {
    die("Comment not found.");
}

$row = $check->fetch_assoc();
$ownerId = $row['user_id'];

// Check permissions: owner or admin
if ($userId != $ownerId && !$isAdmin) {
    die("You do not have permission to edit this comment.");
}

// Update the comment if new text is provided
if ($newText !== '') {
    $stmt = $conn->prepare("UPDATE comments SET comment_text=? WHERE id=?");
    $stmt->bind_param("si", $newText, $commentId);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to article
header("Location: article.php?id=$articleId");
exit();
?>
