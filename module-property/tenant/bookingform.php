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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $bed_rent_amount = $_POST['bed_rent_amount'];
    $bed_id = $_POST['bed_id'];
    $agent_id = !empty($_POST['agent_id']) ? $_POST['agent_id'] : NULL; // Allow null agent
    $bed_status = $_POST['bed_status'];

    if (isset($bed_rent_amount, $bed_id, $bed_status)) {
        // Prepare SQL query to update bed rent amount, agent (nullable), and bed status
        $stmt_bed = $conn->prepare("UPDATE Bed SET BedRentAmount = ?, AgentID = ?, BedStatus = ? WHERE BedID = ?");
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

        .page-wrapper {
            background-color: #e3ecf5;
        }

        .head {
            padding-bottom: 0;
        }

        .breadcrumb {
            margin-bottom: 0;
        }

        .form-section {
            margin-bottom: 20px;
        }

        .form-section h5 {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* .form-group {
            margin-bottom: 15px;
        } */

        .form-control {
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-control:focus {
            border-color: #0066cc;
            box-shadow: 0 0 5px rgba(0, 102, 204, 0.5);
        }

        .form-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .card-header {
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }

        .form-container label {
            font-weight: bold;
        }

        .form-control {
            width: 100% !important;
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
                    <div class="head row">
                        <div class="col">
                            <h3 class="page-title">Booking Overview</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/rentronics/dashboardagent.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Bed</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card form-container">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Tenant Form</h4>
                                <a href="../<?php echo $floorPlanURL; ?>" class="btn btn-secondary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="tenant-booking-form" id="tenantBookingForm" enctype="multipart/form-data">
                                <div class="card-body">
                                    <div class="form-section">
                                        <h5>Personal Information</h5>
                                        <div class="form-group row">
                                            <label class="col-12 col-md-3 col-form-label">Tenant Name</label>
                                            <div class="col-12 col-md-9">
                                                <input type="text" class="form-control" name="tenant_name" required placeholder="Enter Tenant Name">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">I/C or Passport</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="ic_passport" required placeholder="Enter I/C or Passport Number">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Address</label>
                                            <div class="col-lg-9">
                                                <textarea name="address" class="form-control" rows="3" required placeholder="Enter Address"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Booking Details</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Bed Number</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="bed_number_display" value="<?php echo $bed_data['BedNo']; ?>" readonly>
                                                <input type="hidden" name="bed_id" value="<?php echo $bed_data['BedID']; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rental Start Date</label>
                                            <div class="col-lg-9">
                                                <input type="date" class="form-control" name="rental_start_date" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rental Amount</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="rental_amount" required placeholder="Enter Rental Amount">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Documents</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Copy of I/C or Passport</label>
                                            <div class="col-lg-9">
                                                <input type="file" class="form-control" name="ic_passport_file" required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Copy of Bank Statement or Bills</label>
                                            <div class="col-lg-9">
                                                <input type="file" class="form-control" name="bank_statement_file" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Contact Information</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Mobile Number</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="mobile_number" required placeholder="Enter Mobile Number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary" name="submit">Submit</button>
                                        <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
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