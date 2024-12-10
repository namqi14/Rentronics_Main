<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpWord\TemplateProcessor;

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: /rentronics/index.php");
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
        SELECT Bed.*, Unit.FloorPlan, Unit.UnitNo, Unit.PropertyID, Room.RoomID, Room.RoomNo 
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
}

// Update bed rent amount and generate document when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) { 
    // Collect form data
    $bed_rent_amount = $_POST['bed_rent_amount'];
    $bed_id = $_POST['bed_id']; 

    // Get the original rent amount from the database
    $original_rent_amount = $bed_data['BedRentAmount'];

    // Validate bed_rent_amount
    if (!is_numeric($bed_rent_amount)) {
        $error = "Please enter a valid number for Rental Amount.";
    } elseif ($bed_rent_amount < $original_rent_amount) { 
        $error = "Rental Amount cannot be less than the original amount: " . $original_rent_amount;
    }

    // Insert tenant information into the tenant table
    if (empty($error)) {
        $tenantName = $_POST['tenant_name'];
        $tenantPhoneNo = $_POST['mobile_number'];
        $tenantEmail = $_POST['tenant_email']; 
        $rentStartDate = $_POST['rental_start_date'];
        $rentExpiryDate = date('Y-m-d', strtotime("$rentStartDate +1 year"));
        
        // Get the AgentEmail of the currently logged-in agent
        if (isset($_SESSION['auser']) && is_array($_SESSION['auser']) && isset($_SESSION['auser']['AgentEmail'])) { 
            $agentEmail = $_SESSION['auser']['AgentEmail'];
        } else {
            $error = "Agent information not found in the session.";
        }

        // Set bed_status to "booking"
        $bed_status = "booking";

        if (empty($error)) { 
            // Fetch AgentID based on AgentEmail
            $stmt_agent = $conn->prepare("SELECT AgentID FROM agent WHERE AgentEmail = ?");
            $stmt_agent->bind_param("s", $agentEmail);
            if ($stmt_agent->execute()) {
                $result_agent = $stmt_agent->get_result();
                if ($row_agent = $result_agent->fetch_assoc()) {
                    $agentID = $row_agent['AgentID'];
                } else {
                    $error = "Agent not found."; 
                }
                $stmt_agent->close();
            } else {
                $error = "Error fetching agent ID: " . $stmt_agent->error;
                $stmt_agent->close();
            }

            if (empty($error)) { 
                // Generate TenantID with format T-xxxx
                $tenantID = 'T' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

                // Insert into tenant table
                $stmt_tenant = $conn->prepare("
                    INSERT INTO tenant (TenantID, UnitID, RoomID, BedID, AgentID, TenantName, TenantPhoneNo, TenantEmail, RentStartDate, RentExpiryDate, TenantStatus) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt_tenant->bind_param("ssssssssss", $tenantID, $bed_data['UnitID'], $bed_data['RoomID'], $bed_data['BedID'], $agentID, $tenantName, $tenantPhoneNo, $tenantEmail, $rentStartDate, $rentExpiryDate); 
                if ($stmt_tenant->execute()) {
                    // ...
                } else {
                    $error = "Error inserting tenant data: " . $stmt_tenant->error;
                }
                $stmt_tenant->close();
            }
        }
    }

    // Insert tenancy agreement into the tenancyagreement table
    if (empty($error)) {
        $agreementID = 'agreement_' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $propertyDetails = $bed_data['UnitNo']; 
        $monthlyRent = $bed_rent_amount;
        $depositAmount = $bed_rent_amount; 
        $startDate = $_POST['rental_start_date'];
        $endDate = $rentExpiryDate; 

        // Get the path of the agreement file (this will be set after generating the document)
        $agreementPath =  "/../../tenantagreement/Tenancy_Agreement_$tenantName.docx";

        $stmt_agreement = $conn->prepare("
            INSERT INTO tenancyagreement (AgreementID, TenantID, PropertyDetails, MonthlyRent, DepositAmount, RentStartDate, RentExpiryDate, AgreementPath) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_agreement->bind_param("ssssssss", $agreementID, $tenantID, $propertyDetails, $monthlyRent, $depositAmount, $startDate, $endDate, $agreementPath);
        if (!$stmt_agreement->execute()) {
            $error = "Error inserting tenancy agreement data: " . $stmt_agreement->error;
        } else {
            $msg .= "<p class='alert alert-success'>Tenancy agreement inserted successfully.</p>";
        }
        $stmt_agreement->close();
    }

    // Generate Word document after updating rent
    if (empty($error)) {
        $tenantName = $_POST['tenant_name'];
        $passport = $_POST['ic_passport'];
        $address = $_POST['address']; 
        $unitNumber = $_POST['bed_number_display'];
        $monthlyRent = $bed_rent_amount;
        $startDate = $_POST['rental_start_date'];
        $endDate = date('Y-m-d', strtotime("$startDate +1 year"));

        // Get the room number 
        $roomNumber = $bed_data['RoomNo']; 

        // Get the address based on the unit number
        $rentAddress = rentAddress($unitNumber); 

        // Load the Word template
        $templatePath = __DIR__ . "/../../templates/tenancy_agreement_template.docx";

        if (!file_exists($templatePath)) {
            $error = "Tenancy agreement template not found.";
        } else {
            // Create TemplateProcessor instance
            $templateProcessor = new TemplateProcessor($templatePath);

            // Replace placeholders in the template with actual data
            $templateProcessor->setValue('{{Name}}', $tenantName);
            $templateProcessor->setValue('{{IC Passport}}', $passport);
            $templateProcessor->setValue('{{Address}}', $address);
            $templateProcessor->setValue('{{Unit}}', $unitNumber);
            $templateProcessor->setValue('{{Room}}', $roomNumber); 
            $templateProcessor->setValue('{{Start Date}}', $startDate);
            $templateProcessor->setValue('{{End Date}}', $endDate);
            $templateProcessor->setValue('{{RentAddress1}}', $rentAddress[0]);
            $templateProcessor->setValue('{{RentAddress2}}', $rentAddress[1]);
            $templateProcessor->setValue('{{RentAddress3}}', $rentAddress[2]);

            // Save the generated document  
            $outputFile = __DIR__ . "/../../tenantagreement/Tenancy_Agreement_$tenantName.docx"; 

            // Check if the directory exists and is writable
            $outputDir = dirname($outputFile); 
            if (!is_dir($outputDir) || !is_writable($outputDir)) {
                $error = "Output directory does not exist or is not writable: " . $outputDir;
            } else {
                if ($templateProcessor->saveAs($outputFile)) {
                    $msg =  "File generated successfully!";
                } else {
                    $error = "File generation failed!";
                }
            }
        } 

        // Send the generated document via email
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;                   
            $mail->Username = 'accs.sparta@gmail.com'; 
            $mail->Password = 'ptcu geln jnyz mqdo';   
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;                       


            $mail->setFrom('accs.sparta@gmail.com', 'Rentronics'); // Replace with a valid "From" address 
            $mail->addAddress($tenantEmail, $tenantName);            

            // Attachments
            $mail->addAttachment($outputFile, 'TenancyAgreement.docx');

            // Content
            $mail->isHTML(true);                                  
            $mail->Subject = 'Your Tenancy Agreement';
            $mail->Body    = 'Dear ' . $tenantName . ',<br><br>Please find attached your tenancy agreement.<br><br>Regards,<br>Rentronics';

            $mail->send();
            $msg .= "<p class='alert alert-success'>Tenancy agreement has been sent to your email.</p>"; 
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    // Redirect to bookingcheckout.php after a delay if no errors
    if (empty($error)) {
        $_SESSION['msg'] = $msg;
        exit(); // Make sure to exit after echoing the JavaScript
    }
}

$propertyID = $bed_data['PropertyID'];
$unitID = $bed_data['UnitID'];
$floorPlan = $bed_data['FloorPlan'];

$floorPlanURL = $floorPlan . "?propertyID=" . $propertyID . "&unitID=" . $unitID;

// PHP implementation of rentAddress function
function rentAddress($unitNo) {
    $address = [];

    switch ($unitNo) {
        case "PV9, C-15-10":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            return $address;
        case "PV9, C-16-09":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            return $address;
        case "Cyber, E-22-B":
            $address[0] = "CYBERIA SMARTHOMES,";
            $address[1] = "63000";
            $address[2] = "CYBERJAYA";
            return $address;
        // ... add all your other case statements here ...

        default:
            // Handle cases where the unit number is not found
            $address[0] = "Address Line 1"; 
            $address[1] = "Address Line 2";
            $address[2] = "Address Line 3";
            return $address;
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
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
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
                                <a href="../<?php echo $floorPlanURL; ?>" class="btn btn-secondary float-right">Back</a>
                            </div>
                            <form method="post" action="" name="tenant-booking-form" id="tenantBookingForm"
                                enctype="multipart/form-data">
                                <div class="card-body">
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
                                                    value="<?php echo $bed_data['BedNo']; ?>" readonly>
                                                <input type="hidden" name="bed_id"
                                                    value="<?php echo $bed_data['BedID']; ?>">
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
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel"
        aria-hidden="true">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous">
    </script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- PHP condition to include JavaScript code to show the modal -->
    <script>
    $(document).ready(function() {
        var hasMsg = '<?php echo $msg ? "1" : "0"; ?>';
        var hasError = '<?php echo $error ? "1" : "0"; ?>';

        if (hasMsg === "1" || hasError === "1") {
            $('#messageModal').modal('show');
        }
    });
    </script>
    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
</body>

</html>