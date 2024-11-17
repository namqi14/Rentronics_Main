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

        /* Ensure the scroll head and body have the same width */
        .dataTables_scrollHeadInner,
        .dataTables_scrollBody {
            width: 100% !important;
        }

        /* Custom styles for the DataTable */
        .dataTables_wrapper .dataTables_scrollHead {
            background-color: #f8f9fa;
        }

        .dataTables_wrapper .dataTables_scrollHead th {
            color: #343a40;
            font-weight: bold;
        }

        .dataTables_wrapper .dataTables_scrollBody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .dataTables_wrapper .dataTables_scrollBody tr:hover {
            background-color: #d6d8db;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 5px;
            margin-left: 0.5em;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 5px 10px;
            margin: 2px;
            background-color: #fff;
            color: #007bff;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e2e6ea;
            color: #0056b3;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 5px;
        }

        /* Hidden details row styling */
        .hidden {
            display: none;
        }

        .details {
            display: grid;
            gap: 10px; /* Space between rows */
            padding: 10px;
            border: 1px solid #ccc;
            margin: 10px 0;
        }

        .details-row {
            display: flex;
            align-items: center; /* Center items vertically */
        }

        .label {
            flex: 1; /* Allows labels to take appropriate space */
            padding-right: 10px; /* Space between the label and the line */
        }

        .value {
            flex: 2; /* Allows values to take more space */
            border-left: 1px solid #ccc; /* Vertical line separating label and value */
            padding-left: 10px; /* Space between the line and the value */
        }

        .details p {
            margin: 0; /* Remove default margin */
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
                            <h3 class="page-title">Unit Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Unit</li>
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
                    <!-- Unit Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Unit Table</h4>
                                <a href="unitadd.php" class="btn btn-primary">Add</a>
                            </div>
                            <div class="card-body">
                                <table id="unitTable" class="table table-striped table-responsive">
                                    <thead>
                                        <tr>
                                            <th>Unit ID</th>
                                            <th>Property ID</th>
                                            <th>Unit No</th>
                                            <th>FloorPlan</th>
                                            <th>Investor</th>
                                            <th>Actions</th> <!-- New column for actions -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("SELECT * FROM unit");

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $unit_id = $row['UnitID'];
                                                $property_id = $row['PropertyID'];
                                                $unit_no = $row['UnitNo'];
                                                $floor_plan = $row['FloorPlan'];
                                                $investor = $row['Investor'];

                                                echo "
                                                <tr class='toggle' onclick='toggleDetails(\"details_$unit_id\")'>
                                                    <td> 
                                                        <button class='btn btn-link' onclick='event.stopPropagation(); toggleDetails(\"details_$unit_id\");'>
                                                            <i class='fas fa-chevron-down'></i>
                                                        </button>
                                                        $unit_id
                                                    </td>
                                                    <td>$property_id</td>
                                                    <td>$unit_no</td>
                                                    <td>$floor_plan</td>
                                                    <td>$investor</td>
                                                    <td>
                                                        <a href='unitedit.php?UnitID=$unit_id' class='btn btn-warning btn-sm'>Edit</a>
                                                    </td>
                                                </tr>

                                                <tr id='details_$unit_id' class='hidden'>
                                                    <td colspan='6'>
                                                        <div class='details'>
                                                            <div class='details-row'>
                                                                <p class='label'><strong>Property ID:</strong></p>
                                                                <p class='value'>$property_id</p>
                                                            </div>
                                                            <div class='details-row'>
                                                                <p class='label'><strong>Unit No:</strong></p>
                                                                <p class='value'>$unit_no</p>
                                                            </div>
                                                            <div class='details-row'>
                                                                <p class='label'><strong>Floor Plan:</strong></p>
                                                                <p class='value'>$floor_plan</p>
                                                            </div>
                                                            <div class='details-row'>
                                                                <p class='label'><strong>Investor:</strong></p>
                                                                <p class='value'>$investor</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                ";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6'>No units found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /Unit Table -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for messages -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($msg)) : ?>
                        <div class='alert alert-success'><?php echo $msg; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)) : ?>
                        <div class='alert alert-danger'><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
            // Initialize DataTable
            var table = $('#unitTable').DataTable({
                language: {
                    search: "Filter records:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                scrollX: true,
                responsive: true,
                order: [
                    [0, 'asc']
                ],
                autoWidth: false,
                initComplete: function() {
                    this.api().columns.adjust();
                }
            });

            // Adjust column widths when the window is resized
            $(window).on('resize', function() {
                table.columns.adjust();
            });
        });

        // Toggle unit details
        function toggleDetails(id) {
            var element = document.getElementById(id);
            if (element.classList.contains('hidden')) {
                element.classList.remove('hidden');
            } else {
                element.classList.add('hidden');
            }
        }
    </script>
    <script src="../../js/main.js"></script>

</body>
</html>
