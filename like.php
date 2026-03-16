<?php
require_once __DIR__ . "/config/db.php";
header("Content-Type: application/json");

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(["ok"=>false,"msg"=>"Invalid blog id"]); exit; }

$userId = null; 
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$stmt = $conn->prepare("INSERT IGNORE INTO blog_likes (blog_id, user_id, ip_address) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $id, $userId, $ip);
$stmt->execute();

$cnt = $conn->prepare("SELECT COUNT(*) AS cnt FROM blog_likes WHERE blog_id=?");
$cnt->bind_param("i", $id);
$cnt->execute();
$likes = (int)$cnt->get_result()->fetch_assoc()['cnt'];

echo json_encode(["ok"=>true, "likes"=>$likes]);