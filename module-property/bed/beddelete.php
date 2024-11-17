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

// Handle deletion if bed_id is present in the query string
if (isset($_GET['delete_bed_id'])) {
    $bedID = $_GET['delete_bed_id'];

    // Debugging output
    error_log("Deleting bed with BedID: " . $bedID);

    // Check if bedID is valid (non-empty string)
    if (!empty($bedID)) {
        // Escape the BedID to prevent SQL injection
        $bedID = $conn->real_escape_string($bedID);

        // Prepare and execute the delete statement
        $sql = "DELETE FROM Bed WHERE BedID = '$bedID'";
        if ($conn->query($sql) === TRUE) {
            if ($conn->affected_rows > 0) {
                $_SESSION['msg'] = 'Bed deleted successfully.';
            } else {
                $_SESSION['error'] = 'No bed found with the provided BedID.';
            }
        } else {
            $_SESSION['error'] = 'Failed to delete bed. Please try again.';
        }
    } else {
        $_SESSION['error'] = 'Invalid BedID.';
    }
} else {
    $_SESSION['error'] = 'BedID not provided.';
}

// Redirect back to the bed table page
header("Location: bedtable.php");
exit();
?>
