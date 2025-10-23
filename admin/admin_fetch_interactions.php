<?php
// ⚠️ No spaces or newlines before this line!
header('Content-Type: application/json; charset=utf-8');
session_start();

// Prevent PHP notices from polluting JSON
error_reporting(0);
ini_set('display_errors', 0);

// --- Check if admin is logged in ---
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Database connection ---
$conn = new mysqli("localhost", "root", "", "tshonline");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// --- Get and validate inputs ---
$type = $_GET['type'] ?? '';
$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;

if (!in_array($type, ['likes', 'shares', 'saves', 'comments' ,'views'])) {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

if ($article_id <= 0) {
    echo json_encode(['error' => 'Invalid article ID']);
    exit;
}

// --- Choose the correct table and timestamp column ---
switch ($type) {
    case 'likes':
        $table = 'article_likes';
        $date_column = 'liked_at';
        break;
    case 'shares':
        $table = 'article_shares';
        $date_column = 'shared_at';
        break;
    case 'saves':
        $table = 'article_saves';
        $date_column = 'saved_at';
        break;
    case 'comments':
        $table = 'comments';
        $date_column = 'date_commented';
        break;
    case 'views':
        $table = 'article_views';
        $date_column = 'viewed_at';
        break;
}

// --- Query users who interacted ---
$sql = "
    SELECT 
        u.name, 
        COALESCE(u.profile_pic, 'images/default-avatar.png') AS profile_pic, 
        i.$date_column AS date
    FROM $table i
    INNER JOIN users u ON u.id = i.user_id
    WHERE i.article_id = ?
    ORDER BY i.$date_column DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

$interactions = [];
while ($row = $result->fetch_assoc()) {
    $interactions[] = [
        'name' => $row['name'],
        'profile_pic' => $row['profile_pic'],
        'date' => date('M d, Y h:i A', strtotime($row['date']))
    ];
}

$stmt->close();
$conn->close();

echo json_encode($interactions);
exit;
