<?php
session_start();
require_once __DIR__ . "/config/db.php";

/* Clear remember token in DB */
if (isset($_SESSION['user_id'])) {

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE users
        SET remember_token = NULL
        WHERE id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/* Delete remember cookie */
if (isset($_COOKIE['fitzone_remember'])) {
    setcookie("fitzone_remember", "", time() - 3600, "/", "", false, true);
}

/* Destroy session */
$_SESSION = [];
session_destroy();

/* Redirect */
header("Location: /fitzone/login.php");
exit;
?>