<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(['error' => 'Category ID missing']);
    exit();
}

$id = (int) $_POST['id'];

$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

// Move any articles with this category to 'unpublished' first
$stmt = $conn->prepare("UPDATE articles SET status = 'unpublished' WHERE category_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Now delete the category
$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to delete category']);
}
$stmt->close();
$conn->close();
