<?php
require_once __DIR__ . '/../../module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: /index.php");
    exit();
}

// At the top of the file, add the excluded RoomIDs array
$excludedRoomIds = [
    'R0001', // Add the RoomIDs you want to hide
    'R0002',
    'R0003',
    'R0004',
    'R0005',
    'R0006',
    'R0007',
    'R0008',
    'R0009',
    'R0010',
    'R0011',
    'R0012',
    'R0013',
    'R0014',
    'R0015',
    'R0016',
    'R0017',
    'R0021',
    'R0029',
    'R0030',
    'R0031',
    'R0032',
    'R0033',
    'R0034',
    'R0035',
    'R0036',
    'R0037',
    'R0038',
    'R0043',
    'R0050',
    'R0055',
    'R0063',
];

// No need to check AccessLevel since both levels can access
try {
    $agentName = $_SESSION['auser']['AgentName'] ?? 'Agent';

    // Modified query to include agent information
    $query = "SELECT 
                b.BedID, 
                b.RoomID, 
                b.UnitID, 
                b.BedNo, 
                NULLIF(b.BaseRentAmount, 0) as BaseRentAmount,
                NULLIF(b.BedRentAmount, 0) as BedRentAmount, 
                b.BedStatus,
                b.AgentID,
                a.AgentName,
                u.Investor,
                u.UnitNo,
                u.PropertyID,
                p.PropertyName,
                NULLIF(r.RoomRentAmount, 0) as RoomRentAmount,
                r.Katil,
                r.RoomStatus,
                -- Get bed payments
                pm_bed.Month as BedMonth,
                pm_bed.Year as BedYear,
                pm_bed.Amount as BedPaidAmount,
                pm_bed.PaymentStatus as BedPaymentStatus,
                pm_bed.PaymentType as BedPaymentType,
                pm_bed.DateCreated as BedPaymentDate,
                pm_bed.Remarks as BedRemarks,
                -- Get room payments
                pm_room.Month as RoomMonth,
                pm_room.Year as RoomYear,
                pm_room.Amount as RoomPaidAmount,
                pm_room.PaymentStatus as RoomPaymentStatus,
                pm_room.PaymentType as RoomPaymentType,
                pm_room.DateCreated as RoomPaymentDate,
                pm_room.Remarks as RoomRemarks
              FROM bed b
              LEFT JOIN unit u ON b.UnitID = u.UnitID
              LEFT JOIN property p ON u.PropertyID = p.PropertyID
              LEFT JOIN agent a ON b.AgentID = a.AgentID
              LEFT JOIN room r ON b.RoomID = r.RoomID
              -- Get bed payments
              LEFT JOIN (
                SELECT p1.*
                FROM payment p1
                LEFT JOIN payment p2
                ON p1.BedID = p2.BedID 
                AND p1.Month = p2.Month 
                AND p1.Year = p2.Year
                AND p2.DateCreated > p1.DateCreated
                WHERE p1.PaymentStatus = 'Successful'
                AND p1.PaymentType IN ('Rent Payment')
                AND p1.Year = 2025
                AND p1.BedID IS NOT NULL
                AND p2.PaymentID IS NULL
              ) pm_bed ON b.BedID = pm_bed.BedID
              -- Get room payments
              LEFT JOIN (
                SELECT p1.*
                FROM payment p1
                LEFT JOIN payment p2
                ON p1.RoomID = p2.RoomID 
                AND p1.Month = p2.Month 
                AND p1.Year = p2.Year
                AND p2.DateCreated > p1.DateCreated
                WHERE p1.PaymentStatus = 'Successful'
                AND p1.PaymentType IN ('Rent Payment', 'Deposit Payment')
                AND p1.Year = 2025
                AND p1.RoomID IS NOT NULL
                AND p2.PaymentID IS NULL
              ) pm_room ON b.RoomID = pm_room.RoomID
              ORDER BY b.UnitID, b.BedNo";

    $result = mysqli_query($conn, $query);
    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    // Restructure data to organize payments by bed and month
    $rooms = [];
    $payments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $bedId = $row['BedID'];
        $month = $row['BedMonth'];
        $year = $row['BedYear'];

        // Initialize bed data if not exists
        if (!isset($rooms[$bedId])) {
            $rooms[$bedId] = [
                'BedID' => $row['BedID'],
                'RoomID' => $row['RoomID'],
                'UnitID' => $row['UnitID'],
                'BedNo' => $row['BedNo'],
                'BaseRentAmount' => $row['BaseRentAmount'],
                'BedRentAmount' => $row['BedRentAmount'],
                'BedStatus' => $row['BedStatus'],
                'AgentName' => $row['AgentName'],
                'Investor' => $row['Investor'],
                'UnitNo' => $row['UnitNo'],
                'RoomRentAmount' => $row['RoomRentAmount'],
                'RoomStatus' => $row['RoomStatus'],
                'payments' => []
            ];
        }

        // Add payment data if exists
        if ($month && $year) {
            $rooms[$bedId]['payments'][$month] = [
                'Month' => $month,
                'Year' => $year,
                'Amount' => $row['BedPaidAmount'],
                'PaymentStatus' => $row['BedPaymentStatus'],
                'PaymentType' => $row['BedPaymentType'],
                'DateCreated' => $row['BedPaymentDate'],
                'Remarks' => $row['BedRemarks']
            ];
        }

        // Add room payment data
        if ($row['RoomMonth'] && $row['RoomYear']) {
            $rooms[$bedId]['room_payments'][$row['RoomMonth']] = [
                'Month' => $row['RoomMonth'],
                'Year' => $row['RoomYear'],
                'Amount' => $row['RoomPaidAmount'],
                'PaymentStatus' => $row['RoomPaymentStatus'],
                'PaymentType' => $row['RoomPaymentType'],
                'DateCreated' => $row['RoomPaymentDate'],
                'Remarks' => $row['RoomRemarks']
            ];
        }
    }

    // Restructure data to organize by rooms first
    $groupedRooms = [];
    foreach ($rooms as $room) {
        $bedParts = explode('-', $room['BedNo']);
        $roomNo = implode('-', array_slice($bedParts, 0, count($bedParts) - 1));
        $bedNo = end($bedParts);

        if (!isset($groupedRooms[$roomNo])) {
            $groupedRooms[$roomNo] = [];
        }
        $groupedRooms[$roomNo][] = $room;
    }
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>
        Rentronics
    </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Stylesheet -->
    <link href="../../css/dashboardagent.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../../css/dashboardproperty.css" rel="stylesheet">


    <style>
        #propertyTable tfoot td {
            padding: 8px;
            border: 1px solid #dee2e6;
            background-color: #fff;
            white-space: nowrap;
            height: 41px;
            vertical-align: middle;
        }

        #propertyTable tfoot td.text-end {
            text-align: right;
        }

        #propertyTable {
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .tooltip-inner {
            max-width: 300px;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.8);
            white-space: pre-line;
            text-align: left;
        }

        #propertyTable thead th {
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        #propertyTable thead th:hover {
            background-color: #f8f9fa;
        }

        .room-header {
            background-color: #f8f9fa;
        }

        .room-header td.room-name.rented {
            background-color: #ffcdd2 !important;
        }

        .room-header .room-status {
            text-align: center;
            color: #666;
        }

        .room-header .room-status.na-status {
            background-color: #ffcdd2;
        }

        .room-id.rented-room {
            background-color: #ffcdd2;
        }

        .payment-match {
            background-color: #c8e6c9 !important;
        }

        .payment-different {
            background-color: #ffffff !important;
        }

        .na {
            background-color: #ffcdd2 !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!--Navbar Start-->
        <!-- Navbar and Sidebar Start-->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h3 class="page-title">Welcome, <?php echo $agentName; ?></h3>
                            <p></p>
                        </div>
                    </div>
                </div>
                <div class="table-container">
                    <div class="filter-section mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select id="investorFilter" class="form-select">
                                    <option value="">All Investors</option>
                                    <option value="Internal">Internal</option>
                                    <option value="External">External</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Unit Status</option>
                                    <option value="unit-rented">Rented</option>
                                    <option value="unit-available">Available</option>
                                    <option value="unit-booked">Booked</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="paymentFilter" class="form-select">
                                    <option value="">All Payment Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="na">Not Paid</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select id="monthFilter" class="form-select">
                                    <option value="">All Months</option>
                                    <option value="5">Jan</option>
                                    <option value="6">Feb</option>
                                    <option value="7">Mar</option>
                                    <option value="8">Apr</option>
                                    <option value="9">May</option>
                                    <option value="10">Jun</option>
                                    <option value="11">Jul</option>
                                    <option value="12">Aug</option>
                                    <option value="13">Sep</option>
                                    <option value="14">Oct</option>
                                    <option value="15">Nov</option>
                                    <option value="16">Dec</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" id="unitSearch" class="form-control" placeholder="Search Unit/Bed ID...">
                            </div>
                        </div>
                    </div>

                    <table id="propertyTable">
                        <thead>
                            <tr>
                                <th>Internal/External?</th>
                                <th>Base Rate</th>
                                <th>Current Rate</th>
                                <th>Unit</th>
                                <th>Bed ID</th>
                                <th>Jan</th>
                                <th>Feb</th>
                                <th>Mar</th>
                                <th>Apr</th>
                                <th>May</th>
                                <th>Jun</th>
                                <th>Jul</th>
                                <th>Aug</th>
                                <th>Sep</th>
                                <th>Oct</th>
                                <th>Nov</th>
                                <th>Dec</th>
                                <th>Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($groupedRooms as $roomNo => $roomBeds):
                                // Get the RoomID from the first bed in the group
                                $roomId = $roomBeds[0]['RoomID'];

                                // Only print room header row if RoomID is not in excluded list
                                if (!in_array($roomId, $excludedRoomIds)) {
                                    $isRoomRented = strtolower($roomBeds[0]['RoomStatus']) === 'rented';
                                    
                                    echo '<tr class="room-header">';
                                    echo '<td></td>'; // Internal/External
                                    echo '<td>' . number_format($roomBeds[0]['BaseRentAmount'], 2) . '</td>'; // Base Rate
                                    echo '<td>' . number_format($roomBeds[0]['RoomRentAmount'], 2) . '</td>'; // Current Rate
                                    echo '<td class="room-name' . ($isRoomRented ? ' rented' : '') . '"><strong>' . htmlspecialchars($roomNo) . '</strong></td>';
                                    echo '<td><strong>' . htmlspecialchars($roomId) . '</strong></td>'; // Bed ID
                                    
                                    // Display room payments in the header row
                                    foreach (range(1, 12) as $month) {
                                        $monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$month - 1];
                                        $status = 'NA';
                                        $amount = null;
                                        $paymentType = '';
                                        $paymentDate = '';
                                        $remarks = '';
                                        
                                        // Check if there's a room payment for this month
                                        // First check in room_payments array
                                        if (isset($roomBeds[0]['room_payments'][$monthName])) {
                                            $payment = $roomBeds[0]['room_payments'][$monthName];
                                            $status = number_format($payment['Amount'], 2);
                                            $amount = $payment['Amount'];
                                            $paymentType = $payment['PaymentType'];
                                            $paymentDate = $payment['DateCreated'];
                                            $remarks = $payment['Remarks'];
                                        }
                                        // If not found and this is a rented room, check if any bed has a payment with RoomID
                                        else if ($isRoomRented) {
                                            foreach ($roomBeds as $bed) {
                                                if (isset($bed['payments'][$monthName]) && 
                                                    $bed['payments'][$monthName]['PaymentType'] == 'Rent Payment') {
                                                    $payment = $bed['payments'][$monthName];
                                                    $status = number_format($payment['Amount'], 2);
                                                    $amount = $payment['Amount'];
                                                    $paymentType = $payment['PaymentType'];
                                                    $paymentDate = $payment['DateCreated'];
                                                    $remarks = $payment['Remarks'];
                                                    break; // Use the first payment found
                                                }
                                            }
                                        }
                                        
                                        $statusClass = 'room-status';
                                        if ($amount === null) {
                                            $statusClass .= ' na-status';
                                        } else if ($amount == $roomBeds[0]['RoomRentAmount']) {
                                            $statusClass .= ' payment-match';
                                        } else {
                                            $statusClass .= ' payment-mismatch';
                                        }
                                        
                                        echo '<td class="' . $statusClass . '"';
                                        if ($amount !== null) {
                                            echo ' data-bs-toggle="tooltip" data-bs-html="true" ';
                                            echo ' title="Amount: RM' . number_format($amount, 2);
                                            if ($amount != $roomBeds[0]['RoomRentAmount']) {
                                                echo ' (Rate: RM' . number_format($roomBeds[0]['RoomRentAmount'], 2) . ')';
                                            }
                                            echo '&#013;Type: ' . htmlspecialchars($paymentType);
                                            echo '&#013;Date: ' . date('d/m/Y', strtotime($paymentDate));
                                            if (!empty($remarks)) {
                                                echo '&#013;Remarks: ' . htmlspecialchars($remarks);
                                            }
                                            echo '"';
                                        }
                                        echo '>' . $status . '</td>';
                                    }
                                    echo '<td>' . htmlspecialchars($room['AgentName'] ?? 'N/A') . '</td>'; // Agent
                                    echo '</tr>';
                                }

                                // Print individual bed rows
                                foreach ($roomBeds as $room):
                                    // Skip beds with BedStatus of Staff or Unavailable
                                    if (in_array(strtolower($room['BedStatus']), ['staff', 'unavailable'])) {
                                        continue;
                                    }

                                    $unitStatus = $room['BedStatus'];
                                    $unitClass = match (strtolower($unitStatus)) {
                                        'rented' => 'unit-rented',
                                        'available' => 'unit-available',
                                        'booked' => 'unit-booked',
                                        default => ''
                                    };
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['Investor']); ?></td>
                                        <td><?php echo number_format($room['BaseRentAmount'], 2); ?></td>
                                        <td><?php echo $room['BedRentAmount'] ? number_format($room['BedRentAmount'], 2) : 'NA'; ?></td>
                                        <td class="<?php echo $unitClass; ?>"><?php echo htmlspecialchars($room['BedNo']); ?></td>
                                        <td><?php echo htmlspecialchars($room['BedID']); ?></td>
                                        <?php 
                                        // Display bed payments in the bed rows
                                        foreach (range(1, 12) as $month) {
                                            $monthName = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$month - 1];
                                            $status = 'NA';
                                            $amount = null;
                                            $paymentType = '';
                                            $paymentDate = '';
                                            
                                            // Check if there's a bed payment for this month
                                            if (isset($room['payments'][$monthName])) {
                                                $payment = $room['payments'][$monthName];
                                                $status = number_format($payment['Amount'], 2);
                                                $amount = $payment['Amount'];
                                                $paymentType = $payment['PaymentType'];
                                                $paymentDate = $payment['DateCreated'];
                                                $remarks = $payment['Remarks'];
                                            }
                                            
                                            $cellClass = 'na';
                                            if ($amount !== null) {
                                                if ($amount == $room['BedRentAmount']) {
                                                    $cellClass = 'payment-match';
                                                } else {
                                                    // Different amount - use white background
                                                    $cellClass = 'payment-different';
                                                }
                                                if ($paymentType) {
                                                    $cellClass .= ' ' . strtolower(str_replace(' ', '-', $paymentType));
                                                }
                                            }
                                            
                                            echo '<td class="' . $cellClass . '"';
                                            if ($amount !== null) {
                                                echo ' data-bs-toggle="tooltip" data-bs-html="true" ';
                                                echo ' title="Amount: RM' . number_format($amount, 2);
                                                if ($amount != $room['BedRentAmount']) {
                                                    echo ' (Rate: RM' . number_format($room['BedRentAmount'], 2) . ')';
                                                }
                                                echo '&#013;Type: ' . htmlspecialchars($paymentType);
                                                echo '&#013;Date: ' . date('d/m/Y', strtotime($paymentDate));
                                                if (!empty($remarks)) {
                                                    echo '&#013;Remarks: ' . htmlspecialchars($remarks);
                                                }
                                                echo '"';
                                            }
                                            echo '>' . $status . '</td>';
                                        }
                                        ?>
                                        <td><?php echo htmlspecialchars($room['AgentName'] ?? 'N/A'); ?></td>
                                    </tr>
                            <?php
                                endforeach;
                            endforeach;
                            ?>
                        </tbody>
                        <tfoot>
                            <tr id="totalRow">
                                <td>Check Sum</td>
                                <td></td>
                                <td></td>
                                <td>
                                    <?php
                                    $statusCounts = [
                                        'unit-rented' => 0,
                                        'unit-available' => 0,
                                        'unit-booked' => 0
                                    ];

                                    foreach ($rooms as $room) {
                                        $status = match (strtolower($room['BedStatus'])) {
                                            'rented' => 'unit-rented',
                                            'available' => 'unit-available',
                                            'booked' => 'unit-booked',
                                            default => ''
                                        };
                                        if (isset($statusCounts[$status])) {
                                            $statusCounts[$status]++;
                                        }
                                    }

                                    echo "Rented: {$statusCounts['unit-rented']}, ";
                                    echo "Available: {$statusCounts['unit-available']}, ";
                                    echo "Booked: {$statusCounts['unit-booked']}";
                                    ?>
                                </td>
                                <td></td>
                                <?php
                                // Initialize array to store sums for each month
                                $monthlyTotals = array_fill(0, 12, 0);

                                // Calculate sums
                                foreach ($rooms as $room) {
                                    // Add bed payments to monthly totals
                                    if (isset($room['payments']) && !empty($room['payments'])) {
                                        foreach ($room['payments'] as $month => $payment) {
                                            $monthIndex = array_search($month, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                                            if ($monthIndex !== false) {
                                                $monthlyTotals[$monthIndex] += $payment['Amount'];
                                            }
                                        }
                                    }
                                    
                                    // Add room payments to monthly totals (only once per room)
                                    if (isset($room['room_payments']) && !empty($room['room_payments'])) {
                                        foreach ($room['room_payments'] as $month => $payment) {
                                            $monthIndex = array_search($month, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
                                            if ($monthIndex !== false) {
                                                // Only add room payment if this is the first bed in the room
                                                $isFirstBedInRoom = true;
                                                foreach ($rooms as $otherRoom) {
                                                    if ($otherRoom['RoomID'] === $room['RoomID'] && $otherRoom['BedID'] < $room['BedID']) {
                                                        $isFirstBedInRoom = false;
                                                        break;
                                                    }
                                                }
                                                if ($isFirstBedInRoom) {
                                                    $monthlyTotals[$monthIndex] += $payment['Amount'];
                                                }
                                            }
                                        }
                                    }
                                }

                                // Display sums
                                foreach ($monthlyTotals as $index => $total) {
                                    echo '<td class="text-end" data-month="' . $index . '"><strong>' .
                                        number_format($total, 2) . '</strong></td>';
                                }
                                ?>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="payment-legend">
                        <div class="legend-item">
                            <span class="legend-color na"></span> No Payment
                        </div>
                        <div class="legend-item">
                            <span class="legend-color deposit-payment"></span> Rent Payment
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js"
        integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const investorFilter = document.getElementById('investorFilter');
            const statusFilter = document.getElementById('statusFilter');
            const paymentFilter = document.getElementById('paymentFilter');
            const monthFilter = document.getElementById('monthFilter');
            const unitSearch = document.getElementById('unitSearch');
            const tableRows = document.querySelectorAll('#propertyTable tbody tr');
            const table = document.getElementById('propertyTable');

            function filterTable() {
                const selectedInvestor = investorFilter.value.toLowerCase();
                const selectedStatus = statusFilter.value.toLowerCase();
                const selectedPayment = paymentFilter.value.toLowerCase();
                const selectedMonth = parseInt(monthFilter.value);
                const searchText = unitSearch.value.toLowerCase();

                let lastRoomHeaderRow = null;
                let showCurrentGroup = false;

                tableRows.forEach(row => {
                    // Check if this is a room header row (has empty first cell)
                    const isRoomHeader = row.cells[0].textContent.trim() === '';

                    if (isRoomHeader) {
                        lastRoomHeaderRow = row;
                        showCurrentGroup = false; // Reset for new group
                        row.style.display = 'none'; // Hide by default
                    } else {
                        const investorCell = row.cells[0].textContent.toLowerCase();
                        const unitCell = row.cells[3];
                        const unitStatus = unitCell.className.toLowerCase();
                        const unitText = row.cells[3].textContent.toLowerCase();
                        const bedId = row.cells[4]?.textContent.toLowerCase();

                        // Check payment status - Modified logic
                        let matchesPayment = true;
                        if (selectedPayment) {
                            // Get the month column index (default to Jan if no month selected)
                            const monthColumnIndex = selectedMonth ? selectedMonth : 5;
                            const monthCell = row.cells[monthColumnIndex];
                            const cellValue = monthCell.textContent.trim();
                            const cellClass = monthCell.className.toLowerCase();

                            if (selectedPayment === 'na') {
                                matchesPayment = cellValue === 'NA';
                            } else if (selectedPayment === 'paid') {
                                matchesPayment = cellValue !== 'NA' && !isNaN(parseFloat(cellValue.replace(/,/g, '')));
                            }
                        }

                        const matchesInvestor = !selectedInvestor || investorCell.includes(selectedInvestor);
                        const matchesStatus = !selectedStatus || unitStatus.includes(selectedStatus);
                        const matchesSearch = !searchText || unitText.includes(searchText) || bedId?.includes(searchText);

                        const shouldShowRow = matchesInvestor && matchesStatus && matchesSearch && matchesPayment;

                        if (shouldShowRow) {
                            showCurrentGroup = true;
                        }

                        row.style.display = shouldShowRow ? '' : 'none';
                    }

                    // Show room header if any row in its group is visible
                    if (showCurrentGroup && lastRoomHeaderRow) {
                        lastRoomHeaderRow.style.display = '';
                    }
                });

                calculateMonthlyTotals();
            }

            // Month filter functionality
            monthFilter.addEventListener('change', function() {
                const selectedMonth = parseInt(this.value);
                const headers = table.querySelectorAll('thead th');
                const rows = table.querySelectorAll('tbody tr');
                const footerCells = table.querySelectorAll('tfoot td');

                // Show all columns if "All Months" is selected
                if (selectedMonth === 0 || isNaN(selectedMonth)) {
                    headers.forEach(header => header.style.display = '');
                    rows.forEach(row => {
                        Array.from(row.cells).forEach(cell => cell.style.display = '');
                    });
                    Array.from(footerCells).forEach(cell => cell.style.display = '');
                } else {
                    // Show/hide columns based on selection
                    headers.forEach((header, index) => {
                        if (index < 5 || index === selectedMonth || index === headers.length - 1) { // Keep first 5 columns, selected month, and Agent column
                            header.style.display = '';
                        } else {
                            header.style.display = 'none';
                        }
                    });

                    rows.forEach(row => {
                        Array.from(row.cells).forEach((cell, index) => {
                            if (index < 5 || index === selectedMonth || index === row.cells.length - 1) { // Keep first 5 columns, selected month, and Agent column
                                cell.style.display = '';
                            } else {
                                cell.style.display = 'none';
                            }
                        });
                    });

                    Array.from(footerCells).forEach((cell, index) => {
                        if (index < 5 || index === selectedMonth || index === footerCells.length - 1) { // Keep first 5 columns, selected month, and Agent column
                            cell.style.display = '';
                        } else {
                            cell.style.display = 'none';
                        }
                    });
                }

                calculateMonthlyTotals();
            });

            // Add event listeners for all filters
            investorFilter.addEventListener('change', filterTable);
            statusFilter.addEventListener('change', filterTable);
            paymentFilter.addEventListener('change', filterTable);
            unitSearch.addEventListener('input', filterTable);

            // Initial calculation
            calculateMonthlyTotals();

            function calculateMonthlyTotals() {
                const visibleRows = Array.from(document.querySelectorAll('#propertyTable tbody tr')).filter(row =>
                    row.style.display !== 'none'
                );

                // Initialize monthly totals array
                const monthlyTotals = Array(12).fill(0);

                // Calculate totals for visible rows
                visibleRows.forEach(row => {
                    const isRoomHeader = row.cells[0].textContent.trim() === '';
                    
                    // Start from index 5 (Jan) to 16 (Dec)
                    for (let i = 5; i <= 16; i++) {
                        const cell = row.cells[i];
                        if (cell && cell.textContent) {  // Check if cell exists and has content
                            const value = cell.textContent.trim();
                            if (value !== 'NA' && !isNaN(parseFloat(value.replace(/,/g, '')))) {
                                // For room headers (room payments), only add if the room has rented status
                                if (isRoomHeader) {
                                    if (row.querySelector('.room-name.rented')) {
                                        monthlyTotals[i - 5] += parseFloat(value.replace(/,/g, ''));
                                    }
                                } else {
                                    // For bed rows, always add if there's a valid payment
                                    monthlyTotals[i - 5] += parseFloat(value.replace(/,/g, ''));
                                }
                            }
                        }
                    }
                });

                // Update footer cells
                const footerCells = document.querySelectorAll('#propertyTable tfoot td');
                monthlyTotals.forEach((total, index) => {
                    const cell = footerCells[index + 5]; // +5 to skip the first 5 columns
                    if (cell) {
                        cell.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                });
            }

            // Update CSS for highlighted row with proper specificity
            const style = document.createElement('style');
            style.textContent = `
            /* Border styles for highlighted row */
            #propertyTable .highlighted-row td {
                border-top: 2px solid #0d6efd;
                border-bottom: 2px solid #0d6efd;
            }
            
            #propertyTable .highlighted-row td:first-child {
                border-left: 2px solid #0d6efd;
            }
            
            #propertyTable .highlighted-row td:last-child {
                border-right: 2px solid #0d6efd;
            }
            
            /* Remove !important from borders to allow background colors to show */
            #propertyTable .highlighted-row td.na {
                background-color: #ffcdd2;  /* Light red */
            }
            
            #propertyTable .highlighted-row td.unit-available {
                background-color: #c8e6c9;  /* Light green */
            }
            
            #propertyTable .highlighted-row td.unit-rented {
                background-color: #ffcdd2;  /* Light red */
            }
            
            #propertyTable .highlighted-row td.unit-booked {
                background-color: #fff9c4;  /* Light yellow */
            }
        `;
            document.head.appendChild(style);

            // Add click event listeners to all headers
            document.querySelectorAll('#propertyTable thead th').forEach((th, index) => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', sortTable);
            });

            // Add click event listeners to BedID cells
            document.querySelectorAll('#propertyTable tbody tr').forEach(row => {
                const bedIdCell = row.cells[4]; // BedID is the 5th column (index 4)
                bedIdCell.style.cursor = 'pointer';
                bedIdCell.addEventListener('click', (e) => {
                    // Remove highlight from all rows
                    document.querySelectorAll('#propertyTable tbody tr').forEach(r => {
                        r.classList.remove('highlighted-row');
                    });

                    // Add highlight to clicked row
                    const clickedRow = e.target.closest('tr');
                    clickedRow.classList.add('highlighted-row');
                });
            });

            // Function to handle table sorting
            function sortTable(event) {
                const th = event.target;
                const table = document.getElementById('propertyTable');
                const tbody = table.querySelector('tbody');

                // Get current sort direction
                const currentDir = th.getAttribute('data-sort-dir') || 'none';
                const nextDir = currentDir === 'asc' ? 'desc' : 'asc';

                // Group rows by room
                const roomGroups = {};
                Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                    const isHeader = row.cells[0].textContent.trim() === '';
                    if (isHeader) {
                        // Extract room number from the fourth cell (index 3)
                        const roomNo = row.cells[3].textContent.trim();
                        roomGroups[roomNo] = {
                            header: row,
                            beds: []
                        };
                    } else {
                        // Extract room number from the bed number (remove last part after last hyphen)
                        const bedNo = row.cells[3].textContent.trim();
                        const roomNo = bedNo.substring(0, bedNo.lastIndexOf('-'));
                        if (!roomGroups[roomNo]) {
                            roomGroups[roomNo] = {
                                header: null,
                                beds: []
                            };
                        }
                        roomGroups[roomNo].beds.push(row);
                    }
                });

                // Sort room groups by the first BedID in each group
                const sortedRoomGroups = Object.entries(roomGroups).sort(([_, groupA], [__, groupB]) => {
                    const firstBedA = groupA.beds[0]?.cells[4]?.textContent.trim() || '';
                    const firstBedB = groupB.beds[0]?.cells[4]?.textContent.trim() || '';
                    
                    // Extract numeric parts (assuming format B####)
                    const numA = parseInt(firstBedA.replace('B', '')) || 0;
                    const numB = parseInt(firstBedB.replace('B', '')) || 0;
                    
                    return numA - numB;
                });

                // Clear tbody
                tbody.innerHTML = '';

                // Reinsert rows in sorted order, maintaining room groups
                sortedRoomGroups.forEach(([roomNo, group]) => {
                    if (group.header) {
                        tbody.appendChild(group.header);
                    }
                    // Sort beds within each group by BedID
                    group.beds.sort((a, b) => {
                        const aValue = a.cells[4].textContent.trim(); // BedID column
                        const bValue = b.cells[4].textContent.trim();
                        
                        // Extract numeric parts (assuming format B####)
                        const numA = parseInt(aValue.replace('B', '')) || 0;
                        const numB = parseInt(bValue.replace('B', '')) || 0;
                        
                        return numA - numB;
                    });
                    group.beds.forEach(row => {
                        tbody.appendChild(row);
                    });
                });

                // Update sort direction indicator
                const headers = th.closest('tr').querySelectorAll('th');
                headers.forEach(header => {
                    header.setAttribute('data-sort-dir', 'none');
                    header.textContent = header.textContent.replace(' ↑', '').replace(' ↓', '');
                });

                th.setAttribute('data-sort-dir', nextDir);
                th.textContent = th.textContent.replace(' ↑', '').replace(' ↓', '') +
                    (nextDir === 'asc' ? ' ↑' : ' ↓');

                calculateMonthlyTotals();
            }


            // Trigger initial sort on BedID column
            const bedIdHeader = document.querySelector('#propertyTable thead th:nth-child(5)');
            bedIdHeader.setAttribute('data-sort-dir', 'none');
            bedIdHeader.click();

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>

</html>