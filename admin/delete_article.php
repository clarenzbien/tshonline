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

// Check if article ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin-articles.php?error=No article selected.");
    exit();
}

$article_id = intval($_GET['id']);

// Start transaction to ensure all deletes succeed together
$conn->begin_transaction();

try {
    // Delete related records first (to avoid foreign key constraint issues)
    $conn->query("DELETE FROM article_views WHERE article_id = $article_id");
    $conn->query("DELETE FROM article_likes WHERE article_id = $article_id");
    $conn->query("DELETE FROM article_saves WHERE article_id = $article_id");
    $conn->query("DELETE FROM comments WHERE article_id = $article_id");

    // Finally delete the article itself
    $conn->query("DELETE FROM articles WHERE id = $article_id");

    // Commit changes
    $conn->commit();

    header("Location: admin-articles.php?success=Article deleted successfully.");
    exit();

} catch (Exception $e) {
    // Rollback if anything failed
    $conn->rollback();
    header("Location: admin-articles.php?error=Failed to delete article. Please try again.");
    exit();
}
?>
