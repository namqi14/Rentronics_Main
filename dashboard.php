<?php
require_once 'google_sheets_integration.php';

$spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';

$rangeSheet1 = 'Sheet1!A2:F';
$dataSheet1 = getData($spreadsheetId, $rangeSheet1);

$rangeSheet2 = 'Sheet2!A2:K';
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

// Fetch data for the first card
$rangeSheet2Card1 = 'Sheet2!A2:K';
$dataSheet2Card1 = getData($spreadsheetId, $rangeSheet2Card1);
$numRowsCard1 = count($dataSheet2Card1);

// Fetch data for the second card
$rangeSheet2Card2 = 'Sheet2!A2:K'; // Change the range accordingly
$dataSheet2Card2 = getData($spreadsheetId, $rangeSheet2Card2);
$numRowsCard2 = count($dataSheet2Card2);

// Fetch data for the third card
$rangeSheet2Card3 = 'Sheet2!A2:K'; // Change the range accordingly
$dataSheet2Card3 = getData($spreadsheetId, $rangeSheet2Card3);
$numRowsCard3 = count($dataSheet2Card3);

// Fetch data for the fourth card
$rangeSheet2Card4 = 'Sheet2!A2:K'; // Change the range accordingly
$dataSheet2Card4 = getData($spreadsheetId, $rangeSheet2Card4);
$numRowsCard4 = count($dataSheet2Card4);

// Fetch data for the fifth card
$rangeSheet2Card5 = 'Sheet2!A2:K'; // Change the range accordingly
$dataSheet2Card5 = getData($spreadsheetId, $rangeSheet2Card5);
$numRowsCard5 = count($dataSheet2Card5);

// Calculate total number of properties
$totalProperties = count($dataSheet2Card1);

// Calculate total number of apartments
$totalApartments = count(array_filter($dataSheet2Card2, function ($row) {
    return $row[8] == 'Condominium'; // Assuming column index 8 contains Property Type
}));

// Calculate total number of houses
$totalHouses = count(array_filter($dataSheet2Card3, function ($row) {
    return $row[8] == 'Terrace'; // Assuming column index 8 contains Property Type
}));

// Calculate total number of vacant properties (sale)
$totalVacantProperties = count(array_filter($dataSheet2Card4, function ($row) {
    return $row[7] == 'Vacant'; // Assuming column index 6 contains Vacant Status, and column index 9 contains Sale/Rent
}));

// Calculate total number of occupied properties (rent)
$totalOccupiedProperties = count(array_filter($dataSheet2Card5, function ($row) {
    return $row[7] == 'Occupied'; // Assuming column index 6 contains Vacant Status, and column index 9 contains Sale/Rent
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
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid bg-white p-0" style="">
        <!--Navbar Start-->
        <div class="container-fluid nav-bar bg-transparent">
            <nav class="navbar navbar-expand-lg bg-white navbar-light py-0 px-4">
                <a href="index.php" class="navbar-brand d-flex align-items-center text-center">
                    <div class="icon p-2 me-2">
                        <!-- <img class="img-fluid" src="img/icon-deal.png" alt="Icon" style="width: 30px; height: 30px;"> -->
                    </div>
                    <h1 class="m-0 text-primary">Rentronics</h1>
                </a>
                <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto">
                        <!-- <a href="index.php" class="nav-item nav-link active">Home</a> -->
                        <!-- <a href="#abt" class="nav-item nav-link">About</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Property</a>
                            <div class="dropdown-menu rounded-0 m-0">
                                <a href="property-list.html" class="dropdown-item">Property List</a>
                                <a href="property-type.html" class="dropdown-item">Property Type</a>
                                <a href="property-agent.html" class="dropdown-item">Property Agent</a>
                            </div>
                        </div>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu rounded-0 m-0">
                                <a href="testimonial.html" class="dropdown-item">Testimonial</a>
                                <a href="404.html" class="dropdown-item">404 Error</a>
                            </div>
                        </div>
                        <a href="contact.html" class="nav-item nav-link">Contact</a> -->
                    </div>
                    <a href="logout.php" class="btn btn-primary px-3 d-none d-lg-flex">Logout</a>
                </div>
            </nav>
        </div>
        <!--Navbar End-->

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li class="menu-title">
                            <span>Main</span>
                        </li>
                        <li>
                            <a href="dashboard.php"><i class="fe fe-home"></i> <span>Dashboard</span></a>
                        </li>

                        <li class="menu-title">
                            <span>All Users</span>
                        </li>

                        <li class="submenu">
                            <a href=""><i class="fe fe-user"></i> <span> All Users </span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="adminlist.php"> Admin </a></li>
                                <!-- <li><a href="userlist.php"> Users </a></li>
                                <li><a href="useragent.php"> Agent </a></li>
                                <li><a href="userbuilder.php"> Builder </a></li> -->
                            </ul>
                        </li>

                        <!-- <li class="menu-title">
                            <span>State & City</span>
                        </li>

                        <li class="submenu">
                            <a href=""><i class="fe fe-location"></i> <span>State & City</span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="stateadd.php"> State </a></li>
                                <li><a href="cityadd.php"> City </a></li>
                            </ul>
                        </li> -->

                        <li class="menu-title">
                            <span>Property Management</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-map"></i> <span> Property</span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="propertyadd.php"> Add Property</a></li>
                                <li><a href="propertyview.php"> View Property </a></li>

                            </ul>
                        </li>



                        <!-- <li class="menu-title">
                            <span>Query</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-comment"></i> <span> Contact,Feedback </span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="contactview.php"> Contact </a></li>
                                <li><a href="feedbackview.php"> Feedback </a></li>
                            </ul>
                        </li>
                        <li class="menu-title">
                            <span>About</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-browser"></i> <span> About Page </span>
                                <span class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="aboutadd.php"> Add About Content </a></li>
                                <li><a href="aboutview.php"> View About </a></li>
                            </ul>
                        </li> -->

                    </ul>
                </div>
            </div>
        </div>
        <!-- /Sidebar -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">

            <div class="content container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h3 class="page-title">Welcome Admin!</h3>
                            <p></p>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ul>
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
</body>
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
<script src="js/main.js">
</html >