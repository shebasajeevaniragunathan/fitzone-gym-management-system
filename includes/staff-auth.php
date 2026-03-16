<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/db.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$permissions = [
    'can_manage_queries'      => 0,
    'can_manage_attendance'   => 0,
    'can_manage_appointments' => 0,
    'can_view_registrations'  => 0,
    'can_view_payments'       => 0,
    'can_update_membership'   => 0
];

/* Main admin gets all access */
if ($role === 'admin') {
    foreach ($permissions as $key => $value) {
        $permissions[$key] = 1;
    }
}
/* Staff gets limited access from DB */
elseif ($role === 'staff') {
    $stmt = $conn->prepare("
        SELECT 
            can_manage_queries,
            can_manage_attendance,
            can_manage_appointments,
            can_view_registrations,
            can_view_payments,
            can_update_membership
        FROM staff_permissions
        WHERE staff_id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row) {
            $permissions = $row;
        }
        $stmt->close();
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>