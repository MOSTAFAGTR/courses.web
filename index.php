<?php
session_start();
require_once 'partials/db.php'; // We need the database connection here

// If no one is logged in, they must go to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ** THE FIX: This logic correctly separates all roles **

// First, check if the user is an admin.
if ($_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
} 

// If they are not an admin, check if they are an instructor.
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // This user's ID was found in the instructors table.
    $stmt->close();
    header("Location: instructor_dashboard.php");
    exit();
} else {
    // This user is not an admin and not an instructor, so they must be a student.
    $stmt->close();
    header("Location: user_dashboard.php");
    exit();
}
?>