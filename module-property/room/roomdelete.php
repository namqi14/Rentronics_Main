<?php
session_start();
require_once('dbconnection.php');  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

if (isset($_GET['delete_room_id'])) {
    $roomID = $_GET['delete_room_id'];

    // Escape the RoomID to prevent SQL injection
    $roomID = $conn->real_escape_string($roomID);

    // Prepare and execute the delete statement
    $sql = "DELETE FROM Room WHERE RoomID = '$roomID'";
    if ($conn->query($sql) === TRUE) {
        if ($conn->affected_rows > 0) {
            $_SESSION['msg'] = 'Room deleted successfully.';
        } else {
            $_SESSION['error'] = 'No room found with the provided RoomID.';
        }
    } else {
        $_SESSION['error'] = 'Failed to delete room. Please try again.';
    }
} else {
    $_SESSION['error'] = 'RoomID not provided.';
}

// Redirect back to the room table page
header("Location: roomtable.php");
exit();
?>
