<?php
require_once __DIR__ . "/config/db.php";

$blog_id = (int)($_POST['blog_id'] ?? 0);
$name    = trim($_POST['name'] ?? '');
$comment = trim($_POST['comment'] ?? '');

if ($blog_id <= 0 || $name === '' || $comment === '') {
  header("Location: blogs.php");
  exit;
}

$stmt = $conn->prepare("INSERT INTO blog_comments (blog_id, name, comment, status) VALUES (?, ?, ?, 'approved')");
$stmt->bind_param("iss", $blog_id, $name, $comment);
$stmt->execute();

header("Location: blog.php?id=" . $blog_id);
exit;