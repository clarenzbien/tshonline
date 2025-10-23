<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$articleId = intval($data['article_id']);
$actionType = in_array($data['action_type'], ['copy', 'social']) ? $data['action_type'] : 'copy';
$userId = $_SESSION['user_id'] ?? null;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tshonline";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO article_shares (article_id, user_id, action_type) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $articleId, $userId, $actionType);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['status' => 'success']);
?>
