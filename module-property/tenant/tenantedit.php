<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: /index.php");
    exit();
}

$error = '';
$msg = '';

// Get TenantID from URL
$tenant_id = isset($_GET['TenantID']) ? $_GET['TenantID'] : '';

if (!$tenant_id) {
    $error = "No TenantID provided.";
} else {
    // Fetch current tenant data
    $stmt_tenant = $conn->prepare("SELECT * FROM tenant WHERE TenantID = ?");
    $stmt_tenant->bind_param("s", $tenant_id);
    if ($stmt_tenant->execute()) {
        $result = $stmt_tenant->get_result();
        $tenant_data = $result->fetch_assoc();
        $stmt_tenant->close();
    } else {
        $error = "Error fetching tenant details: " . $stmt_tenant->error;
        $stmt_tenant->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Debug information
    error_log("POST data received: " . print_r($_POST, true));
    
    $old_tenant_id = $_POST['old_tenant_id'];
    $tenant_id = $_POST['tenant_id'];
    $tenant_name = $_POST['tenant_name'];
    $tenant_email = $_POST['tenant_email'];
    $tenant_phone = $_POST['tenant_phone'];
    $tenant_status = $_POST['tenant_status'];

    if (isset($tenant_id, $tenant_name, $tenant_email, $tenant_phone, $tenant_status)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            if ($old_tenant_id !== $tenant_id) {
                // Check if new ID exists
                $check_stmt = $conn->prepare("SELECT TenantID FROM tenant WHERE TenantID = ?");
                $check_stmt->bind_param("s", $tenant_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    throw new Exception("TenantID already exists. Please choose a different ID.");
                }
                $check_stmt->close();

                // Temporarily disable foreign key checks
                $conn->query('SET FOREIGN_KEY_CHECKS=0');

                // Update all tables
                $tables = ['tenant', 'paymenthistory', 'payment', 'deposit'];
                foreach ($tables as $table) {
                    if ($table === 'tenant') {
                        $stmt = $conn->prepare("UPDATE tenant SET TenantID = ?, TenantName = ?, TenantEmail = ?, TenantPhoneNo = ?, TenantStatus = ? WHERE TenantID = ?");
                        $stmt->bind_param("ssssss", $tenant_id, $tenant_name, $tenant_email, $tenant_phone, $tenant_status, $old_tenant_id);
                    } else {
                        $stmt = $conn->prepare("UPDATE $table SET TenantID = ? WHERE TenantID = ?");
                        $stmt->bind_param("ss", $tenant_id, $old_tenant_id);
                    }
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating $table: " . $stmt->error);
                    }
                    $stmt->close();
                }

                // Re-enable foreign key checks
                $conn->query('SET FOREIGN_KEY_CHECKS=1');

            } else {
                // If not changing ID, just update tenant details
                $stmt_tenant = $conn->prepare("UPDATE tenant SET TenantName = ?, TenantEmail = ?, TenantPhoneNo = ?, TenantStatus = ? WHERE TenantID = ?");
                if (!$stmt_tenant) {
                    throw new Exception("Error preparing tenant update: " . $conn->error);
                }
                $stmt_tenant->bind_param("sssss", $tenant_name, $tenant_email, $tenant_phone, $tenant_status, $old_tenant_id);
                if (!$stmt_tenant->execute()) {
                    throw new Exception("Error updating tenant: " . $stmt_tenant->error);
                }
            }

            // If everything is successful, commit the transaction
            if ($conn->commit()) {
                if ($old_tenant_id !== $tenant_id) {
                    $msg = "Tenant ID updated successfully from $old_tenant_id to $tenant_id!";
                } else {
                    $msg = "Tenant details updated successfully!";
                }
            } else {
                throw new Exception("Failed to commit transaction");
            }
            
        } catch (Exception $e) {
            // If there's an error, rollback the changes and re-enable foreign key checks
            $conn->query('SET FOREIGN_KEY_CHECKS=1');
            $conn->rollback();
            $error = "Update failed: " . $e->getMessage();
            error_log("Error during update: " . $e->getMessage());
        }
    } else {
        $error = "Incomplete form data. Please fill in all required fields.";
        error_log("Missing form data. Received: " . print_r($_POST, true));
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
                            <h3 class="page-title">Edit Tenant</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Tenant</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Tenant Edit Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Edit Tenant</h4>
                                <a href="tenanttable.php" class="btn btn-primary float-right">Back</a>
                            </div>
                            <?php if (!empty($msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                    <?php echo $msg; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            <form method="post" action="" name="tenant-edit-form" id="tenantEditForm" onsubmit="return true;">
                                <div class="card-body">
                                    <h5 class="card-title">Tenant Details</h5>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <input type="hidden" name="old_tenant_id" value="<?php echo htmlspecialchars($tenant_data['TenantID']); ?>">
                                            
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Tenant ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="tenant_id" required value="<?php echo htmlspecialchars($tenant_data['TenantID']); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Tenant Name</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="tenant_name" required value="<?php echo $tenant_data['TenantName']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Email</label>
                                                <div class="col-lg-9">
                                                    <input type="email" class="form-control" name="tenant_email" required value="<?php echo $tenant_data['TenantEmail']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Phone</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="tenant_phone" required value="<?php echo $tenant_data['TenantPhoneNo']; ?>">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Status</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="tenant_status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Rented" <?php echo ($tenant_data['TenantStatus'] == 'Rented') ? 'selected' : ''; ?>>Rented</option>
                                                        <option value="Moved Out" <?php echo ($tenant_data['TenantStatus'] == 'Moved Out') ? 'selected' : ''; ?>>Moved Out</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="update">Update</button>
                                    <button type="reset" class="btn btn-secondary" id="resetButton">Reset</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Tenant Edit Form -->
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

    <script>
    // Remove any existing submit event listeners
    $(document).ready(function() {
        $('#tenantForm').off('submit');
        
        // Prevent default notifications
        if (window.Notification) {
            Notification.requestPermission().then(function(permission) {
                // Do nothing, just to prevent other notifications
            });
        }
    });
    </script>

    <script>
    document.getElementById('resetButton').addEventListener('click', function(e) {
        e.preventDefault();
        // Store the old ID value
        var oldId = document.querySelector('input[name="old_tenant_id"]').value;
        
        // Reset the form
        document.getElementById('tenantEditForm').reset();
        
        // Restore the old ID value
        document.querySelector('input[name="old_tenant_id"]').value = oldId;
    });
    </script>
</body>
</html>
