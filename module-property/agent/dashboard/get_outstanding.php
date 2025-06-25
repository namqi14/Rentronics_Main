<?php
require_once __DIR__ . '/../../../module-auth/dbconnection.php';
session_start();

if (!isset($_GET['month']) || !isset($_GET['year']) || !isset($_GET['agent_id'])) {
    exit(json_encode(['error' => 'Missing parameters']));
}

$month = $_GET['month'];
$year = $_GET['year'];
$agentID = $_GET['agent_id'];

// Get outstanding payments
$query = "
    SELECT 
        t.TenantName,
        p.PropertyName,
        u.UnitNo,
        py.Amount
    FROM Payment py
    JOIN Tenant t ON py.TenantID = t.TenantID
    JOIN Bed b ON t.BedID = b.BedID
    JOIN Unit u ON b.UnitID = u.UnitID
    JOIN Property p ON u.PropertyID = p.PropertyID
    WHERE py.Month = ?
    AND py.Year = ?
    AND py.AgentID = ?
    AND py.PaymentStatus = 'Pending'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $month, $year, $agentID);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $total += $row['Amount'];
}

$response = [
    'payments' => $payments,
    'total' => $total
];

header('Content-Type: application/json');
echo json_encode($response); 