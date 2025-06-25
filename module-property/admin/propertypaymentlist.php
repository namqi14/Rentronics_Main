<?php
// Start the session
session_start();

// Require the Google Sheets integration file
require_once __DIR__ . '/../../module-auth/google_sheets_integration.php';

// Google Sheets parameters
$spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';
$rangeSheet2 = 'Payment List!A2:Q'; // Range to fetch Room details from the "Room List" sheet

// Fetch data from Google Sheets
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

if (!$dataSheet2) {
    die("Error: Failed to retrieve data from Google Sheets");
}

// Function to convert date from mm/dd/yyyy to dd/mm/yyyy
function convertDate($dateStr) {
    $date = DateTime::createFromFormat('m/d/Y', $dateStr);
    return $date ? $date->format('d/m/Y') : $dateStr;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Rentronics</title>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <!-- Main CSS -->
    <link href="../../css/style.css" rel="stylesheet">
    <link href="../../css/dashboard.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">
    <style>
    .nav-bar {
        position: sticky;
        top: 0;
        z-index: 1000; /* Ensures it stays on top of other elements */
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
                            <h3 class="page-title">Payment List</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Payment List</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <!-- Room List -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Room List</h4>
                            </div>
                            <div class="card-body">
                                <div class="filter-toggle-container">
                                    <button class="btn btn-primary filter-button" id="filterToggle">Search by Filtering</button>
                                </div>
                                <div id="filterOptions">
                                    <div class="filter-group">
                                        <span>Month:</span>
                                        <div class="filter-button-group">
                                            <select id="month-select" class="form-control">
                                                <option value="">All</option>
                                                <option value="01">January</option>
                                                <option value="02">February</option>
                                                <option value="03">March</option>
                                                <option value="04">April</option>
                                                <option value="05">May</option>
                                                <option value="06">June</option>
                                                <option value="07">July</option>
                                                <option value="08">August</option>
                                                <option value="09">September</option>
                                                <option value="10">October</option>
                                                <option value="11">November</option>
                                                <option value="12">December</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="filter-group">
                                        <span>Agent Closed:</span>
                                        <div class="filter-button-group">
                                            <button class='filter-button' data-filter='agent' data-value=''>All</button>
                                            <?php
                                            $agents = array_unique(array_column($dataSheet2, 6));
                                            foreach ($agents as $agent) {
                                                echo "<button class='filter-button' data-filter='agent' data-value='$agent'>$agent</button>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <table id="roomListTable" class="display nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Room No</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Receipt Number</th>
                                            <th>Receipt Url</th>
                                            <th>Agent Closed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dataSheet2 as $row): ?>
                                            <?php
                                            // Convert date from mm/dd/yyyy to dd/mm/yyyy
                                            $convertedDate = convertDate($row[0]);
                                            ?>
                                            <tr>
                                                <td><?php echo $row[4]; ?></td>
                                                <td><?php echo $convertedDate; ?></td>
                                                <td><?php echo $row[5]; ?></td>
                                                <td><?php echo $row[8]; ?></td>
                                                <td><?php if (!empty($row[9])): ?>
                                                    <a href="<?php echo $row[9]; ?>" target="_blank">View Receipt</a>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $row[6]; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Room List -->

            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>

    <!-- Bootstrap Core JS -->
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js"
        integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>

    <!-- Custom JS -->
    <script>
    $(document).ready(function() {
        // Custom sorting plugin for date column
        jQuery.extend(jQuery.fn.dataTableExt.oSort, {
            "date-custom-pre": function(a) {
                var dateParts = a.split('/');
                return new Date(dateParts[2], dateParts[1] - 1, dateParts[0]).getTime();
            },
            "date-custom-asc": function(a, b) {
                return a - b;
            },
            "date-custom-desc": function(a, b) {
                return b - a;
            }
        });

        var table = $('#roomListTable').DataTable({
            responsive: true,
            columnDefs: [
                { type: 'date-custom', targets: 1 } // Apply custom sorting to the Date column
            ]
        });

        // Custom filtering function for month and agent closed
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var month = $('#month-select').val();
                var dateStr = data[1]; // Assuming the date is in the second column
                var agent = $('.filter-button.active').data('value');
                var agentData = data[5]; // Assuming the agent closed is in the sixth column

                // Parse the date string into a date object
                var dateParts = dateStr.split('/');
                var date = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);

                // Get the month from the date
                var dataMonth = ('0' + (date.getMonth() + 1)).slice(-2);

                if (
                    (month === "" || dataMonth === month) &&
                    (agent === undefined || agent === "" || agent === agentData)
                ) {
                    return true;
                }
                return false;
            }
        );

        // Event listener to the month and agent closed filtering inputs
        $('#month-select').change(function() {
            table.draw();
        });

        $('#filterToggle').click(function() {
            $('#filterOptions').toggle();
        });

        $('.filter-button').click(function() {
            $(this).toggleClass('active').siblings().removeClass('active');
            table.draw();
        });
    });
    </script>
    <script src="../../js/main.js"></script>
</body>
</html>
