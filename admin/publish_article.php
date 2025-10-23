<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    header("Location: admin_drafts.php?error=Missing article ID");
    exit();
}

$articleId = (int) $_GET['id'];

// ✅ Update status to 'published' and refresh post date
$updateQuery = "UPDATE articles SET status = 'published', date_posted = NOW() WHERE id = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("i", $articleId);

if ($stmt->execute()) {
    // ✅ Redirect to admin-articles.php after successful publish
    header("Location: admin-articles.php?success=Article published successfully!");
} else {
    header("Location: admin_drafts.php?error=Failed to publish article.");
}

$stmt->close();
$conn->close();
exit();
?>
