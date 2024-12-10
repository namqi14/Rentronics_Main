<?php
include 'process-booking.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Your existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentronics</title>
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">
    <link href="../css/booking-form.css" rel="stylesheet">
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
                                <a href="../<?php echo isset($floorPlanURL) ? htmlspecialchars($floorPlanURL) : '#'; ?>"
                                    class="btn btn-secondary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="tenant-booking-form" id="tenantBookingForm"
                                enctype="multipart/form-data">
                                <div class="card-body">
                                    <!-- Display Success or Error Messages -->
                                    <?php 
                                        if (!empty($msg)) {
                                            echo "<div class='alert alert-success'>{$msg}</div>";
                                        }
                                        if (!empty($error)) {
                                            echo "<div class='alert alert-danger'>{$error}</div>";
                                        }
                                    ?>

                                    <div class="form-section">
                                        <h5>Personal Information</h5>
                                        <div class="form-group row">
                                            <label class="col-12 col-md-3 col-form-label">Tenant Name</label>
                                            <div class="col-12 col-md-9">
                                                <input type="text" class="form-control" name="tenant_name" required
                                                    placeholder="Enter Tenant Name">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">I/C or Passport</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="ic_passport" required
                                                    placeholder="Enter I/C or Passport Number">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Address</label>
                                            <div class="col-lg-9">
                                                <textarea name="address" class="form-control" rows="3" required
                                                    placeholder="Enter Address"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Booking Details</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Bed Number</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="bed_number_display"
                                                    value="<?php echo isset($bed_data['BedNo']) ? htmlspecialchars($bed_data['BedNo']) : ''; ?>"
                                                    readonly>
                                                <input type="hidden" name="bed_id"
                                                    value="<?php echo isset($bed_data['BedID']) ? htmlspecialchars($bed_data['BedID']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rental Start Date</label>
                                            <div class="col-lg-9">
                                                <input type="date" class="form-control" name="rental_start_date"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rental Amount</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="bed_rent_amount" required
                                                    placeholder="Enter Rental Amount">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Documents</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Copy of I/C or Passport</label>
                                            <div class="col-lg-9">
                                                <input type="file" class="form-control" name="ic_passport_file"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Copy of Bank Statement or
                                                Bills</label>
                                            <div class="col-lg-9">
                                                <input type="file" class="form-control" name="bank_statement_file"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5>Contact Information</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Mobile Number</label>
                                            <div class="col-lg-9">
                                                <input type="text" class="form-control" name="mobile_number" required
                                                    placeholder="Enter Mobile Number">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Email</label>
                                            <div class="col-lg-9">
                                                <input type="email" class="form-control" name="tenant_email" required
                                                    placeholder="Enter Tenant Email">
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

        <!-- Message Modal -->
        <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="messageModalLabel">Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ensure jQuery is loaded first -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Other JS scripts -->
        <script>
        $(document).ready(function() {
            var hasMsg = '<?php echo !empty($msg) ? "1" : "0"; ?>';
            var hasError = '<?php echo !empty($error) ? "1" : "0"; ?>';

            if (hasMsg === "1" || hasError === "1") {
                var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
                messageModal.show();
            }
        });
        </script>
        <!-- Template Javascript -->
        <script src="../../js/main.js"></script>
</body>
</html>