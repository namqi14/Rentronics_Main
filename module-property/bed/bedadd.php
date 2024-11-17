<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

// Fetch Unit IDs and UnitNos from the unit table
$unitOptions = '';
$result = $conn->query("
    SELECT unit.UnitID, unit.UnitNo, property.PropertyName 
    FROM unit 
    INNER JOIN property ON unit.PropertyID = property.PropertyID
"); 
$unitNos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitOptions .= "<option value='" . $row['UnitID'] . "' data-unitno='" . $row['UnitNo'] . "'>" . $row['PropertyName'] . " - " . $row['UnitNo'] . "</option>";
        $unitNos[$row['UnitID']] = $row['UnitNo'];
    }
}


// Fetch Room IDs and other details from the room table
$roomOptions = [];
$katilByRoom = [];
$result = $conn->query("SELECT RoomID, RoomNo, Katil, UnitID FROM room");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitID = $row['UnitID'];
        $roomOptions[$unitID][] = "<option value='" . $row['RoomID'] . "' data-roomno='" . $row['RoomNo'] . "' data-katil='" . $row['Katil'] . "' data-unitid='" . $row['UnitID'] . "'>" . $row['RoomID'] . " - " . $row['RoomNo'] . "</option>";
        $katilByRoom[$row['RoomID']] = $row['Katil'];
    }
}

// Check for the last existing BedNo in the bed table per unit
$lastBedNoByUnit = [];
$result = $conn->query("SELECT UnitID, MAX(CAST(SUBSTRING_INDEX(BedNo, '-B', -1) AS UNSIGNED)) AS LastBedNo FROM bed GROUP BY UnitID");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitID = $row['UnitID'];
        $lastBedNoByUnit[$unitID] = $row['LastBedNo'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $room_id = $_POST['room_id'];
    $bed_rent_amount = $_POST['bed_rent_amount'];
    $num_beds = $_POST['num_beds'];
    $unit_id = $_POST['unit_id'];
    $room_no = '';

    // Fetch the RoomNo based on RoomID
    $stmt_room_no = $conn->prepare("SELECT RoomNo FROM room WHERE RoomID = ?");
    $stmt_room_no->bind_param("s", $room_id);
    if ($stmt_room_no->execute()) {
        $result = $stmt_room_no->get_result();
        $room_data = $result->fetch_assoc();
        $room_no = $room_data['RoomNo'];
        $stmt_room_no->close();
    } else {
        $error = "<p class='alert alert-warning'>Error fetching room details: " . $stmt_room_no->error . "</p>";
        $stmt_room_no->close();
    }

    if (isset($room_id, $bed_rent_amount, $num_beds, $unit_id, $room_no)) {
        // Prepare SQL query to insert data into the bed table
        $stmt_bed = $conn->prepare("INSERT INTO bed (BedID, RoomID, BedNo, BedRentAmount, UnitID) VALUES (?, ?, ?, ?, ?)");

        if ($stmt_bed === false) {
            $error .= "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            // Check for the last existing BedID in the bed table
            $result = $conn->query("SELECT BedID FROM bed ORDER BY BedID DESC LIMIT 1");
            $row = $result->fetch_assoc();

            if ($row) {
                $last_bed_id_number = (int)substr($row['BedID'], 1);
                $start_bed_id_number = $last_bed_id_number + 1;
            } else {
                $start_bed_id_number = 1; // Start bed ID numbering from 1 if no records found
            }

            // Determine the starting BedNo for the current unit
            $lastBedCounter = isset($lastBedNoByUnit[$unit_id]) ? $lastBedNoByUnit[$unit_id] : 0;

            for ($i = 0; $i < $num_beds; $i++) {
                $current_bed_id_number = $start_bed_id_number + $i;
                $bed_id = 'B' . str_pad($current_bed_id_number, 4, '0', STR_PAD_LEFT);  // Generate BedID
                $lastBedCounter++;  // Increment the global bed counter within the unit
                $bed_no = $room_no . '-B' . $lastBedCounter;  // Generate BedNo based on RoomNo
                $stmt_bed->bind_param("sssss", $bed_id, $room_id, $bed_no, $bed_rent_amount, $unit_id);

                // Check if bed insertion is successful
                if (!$stmt_bed->execute()) {
                    $error .= "<p class='alert alert-warning'>Error inserting bed $bed_id into database: " . $stmt_bed->error . "</p>";
                } else {
                    $msg .= "<p class='alert alert-success'>BedID: $bed_id, BedNo: $bed_no, Rent: $bed_rent_amount</p>";
                }
            }

            // Update the last bed number for the unit
            $lastBedNoByUnit[$unit_id] = $lastBedCounter;

            $stmt_bed->close();
        }
    } else {
        $error = "<p class='alert alert-warning'>Incomplete form data. Please fill in all required fields</p>";
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">
    <style>
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .place-picker-container {
            padding: 20px;
        }
        #map {
            height: 400px;
            width: 100%;
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
                            <h3 class="page-title">Bed Form</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Bed</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Bed Details Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Bed Form</h4>
                                <a href="bedtable.php" class="btn btn-primary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="bed-form" id="bedForm">
                                <div class="card-body">
                                    <h5 class="card-title">Bed Details</h5>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit No</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" id="unitIdSelect" name="unit_id" required>
                                                        <option value="">Select Unit</option>
                                                        <?php echo $unitOptions; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room ID</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" id="roomIdSelect" name="room_id" required>
                                                        <option value="">Select Room</option>
                                                        <!-- Room options will be populated based on the selected Unit -->
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Number of Beds</label>
                                                <div class="col-lg-9">
                                                    <input type="number" class="form-control" id="numBedsInput" name="num_beds" readonly placeholder="Number of Beds will be displayed here">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Bed Rent Amount</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="bed_rent_amount" required placeholder="Enter Bed Rent Amount">
                                                </div>
                                            </div>
                                            <input type="hidden" name="unit_id" id="unitIdHidden">
                                            <input type="hidden" name="add" value="1">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="add">Submit</button>
                                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Bed Details Form -->
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
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

    <!-- Ensure jQuery is loaded first -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- PHP condition to include JavaScript code to show the modal -->
    <?php if (!empty($msg) || !empty($error)) : ?>
    <script>
        $(document).ready(function() {
            $('#messageModal').modal('show');
        });
    </script>
    <?php endif; ?>
    <!-- Template Javascript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pre-defined room options
        var roomOptions = <?php echo json_encode($roomOptions); ?>;

        // Function to update Room options based on selected Unit
        function updateRoomOptions() {
            var unitSelect = document.querySelector('select[name="unit_id"]');
            var roomSelect = document.querySelector('select[name="room_id"]');
            var selectedUnitId = unitSelect.value;

            roomSelect.innerHTML = ''; // Clear existing options

            if (selectedUnitId && roomOptions[selectedUnitId]) {
                roomSelect.innerHTML = roomOptions[selectedUnitId].join(''); // Add new options
            }

            // Trigger the change event to update number of beds
            roomSelect.dispatchEvent(new Event('change'));
        }

        // Function to update Number of Beds
        function updateNumBeds() {
            var roomSelect = document.querySelector('select[name="room_id"]');
            var numBedsInput = document.querySelector('input[name="num_beds"]');
            var unitIdHidden = document.querySelector('input[name="unit_id"]');

            if (roomSelect && numBedsInput) {
                var selectedOption = roomSelect.options[roomSelect.selectedIndex];
                if (selectedOption) {
                    numBedsInput.value = selectedOption.getAttribute('data-katil');
                    unitIdHidden.value = selectedOption.getAttribute('data-unitid');
                } else {
                    numBedsInput.value = ''; // Clear the input if no option is selected
                    unitIdHidden.value = '';
                }
            }
        }

        // Add event listener for Unit ID select change
        if (document.querySelector('select[name="unit_id"]')) {
            document.querySelector('select[name="unit_id"]').addEventListener('change', updateRoomOptions);
        }

        // Add event listener for Room ID select change
        if (document.querySelector('select[name="room_id"]')) {
            document.querySelector('select[name="room_id"]').addEventListener('change', updateNumBeds);
        }

        // Add event listener for reset button
        if (document.getElementById('resetButton')) {
            document.getElementById('resetButton').addEventListener('click', function() {
                document.querySelector('select[name="room_id"]').innerHTML = ''; // Clear the room select options
                document.querySelector('input[name="num_beds"]').value = ''; // Clear the number of beds input
                document.querySelector('input[name="unit_id"]').value = ''; // Clear the unit ID hidden input
            });
        }
    });
    </script>
    <script src="../../js/main.js"></script>
</body>
</html>
