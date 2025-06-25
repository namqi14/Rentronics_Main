<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: /../index.php");
    exit();
}

$access_level = $_SESSION['access_level']; // Fetch the user's access level

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';  // Fetch the error message from the session
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';  // Fetch the success message from the session

// Clear the messages after displaying them
unset($_SESSION['error']);
unset($_SESSION['msg']);

if (isset($_SESSION['success_msg'])) {
    echo "<div class='alert alert-success'>" . $_SESSION['success_msg'] . "</div>";
    unset($_SESSION['success_msg']);
}

function getTenantDocuments($tenantName) {
    $folderName = str_replace(' ', '_', strtoupper($tenantName));
    $tenant_folder = $_SERVER['DOCUMENT_ROOT'] . '/rentronics/tenant_documents/' . $folderName;
    
    if (file_exists($tenant_folder)) {
        $files = scandir($tenant_folder);
        $output = '<ul class="document-list">';
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = '/rentronics/tenant_documents/' . $folderName . '/' . $file;
                $full_file_path = $tenant_folder . '/' . $file;
                $file_size = filesize($full_file_path);
                $file_date = date('d-m-Y H:i', filemtime($full_file_path));
                $output .= "<li>
                            <i class='fas fa-file-alt'></i>
                            <a href='{$file_path}' target='_blank'>{$file}</a>
                            <span class='document-info'>
                                " . number_format($file_size / 1024, 2) . " KB - {$file_date}
                            </span>
                          </li>";
            }
        }
        $output .= '</ul>';
        return $output;
    }
    return '<p>No documents available</p>';
}

// Fetch tenant data before the table
$tenants = [];
$result = $conn->query("
    SELECT 
        t.*,
        b.BedNo,
        r.RoomNo,
        CASE 
            WHEN t.RentalType = 'Room' THEN CONCAT('Room ', r.RoomNo)
            WHEN t.RentalType = 'Bed' THEN CONCAT('Bed ', b.BedNo)
            ELSE 'Not Assigned'
        END as PropertyNo,
        a.AgentName 
    FROM tenant t
    LEFT JOIN bed b ON t.BedID = b.BedID
    LEFT JOIN room r ON t.RoomID = r.RoomID
    LEFT JOIN agent a ON t.AgentID = a.AgentID
    WHERE t.RentalType IS NOT NULL
    ORDER BY 
        t.RentalType,
        CASE 
            WHEN t.RentalType = 'Room' THEN r.RoomNo
            WHEN t.RentalType = 'Bed' THEN b.BedNo
        END
");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tenants[] = $row;
    }
}
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
                            <h3 class="page-title">Tenant Overview</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Tenant</li>
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
                    <!-- Tenant Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Tenant Table</h4>
                                <a href="existingtenant.php" class="btn btn-primary" style="background-color:#005f73 !important;">Add</a>
                            </div>
                            <div class="card-body">
                                <?php 
                                if (isset($_SESSION['success_msg'])) {
                                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                    echo $_SESSION['success_msg'];
                                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                                    echo '</div>';
                                    // Clear the message after displaying it
                                    unset($_SESSION['success_msg']);
                                }
                                ?>
                                
                                <div class="d-flex gap-2 mb-3">
                                    <input type="text" id="filterInput" class="filter-input" placeholder="Filter records...">
                                    <select id="agentFilter" class="form-select" style="width: auto;">
                                        <option value="">All Agents</option>
                                        <?php
                                        $agentQuery = $conn->query("SELECT DISTINCT AgentName FROM agent 
                                                           WHERE AgentName IS NOT NULL 
                                                           ORDER BY AgentName");
                                        while ($agent = $agentQuery->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($agent['AgentName']) . "'>" . htmlspecialchars($agent['AgentName']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <select id="bedFilter" class="form-select" style="width: auto;">
                                        <option value="">All Properties</option>
                                        <?php
                                        // Get unique unit numbers
                                        $unitQuery = $conn->query("SELECT DISTINCT 
                                                                  SUBSTRING_INDEX(b.BedNo, '-R', 1) as UnitNumber
                                                                  FROM bed b
                                                                  WHERE b.BedNo IS NOT NULL 
                                                                  ORDER BY UnitNumber");
                                        
                                        while ($unit = $unitQuery->fetch_assoc()) {
                                            $unitNumber = $unit['UnitNumber'];
                                            echo "<option value='UNIT:{$unitNumber}'>Unit {$unitNumber}</option>";
                                        }
                                        ?>
                                    </select>
                                    <select id="statusFilter" class="form-select" style="width: auto;">
                                        <option value="">All Status</option>
                                        <?php
                                        $statusQuery = $conn->query("SELECT DISTINCT TenantStatus FROM tenant 
                                                           WHERE TenantStatus IS NOT NULL 
                                                           ORDER BY TenantStatus");
                                        while ($status = $statusQuery->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($status['TenantStatus']) . "'>" . htmlspecialchars($status['TenantStatus']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="table-responsive">
                                    <table id="tenantTable" class="table table-striped">
                                        <thead class="thead">
                                            <tr>
                                                <th>Tenant ID</th>
                                                <th>Rental Type</th>
                                                <th>Property</th>
                                                <th>Tenant Name</th>
                                                <th>Rent Period</th>
                                                <th>Agent</th>
                                                <th>Status</th>
                                                <th class="actions-column">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($tenants)): ?>
                                                <?php foreach ($tenants as $row): ?>
                                                    <?php
                                                    $tenant_id = $row['TenantID'];
                                                    $rental_type = $row['RentalType'];
                                                    $property_name = $row['PropertyNo'];
                                                    $tenant_name = $row['TenantName'];
                                                    $rent_start = date('d-m-Y', strtotime($row['RentStartDate']));
                                                    $rent_expiry = date('d-m-Y', strtotime($row['RentExpiryDate']));
                                                    $agent_name = $row['AgentName'] ?? 'Not Assigned';
                                                    $status = $row['TenantStatus'] ?? 'Active';
                                                    ?>
                                                    <tr class="main-row">
                                                        <td>
                                                            <button class="btn btn-link toggle-details" title="Show Details">
                                                                <i class="fas fa-chevron-down"></i>
                                                            </button>
                                                            <?= $tenant_id ?>
                                                        </td>
                                                        <td><?= $rental_type ?></td>
                                                        <td><?= $property_name ?></td>
                                                        <td><?= $tenant_name ?></td>
                                                        <td><?= $rent_start ?> to <?= $rent_expiry ?></td>
                                                        <td><?= $agent_name ?></td>
                                                        <td><?= $status ?></td>
                                                        <td class="actions-column">
                                                            <a href="tenantedit.php?TenantID=<?= $tenant_id ?>" class="btn btn-warning btn-sm btn-action" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="tenantdelete.php?delete_tenant_id=<?= $tenant_id ?>" class="btn btn-danger btn-sm btn-action" title="Delete" onclick="return confirm('Are you sure you want to delete this tenant?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <tr class="details">
                                                        <td colspan="8">
                                                            <div class="details-container">
                                                                <div class="details-grid">
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Email:</span>
                                                                        <span class="detail-value"><?= $row['TenantEmail'] ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Bed ID:</span>
                                                                        <span class="detail-value"><?= $row['BedID'] ?? 'Not Assigned' ?></span>
                                                                    </div>
                                                                    <div class="detail-item">
                                                                        <span class="detail-label">Agent ID:</span>
                                                                        <span class="detail-value"><?= $row['AgentID'] ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="documents-section">
                                                                    <span class="detail-label">Documents:</span>
                                                                    <?php 
                                                                    $documents = getTenantDocuments($row['TenantName']);
                                                                    if (strpos($documents, 'No documents') === false) {
                                                                        echo $documents;
                                                                    } else {
                                                                        echo '<div class="detail-value">No documents available</div>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="8">No tenants found</td></tr>
                                            <?php endif; ?>
                                        </tbody>

                                    </table>
                                </div>
                                <div class="pagination" id="pagination"></div>
                            </div>
                        </div>
                    </div>
                    <!-- /Tenant Table -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Pagination setup
        const rowsPerPage = 15;
        const rows = $('#tenantTable tbody tr.main-row'); // Only target main rows
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

        // Initial setup: Show the first page
        showPage(1, rows);
        setupPagination(rows);

        // Combined filter function
        function filterTable() {
            const searchText = $('#filterInput').val().toLowerCase();
            const selectedAgent = $('#agentFilter').val();
            const selectedBed = $('#bedFilter').val();
            const selectedStatus = $('#statusFilter').val();

            const rows = $('#tenantTable tbody tr.main-row');
            
            rows.each(function() {
                const $row = $(this);
                const rowText = $row.text().toLowerCase();
                const agent = $row.find('td:eq(5)').text().trim(); // Changed from 4 to 5 (Agent column)
                const rentalType = $row.find('td:eq(1)').text().trim(); // Rental Type column
                const property = $row.find('td:eq(2)').text().trim(); // Property column
                const status = $row.find('td:eq(6)').text().trim(); // Changed from 5 to 6 (Status column)

                const matchesSearch = rowText.includes(searchText);
                const matchesAgent = !selectedAgent || agent === selectedAgent;
                const matchesStatus = !selectedStatus || status === selectedStatus;
                
                // Simplified bed filtering logic - only unit level
                let matchesBed = true;
                if (selectedBed) {
                    const unitNumber = selectedBed.replace('UNIT:', '');
                    matchesBed = property.includes(unitNumber);
                }

                if (matchesSearch && matchesAgent && matchesBed && matchesStatus) {
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

        // Bind filter function to all inputs
        $('#filterInput').on('input', filterTable);
        $('#agentFilter, #bedFilter, #statusFilter').on('change', filterTable);

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

        // Sorting functionality
        $('#sortSelect').on('change', function() {
            const sortBy = $(this).val();
            if (sortBy === 'none') return;

            const rows = $('#tenantTable tbody tr.main-row').get();
            
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortBy) {
                    case 'tenant_id':
                        aValue = $(a).find('td:eq(0)').text().trim();
                        bValue = $(b).find('td:eq(0)').text().trim();
                        break;
                    case 'bed_name':
                        aValue = $(a).find('td:eq(1)').text().trim();
                        bValue = $(b).find('td:eq(1)').text().trim();
                        break;
                    case 'tenant_name':
                        aValue = $(a).find('td:eq(2)').text().trim();
                        bValue = $(b).find('td:eq(2)').text().trim();
                        break;
                    case 'rent_period':
                        aValue = $(a).find('td:eq(3)').text().trim();
                        bValue = $(b).find('td:eq(3)').text().trim();
                        break;
                    case 'agent':
                        aValue = $(a).find('td:eq(4)').text().trim();
                        bValue = $(b).find('td:eq(4)').text().trim();
                        break;
                    case 'status':
                        aValue = $(a).find('td:eq(5)').text().trim();
                        bValue = $(b).find('td:eq(5)').text().trim();
                        break;
                }
                
                return aValue.localeCompare(bValue);
            });

            $('#tenantTable tbody').append(rows);
            
            // Update pagination after sorting
            const visibleRows = $(rows).filter(':visible');
            setupPagination(visibleRows);
            showPage(1, visibleRows);
        });
    });
    </script>
    <script src="../../js/main.js"></script>
</body>

</html>