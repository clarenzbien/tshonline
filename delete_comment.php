<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? (isset($_SESSION['admin_id']) ? 1 : 0);

// ✅ Ensure comment ID and article ID are provided
if (!isset($_GET['id']) || !isset($_GET['article_id'])) {
    echo "Invalid request.";
    exit();
}

$commentId = intval($_GET['id']);
$articleId = intval($_GET['article_id']);

// ✅ Prepare delete statement
if ($isAdmin == 1) {
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $commentId);
} else {
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $commentId, $userId);
}

$stmt->execute();

// ✅ Check if comment was deleted
if ($stmt->affected_rows > 0) {
    header("Location: article.php?id=$articleId");
    exit();
} else {
    echo "You are not authorized to delete this comment or it does not exist.";
}

$stmt->close();
$conn->close();
?>
