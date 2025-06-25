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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    <link href="css/cyber-e22b.css" rel="stylesheet">

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
                                    <i style='font-size:20px; padding-right: 10px;' class="fas fa-bed"></i>
                                    Bed Available: <?php echo $availableBedsCount; ?>
                                </p>
                            </h3>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="plan-container">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="switchTab('floor1')">Floor 1</button>
                        <button class="tab-btn" onclick="switchTab('floor2')">Floor 2</button>
                    </div>
                    <div class="floorplan active" id="floor1">
                        <!-- Dynamically Generated Rooms and Beds -->
                        <?php
                        // Ensure $unitID is properly defined
                        $unitID = isset($_GET['unitID']) ? $_GET['unitID'] : null; // or any logic to set $unitID

                        if ($unitID) {
                            $roomResult = $conn->query("SELECT RoomID, RoomNo FROM room WHERE UnitID = '$unitID'");

                            if ($roomResult->num_rows > 0) {
                                $roomCounter = 1; // Counter for room class naming (R1, R2, etc.)
                                while ($room = $roomResult->fetch_assoc()) {
                                    $roomID = $room['RoomID'];
                                    $roomNo = $room['RoomNo'];

                                    // Only show rooms R1 to R6 on floor1
                                    if ($roomCounter <= 6) {
                                        // Display the room div with a readable class name (e.g., room-r1, room-r2)
                                        echo "<div class='room room-r{$roomCounter}'>";
                                        echo "<h1 class='label'>R{$roomCounter}</h1>"; // Display the room number or other identifier
                                        echo "<ul class='bed-container'>";

                                        // Check if there are any beds associated with the current room
                                        if (isset($beds[$roomID])) {
                                            // Reverse the order of the beds array to start from top (T)
                                            $beds[$roomID] = array_reverse($beds[$roomID]);
                                            $positionCounter = 0; // Counter to alternate between T and B

                                            foreach ($beds[$roomID] as $bed) {
                                                preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                                $bedLabel = $matches[0];
                                                $bedNumber = $matches[1];

                                                // Alternate label position between 'T' and 'B'
                                                $labelPosition = ($positionCounter % 2 == 0) ? 'A' : 'B';
                                                $positionCounter++; // Increment position counter

                                                // Determine the button class and icon based on the BedStatus
                                                if ($bed['BedStatus'] == 'Rented') {
                                                    $buttonClass = 'rented';
                                                    $icon = '<i class="bt fas fa-lock"></i>'; // Lock icon for rented
                                                } elseif ($bed['BedStatus'] == 'Booked') {
                                                    $buttonClass = 'booked';
                                                    $icon = '<i class="bt fas fa-calendar-check"></i>'; // Calendar check icon for booked
                                                } else {
                                                    $buttonClass = 'available';
                                                    $icon = '<i class="bt fas fa-check-circle"></i>'; // Check-circle icon for available
                                                }

                                                $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                                echo "  <li class='bed'>
                                                            <span class='label-TB'>{$labelPosition}</span>
                                                            <a href='{$bedDetailsUrl}'>
                                                                <button class='bed-button {$buttonClass}'>{$icon} {$bedLabel}</button>
                                                            </a>
                                                        </li>";
                                            }
                                        } else {
                                            echo "<!-- No beds found for Room {$roomID} -->";
                                        }

                                        echo "</ul>";
                                        echo "</div>";
                                    }

                                    $roomCounter++; // Increment the room counter for the next room class name
                                }
                            } else {
                                echo "<p>No rooms found for this unit.</p>";
                            }
                        } else {
                            echo "<p>Invalid UnitID.</p>";
                        }
                        ?>

                        <div class="toilet toilet-1">
                            <h1 class="label"><i style='font-size:24px' class='not fas'>&#xf7d8;</i></h1>
                        </div>
                        <div class="toilet toilet-2">
                            <h1 class="label">
                                <img 
                                    src="img/stair_up.png" 
                                    alt="Stairs" 
                                    style="width:24px; height:24px;"
                                />
                            </h1>
                        </div>
                        <div class="kitchen">
                            <h1 class="label"><i style='font-size:24px' class="not fas fa-utensils"></i></h1>
                        </div>
                    </div>


                    <div class="floorplan" id="floor2">
                        <!-- Dynamically Generated Rooms and Beds -->
                        <?php
                        // Fetch room data from the database for the current UnitID
                        $unitID = isset($_GET['unitID']) ? $_GET['unitID'] : null; // Replace with the actual $unitID variable
                        $roomResult = $conn->query("SELECT RoomID, RoomNo, RoomStatus FROM room WHERE UnitID = '$unitID'");

                        if ($roomResult->num_rows > 0) {
                            $roomCounter = 1; // Counter to create unique class names (e.g., room-r1, room-r2)
                            while ($room = $roomResult->fetch_assoc()) {
                                $roomID = $room['RoomID'];
                                $roomNo = $room['RoomNo'];
                                $roomStatus = $room['RoomStatus'] ?? 'Available'; // Default to 'Available' if not set

                                // Only show rooms R7 to R10 on floor2
                                if ($roomCounter >= 7 && $roomCounter <= 10) {
                                    // Display the room div with a readable class name (e.g., room-r7, room-r8)
                                    echo "<div class='room room-r{$roomCounter}'>";
                                    echo "<h1 class='label'>R{$roomCounter}</h1>"; // Display the room number or other identifier
                                    echo "<ul class='bed-container'>";

                                    // Check if there are any beds associated with the current room
                                    if (isset($beds[$roomID])) {
                                        // Reverse the order of the beds array to start from top (T)
                                        $beds[$roomID] = array_reverse($beds[$roomID]);
                                        $positionCounter = 0; // Counter to alternate between T and B

                                        foreach ($beds[$roomID] as $bed) {
                                            preg_match('/B(\d+)/', $bed['BedNo'], $matches);
                                            $bedLabel = $matches[0];
                                            $bedNumber = $matches[1];

                                            // Alternate label position between 'T' and 'B'
                                            $labelPosition = ($positionCounter % 2 == 0) ? 'A' : 'B';
                                            $positionCounter++; // Increment position counter

                                            // Determine the button class and icon based on the BedStatus
                                            if ($bed['BedStatus'] == 'Rented') {
                                                $buttonClass = 'rented';
                                                $icon = '<i class="bt fas fa-lock"></i>'; // Lock icon for rented
                                            } elseif ($bed['BedStatus'] == 'Booked') {
                                                $buttonClass = 'booked';
                                                $icon = '<i class="bt fas fa-calendar-check"></i>'; // Calendar check icon for booked
                                            } else {
                                                $buttonClass = 'available';
                                                $icon = '<i class="bt fas fa-check-circle"></i>'; // Check-circle icon for available
                                            }

                                            $bedDetailsUrl = "../tenant/bookingform.php?bedID=" . $bed['BedID'];

                                            echo "  <li class='bed'>
                                                        <span class='label-TB'>{$labelPosition}</span>
                                                        <a href='{$bedDetailsUrl}'>
                                                            <button class='bed-button {$buttonClass}'>{$icon} {$bedLabel}</button>
                                                        </a>
                                                    </li>";
                                        }
                                    } else {
                                        // If no beds, show the room status as a whole
                                        if ($roomStatus == 'Rented') {
                                            $buttonClass = 'rented';
                                            $icon = '<i class="bt fas fa-lock"></i>'; // Lock icon for rented
                                        } elseif ($roomStatus == 'Booked') {
                                            $buttonClass = 'booked';
                                            $icon = '<i class="bt fas fa-calendar-check"></i>'; // Calendar check icon for booked
                                        } else {
                                            $buttonClass = 'available';
                                            $icon = '<i class="bt fas fa-check-circle"></i>'; // Check-circle icon for available
                                        }

                                        $roomDetailsUrl = "../tenant/roomform.php?roomID=" . $roomID;

                                        echo "<li class='bed'>
                                                <a href='{$roomDetailsUrl}'>
                                                    <button class='bed-button {$buttonClass}'>{$icon} {$roomNo}</button>
                                                </a>
                                            </li>";
                                    }

                                    echo "</ul>";
                                    echo "</div>";
                                }

                                $roomCounter++; // Increment the room counter for the next room class
                            }
                        } else {
                            echo "<p>No rooms found for this unit.</p>";
                        }
                        ?>
                        <!-- Additional areas like Toilet and Kitchen -->
                        <div class="toilet toilet-3">
                            <h1 class="label">
                                <img 
                                    src="img/stairs_down.png" 
                                    alt="Stairs"
                                    style="width:24px; height:24px;"
                                />
                            </h1>
                        </div>
                        <div class="toilet toilet-4">
                            <h1 class="label"><i style='font-size:24px' class='not fas'>&#xf7d8;</i></h1>
                        </div>
                        <div class="toilet toilet-5">
                            <h1 class="label"><i style='font-size:24px' class='not fas'>&#xf7d8;</i></h1>
                        </div>
                    </div>

                    <!-- Legend for the floor plan -->
                    <div class="legend">
                        <h4>Legend</h4>
                        <ul class="legend-list">
                            <li>
                                <button class="bed-button available">
                                    <i class="le fas fa-check-circle"></i>
                                </button>
                                Available
                            </li>
                            <li>
                                <button class="bed-button booked">
                                    <i class="le fas fa-calendar-check"></i>
                                </button>
                                Booked
                            </li>
                            <li>
                                <button class="bed-button rented">
                                    <i class="le fas fa-lock"></i>
                                </button>
                                Rented
                            </li>
                        </ul>
                        <ul class="legend-list">
                            <li>
                                <p class="bed-position">
                                    <i style='font-size:20px; padding-right: 10px;' class="fas fa-bed"></i>
                                    A : Atas
                                </p>
                            </li>
                            <li>
                                <p class="bed-position">
                                    B : Bawah
                                </p>
                            </li>
                        </ul>
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

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.floorplan').forEach(container => {
                container.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(button => {
                button.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }
    </script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
</body>

</html>