<?php
require_once __DIR__ . '/../../module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Fetch agent name from the database
$user = $_SESSION['auser']['AgentEmail'];
// Debug line - you can remove this after confirming the value
error_log("Agent Email from Session: " . $user);

$stmt = $conn->prepare("SELECT AgentName FROM agent WHERE AgentEmail = ?");  // Changed AgentEmail to AgentEMail
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Debug line - you can remove this after debugging
error_log("Query Result: " . print_r($row, true));

// Add error handling
if ($row === null) {
    // Handle case where no agent was found
    $agentName = "Unknown Agent";
    error_log("No agent found for email: " . $user);
} else {
    $agentName = $row['AgentName'];
}
$stmt->close();

// Fetch property data
$properties = [];
$result = $conn->query("SELECT PropertyID, PropertyName, PropertyType, Location FROM Property");
while ($row = $result->fetch_assoc()) {
    $properties[$row['PropertyID']] = $row;
}
$result->close();

// Fetch unit data
$units = [];
$result = $conn->query("SELECT UnitID, PropertyID, UnitNo, FloorPlan FROM Unit");
while ($row = $result->fetch_assoc()) {
    $units[$row['PropertyID']][] = $row;
}
$result->close();

// Fetch room data
$rooms = [];
$result = $conn->query("SELECT RoomID, UnitID, RoomNo FROM Room");
while ($row = $result->fetch_assoc()) {
    $rooms[$row['UnitID']][] = $row;
}
$result->close();

// Fetch bed data
$beds = [];
$result = $conn->query("SELECT BedID, RoomID, BedNo, BedStatus FROM Bed"); // Added BedStatus
while ($row = $result->fetch_assoc()) {
    $beds[$row['RoomID']][] = $row;
}
$result->close();

// Function to count available beds
function countAvailableBeds($unitID, $rooms, $beds) {
    $availableBeds = 0;

    if (isset($rooms[$unitID])) {
        foreach ($rooms[$unitID] as $room) {
            $roomID = $room['RoomID'];
            if (isset($beds[$roomID])) {
                foreach ($beds[$roomID] as $bed) {
                    if ($bed['BedStatus'] === 'Available' || $bed['BedStatus'] === 'Vacant' || $bed['BedStatus'] === '' || is_null($bed['BedStatus'])) {
                        $availableBeds++;
                    }
                }
            }
        }
    }

    return $availableBeds;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Rentronics</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="/rentronics/img/favicon.ico" rel="icon">

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

    <!-- Template Stylesheet -->
    <link href="../../css/dashboardagent.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <style>
    body {
        background-color: #e3ecf5;
        font-family: 'Heebo', sans-serif;
    }

    .page-header {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .location-section {
        margin-bottom: 20px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .location-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .location-icon {
        color: red;
        margin-right: 10px;
        font-size: 24px;
        transition: color 0.2s;
    }

    .unit-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 10px;
    }

    .unit-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f3f3f3, #e0e0e0);
        padding: 20px;
        margin: 5px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        color: #000;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        width: 250px;
        height: 60px;
        text-decoration: none;
        /* Remove underline from anchor */
    }

    .unit-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .unit-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 14px;
        font-weight: bold;
        width: 140px;
        align-content: center;
        transition: color 0.2s, font-size 0.2s;
    }

    .bed-count {
        background-color: #fff;
        color: #000;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        width: 50px;
    }

    .unit-box:hover .unit-name {
        color: grey;
        /* Change color of unit-name to white when hovering */
        font-size: 16px;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    }

    .unit-box-content {
        display: flex;
        justify-content: space-between;
        width: 100%;
    }

    .unit-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 10px;
    }

    @media (max-width: 768px) {
        .unit-box {
            width: 100%;
            height: auto;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!-- Navbar and Sidebar Start -->
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
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <!-- Location Sections -->
                <?php foreach ($properties as $propertyID => $property): ?>
                <div class="location-section">
                    <div class="location-title">
                        <i class="bi bi-geo-alt-fill location-icon"></i><?php echo $property['PropertyName']; ?>
                    </div>

                    <?php if (isset($units[$propertyID])): ?>
                    <div class="unit-container">
                        <?php foreach ($units[$propertyID] as $unit): ?>
                        <!-- Remove the space between FloorPlan and the query parameters -->
                        <a href="../<?php echo $unit['FloorPlan']; ?>?propertyID=<?php echo $propertyID; ?>&unitID=<?php echo $unit['UnitID']; ?>"
                            class="unit-box">
                            <div class="unit-box-content">
                                <div class="unit-name">
                                    <?php echo $unit['UnitNo']; ?>
                                </div>
                                <div class="bed-count">
                                    <?php
                                    $availableBeds = countAvailableBeds($unit['UnitID'], $rooms, $beds);
                                    echo $availableBeds;
                                    ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous">
    </script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
</body>

</html>