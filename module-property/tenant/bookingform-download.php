<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Clear any existing error messages
$error = '';
$msg = '';

require __DIR__ . '/process-booking.php';

// Override the error from process-booking.php if it's the property ID error
if (isset($error) && $error === "No property found with the provided ID.") {
    $error = '';
}

// Clear the generated agreements from session if this is a fresh page load (not a form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['keep_results'])) {
    unset($_SESSION['generated_agreements']);
}

// Get agent ID from session
$agentID = $_SESSION['auser']['AgentID'] ?? '';

// Fetch all tenants for this agent
$tenantQuery = "
    SELECT 
        t.TenantID,
        t.TenantName,
        t.TenantPhoneNo,
        t.TenantEmail,
        t.RentStartDate,
        t.RentExpiryDate,
        t.RentalType,
        t.UnitID,
        t.RoomID,
        t.BedID,
        p.PropertyName,
        u.UnitNo,
        r.RoomNo,
        b.BedNo,
        CASE 
            WHEN t.BedID IS NOT NULL THEN b.BedRentAmount
            WHEN t.RoomID IS NOT NULL THEN r.BaseRentAmount
            ELSE 0
        END as RentAmount
    FROM tenant t
    INNER JOIN unit u ON t.UnitID = u.UnitID
    INNER JOIN property p ON u.PropertyID = p.PropertyID
    LEFT JOIN room r ON t.RoomID = r.RoomID
    LEFT JOIN bed b ON t.BedID = b.BedID
    WHERE t.AgentID = ?
    AND t.TenantStatus IN ('Active', 'Booked', 'Rented')
";

$stmt = $conn->prepare($tenantQuery);
$stmt->bind_param("s", $agentID);
$stmt->execute();
$result = $stmt->get_result();
$tenants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Get selected tenant data
    $selectedTenantID = $_POST['tenant_id'] ?? '';
    $selectedTenant = null;
    
    foreach ($tenants as $tenant) {
        if ($tenant['TenantID'] === $selectedTenantID) {
            $selectedTenant = $tenant;
            break;
        }
    }

    if ($selectedTenant) {
        try {
            // Create safe tenant name and directory
            $safeTenantName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selectedTenant['TenantName']);
            $tenantDirectory = __DIR__ . "/../../tenant_documents/" . $safeTenantName;
            
            if (!is_dir($tenantDirectory)) {
                mkdir($tenantDirectory, 0755, true);
            }

            // Generate agreement file names
            $agreementFileName = "agreement_" . $safeTenantName . "_" . date('d-m-Y');
            $docxPath = $tenantDirectory . "/" . $agreementFileName . ".docx";
            $pdfPath = $agreementFileName . ".pdf";
            $agreementPathPdf = $tenantDirectory . "/" . $pdfPath;

            // Process tenant signature from form
            $tenantSignature = $_POST['tenant_signature'] ?? '';
            $signatureFileName = "signature_" . $safeTenantName . "_" . date('d-m-Y') . ".png";
            $signaturePath = $tenantDirectory . "/" . $signatureFileName;
            
            if (!empty($tenantSignature)) {
                // Decode and save the signature image
                $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $tenantSignature));
                file_put_contents($signaturePath, $signatureImage);
            } else {
                $error = "Tenant signature is required.";
                // Stop processing if there's an error
                throw new Exception("Tenant signature is required.");
            }
            
            // Prepare data for agreement generation
            $agreementData = [
                'tenantName' => $selectedTenant['TenantName'],
                'passport' => $selectedTenant['TenantID'],
                'address' => '', // You might want to add this to your tenant table
                'unitNumber' => $selectedTenant['UnitNo'],
                'roomNumber' => $selectedTenant['RoomNo'] ?? '',
                'startDate' => $selectedTenant['RentStartDate'],
                'endDate' => $selectedTenant['RentExpiryDate'],
                'docxPath' => $docxPath,
                'pdfPath' => $pdfPath,
                'agreementDirectory' => $tenantDirectory,
                'signaturePath' => $signaturePath,
                'agreementPaths' => []
            ];

            // Create AgreementProcessor instance and generate agreements
            $agreementProcessor = new AgreementProcessor($conn);
            $generatedPaths = $agreementProcessor->generateAgreement($agreementData);

            // Store the paths for download
            $_SESSION['generated_agreements'] = $generatedPaths;
            $_SESSION['agreement_timestamp'] = time(); // Add timestamp for cache busting
            $msg = "Agreement generated successfully. You can now download the documents.";
            
            // Add a flag to the URL to prevent clearing the results on redirect
            header("Location: " . $_SERVER['PHP_SELF'] . "?keep_results=1&t=" . time() . "#download-section");
            exit;

        } catch (Exception $e) {
            $error = "Error processing agreement: " . $e->getMessage();
        }
    } else {
        $error = "Selected tenant not found.";
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
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">
    <style>
        /* Custom font size adjustments */
        body {
            font-size: 16px;
        }
        .page-title {
            font-size: 28px;
        }
        .card-title {
            font-size: 20px;
        }
        .form-label {
            font-size: 16px;
        }
        .form-select, .form-control {
            font-size: 16px;
        }
        .btn {
            font-size: 16px;
        }
        .btn-lg {
            font-size: 18px;
        }
        .card-header h4 {
            font-size: 22px;
        }
        .alert {
            font-size: 15px;
        }
        .form-text {
            font-size: 14px;
        }
        .signature-pad-container small {
            font-size: 14px;
        }
        h5.text-primary {
            font-size: 20px;
            margin-bottom: 15px;
        }
        .card-footer {
            font-size: 14px;
        }
        .breadcrumb {
            font-size: 14px;
        }
        
        /* Mobile responsiveness improvements */
        @media (max-width: 767px) {
            .container-fluid {
                padding: 0;
            }
            .page-wrapper {
                padding: 0 10px;
            }
            .page-title {
                font-size: 24px;
            }
            .card {
                margin-bottom: 15px;
            }
            .card-body {
                padding: 15px;
            }
            .form-group.row {
                margin-bottom: 0;
            }
            .form-group.row .col-form-label {
                padding-bottom: 5px;
                text-align: left;
            }
            .signature-pad-wrapper {
                width: 100% !important;
                max-width: 100% !important;
            }
            .btn-lg {
                padding: 10px 15px;
                font-size: 16px;
            }
            .card-footer {
                padding: 10px;
                flex-direction: column;
                align-items: flex-start;
            }
            .card-footer button {
                margin-top: 5px;
                align-self: flex-end;
            }
            .text-right {
                text-align: center !important;
            }
            canvas.signature-pad {
                height: 180px !important;
            }
        }
        
        /* Additional improvements for very small screens */
        @media (max-width: 480px) {
            .page-title {
                font-size: 20px;
            }
            .breadcrumb {
                font-size: 12px;
            }
            .card-header h4 {
                font-size: 18px;
            }
            .btn-lg {
                width: 100%;
                margin-bottom: 10px;
            }
            canvas.signature-pad {
                height: 150px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-white p-0">
        <?php include('../../nav_sidebar.php'); ?>

        <div class="page-wrapper">
            <div class="content container-fluid">
                <div class="page-header">
                    <div class="head row">
                        <div class="col">
                            <h3 class="page-title">Generate Agreement</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/rentronics/dashboardagent.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Generate Agreement</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="card-title mb-0"><i class="fas fa-file-signature me-2"></i>Generate Tenancy Agreement</h4>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($msg)): ?>
                                    <div class="alert alert-success"><?php echo $msg; ?></div>
                                <?php endif; ?>
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>

                                <form method="post" action="">
                                    <div class="form-group mb-4">
                                        <label for="tenant_id" class="form-label fw-bold"><i class="fas fa-user me-2"></i>Select Tenant:</label>
                                        <select class="form-select form-select-lg" id="tenant_id" name="tenant_id" required>
                                            <option value="">-- Select a tenant --</option>
                                            <?php foreach ($tenants as $tenant): ?>
                                                <option value="<?php echo htmlspecialchars($tenant['TenantID']); ?>">
                                                    <?php 
                                                    echo htmlspecialchars($tenant['TenantName']) . 
                                                         ' - ' . htmlspecialchars($tenant['PropertyName']) . 
                                                         ' Unit ' . htmlspecialchars($tenant['UnitNo']) .
                                                         (isset($tenant['RoomNo']) ? ' Room ' . htmlspecialchars($tenant['RoomNo']) : '') .
                                                         (isset($tenant['BedNo']) ? ' Bed ' . htmlspecialchars($tenant['BedNo']) : '');
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-muted mt-2" style="font-size: 15px;">
                                            <i class="fas fa-info-circle"></i> Select the tenant for whom you want to generate the tenancy agreement.
                                        </div>
                                    </div>
                                    <div class="form-section mt-4">
                                        <h5 class="text-primary"><i class="fas fa-signature me-2"></i>Digital Signature</h5>
                                        <div class="alert alert-info" style="font-size: 15px;">
                                            <i class="fas fa-info-circle me-2"></i> The tenant's signature will be used on the tenancy agreement. Please ensure the tenant signs below or have them sign in person.
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-sm-12 col-form-label fw-bold">Tenant Signature</label>
                                            <div class="col-lg-9 col-sm-12">
                                                <div class="signature-pad-container">
                                                    <div class="signature-pad-wrapper" style="width: 100%; max-width: 600px;">
                                                        <div class="card shadow-sm">
                                                            <div class="card-body p-0">
                                                                <canvas id="tenantSignature" class="signature-pad" style="border: 1px solid #e0e0e0; border-radius: 4px; background-color: #fcfcfc; touch-action: none;"></canvas>
                                                                <input type="hidden" name="tenant_signature" id="tenantSignatureData">
                                                            </div>
                                                            <div class="card-footer bg-light d-flex justify-content-between align-items-center flex-wrap">
                                                                <small class="text-muted" style="font-size: 14px;">Sign above using mouse or touch</small>
                                                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" id="clearTenantSignature">
                                                                    <i class="fas fa-eraser me-1"></i> Clear Signature
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right mt-4 d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-success btn-lg" name="submit">
                                            <i class="fas fa-file-contract me-2"></i> Generate Agreement
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Add Download Section if agreements are generated -->
                 <?php if (isset($_SESSION['generated_agreements']) && !empty($_SESSION['generated_agreements'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-file-download me-2"></i>Download Generated Documents</h5>
                                <div class="alert alert-success" style="font-size: 16px;" id="download-section">
                                    <i class="fas fa-check-circle me-2"></i> Agreement successfully generated! You can now download the documents below.
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card mb-3 border-primary h-100 shadow-sm">
                                            <div class="card-body text-center p-3 p-md-4">
                                                <i class="fas fa-file-alt text-primary mb-3" style="font-size: 2.5rem;"></i>
                                                <h5 class="card-title" style="font-size: 18px;">Tenancy Rules</h5>
                                                <p class="card-text" style="font-size: 15px;">Download the rules and regulations for the tenancy.</p>
                                                <a href="/rentronics/tenant_documents/<?php echo $safeTenantName; ?>/<?php echo basename($_SESSION['generated_agreements']['rules']); ?>" 
                                                   class="btn btn-primary btn-lg w-100" 
                                                   download="tenancy_rules<?php echo strpos($_SESSION['generated_agreements']['rules'], '.docx') !== false ? '.docx' : '.pdf'; ?>">
                                                    <i class="fas fa-download me-2"></i> Download Rules
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3 border-primary h-100 shadow-sm">
                                            <div class="card-body text-center p-3 p-md-4">
                                                <i class="fas fa-file-contract text-primary mb-3" style="font-size: 2.5rem;"></i>
                                                <h5 class="card-title" style="font-size: 18px;">Tenancy Agreement</h5>
                                                <p class="card-text" style="font-size: 15px;">Download the official tenancy agreement document.</p>
                                                <a href="/rentronics/tenant_documents/<?php echo $safeTenantName; ?>/<?php echo basename($_SESSION['generated_agreements']['agreement']); ?>" 
                                                   class="btn btn-primary btn-lg w-100" 
                                                   download="tenancy_agreement<?php echo strpos($_SESSION['generated_agreements']['agreement'], '.docx') !== false ? '.docx' : '.pdf'; ?>">
                                                    <i class="fas fa-download me-2"></i> Download Agreement
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../js/main.js"></script>
    <script>
    // Handle loading screen and success messages
    document.addEventListener('DOMContentLoaded', function() {
        // Close any existing SweetAlert dialogs
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
        
        // Check if we were generating an agreement and now have results
        if (sessionStorage.getItem('generatingAgreement') === 'true') {
            // Check if we have success message
            if (document.querySelector('.alert-success') !== null) {
                // Clear the flag
                sessionStorage.removeItem('generatingAgreement');
                sessionStorage.removeItem('generationStartTime');
                
                // Scroll to the download section
                const downloadSection = document.querySelector('.alert-success');
                if (downloadSection) {
                    downloadSection.scrollIntoView({ behavior: 'smooth' });
                }
            } else {
                // Check if we've been waiting too long (over 60 seconds)
                const startTime = parseInt(sessionStorage.getItem('generationStartTime') || '0');
                const currentTime = Date.now();
                const elapsedTime = currentTime - startTime;
                
                if (elapsedTime > 60000) { // 60 seconds
                    // Clear the flag
                    sessionStorage.removeItem('generatingAgreement');
                    sessionStorage.removeItem('generationStartTime');
                    
                    // Show timeout message
                    Swal.fire({
                        title: 'Taking longer than expected',
                        html: '<div>The agreement generation is taking longer than expected.</div>' +
                              '<div class="mt-3">You can:</div>' +
                              '<ul class="text-start mt-2">' +
                              '  <li>Wait a bit longer - the process might still complete</li>' +
                              '  <li>Try again with a different browser</li>' +
                              '  <li>Contact support if the problem persists</li>' +
                              '</ul>',
                        icon: 'warning',
                        confirmButtonText: 'Try Again',
                        showCancelButton: true,
                        cancelButtonText: 'Close'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Reload the page to try again
                            window.location.reload();
                        }
                    });
                }
            }
        }
    });
    $(document).ready(function() {
        // Initialize signature pad with better styling
        const canvas = document.getElementById('tenantSignature');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: '#fcfcfc',
            penColor: '#000000',
            minWidth: 1,
            maxWidth: 2.5,
            velocityFilterWeight: 0.7
        });
        
        // Adjust canvas size
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const container = canvas.parentElement;
            canvas.width = container.clientWidth * ratio;
            
            // Adjust height based on screen size
            let canvasHeight = 200;
            if (window.innerWidth <= 480) {
                canvasHeight = 150;
            } else if (window.innerWidth <= 767) {
                canvasHeight = 180;
            }
            
            canvas.height = canvasHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Clear the canvas
        }
        
        // Initial resize
        resizeCanvas();
        
        // Resize on window resize
        window.addEventListener("resize", resizeCanvas);
        
        // Enhance tenant selection with select2 if available
        if ($.fn.select2) {
            $('#tenant_id').select2({
                placeholder: "-- Select a tenant --",
                allowClear: true,
                theme: "bootstrap"
            });
        }
        
        // Clear signature button
        $('#clearTenantSignature').on('click', function() {
            Swal.fire({
                title: 'Clear signature?',
                text: 'Are you sure you want to clear the signature?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    signaturePad.clear();
                }
            });
        });
        
        // Validate form before submitting
        $('form').on('submit', function(e) {
            e.preventDefault();
            
            if ($('#tenant_id').val() === '') {
                Swal.fire({
                    title: 'Tenant Required',
                    text: 'Please select a tenant before generating the agreement.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            
            if (signaturePad.isEmpty()) {
                Swal.fire({
                    title: 'Signature Required',
                    text: 'Please provide a signature before generating the agreement.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return false;
            }
            
            // Get signature as data URL and store in hidden input
            const signatureData = signaturePad.toDataURL();
            $('#tenantSignatureData').val(signatureData);
            
            // Store a flag in sessionStorage to indicate we're generating an agreement
            sessionStorage.setItem('generatingAgreement', 'true');
            sessionStorage.setItem('generationStartTime', Date.now());
            
            // Show loading indicator with progress updates and timeout handling
            const loadingAlert = Swal.fire({
                title: 'Generating Agreement',
                html: '<div class="mb-3">Please wait while we generate the agreement...</div>' +
                      '<div class="progress mb-2" style="height: 20px;">' +
                      '  <div id="generation-progress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>' +
                      '</div>' +
                      '<div id="generation-status" class="small text-muted">Initializing...</div>' +
                      '<div id="timeout-warning" class="small text-warning mt-3" style="display: none;">' +
                      '  <i class="fas fa-exclamation-triangle me-1"></i> ' +
                      '  This is taking longer than expected. Please be patient...' +
                      '</div>' +
                      '<div class="mt-3" style="display: none;" id="cancel-option">' +
                      '  <button type="button" class="btn btn-sm btn-outline-secondary" id="cancel-generation">Cancel</button>' +
                      '</div>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    // Simulate progress updates
                    const progressBar = document.getElementById('generation-progress');
                    const statusText = document.getElementById('generation-status');
                    const timeoutWarning = document.getElementById('timeout-warning');
                    const cancelOption = document.getElementById('cancel-option');
                    const cancelButton = document.getElementById('cancel-generation');
                    
                    // Add cancel button functionality
                    if (cancelButton) {
                        cancelButton.addEventListener('click', function() {
                            Swal.fire({
                                title: 'Cancel Generation?',
                                text: 'Are you sure you want to cancel the agreement generation?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, cancel it',
                                cancelButtonText: 'No, continue waiting'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Clear session storage flags
                                    sessionStorage.removeItem('generatingAgreement');
                                    sessionStorage.removeItem('generationStartTime');
                                    
                                    // Reload the page
                                    window.location.reload();
                                }
                            });
                        });
                    }
                    
                    const steps = [
                        { percent: 10, text: 'Preparing templates...' },
                        { percent: 25, text: 'Processing tenant information...' },
                        { percent: 40, text: 'Generating agreement document...' },
                        { percent: 60, text: 'Adding digital signature...' },
                        { percent: 75, text: 'Converting to PDF format...' },
                        { percent: 90, text: 'Finalizing documents...' },
                        { percent: 95, text: 'Almost done...' }
                    ];
                    
                    let currentStep = 0;
                    const updateProgress = () => {
                        if (currentStep < steps.length) {
                            const step = steps[currentStep];
                            progressBar.style.width = step.percent + '%';
                            progressBar.setAttribute('aria-valuenow', step.percent);
                            progressBar.textContent = step.percent + '%';
                            statusText.textContent = step.text;
                            currentStep++;
                            
                            // Calculate timing based on expected total time
                            const expectedTotalTime = 30000; // 30 seconds total expected time
                            const nextStepDelay = expectedTotalTime / (steps.length + 1);
                            setTimeout(updateProgress, nextStepDelay);
                            
                            // Show timeout warning after 20 seconds
                            if (currentStep === 4) {
                                setTimeout(() => {
                                    if (timeoutWarning) timeoutWarning.style.display = 'block';
                                    if (cancelOption) cancelOption.style.display = 'block';
                                }, 20000);
                            }
                        }
                    };
                    
                    // Start progress updates
                    setTimeout(updateProgress, 500);
                }
            });
            
            // Submit the form
            this.submit();
        });
    });
    </script>
</body>
</html> 