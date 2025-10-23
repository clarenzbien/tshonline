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

if (isset($_GET['id'])) {
    $articleId = intval($_GET['id']);
    $adminId = $_SESSION['admin_id'];

    // Insert record into unpublished tracking table
    $stmt = $conn->prepare("INSERT INTO unpublished (article_id, admin_id) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ii", $articleId, $adminId);
        $stmt->execute();
        $stmt->close();
    }

    // Update status safely
    $updateStmt = $conn->prepare("UPDATE articles SET status = 'unpublished' WHERE id = ?");
    $updateStmt->bind_param("i", $articleId);

    if ($updateStmt->execute()) {
        header("Location: admin-articles.php?success=Article+unpublished+successfully");
        exit();
    } else {
        // Show MySQL error if update fails
        header("Location: admin-articles.php?error=Failed+to+update+status:+{$conn->error}");
        exit();
    }

    $updateStmt->close();
} else {
    header("Location: admin-articles.php?error=Invalid+article+ID");
    exit();
}

$conn->close();
?>
