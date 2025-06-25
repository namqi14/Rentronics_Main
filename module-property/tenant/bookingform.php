<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/process-booking.php';

// Get property ID from URL
$bed_id = isset($_GET['bedID']) ? $_GET['bedID'] : '';
$room_id = isset($_GET['roomID']) ? $_GET['roomID'] : '';


// Fetch property details
if ($bed_id || $room_id) {
    $stmt = $conn->prepare("
        SELECT 
            b.BedID,
            b.BedNo,
            b.BaseRentAmount as BedRentAmount,
            r.RoomID,
            r.RoomNo,
            r.RoomRentAmount,
            u.UnitID,
            u.UnitNo
        FROM Room r
        INNER JOIN Unit u ON r.UnitID = u.UnitID
        LEFT JOIN Bed b ON b.RoomID = r.RoomID
        WHERE " . ($bed_id ? "b.BedID = ?" : "r.RoomID = ?")
    );

    $id_to_bind = $bed_id ?: $room_id;
    $stmt->bind_param("s", $id_to_bind);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $property_data = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = "Error fetching property details: " . $stmt->error;
    }
}
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
                                            <label class="col-lg-3 col-form-label">
                                                <?php echo isset($_GET['bedID']) ? 'Bed Number' : 'Room Number'; ?>
                                            </label>
                                            <div class="col-lg-9">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="bed_number_display" 
                                                       value="<?php echo htmlspecialchars($property_data['BedNo'] ?? ''); ?>" 
                                                       readonly>
                                                
                                                <!-- Hidden fields for IDs -->
                                                <input type="hidden" 
                                                       name="bed_id" 
                                                       value="<?php echo htmlspecialchars($property_data['BedID'] ?? ''); ?>">
                                                <input type="hidden" 
                                                       name="room_id" 
                                                       value="<?php echo htmlspecialchars($property_data['RoomID'] ?? ''); ?>">
                                                <input type="hidden" 
                                                       name="unit_id" 
                                                       value="<?php echo htmlspecialchars($property_data['UnitID'] ?? ''); ?>">
                                                <!-- Added hidden field for rental type -->
                                                <input type="hidden" 
                                                       name="rental_type" 
                                                       value="Bed">
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
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="bed_rent_amount" 
                                                       required 
                                                       placeholder="Enter Rental Amount"
                                                       value="<?php echo htmlspecialchars($property_data[isset($_GET['bedID']) ? 'BedRentAmount' : 'RoomRentAmount'] ?? ''); ?>">
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
                                                <input type="file" class="form-control" name="bank_statement"
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

                                    <div class="form-section">
                                        <h5>Digital Signature</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Tenant Signature</label>
                                            <div class="col-lg-9">
                                                <div class="signature-pad-container">
                                                    <div class="signature-pad-wrapper" style="width: 100%; max-width: 600px;">
                                                        <canvas id="tenantSignature" class="signature-pad"></canvas>
                                                        <input type="hidden" name="tenant_signature" id="tenantSignatureData">
                                                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="clearTenantSignature">Clear</button>
                                                    </div>
                                                </div>
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
        <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true" data-bs-backdrop="static">
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
                        <button type="button" class="btn btn-secondary close-button" data-bs-dismiss="modal">Close</button>
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
        <!-- Add SignaturePad library -->
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Get the canvas and its container
                const canvas = document.getElementById('tenantSignature');
                const wrapper = canvas.parentElement;

                // Function to resize canvas
                function resizeCanvas() {
                    // Get the device pixel ratio
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);

                    // Get wrapper dimensions
                    const wrapperWidth = wrapper.offsetWidth;
                    const wrapperHeight = wrapperWidth * 0.5; // 2:1 aspect ratio

                    // Set canvas size with device pixel ratio considered
                    canvas.width = wrapperWidth * ratio;
                    canvas.height = wrapperHeight * ratio;

                    // Set display size
                    canvas.style.width = `${wrapperWidth}px`;
                    canvas.style.height = `${wrapperHeight}px`;

                    // Scale the context
                    const context = canvas.getContext("2d");
                    context.scale(ratio, ratio);
                }

                // Initial resize
                resizeCanvas();

                // Resize canvas when window is resized
                window.addEventListener('resize', resizeCanvas);

                // Initialize signature pad with proper options
                const signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgb(255, 255, 255)',
                    penColor: 'rgb(0, 0, 0)',
                    velocityFilterWeight: 0.7,
                    minWidth: 0.5,
                    maxWidth: 2.5,
                    throttle: 16, // Increase this value for better performance on mobile
                });

                // Clear signature button
                document.getElementById('clearTenantSignature').addEventListener('click', function() {
                    signaturePad.clear();
                });

                // Form submission
                document.getElementById('tenantBookingForm').addEventListener('submit', function(e) {
                    if (signaturePad.isEmpty()) {
                        e.preventDefault();
                        alert('Please provide your signature');
                        return false;
                    }

                    // Save signature data to hidden input
                    document.getElementById('tenantSignatureData').value = signaturePad.toDataURL();
                });

                // Handle window resize
                let resizeTimeout;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function() {
                        // Save current signature data
                        const data = signaturePad.toData();

                        // Resize canvas
                        resizeCanvas();

                        // Restore signature data
                        signaturePad.fromData(data);
                    }, 100);
                });

                // Get the close button element
                const closeButton = document.querySelector('.close-button');

                if (closeButton) {
                    closeButton.addEventListener('click', function() {
                        // Get the bedID from the hidden input field
                        const bedID = document.querySelector('input[name="bed_id"]').value;
                        // Redirect to bookingcheckout.php with bedID parameter
                        window.location.href = 'bookingcheckout.php?bedID=' + bedID;
                    });
                }
            });
        </script>
</body>

</html>