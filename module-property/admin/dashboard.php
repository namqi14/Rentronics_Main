<?php
require_once __DIR__ . '/../../module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: ../index.php");
    exit();
}

// Add error reporting and connection verification
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch metrics from database
$totalProperties = 0;
$totalApartments = 0;
$totalHouses = 0;
$totalVacantProperties = 0;
$totalOccupiedProperties = 0;

// Get total properties and types
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN PropertyType = 'Condominium' THEN 1 ELSE 0 END) as condos,
    SUM(CASE WHEN PropertyType = 'Terrace' THEN 1 ELSE 0 END) as houses
FROM Property";
$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $totalApartments = $row['condos'];
    $totalHouses = $row['houses'];
}

// Get bed counts by property type using joins
$sql = "SELECT 
    COUNT(b.BedID) as total_beds,
    SUM(CASE WHEN p.PropertyType = 'Condominium' THEN 1 ELSE 0 END) as condo_beds,
    SUM(CASE WHEN p.PropertyType = 'Terrace' THEN 1 ELSE 0 END) as terrace_beds,
    SUM(CASE WHEN b.BedStatus = 'Available' THEN 1 ELSE 0 END) as vacant,
    SUM(CASE WHEN b.BedStatus = 'Rented' THEN 1 ELSE 0 END) as occupied
FROM Bed b
JOIN Unit u ON b.UnitID = u.UnitID
JOIN Property p ON u.PropertyID = p.PropertyID";

$result = $conn->query($sql);
if ($row = $result->fetch_assoc()) {
    $totalProperties = $row['total_beds'];
    $totalApartments = $row['condo_beds'];
    $totalHouses = $row['terrace_beds'];
    $totalVacantProperties = $row['vacant'];
    $totalOccupiedProperties = $row['occupied'];
}

$agentName = $_SESSION['auser']['AgentName']; // Default value

if (isset($_SESSION['auser']) && is_array($_SESSION['auser'])) {
    // If the name is directly stored in the session
    if (isset($_SESSION['auser']['name'])) {
        $agentName = $_SESSION['auser']['name'];
    } 
    // If we need to get it from email
    elseif (isset($_SESSION['auser']['email'])) {
        $userEmail = $_SESSION['auser']['email'];
        $stmt = $conn->prepare("SELECT AgentName FROM agent WHERE AgentEmail = ?");
        if ($stmt) {
            $stmt->bind_param("s", $userEmail);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $agentName = $row['AgentName'];
            }
            $stmt->close();
        }
    }
}

// Debug the final value
error_log("Final agent name: " . $agentName);

// Debug the session value
error_log("Session auser value type: " . gettype($_SESSION['auser']));
if (is_array($_SESSION['auser'])) {
    error_log("Session auser contents: " . print_r($_SESSION['auser'], true));
    // If auser is an array, try to get the email from it
    $user = isset($_SESSION['auser']['email']) ? $_SESSION['auser']['email'] : '';
}

$stmt = $conn->prepare("SELECT AgentName FROM agent WHERE AgentEmail = ?");
if ($stmt) {
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $agentName = $row['AgentName'];
    }
    $stmt->close();
}

// Debug the final values
error_log("User value: " . print_r($user, true));
error_log("Agent name value: " . print_r($agentName, true));
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Table</title>
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../../css/dashboard.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">

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
                                        <i class="fas fa-building"></i>
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
                                        <i class="fas fa-city"></i>
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
                                        <i class="fas fa-home"></i>
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
                                        <i class="fas fa-door-open"></i>
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
                                    <span class="dash-widget-icon bg-danger">
                                        <i class="fas fa-door-closed"></i>
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
    <script src="../../js/main.js"></script>
</body>

</html>