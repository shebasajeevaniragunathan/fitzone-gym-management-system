<?php
session_start();
require_once __DIR__ . "/../config/db.php";

/* -----------------------------
   Protect customer page
----------------------------- */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$program_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($program_id <= 0) {
    die("Invalid program selected.");
}

/* -----------------------------
   Check if program exists
----------------------------- */
$stmt = $conn->prepare("
    SELECT id, title, day_name, time_slot, trainer_name
    FROM programs
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $program_id);
$stmt->execute();
$result = $stmt->get_result();
$program = $result->fetch_assoc();
$stmt->close();

if (!$program) {
    die("Program not found.");
}

/* -----------------------------
   Check already enrolled
----------------------------- */
$check = $conn->prepare("
    SELECT id
    FROM program_enrollments
    WHERE user_id = ? AND program_id = ?
    LIMIT 1
");

if (!$check) {
    die("Database error: " . $conn->error);
}

$check->bind_param("ii", $user_id, $program_id);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    $check->close();
    header("Location: program-details.php?id=" . $program_id);
    exit;
}
$check->close();

/* -----------------------------
   Start transaction
----------------------------- */
$conn->begin_transaction();

try {
    /* Insert into program_enrollments */
    $insert = $conn->prepare("
        INSERT INTO program_enrollments (user_id, program_id, status)
        VALUES (?, ?, 'active')
    ");

    if (!$insert) {
        throw new Exception("Enrollment prepare failed: " . $conn->error);
    }

    $insert->bind_param("ii", $user_id, $program_id);

    if (!$insert->execute()) {
        throw new Exception("Failed to join program.");
    }

    $insert->close();

    /* -----------------------------
       Also insert into bookings table
    ----------------------------- */
    $tableCheck = $conn->query("SHOW TABLES LIKE 'bookings'");

    if ($tableCheck && $tableCheck->num_rows > 0) {
        /* Avoid duplicate booking */
        $bookingCheck = $conn->prepare("
            SELECT id
            FROM bookings
            WHERE user_id = ? AND program_id = ?
            LIMIT 1
        ");

        if (!$bookingCheck) {
            throw new Exception("Booking check prepare failed: " . $conn->error);
        }

        $bookingCheck->bind_param("ii", $user_id, $program_id);
        $bookingCheck->execute();
        $bookingCheckResult = $bookingCheck->get_result();
        $alreadyBooked = ($bookingCheckResult && $bookingCheckResult->num_rows > 0);
        $bookingCheck->close();

        if (!$alreadyBooked) {
            $booking_date = date("Y-m-d");
            $booking_time = date("H:i:s");
            $booking_status = "confirmed";

            /* trainer_id currently unknown because programs table has trainer_name only */
            $bookingInsert = $conn->prepare("
                INSERT INTO bookings (user_id, program_id, trainer_id, booking_date, booking_time, status)
                VALUES (?, ?, NULL, ?, ?, ?)
            ");

            if (!$bookingInsert) {
                throw new Exception("Booking insert prepare failed: " . $conn->error);
            }

            $bookingInsert->bind_param(
                "iisss",
                $user_id,
                $program_id,
                $booking_date,
                $booking_time,
                $booking_status
            );

            if (!$bookingInsert->execute()) {
                throw new Exception("Booking insert failed: " . $bookingInsert->error);
            }

            $bookingInsert->close();
        }
    }

    $conn->commit();

    /* Redirect to bookings page so user can immediately see result */
    header("Location: bookings.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die($e->getMessage());
}
?>