<?php
session_start();
require_once('dbconnection.php');  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : '';

if (!$room_id) {
    $error = "No RoomID provided.";
} else {
    // Fetch current room data
    $stmt_room = $conn->prepare("SELECT * FROM Room WHERE RoomID = ?");
    $stmt_room->bind_param("s", $room_id);
    if ($stmt_room->execute()) {
        $result = $stmt_room->get_result();
        $room_data = $result->fetch_assoc();
        $stmt_room->close();
    } else {
        $error = "Error fetching room details: " . $stmt_room->error;
        $stmt_room->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $room_rent_amount = $_POST['room_rent_amount'];
    $room_id = $_POST['room_id'];

    if (isset($room_rent_amount, $room_id)) {
        // Prepare SQL query to update room rent amount in the Room table
        $stmt_room = $conn->prepare("UPDATE Room SET RoomRentAmount = ? WHERE RoomID = ?");

        if ($stmt_room === false) {
            $error .= "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            $stmt_room->bind_param("ss", $room_rent_amount, $room_id);

            // Check if room update is successful
            if (!$stmt_room->execute()) {
                $error .= "<p class='alert alert-warning'>Error updating room $room_id in database: " . $stmt_room->error . "</p>";
            } else {
                $msg .= "<p class='alert alert-success'>RoomID: $room_id, Rent: $room_rent_amount</p>";
            }

            $stmt_room->close();
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
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
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
        <?php include('nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Edit Room Rent</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Room</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Room Rent Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Edit Room Rent</h4>
                                <a href="roomtable.php" class="btn btn-primary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="room-form" id="roomForm">
                                <div class="card-body">
                                    <h5 class="card-title">Room Details</h5>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $room_data['RoomID']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room No</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $room_data['RoomNo']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $room_data['UnitID']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room Rent Amount</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="room_rent_amount" required placeholder="Enter Room Rent Amount" value="<?php echo $room_data['RoomRentAmount']; ?>">
                                                </div>
                                            </div>
                                            <input type="hidden" name="room_id" value="<?php echo $room_data['RoomID']; ?>">
                                            <input type="hidden" name="update" value="1">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="update">Update</button>
                                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Room Rent Form -->
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
    <script src="js/main.js"></script>
</body>
</html>
