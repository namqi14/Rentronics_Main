<?php
require_once 'floorplan.php';
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
    <link href="css/pv5-a-17-2.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
    body {
        background-color: #e3ecf5;
        font-family: 'Heebo', sans-serif;
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
                            <h3 class="page-title">
                                <i class="bi bi-geo-alt-fill location-icon"></i> 
                                <?php echo $propertyName; ?> (<?php echo $unitNo; ?>)
                                <br>
                                <p class="bed-available">
                                Bed Available: <?php echo $availableBedsCount; ?>
                                </p>
                            </h3>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="plan-container">
                    <div class="floorplan">
                        <!-- Dynamically Generated Rooms and Beds -->

                        <!-- Room R1 -->
                        <div class="room room-r1">
                            <h1 class="label">R1</h1>
                            <ul class="bed-container">
                                <?php
                                // Dynamically render buttons for Room R1
                                if (isset($beds['R0005'])) {
                                    foreach ($beds['R0005'] as $bed) {
                                        preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                        $bedLabel = $matches[0];
                                        $bedNumber = $matches[1];

                                        $labelPosition = ($bedNumber % 2 == 0) ? 'T' : 'B';

                                        if ($bed['BedStatus'] == 'Rented') {
                                            $buttonClass = 'rented';
                                        } elseif ($bed['BedStatus'] == 'Booked') {
                                            $buttonClass = 'booked'; // Use 'booked' for beds that are booked but not yet rented
                                        } else {
                                            $buttonClass = 'available'; // Default to 'available' if not rented or booked
                                        }

                                        $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                        echo "<li class='bed'>
                                                <span class='label-TB'>{$labelPosition}</span>
                                                <a href='{$bedDetailsUrl}'>
                                                    <button class='bed-button {$buttonClass}'>{$bedLabel}</button>
                                                </a>
                                            </li>";
                                    }
                                } else {
                                    echo "<!-- No beds found for Room R1 -->";
                                }
                                ?>
                            </ul>
                        </div>

                        <!-- Repeat similar structure for other rooms (R2, R3, R4) -->

                        <div class="room room-r2">
                            <h1 class="label">R2</h1>
                            <ul class="bed-container">
                                <?php
                                if (isset($beds['R0006'])) {
                                    foreach ($beds['R0006'] as $bed) {
                                        preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                        $bedLabel = $matches[0];
                                        $bedNumber = $matches[1];
                                        $labelPosition = ($bedNumber % 2 == 0) ? 'T' : 'B';

                                        $buttonClass = ($bed['BedStatus'] == 'Rented') ? 'rented' :
                                                      (($bed['BedStatus'] == 'Booked') ? 'booked' : 'available');

                                        $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                        echo "<li class='bed'>
                                                <span class='label-TB'>{$labelPosition}</span>
                                                <a href='{$bedDetailsUrl}'>
                                                    <button class='bed-button {$buttonClass}'>{$bedLabel}</button>
                                                </a>
                                            </li>";
                                    }
                                } else {
                                    echo "<!-- No beds found for Room R2 -->";
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="room room-r3">
                            <h1 class="label">R3</h1>
                            <ul class="bed-container">
                                <?php
                                if (isset($beds['R0007'])) {
                                    foreach ($beds['R0007'] as $bed) {
                                        preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                        $bedLabel = $matches[0];
                                        $bedNumber = $matches[1];
                                        $labelPosition = ($bedNumber % 2 == 0) ? 'T' : 'B';

                                        $buttonClass = ($bed['BedStatus'] == 'Rented') ? 'rented' :
                                                      (($bed['BedStatus'] == 'Booked') ? 'booked' : 'available');

                                        $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                        echo "<li class='bed'>
                                                <span class='label-TB'>{$labelPosition}</span>
                                                <a href='{$bedDetailsUrl}'>
                                                    <button class='bed-button {$buttonClass}'>{$bedLabel}</button>
                                                </a>
                                            </li>";
                                    }
                                } else {
                                    echo "<!-- No beds found for Room R3 -->";
                                }
                                ?>
                            </ul>
                        </div>

                        <div class="room room-r4">
                            <h1 class="label">R4</h1>
                            <ul class="bed-container">
                                <?php
                                if (isset($beds['R0008'])) {
                                    foreach ($beds['R0008'] as $bed) {
                                        preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                        $bedLabel = $matches[0];
                                        $bedNumber = $matches[1];
                                        $labelPosition = ($bedNumber % 2 == 0) ? 'T' : 'B';

                                        $buttonClass = ($bed['BedStatus'] == 'Rented') ? 'rented' :
                                                      (($bed['BedStatus'] == 'Booked') ? 'booked' : 'available');

                                        $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                        echo "<li class='bed'>
                                                <span class='label-TB'>{$labelPosition}</span>
                                                <a href='{$bedDetailsUrl}'>
                                                    <button class='bed-button {$buttonClass}'>{$bedLabel}</button>
                                                </a>
                                            </li>";
                                    }
                                } else {
                                    echo "<!-- No beds found for Room R4 -->";
                                }
                                ?>
                            </ul>
                        </div>

                        <!-- Additional areas like Toilet and Kitchen -->
                        <div class="toilet toilet-1">
                            <h1 class="label">Toilet 1</h1>
                        </div>
                        <div class="toilet toilet-2">
                            <h1 class="label">Toilet 2</h1>
                        </div>
                        <div class="toilet toilet-3"></div>
                        <div class="kitchen">
                            <h1 class="label">Kitchen</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
</body>

</html>
