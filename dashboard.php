<?php
require_once __DIR__ . '/module-auth/google_sheets_integration.php';
require_once __DIR__ . '/module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: ../index.php");
    exit();
}

$spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';

$rangeSheet1 = 'Property List!A2:F';
$dataSheet1 = getData($spreadsheetId, $rangeSheet1);

$rangeSheet2 = 'Room List!A2:K';
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

// Fetch data for the first card
$rangeSheet2Card1 = 'Room List!A2:K';
$dataSheet2Card1 = getData($spreadsheetId, $rangeSheet2Card1);
$numRowsCard1 = count($dataSheet2Card1);

// Fetch data for the second card
$rangeSheet2Card2 = 'Room List!A2:K';
$dataSheet2Card2 = getData($spreadsheetId, $rangeSheet2Card2);
$numRowsCard2 = count($dataSheet2Card2);

// Fetch data for the third card
$rangeSheet2Card3 = 'Room List!A2:K';
$dataSheet2Card3 = getData($spreadsheetId, $rangeSheet2Card3);
$numRowsCard3 = count($dataSheet2Card3);

// Fetch data for the fourth card
$rangeSheet2Card4 = 'Room List!A2:K';
$dataSheet2Card4 = getData($spreadsheetId, $rangeSheet2Card4);
$numRowsCard4 = count($dataSheet2Card4);

// Fetch data for the fifth card
$rangeSheet2Card5 = 'Room List!A2:K';
$dataSheet2Card5 = getData($spreadsheetId, $rangeSheet2Card5);
$numRowsCard5 = count($dataSheet2Card5);

// Fetch agent name from the database
$user = $_SESSION['auser'];
$stmt = $conn->prepare("SELECT AgentName FROM agent WHERE AgentEmail = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($agentName);
$stmt->fetch();
$stmt->close();

// Calculate total number of properties
$totalProperties = count($dataSheet2Card1);

// Calculate total number of apartments
$totalApartments = count(array_filter($dataSheet2Card2, function ($row) {
    return isset($row[7]) && $row[7] == 'Condominium'; // Ensure column index 7 exists and contains Property Type
}));

// Calculate total number of houses
$totalHouses = count(array_filter($dataSheet2Card3, function ($row) {
    return isset($row[7]) && $row[7] == 'Terrace'; // Ensure column index 7 exists and contains Property Type
}));

// Calculate total number of vacant properties (sale)
$totalVacantProperties = count(array_filter($dataSheet2Card4, function ($row) {
    return isset($row[6]) && $row[6] == 'TRUE'; // Ensure column index 6 exists and contains Vacant Status
}));

// Calculate total number of occupied properties (rent)
$totalOccupiedProperties = count(array_filter($dataSheet2Card5, function ($row) {
    return isset($row[6]) && $row[6] == 'FALSE'; // Ensure column index 6 exists and contains Vacant Status
}));
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

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/navbar.css" rel="stylesheet">

</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!--Navbar Start-->
        <!-- Navbar and Sidebar Start-->
        <?php include('nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">

            <div class="content container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h3 class="page-title">Welcome, <?php echo $agentName; ?></h3>
                            <p></p>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="dash-widget-header">
                                    <span class="dash-widget-icon bg-info">
                                        <i class="fe fe-home"></i>
                                    </span>

                                </div>
                                <div class="dash-widget-info">

                                    <h3>
                                        <?php echo $totalProperties; ?>
                                    </h3>

                                    <h6 class="text-muted">Properties</h6>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info w-50"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="dash-widget-header">
                                    <span class="dash-widget-icon bg-warning">
                                        <i class="fe fe-table"></i>
                                    </span>

                                </div>
                                <div class="dash-widget-info">

                                    <h3>
                                        <?php echo $totalApartments; ?>
                                    </h3>

                                    <h6 class="text-muted">No. of Condominium</h6>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info w-50"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="dash-widget-header">
                                    <span class="dash-widget-icon bg-info">
                                        <i class="fe fe-home"></i>
                                    </span>

                                </div>
                                <div class="dash-widget-info">

                                    <h3>
                                        <?php echo $totalHouses; ?>
                                    </h3>

                                    <h6 class="text-muted">No. of Terrace</h6>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info w-50"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="dash-widget-header">
                                    <span class="dash-widget-icon bg-success">
                                        <i class="fe fe-quote-left"></i>
                                    </span>

                                </div>
                                <div class="dash-widget-info">

                                    <h3>
                                        <?php echo $totalVacantProperties; ?>
                                    </h3>

                                    <h6 class="text-muted">Vacant</h6>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info w-50"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="dash-widget-header">
                                    <span class="dash-widget-icon bg-info">
                                        <i class="fe fe-quote-right"></i>
                                    </span>

                                </div>
                                <div class="dash-widget-info">

                                    <h3>
                                        <?php echo $totalOccupiedProperties; ?>
                                    </h3>

                                    <h6 class="text-muted">Occupied</h6>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-info w-50"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>
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

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>