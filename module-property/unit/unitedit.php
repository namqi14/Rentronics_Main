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

// Correctly retrieve UnitID from the URL
$unit_id = isset($_GET['UnitID']) ? $_GET['UnitID'] : '';

if (!$unit_id) {
    $error = "No UnitID provided.";
} else {
    // Fetch current unit data
    $stmt_unit = $conn->prepare("SELECT * FROM unit WHERE UnitID = ?");
    $stmt_unit->bind_param("s", $unit_id);
    if ($stmt_unit->execute()) {
        $result = $stmt_unit->get_result();
        $unit_data = $result->fetch_assoc();
        $stmt_unit->close();
    } else {
        $error = "Error fetching unit details: " . $stmt_unit->error;
        $stmt_unit->close();
    }

    // Fetch list of agents if necessary (not related to units but kept for reference)
    $agents = [];
    $result = $conn->query("SELECT AgentID, AgentName FROM agent");
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $unit_no = $_POST['unit_no'];
    $property_id = $_POST['property_id'];
    $floor_plan = $_POST['floor_plan']; // Now a local file path
    $investor = $_POST['investor'];
    $unit_id = $_POST['unit_id'];

    if (isset($unit_no, $property_id, $floor_plan, $investor)) {
        // Prepare SQL query to update unit details
        $stmt_unit = $conn->prepare("UPDATE unit SET UnitNo = ?, PropertyID = ?, FloorPlan = ?, Investor = ? WHERE UnitID = ?");

        if ($stmt_unit === false) {
            $error .= "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            $stmt_unit->bind_param("sssss", $unit_no, $property_id, $floor_plan, $investor, $unit_id);

            // Check if unit update is successful
            if (!$stmt_unit->execute()) {
                $error .= "<p class='alert alert-warning'>Error updating unit $unit_id in database: " . $stmt_unit->error . "</p>";
            } else {
                $msg .= "<p class='alert alert-success'>UnitID: $unit_id, Unit No: $unit_no, FloorPlan: $floor_plan</p>";
            }

            $stmt_unit->close();
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
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">
    <style>
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
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
                        <h3 class="page-title">Edit Unit Details</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Unit</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="row">
                <!-- Unit Edit Form -->
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Edit Unit</h4>
                            <a href="unittable.php" class="btn btn-primary float-right">Back</a>
                        </div>
                        <form method="post" action="" name="unit-form" id="unitForm">
                            <div class="card-body">
                                <h5 class="card-title">Unit Details</h5>
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Unit ID</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" value="<?php echo $unit_data['UnitID']; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Property ID</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="property_id" required placeholder="Enter Property ID" value="<?php echo $unit_data['PropertyID']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Unit No</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="unit_no" required placeholder="Enter Unit Number" value="<?php echo $unit_data['UnitNo']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Floor Plan</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="floor_plan" required placeholder="Enter local path for the Floor Plan" value="<?php echo $unit_data['FloorPlan']; ?>">
                                                <?php if ($unit_data['FloorPlan']) : ?>
                                                    <p>Current Floor Plan: <a href="<?php echo $unit_data['FloorPlan']; ?>" target="_blank">View File</a></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Investor</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="investor" required placeholder="Enter Investor Name" value="<?php echo $unit_data['Investor']; ?>">
                                            </div>
                                        </div>
                                        <input type="hidden" name="unit_id" value="<?php echo $unit_data['UnitID']; ?>">
                                        <input type="hidden" name="update" value="1">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" name="update">Update</button>
                                <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Unit Edit Form -->
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
