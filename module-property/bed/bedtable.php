<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: /../index.php");
    exit();
}

$access_level = $_SESSION['access_level'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';

// Clear the messages after displaying them
unset($_SESSION['error']);
unset($_SESSION['msg']);

// Fetch bed data
$beds = [];
$result = $conn->query("
    SELECT b.*, p.PropertyName, r.RoomNo, t.TenantName, t.TenantID,
           t.RentStartDate, t.RentExpiryDate, a.AgentName
    FROM bed b
    LEFT JOIN room r ON b.RoomID = r.RoomID
    LEFT JOIN unit u ON r.UnitID = u.UnitID
    LEFT JOIN property p ON u.PropertyID = p.PropertyID
    LEFT JOIN tenant t ON b.BedID = t.BedID
    LEFT JOIN agent a ON b.AgentID = a.AgentID
    ORDER BY b.BedID
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $beds[] = $row;
    }
}

// Add these queries after the existing bed query
$properties_query = "SELECT DISTINCT PropertyName FROM property ORDER BY PropertyName";
$properties_result = $conn->query($properties_query);
$properties = [];
while ($row = $properties_result->fetch_assoc()) {
    $properties[] = $row['PropertyName'];
}

$units_query = "SELECT DISTINCT u.UnitNo FROM unit u ORDER BY u.UnitNo";
$units_result = $conn->query($units_query);
$units = [];
while ($row = $units_result->fetch_assoc()) {
    $units[] = $row['UnitNo'];
}

$bed_statuses = ['Available', 'Booked', 'Rented'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentronics</title>
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
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
    }

    .shown .details {
        display: table-row;
    }

    .actions-column {
        text-align: center;
    }

    .btn-action {
        margin-right: 5px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination button {
        margin: 0 5px;
        padding: 5px 10px;
        border: 1px solid #ddd;
        color: #666565;
        background-color: #f8f9fa;
        cursor: pointer;
    }

    .pagination button.active {
        background-color: #007bff;
        color: white;
    }

    .filter-input {
        margin-bottom: 20px;
        width: 100%;
        padding: 5px;
        border: 1px solid #ddd;
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

    .main-row td {
        align-content: center;
        color: white;
        font-weight: 300;
        padding: 4px;
        text-align: center;
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

    .details ul li {
        margin: 5px 0;
    }
    .details ul li a {
        color: #6ba1d7;
        text-decoration: none;
        margin-left: 5px;
    }
    .details ul li a:hover {
        text-decoration: underline;
    }

    .document-list {
        list-style-type: none;
        padding-left: 0;
    }

    .details-container {
        padding: 20px;
        background: #ffffff;
        border-radius: 8px;
        margin: 10px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .detail-item {
        display: flex;
        align-items: baseline;
    }

    .detail-label {
        color: #666666;
        min-width: 120px;
        font-weight: 500;
    }

    .detail-value {
        color: #666666;
        flex: 1;
    }

    .documents-section {
        border-top: 1px solid #e0e0e0;
        padding-top: 15px;
    }

    .document-list {
        list-style: none;
        padding: 0;
        margin: 10px 0;
    }

    .document-list li {
        display: flex;
        align-items: center;
        margin: 8px 0;
        padding: 8px;
        background: #ffffff;
        border-radius: 6px;
        transition: background-color 0.2s;
    }

    .document-list li:hover {
        background: #f0f0f0;
    }

    .document-list i {
        color: #87CEEB;
        margin-right: 10px;
    }

    .document-list a {
        color: #87CEEB;
        text-decoration: none;
        flex: 1;
    }

    .document-info {
        color: #666666;
        font-size: 0.9em;
        margin-left: 10px;
    }

    .form-select.filter-input {
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        width: 100%;
        color: #666565;
    }

    .form-select.filter-input:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start -->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Bed Management</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Bed</li>
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
                    <!-- Bed Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Bed Management</h4>
                                <a href="bedadd.php" class="btn btn-primary" style="background-color:#005f73 !important;">Add New Bed</a>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <input type="text" id="filterInput" class="filter-input" placeholder="Search...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="propertyFilter" class="form-select filter-input">
                                            <option value="">All Properties</option>
                                            <?php foreach ($properties as $property): ?>
                                                <option value="<?= htmlspecialchars($property) ?>"><?= htmlspecialchars($property) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="unitFilter" class="form-select filter-input">
                                            <option value="">All Units</option>
                                            <?php foreach ($units as $unit): ?>
                                                <option value="<?= htmlspecialchars($unit) ?>"><?= htmlspecialchars($unit) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select id="statusFilter" class="form-select filter-input">
                                            <option value="">All Statuses</option>
                                            <?php foreach ($bed_statuses as $status): ?>
                                                <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table id="bedTable" class="table table-striped">
                                        <thead class="thead">
                                            <tr>
                                                <th>Bed ID</th>
                                                <th>Property</th>
                                                <th>Bed No</th>
                                                <th>Status</th>
                                                <th>Agent</th>
                                                <th class="actions-column">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($beds)): ?>
                                                <?php foreach ($beds as $row): ?>
                                                    <?php
                                                    $rental_period = '';
                                                    if (!empty($row['RentStartDate']) && !empty($row['RentExpiryDate'])) {
                                                        $rental_period = date('d-m-Y', strtotime($row['RentStartDate'])) . ' to ' . 
                                                       date('d-m-Y', strtotime($row['RentExpiryDate']));
                                                    }
                                                    
                                                    $status_class = '';
                                                    switch($row['BedStatus']) {
                                                        case 'Available':
                                                            $status_class = 'row-available';
                                                            break;
                                                        case 'Booked':
                                                            $status_class = 'row-booked';
                                                            break;
                                                        case 'Rented':
                                                            $status_class = 'row-rented';
                                                            break;
                                                    }
                                                    ?>
                                                    <tr class="main-row <?= $status_class ?>">
                                                        <td>
                                                            <button class="btn btn-link toggle-details" title="Show Details">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                            <?= $row['BedID'] ?>
                                                        </td>
                                                        <td><?= $row['PropertyName'] ?? 'Not Assigned' ?></td>
                                                        <td><?= $row['BedNo'] ?></td>
                                                        <td><?= $row['BedStatus'] ?></td>
                                                        <td><?= $row['AgentName'] ?? 'Not Assigned' ?></td>
                                                        <td class="actions-column">
                                                            <a href="bededit.php?BedID=<?= $row['BedID'] ?>" class="btn btn-warning btn-sm btn-action" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($access_level >= 2): ?>
                                                            <a href="beddelete.php?delete_bed_id=<?= $row['BedID'] ?>" 
                                                               class="btn btn-danger btn-sm btn-action" 
                                                               title="Delete" 
                                                               onclick="return confirm('Are you sure you want to delete this bed?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <tr class="details">
                                                        <td colspan="8">
                                                            <div class="details-container">
                                                                <div class="details-grid">
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Room ID:</span>
                                                                        <span class="detail-value"><?= $row['RoomID'] ?? 'Not Assigned' ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Tenant ID:</span>
                                                                        <span class="detail-value"><?= $row['TenantID'] ?? 'No Tenant' ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Created At:</span>
                                                                        <span class="detail-value"><?= date('d-m-Y H:i', strtotime($row['CreatedAt'])) ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Last Updated:</span>
                                                                        <span class="detail-value"><?= date('d-m-Y H:i', strtotime($row['UpdatedAt'])) ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="8">No beds found</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="pagination" id="pagination"></div>
                            </div>
                        </div>
                    </div>
                    <!-- /Bed Table -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        const rowsPerPage = 15;
        const rows = $('#bedTable tbody tr.main-row');
        const pagination = $('#pagination');

        function showPage(page, rowsToShow) {
            // Hide all rows initially
            rows.hide();
            // Show only the rows for the current page
            rowsToShow.hide();
            rowsToShow.slice((page - 1) * rowsPerPage, page * rowsPerPage).show();

            // Highlight the active page button
            pagination.find('button').removeClass('active');
            pagination.find(`button[data-page="${page}"]`).addClass('active');
        }

        function setupPagination(filteredRows) {
            const pageCount = Math.ceil(filteredRows.length / rowsPerPage);
            pagination.empty(); // Clear existing buttons

            // Generate new pagination buttons
            for (let i = 1; i <= pageCount; i++) {
                pagination.append(`<button data-page="${i}">${i}</button>`);
            }

            // Add active class to the first button by default
            if (pageCount > 0) {
                pagination.find('button').first().addClass('active');
            }

            // Bind click event for pagination buttons
            pagination.off('click').on('click', 'button', function() {
                const page = $(this).data('page');
                showPage(page, filteredRows);
            });
        }

        function filterTable() {
            const searchValue = $('#filterInput').val().toLowerCase();
            const propertyValue = $('#propertyFilter').val().toLowerCase();
            const unitValue = $('#unitFilter').val().toLowerCase();
            const statusValue = $('#statusFilter').val().toLowerCase();

            rows.each(function() {
                const $row = $(this);
                const rowText = $row.text().toLowerCase();
                const propertyText = $row.find('td:eq(1)').text().toLowerCase();
                const statusText = $row.find('td:eq(3)').text().toLowerCase();
                const unitText = $row.find('td:eq(2)').text().toLowerCase();

                const matchesSearch = rowText.includes(searchValue);
                const matchesProperty = !propertyValue || propertyText.includes(propertyValue);
                const matchesUnit = !unitValue || unitText.includes(unitValue);
                const matchesStatus = !statusValue || statusText.includes(statusValue);

                if (matchesSearch && matchesProperty && matchesUnit && matchesStatus) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });

            // Update pagination after filtering
            const visibleRows = rows.filter(':visible');
            setupPagination(visibleRows);
            showPage(1, visibleRows);
        }

        // Update the event listeners
        $('#filterInput, #propertyFilter, #unitFilter, #statusFilter').on('input change', filterTable);

        // Initial setup: Show the first page
        showPage(1, rows);
        setupPagination(rows);

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