<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

// Correctly retrieve BedID from the URL
$bed_id = isset($_GET['BedID']) ? $_GET['BedID'] : '';

if (!$bed_id) {
    $error = "No BedID provided.";
} else {
    // Fetch current bed data
    $stmt_bed = $conn->prepare("SELECT * FROM bed WHERE BedID = ?");
    $stmt_bed->bind_param("s", $bed_id);
    if ($stmt_bed->execute()) {
        $result = $stmt_bed->get_result();
        $bed_data = $result->fetch_assoc();
        $stmt_bed->close();
    } else {
        $error = "Error fetching bed details: " . $stmt_bed->error;
        $stmt_bed->close();
    }

    // Fetch list of agents
    $agents = [];
    $result = $conn->query("SELECT AgentID, AgentName FROM agent");
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $bed_rent_amount = $_POST['bed_rent_amount'];
    $bed_id = $_POST['bed_id'];
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : NULL; // Allow null agent
    $bed_status = $_POST['bed_status'];

    if (isset($bed_rent_amount, $bed_id, $bed_status)) {
        // Prepare SQL query to update bed rent amount, agent (nullable), and bed status
        $stmt_bed = $conn->prepare("UPDATE bed SET BedRentAmount = ?, AgentID = ?, BedStatus = ? WHERE BedID = ?");

        if ($stmt_bed === false) {
            $error .= "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            $stmt_bed->bind_param("ssss", $bed_rent_amount, $agent_id, $bed_status, $bed_id);

            // Check if bed update is successful
            if (!$stmt_bed->execute()) {
                $error .= "<p class='alert alert-warning'>Error updating bed $bed_id in database: " . $stmt_bed->error . "</p>";
            } else {
                $msg .= "<p class='alert alert-success'>BedID: $bed_id, Rent: $bed_rent_amount, Status: $bed_status</p>";
            }

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
    <link href="/img/favicon.ico" rel="icon">
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
                            <h3 class="page-title">Edit Bed Rent</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Bed</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Bed Rent Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Edit Bed Rent</h4>
                                <a href="bedtable.php" class="btn btn-primary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="bed-form" id="bedForm">
                                <div class="card-body">
                                    <h5 class="card-title">Bed Details</h5>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Bed ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $bed_data['BedID']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $bed_data['RoomID']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Bed No</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $bed_data['BedNo']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" value="<?php echo $bed_data['UnitID']; ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Bed Rent Amount</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="bed_rent_amount" required placeholder="Enter Bed Rent Amount" value="<?php echo $bed_data['BedRentAmount']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Agent</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="agent_id">
                                                        <option value="">No Agent</option> <!-- Allow setting agent to null -->
                                                        <?php foreach ($agents as $agent) : ?>
                                                            <option value="<?php echo $agent['AgentID']; ?>" <?php echo ($agent['AgentID'] == $bed_data['AgentID']) ? 'selected' : ''; ?>>
                                                                <?php echo $agent['AgentName']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Bed Status</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="bed_status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Available" <?php echo ($bed_data['BedStatus'] == 'Available') ? 'selected' : ''; ?>>Available</option>
                                                        <option value="Rented" <?php echo ($bed_data['BedStatus'] == 'Rented') ? 'selected' : ''; ?>>Rented</option>
                                                        <option value="Occupied" <?php echo ($bed_data['BedStatus'] == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                                                        <option value="Unavailable" <?php echo ($bed_data['BedStatus'] == 'Unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                                        <option value="Staff" <?php echo ($bed_data['BedStatus'] == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <input type="hidden" name="bed_id" value="<?php echo $bed_data['BedID']; ?>">
                                            <input type="hidden" name="update" value="1">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="update">Update</button>
                                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Bed Rent Form -->
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
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
</body>
</html>
