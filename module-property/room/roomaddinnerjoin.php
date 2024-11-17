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

$room = [];
$unit_id = '';

// Check if this is an edit action
if (isset($_GET['room_id']) && isset($_GET['unit_id'])) {
    $room_id = $_GET['room_id'];
    $unit_id = $_GET['unit_id'];
    $stmt = $conn->prepare("SELECT * FROM Room WHERE RoomID = ?");
    $stmt->bind_param("s", $room_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $room = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = "<p class='alert alert-warning'>Error fetching room details: " . $stmt->error . "</p>";
        $stmt->close();
    }
}

// Fetch Unit IDs and UnitNos from the Unit table
$unitOptions = '';
$result = $conn->query("SELECT UnitID, UnitNo FROM Unit");
$unitNos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitOptions .= "<option value='" . $row['UnitID'] . "'";
        if (isset($room['UnitID']) && $room['UnitID'] == $row['UnitID']) {
            $unitOptions .= " selected";
        }
        $unitOptions .= " data-unitno='" . $row['UnitNo'] . "'>" . $row['UnitID'] . " - " . $row['UnitNo'] . "</option>";
        $unitNos[$row['UnitID']] = $row['UnitNo'];
    }
}

// Fetch Agent IDs from the Agent table
$agentOptions = '';
$result = $conn->query("SELECT AgentID, AgentName FROM Agent");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agentOptions .= "<option value='" . $row['AgentID'] . "'";
        if (isset($room['AgentID']) && $room['AgentID'] == $row['AgentID']) {
            $agentOptions .= " selected";
        }
        $agentOptions .= ">" . $row['AgentID'] . " - " . $row['AgentName'] . "</option>";
    }
}

// Handle AJAX request to check if Room ID exists
if (isset($_POST['action']) && $_POST['action'] == 'check_room_id') {
    $room_id = $_POST['room_id'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Room WHERE RoomID = ?");
    $stmt->bind_param("s", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo $row['count'] > 0 ? 'exists' : 'not exists';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (isset($_POST['room_id']) && !empty($_POST['room_id'])) {
        // Edit action
        $room_id = $_POST['room_id'];
        $unit_id = $_POST['unit_id'];
        $room_no = $_POST['room_no'];
        $room_rent_amount = $_POST['room_rent_amount'];
        $katil = $_POST['katil'];
        $room_status = !empty($_POST['room_status']) ? $_POST['room_status'] : NULL;
        $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : NULL;

        $stmt = $conn->prepare("UPDATE Room SET UnitID=?, RoomNo=?, RoomRentAmount=?, Katil=?, RoomStatus=?, AgentID=? WHERE RoomID=?");
        $stmt->bind_param("sssisss", $unit_id, $room_no, $room_rent_amount, $katil, $room_status, $agent_id, $room_id);

        if ($stmt->execute()) {
            $msg = "<p class='alert alert-success'>Room $room_id updated successfully</p>";
        } else {
            $error = "<p class='alert alert-warning'>Error updating room $room_id: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        // Add action
        $unit_id = $_POST['unit_id'];
        $start_room_id = $_POST['start_room_id'];
        $num_rooms = $_POST['num_rooms'];
        $room_status = !empty($_POST['room_status']) ? $_POST['room_status'] : NULL;
        $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : NULL;

        $katils = $_POST['katil'];
        $room_rent_amounts = $_POST['room_rent_amount'];

        if (isset($unit_id, $start_room_id, $num_rooms, $room_rent_amounts, $katils)) {
            // Prepare SQL query to insert data into the Room table
            $stmt_room = $conn->prepare("INSERT INTO Room (RoomID, UnitID, RoomNo, RoomRentAmount, Katil, RoomStatus, AgentID) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if ($stmt_room === false) {
                $error .= "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
            } else {
                $unit_no = $unitNos[$unit_id];

                // Check for the last existing RoomNo for the given UnitID
                $result = $conn->query("SELECT RoomNo FROM Room WHERE UnitID = '$unit_id' ORDER BY RoomNo DESC LIMIT 1");
                $row = $result->fetch_assoc();

                if ($row) {
                    $last_room_number = (int)substr($row['RoomNo'], strrpos($row['RoomNo'], 'R') + 1);
                    $start_room_number = $last_room_number + 1;
                } else {
                    $start_room_number = 1; // Start room numbering from 1 if no records found
                }

                for ($i = 0; $i < $num_rooms; $i++) {
                    $current_room_number = $start_room_number + $i;
                    $room_id = 'R' . str_pad((int)substr($start_room_id, 1) + $i, 4, '0', STR_PAD_LEFT);  // Use user input for RoomID
                    $room_no = $unit_no . '-R' . $current_room_number;  // Generate RoomNo based on UnitNo
                    $katil = $katils[$i]; // Get number of katil for the current room
                    $room_rent_amount = $room_rent_amounts[$i]; // Get rent amount for the current room
                    $stmt_room->bind_param("ssssiss", $room_id, $unit_id, $room_no, $room_rent_amount, $katil, $room_status, $agent_id);

                    // Check if room insertion is successful
                    if (!$stmt_room->execute()) {
                        $error .= "<p class='alert alert-warning'>Error inserting room $room_id into database: " . $stmt_room->error . "</p>";
                    } else {
                        $msg .= "<p class='alert alert-success'>RoomID: $room_id, RoomNo: $room_no, Rent: $room_rent_amount, Katil: $katil, Status: $room_status, AgentID: $agent_id</p>";
                    }
                }

                $stmt_room->close();
            }
        } else {
            $error = "<p class='alert alert-warning'>Incomplete form data. Please fill in all required fields</p>";
        }
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
    <link href="../../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../../lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/room.css" rel="stylesheet">
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
                            <h3 class="page-title">Room Form</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Room</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Room Details Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Room Form</h4>
                                <a href="roomtable.php" class="btn btn-primary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="room-form" id="roomForm">
                                <div class="card-body">
                                    <h5 class="card-title">Room Details</h5>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit ID</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" id="unitIdSelect" name="unit_id" required>
                                                        <option value="">Select Unit</option>
                                                        <?php echo $unitOptions; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit No</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" id="unitNoInput" name="unit_no" readonly placeholder="Unit No will be displayed here" value="<?php echo isset($room['UnitNo']) ? $room['UnitNo'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Starting Room ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="start_room_id" id="startRoomIdInput" required placeholder="Enter Starting Room ID (e.g., R0001)" value="<?php echo isset($room['RoomID']) ? $room['RoomID'] : ''; ?>">
                                                    <div id="roomIdExistMsg" class="text-danger mt-1" style="display: none;">Room ID already exists.</div>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Number of Rooms</label>
                                                <div class="col-lg-9">
                                                    <input type="number" class="form-control" name="num_rooms" id="numRoomsInput" required placeholder="Enter Number of Rooms" value="1">
                                                </div>
                                            </div>
                                            <div id="roomsContainer"></div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room Status</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="room_status">
                                                        <option value="">Select Status</option>
                                                        <option value="Vacant" <?php echo (isset($room['RoomStatus']) && $room['RoomStatus'] == 'Vacant') ? 'selected' : ''; ?>>Vacant</option>
                                                        <option value="Partially Rented" <?php echo (isset($room['RoomStatus']) && $room['RoomStatus'] == 'Partially Rented') ? 'selected' : ''; ?>>Partially Rented</option>
                                                        <option value="Fully Rented" <?php echo (isset($room['RoomStatus']) && $room['RoomStatus'] == 'Fully Rented') ? 'selected' : ''; ?>>Fully Rented</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Agent ID</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="agent_id">
                                                        <option value="">Select Agent</option>
                                                        <?php echo $agentOptions; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <input type="hidden" name="add" value="1">
                                            <input type="hidden" name="room_id" value="<?php echo isset($room['RoomID']) ? $room['RoomID'] : ''; ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="add">Submit</button>
                                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Room Details Form -->
                </div>
            </div>
        </div>
    </div>

    <!-- Room Details Confirmation Modal -->
    <div class="modal fade" id="confirmRoomModal" tabindex="-1" role="dialog" aria-labelledby="confirmRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmRoomModalLabel">Confirm Room Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="roomDetails"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
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
    <script src="../../js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update Unit Number Function
        function updateUnitNo() {
            var unitSelect = document.querySelector('select[name="unit_id"]');
            var unitNoInput = document.querySelector('input[name="unit_no"]');
            if (unitSelect && unitNoInput) {
                var selectedOption = unitSelect.options[unitSelect.selectedIndex];
                unitNoInput.value = selectedOption.getAttribute('data-unitno');
            }
        }

        // Add event listener for Unit ID select change
        if (document.querySelector('select[name="unit_id"]')) {
            document.querySelector('select[name="unit_id"]').addEventListener('change', updateUnitNo);
        }

        // Pre-fill the Unit No input when the page loads (for editing)
        updateUnitNo();

        // Add event listener for Room ID input change
        if (document.querySelector('#startRoomIdInput')) {
            document.querySelector('#startRoomIdInput').addEventListener('change', checkRoomIdExistence);
        }

        // Function to check if Room ID exists
        function checkRoomIdExistence() {
            var roomId = document.querySelector('#startRoomIdInput').value;
            if (roomId) {
                $.post('', { action: 'check_room_id', room_id: roomId }, function(response) {
                    if (response === 'exists') {
                        $('#roomIdExistMsg').show();
                    } else {
                        $('#roomIdExistMsg').hide();
                    }
                });
            }
        }

        // Add event listener for number of rooms input change
        if (document.querySelector('#numRoomsInput')) {
            document.querySelector('#numRoomsInput').addEventListener('change', generateRoomInputs);
        }

        // Function to generate room input fields
        function generateRoomInputs() {
            var numRooms = document.querySelector('#numRoomsInput').value;
            var roomsContainer = document.querySelector('#roomsContainer');
            roomsContainer.innerHTML = '';

            for (var i = 0; i < numRooms; i++) {
                var roomInputGroup = document.createElement('div');
                roomInputGroup.className = 'form-group row';

                var roomLabel = document.createElement('label');
                roomLabel.className = 'col-lg-3 col-form-label';
                roomLabel.innerText = 'Room ' + (i + 1) + ' Rent Amount';

                var roomInputDiv = document.createElement('div');
                roomInputDiv.className = 'col-lg-9';

                var roomInput = document.createElement('input');
                roomInput.type = 'text';
                roomInput.className = 'form-control';
                roomInput.name = 'room_rent_amount[]';
                roomInput.required = true;
                roomInput.placeholder = 'Enter Room ' + (i + 1) + ' Rent Amount';

                roomInputDiv.appendChild(roomInput);
                roomInputGroup.appendChild(roomLabel);
                roomInputGroup.appendChild(roomInputDiv);

                roomsContainer.appendChild(roomInputGroup);

                var katilInputGroup = document.createElement('div');
                katilInputGroup.className = 'form-group row';

                var katilLabel = document.createElement('label');
                katilLabel.className = 'col-lg-3 col-form-label';
                katilLabel.innerText = 'Number of Katil for Room ' + (i + 1);

                var katilInputDiv = document.createElement('div');
                katilInputDiv.className = 'col-lg-9';

                var katilInput = document.createElement('input');
                katilInput.type = 'number';
                katilInput.className = 'form-control';
                katilInput.name = 'katil[]';
                katilInput.required = true;
                katilInput.placeholder = 'Enter Number of Katil for Room ' + (i + 1);

                katilInputDiv.appendChild(katilInput);
                katilInputGroup.appendChild(katilLabel);
                katilInputGroup.appendChild(katilInputDiv);

                roomsContainer.appendChild(katilInputGroup);
            }
        }

        // Confirm Room Details Function
        function confirmRoomDetails() {
            var details = generateRoomDetails();
            if (details) {
                document.getElementById('roomDetails').innerText = details;
                $('#confirmRoomModal').modal('show');
            }
        }

        // Function to generate room details
        function generateRoomDetails() {
            var unit_id = document.querySelector('select[name="unit_id"]').value;
            var unit_no = document.querySelector('input[name="unit_no"]').value;
            var start_room_id = document.querySelector('input[name="start_room_id"]').value;
            var num_rooms = document.querySelector('input[name="num_rooms"]').value;
            var room_rent_amounts = document.querySelectorAll('input[name="room_rent_amount[]"]');
            var katils = document.querySelectorAll('input[name="katil[]"]');
            var room_status = document.querySelector('select[name="room_status"]').value;
            var agent_id = document.querySelector('select[name="agent_id"]').value;
        
            if (unit_id && start_room_id && num_rooms && room_rent_amounts.length > 0 && katils.length > 0) {
                var details = '';
                var start_room_number = 1;
        
                $.ajax({
                    url: 'check_unit_rooms.php',
                    type: 'POST',
                    data: { unit_id: unit_id },
                    async: false,
                    success: function(response) {
                        if (response === '0') {
                            start_room_number = 1;
                        }
                    }
                });
        
                for (var i = 0; i < num_rooms; i++) {
                    var current_room_number = start_room_number + i;
                    var room_id = 'R' + (String(parseInt(start_room_id.substring(1)) + i).padStart(4, '0'));
                    var room_no = unit_no + '-R' + current_room_number;
                    var katil = katils[i].value;
                    var room_rent_amount = room_rent_amounts[i].value;
        
                    details += 'RoomID: ' + room_id + ', RoomNo: ' + room_no + ', Rent: ' + room_rent_amount + ', Katil: ' + katil + ', Status: ' + room_status + ', AgentID: ' + agent_id + '\n';
                }
                return details;
            } else {
                alert('Please fill in all required fields');
                return '';
            }
        }

        // Add event listener for confirm button in the modal
        if (document.getElementById('confirmBtn')) {
            document.getElementById('confirmBtn').addEventListener('click', function() {
                console.log('Confirm button clicked');
                $('#confirmRoomModal').modal('hide');
                setTimeout(function() {
                    document.getElementById('roomForm').submit();
                }, 1000);
            });
        }

        // Add event listener for reset button
        if (document.getElementById('resetButton')) {
            document.getElementById('resetButton').addEventListener('click', function() {
                $('#roomIdExistMsg').hide(); // Hide the Room ID exists message
                document.querySelector('#roomsContainer').innerHTML = ''; // Clear the dynamically generated room inputs
            });
        }

        // Add event listener for the submit button to confirm room details
        if (document.querySelector('button[name="add"]')) {
            document.querySelector('button[name="add"]').addEventListener('click', function(event) {
                event.preventDefault();
                confirmRoomDetails();
            });
        }

    });
    </script>
</body>
</html>
