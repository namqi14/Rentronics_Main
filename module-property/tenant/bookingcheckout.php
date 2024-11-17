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
$bed_id = isset($_GET['bedID']) ? $_GET['bedID'] : '';

if (!$bed_id) {
    $error = "No BedID provided.";
} else {
    // Fetch current bed data and floor plan information
    $stmt_bed = $conn->prepare("
        SELECT Bed.*, Unit.FloorPlan, Unit.UnitNo, Unit.PropertyID 
        FROM Bed 
        INNER JOIN Room ON Bed.RoomID = Room.RoomID
        INNER JOIN Unit ON Room.UnitID = Unit.UnitID
        WHERE Bed.BedID = ?
    ");
    $stmt_bed->bind_param("s", $bed_id);
    if ($stmt_bed->execute()) {
        $result = $stmt_bed->get_result();
        $bed_data = $result->fetch_assoc();
        $stmt_bed->close();
    } else {
        $error = "Error fetching bed details: " . $stmt_bed->error;
        $stmt_bed->close();
    }

    // Fetch list of agents (if required)
    $agents = [];
    $result = $conn->query("SELECT AgentID, AgentName FROM agent");
    while ($row = $result->fetch_assoc()) {
        $agents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $insert_amount = $_POST['insert_amount'];
    
    if (!empty($insert_amount)) {
        $msg = "<p class='alert alert-success'>Amount entered: RM $insert_amount</p>";
    } else {
        $error = "<p class='alert alert-warning'>Please enter the amount to pay.</p>";
    }
}

$propertyID = $bed_data['PropertyID'];
$unitID = $bed_data['UnitID'];
$floorPlan = $bed_data['FloorPlan'];

$floorPlanURL = $floorPlan . "?propertyID=" . $propertyID . "&unitID=" . $unitID;
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
                        <h3 class="page-title">Checkout</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Checkout</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="row">
                <!-- Checkout Form -->
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Checkout</h4>
                        </div>
                        <form method="post" action="" name="tenant-booking-form" id="tenantBookingForm" enctype="multipart/form-data">
                            <div class="card-body">
                                <h5 class="card-title text-center">CHECKOUT</h5>
                                <div class="p-3 mb-4" style="background-color: #f5f5f5;">
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label font-weight-bold">Unit:</label>
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control-plaintext" name="unit" value="PV2 B-16-3A" readonly>
                                        </div>
                                        <label class="col-lg-3 col-form-label font-weight-bold">Room:</label>
                                        <div class="col-lg-3">
                                            <input type="text" class="form-control" name="room" placeholder="">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-lg-3 col-form-label font-weight-bold">Bed No:</label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" name="bed_no" placeholder="">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group row mb-1">
                                        <label class="col-lg-6 col-form-label">Deposit 1 Month:</label>
                                        <div class="col-lg-6 text-right">
                                            <input type="text" class="form-control-plaintext text-right" name="deposit" value="RM 250" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-lg-6 col-form-label">Advance Rental:</label>
                                        <div class="col-lg-6 text-right">
                                            <input type="text" class="form-control-plaintext text-right" name="advance_rental" value="RM 250" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-lg-6 col-form-label">Processing Fee:</label>
                                        <div class="col-lg-6 text-right">
                                            <input type="text" class="form-control-plaintext text-right" name="processing_fee" value="RM 50" readonly>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group row">
                                        <label class="col-lg-6 col-form-label font-weight-bold">Total:</label>
                                        <div class="col-lg-6 text-right">
                                            <input type="text" class="form-control-plaintext font-weight-bold text-right" name="total_amount" value="RM 550" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group text-center mb-4">
                                    <label for="insertAmount" class="d-block">Insert Amount To Pay</label>
                                    <input type="text" id="insertAmount" class="form-control d-inline-block w-50" name="insert_amount" placeholder="">
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn-proceed btn-primary px-5 py-2" name="submit">PAY</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /Checkout Form -->
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
