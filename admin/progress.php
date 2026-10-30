<?php
require __DIR__ . '/../include/config.php';
header('Content-Type: application/json');

$campaign_id = (int)($_GET['campaign_id'] ?? 0);
if ($campaign_id <= 0) exit(json_encode(['error' => 'invalid']));

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) exit(json_encode(['error' => 'db']));
$conn->set_charset('utf8mb4');

$stmt = $conn->prepare("
    SELECT total_clicks, completed_clicks, status
    FROM traffic_campaigns
    WHERE id = ?
");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$data) exit(json_encode(['error' => 'not_found']));

echo json_encode([
    'total'     => (int)$data['total_clicks'],
    'completed' => (int)$data['completed_clicks'],
    'status'    => $data['status']
]);