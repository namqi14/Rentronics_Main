<?php
require __DIR__ . '/process-booking.php';

// Add this at the top to fetch tenant details
if (isset($_GET['bedID'])) {
    $bedID = $_GET['bedID'];
    $query = "SELECT b.*, r.RoomNo, u.UnitNo, p.PropertyName, 
              t.TenantName, t.TenantPhoneNo, t.TenantEmail,
              t.RentStartDate, t.RentExpiryDate, t.TenantStatus,
              a.AgentName
              FROM Bed b
              LEFT JOIN Room r ON b.RoomID = r.RoomID
              LEFT JOIN Unit u ON r.UnitID = u.UnitID
              LEFT JOIN Property p ON u.PropertyID = p.PropertyID
              LEFT JOIN Tenant t ON b.BedID = t.BedID
              LEFT JOIN Agent a ON t.AgentID = a.AgentID
              WHERE b.BedID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $bedID);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
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
                            <h3 class="page-title">Tenant Overview</h3>
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
                                    <?php if (isset($data)): ?>
                                        <div class="form-section">
                                            <h5>Bed Information</h5>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Bed Number</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['BedNo']); ?></p>
                                                </div>
                                            </div>
                                            <!-- <div class="row mb-3">
                                                <label class="col-lg-3">Room Number</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['RoomNo']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Unit Number</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['UnitNo']); ?></p>
                                                </div>
                                            </div> -->
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Status</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static">
                                                        <span class="badge <?php echo $data['BedStatus'] == 'Rented' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo htmlspecialchars($data['BedStatus']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Rental Amount</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static">RM <?php echo number_format($data['BedRentAmount'], 2); ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($data['TenantName']): ?>
                                        <div class="form-section">
                                            <h5>Tenant Information</h5>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Tenant Name</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['TenantName']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Phone Number</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['TenantPhoneNo']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Email</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static"><?php echo htmlspecialchars($data['TenantEmail']); ?></p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Rental Period</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static">
                                                        <?php 
                                                        echo date('d/m/Y', strtotime($data['RentStartDate'])) . ' - ' . 
                                                             date('d/m/Y', strtotime($data['RentExpiryDate'])); 
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <label class="col-lg-3">Agent Name</label>
                                                <div class="col-lg-9">
                                                    <p class="form-control-static">
                                                        <?php echo isset($data['AgentName']) ? htmlspecialchars($data['AgentName']) : 'N/A'; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            No information found for this bed.
                                        </div>
                                    <?php endif; ?>
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