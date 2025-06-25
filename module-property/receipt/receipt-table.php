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
        background-color: #005f73;
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

    .row-available,
    .row-booked,
    .row-rented {
        background-color: #005f73;
        color: white;
    }

    .btn-secondary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
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
                            <h3 class="page-title">Receipt Table</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Receipt</li>
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
                                <h4 class="card-title">Payment Table</h4>
                            </div>
                            <div class="card-body">
                                <input type="text" id="filterInput" class="filter-input" placeholder="Filter records...">
                                <div class="table-responsive">
                                    <table id="bedTable" class="table table-striped">
                                        <thead class="thead">
                                            <tr>
                                                <th>File Name</th>
                                                <th>Receipt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $receiptDir = "../../uploads/receipts/";
                                            $files = glob($receiptDir . "*");
                                            
                                            if (!empty($files)) {
                                                foreach ($files as $file) {
                                                    $filename = basename($file);
                                                    echo "
                                                    <tr class='main-row'>
                                                        <td>{$filename}</td>
                                                        <td>
                                                            <a href='{$file}' class='btn btn-primary' target='_blank'>
                                                                <i class='fas fa-receipt'></i> View Receipt
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    ";
                                                }
                                            } else {
                                                echo "<tr><td colspan='2'>No receipts found</td></tr>";
                                            }
                                            ?>
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
        // Pagination setup
        const rowsPerPage = 15;
        const rows = $('#bedTable tbody tr.main-row'); // Only target main rows
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

            // Update the rows for pagination
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