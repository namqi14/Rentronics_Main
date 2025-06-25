<?php
session_start();
require_once '../../module-auth/dbconnection.php';

// Get payment ID from URL
$payment_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($payment_id) {
    $stmt = $conn->prepare("
        SELECT 
            p.PaymentID,
            p.Amount,
            p.DateCreated,
            p.PaymentType,
            p.Month,
            p.Year,
            t.TenantName,
            t.TenantID,
            b.BedNo,
            b.BedID,
            r.RoomNo,
            u.UnitNo,
            a.AgentName,
            a.AgentID
        FROM payment p
        LEFT JOIN tenant t ON t.TenantID = p.TenantID
        LEFT JOIN bed b ON b.BedID = p.BedID
        LEFT JOIN room r ON r.RoomID = b.RoomID
        LEFT JOIN unit u ON u.UnitID = r.UnitID
        LEFT JOIN agent a ON a.AgentID = p.AgentID
        WHERE p.PaymentID = ?
    ");

    $stmt->bind_param("s", $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $payment_data = $result->fetch_assoc();
        $error = null; // No error
    } else {
        $error = "Payment not found";
    }
} else {
    $error = "No payment ID provided";
}

// Include your existing receipt template
include 'receipt.html.php';
?>