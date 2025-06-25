<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$access_level = $_SESSION['access_level']; // Fetch the user's access level

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';  // Fetch the error message from the session
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';  // Fetch the success message from the session

// Clear the messages after displaying them
unset($_SESSION['error']);
unset($_SESSION['msg']);

// Fetch Unit IDs and UnitNos from the Unit table
$unitOptions = '';
$result = $conn->query("SELECT UnitID, UnitNo FROM Unit");
$unitNos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitOptions .= "<option value='" . $row['UnitID'] . "' data-unitno='" . $row['UnitNo'] . "'>" . $row['UnitID'] . " - " . $row['UnitNo'] . "</option>";
        $unitNos[$row['UnitID']] = $row['UnitNo'];
    }
}

// Fetch Agent IDs from the Agent table
$agentOptions = '';
$result = $conn->query("SELECT AgentID, AgentName FROM Agent");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agentOptions .= "<option value='" . $row['AgentID'] . "'>" . $row['AgentID'] . " - " . $row['AgentName'] . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/rentronics/assets/css/feathericon.min.css">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">

    <style>
    .nav-bar {
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .details {
        display: none;
        background-color: #066889;
    }

    .details-row {
        display: flex;
        align-items: center;
    }

    .label {
        flex: 1;
        padding-right: 10px;
    }

    .value {
        flex: 2;
        border-left: 1px solid #ccc;
        padding-left: 10px;
    }

    .details p {
        margin: 0;
    }

    .row-available {
        background-color: #005f73;
        color: black;
    }

    .row-booked {
        background-color: #7289ab;
        color: black;
    }

    .row-rented {
        background-color: #3d405b;
        color: white;
    }

    .main-row {
        background-color: #066889;
    }

    .main-row td {
        align-content: center;
        color: white;
        font-weight: 300;
        padding: 8px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .table {
        background-color: #066889; 
        border-radius: 8px;
        margin-bottom: 0;
    }

    .btn {
        background-color: transparent !important;
        border: transparent;
    }

    .thead {
        font-size: 16px;
        text-align: center;
        color: white;
    }

    .details-container {
        padding: 20px;
        background: #ffffff;
        border-radius: 8px;
        margin: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
    }

    .detail-label {
        color: #666666;
        font-weight: 500;
        margin-right: 15px;
    }

    .detail-value {
        color: #666666;
    }

    .toggle-details {
        padding: 0;
        color: white;
    }

    .toggle-details i {
        transition: transform 0.2s;
    }

    .toggle-details.active i {
        transform: rotate(180deg);
    }

    .table > :not(caption) > * > * {
        border-bottom-width: 0;
    }

    .actions-column {
        white-space: nowrap;
    }

    .btn-action {
        padding: 4px 8px;
        margin: 0 2px;
    }

    .btn-action i {
        color: white;
    }

    /* Update pagination styles */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;  /* Adds space between buttons */
    }

    .pagination button {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #066889;
        padding: 8px 12px;
        cursor: pointer;
        border-radius: 4px;
        min-width: 40px;
        transition: all 0.3s ease;
    }

    .pagination button:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0056b3;
    }

    .pagination button.active {
        background-color: #066889;
        border-color: #066889;
        color: white;
    }

    /* Remove any margin/padding from the container */
    .card-body {
        padding-bottom: 30px;
    }

    .table-responsive {
        margin-bottom: 0;
    }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Room Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Room</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <?php if (!empty($msg)) : ?>
                <div class="alert alert-success"><?php echo $msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Room Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Room Table</h4>
                                <a href="roomaddinnerjoin.php" class="btn btn-primary">Add</a>
                            </div>
                            <div class="card-body">
                                <input type="text" id="filterInput" class="filter-input" placeholder="Filter records...">
                                <div class="table-responsive">
                                    <table id="roomTable" class="table table-striped">
                                        <thead class="thead">
                                            <tr>
                                                <th>Room ID</th>
                                                <th>Unit No</th>
                                                <th>Room No</th>
                                                <th>Status</th>
                                                <th class="actions-column">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $result = $conn->query("
                                                SELECT Room.RoomID, Room.UnitID, Unit.UnitNo, Property.PropertyName, Room.RoomNo, Room.RoomRentAmount, Room.Katil, Room.RoomStatus, Room.AgentID 
                                                FROM Room 
                                                INNER JOIN Unit ON Room.UnitID = Unit.UnitID 
                                                INNER JOIN Property ON Unit.PropertyID = Property.PropertyID
                                            ");
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $room_id = $row['RoomID'];
                                                    $unit_no = $row['UnitNo'];
                                                    $room_no = $row['RoomNo'];
                                                    $room_rent = $row['RoomRentAmount'];
                                                    $katil = $row['Katil'];
                                                    $room_status = $row['RoomStatus'];
                                                    $agent_id = $row['AgentID'];
                                                    ?>
                                                    <tr class="main-row">
                                                        <td>
                                                            <button class="btn btn-link toggle-details" title="Show Details">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                            <?= $room_id ?>
                                                        </td>
                                                        <td><?= $row['PropertyName'] . ' ' . $unit_no ?></td>
                                                        <td><?= $room_no ?></td>
                                                        <td><?= $room_status ?></td>
                                                        <td class="actions-column">
                                                            <a href="roomedit.php?room_id=<?= $room_id ?>" class="btn btn-warning btn-sm btn-action" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="roomdelete.php?delete_room_id=<?= $room_id ?>" class="btn btn-danger btn-sm btn-action" title="Delete" onclick="return confirm('Are you sure you want to delete this room?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr class="details">
                                                        <td colspan="5">
                                                            <div class="details-container">
                                                                <div class="details-grid">
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Rent Amount:</span>
                                                                        <span class="detail-value">RM <?= $room_rent ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Katil:</span>
                                                                        <span class="detail-value"><?= $katil ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Status:</span>
                                                                        <span class="detail-value"><?= $room_status ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Agent ID:</span>
                                                                        <span class="detail-value"><?= $agent_id ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="pagination" id="pagination"></div>
                            </div>
                        </div>
                    </div>
                    <!-- /Room Table -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap, and DataTables scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
    $(document).ready(function() {
        // Pagination setup
        const rowsPerPage = 15;
        const rows = $('#roomTable tbody tr.main-row'); // Only target main rows
        const pagination = $('#pagination');

        function showPage(page, rowsToShow) {
            rows.hide();
            rowsToShow.hide();
            rowsToShow.slice((page - 1) * rowsPerPage, page * rowsPerPage).show();
            pagination.find('button').removeClass('active');
            pagination.find(`button[data-page="${page}"]`).addClass('active');
        }

        function setupPagination(filteredRows) {
            const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
            pagination.empty();

            for (let i = 1; i <= pageCount; i++) {
                pagination.append(`<button data-page="${i}">${i}</button>`);
            }

            if (pageCount > 0) {
                pagination.find('button').first().addClass('active');
            }

            pagination.off('click').on('click', 'button', function() {
                const page = $(this).data('page');
                showPage(page, filteredRows);
            });
        }

        // Initial setup
        showPage(1, rows);
        setupPagination(rows);

        // Filter functionality
        $('#filterInput').on('input', function() {
            const filterValue = $(this).val().toLowerCase();
            rows.each(function() {
                const rowText = $(this).text().toLowerCase();
                if (rowText.indexOf(filterValue) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            const visibleRows = rows.filter(':visible');
            setupPagination(visibleRows);
            showPage(1, visibleRows);
        });

        // Toggle details
        $('.toggle-details').on('click', function() {
            const $icon = $(this).find('i');
            const $row = $(this).closest('tr').next('.details');

            if ($row.is(':visible')) {
                $row.hide();
                $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                $row.show();
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
    });
    </script>
    <script src="../../js/main.js"></script>

</body>

</html>