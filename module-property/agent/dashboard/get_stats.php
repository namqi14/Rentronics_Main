<?php
require_once __DIR__ . '/../../../module-auth/dbconnection.php';
session_start();

if (!isset($_GET['year']) || !isset($_GET['agent_id'])) {
    exit(json_encode(['error' => 'Missing parameters']));
}

$year = $_GET['year'];
$agentID = $_GET['agent_id'];

// Initialize monthly stats
$monthlyStats = array_fill_keys([
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
], 0);

// Fetch data for the selected year
$query = "
    SELECT 
        Month, 
        SUM(Amount) as TotalAmount
    FROM Payment 
    WHERE AgentID = ? 
    AND Claimed = 0 
    AND Year = ?
    AND PaymentStatus = 'Successful'
    GROUP BY Month
    ORDER BY FIELD(Month, 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $agentID, $year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $monthlyStats[$row['Month']] = (float)$row['TotalAmount'];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($monthlyStats); 