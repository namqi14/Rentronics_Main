<?php
// process_booking.php

session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PhpOffice\PhpWord\TemplateProcessor;
use Dotenv\Dotenv;

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize variables
$error = '';
$msg = '';
$debugLog = __DIR__ . '/debug_log.txt'; // Path to debug log
$errorLog = __DIR__ . '/error_log.txt'; // Path to error log

// Function to log messages
function logMessage($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Function to pause execution for debugging
function pauseExecution($message) {
    echo "<pre>$message</pre>";
    exit();
}

// Check if DEBUG_MODE is enabled
$debugMode = filter_var(getenv('DEBUG_MODE'), FILTER_VALIDATE_BOOLEAN);

// Redirect to login if not authenticated
if (!isset($_SESSION['auser'])) {
    header("Location: /rentronics/index.php");
    exit();
}

// Retrieve BedID from URL
$bed_id = isset($_GET['bedID']) ? trim($_GET['bedID']) : '';

if (!$bed_id) {
    $error = "No BedID provided.";
    logMessage("Error: $error", $errorLog);
} else {
    // Fetch bed details
    $stmt_bed = $conn->prepare("
        SELECT Bed.*, Unit.UnitID, Unit.FloorPlan, Unit.UnitNo, Unit.PropertyID, Room.RoomID, Room.RoomNo 
        FROM Bed
        INNER JOIN Room ON Bed.RoomID = Room.RoomID
        INNER JOIN Unit ON Room.UnitID = Unit.UnitID
        WHERE Bed.BedID = ?
    ");
    if ($stmt_bed === false) {
        $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        logMessage("Error: $error", $errorLog);
    } else {
        $stmt_bed->bind_param("s", $bed_id);
        if ($stmt_bed->execute()) {
            $result = $stmt_bed->get_result();
            if ($result->num_rows > 0) {
                $bed_data = $result->fetch_assoc();
                logMessage("Fetched bed data for BedID $bed_id.", $debugLog);
            } else {
                $error = "No bed found with the provided BedID.";
                logMessage("Error: $error", $errorLog);
            }
            $stmt_bed->close();
        } else {
            $error = "Error executing query: " . $stmt_bed->error;
            logMessage("Error: $error", $errorLog);
            $stmt_bed->close();
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) { 
    // Collect and sanitize form data
    $bed_rent_amount   = isset($_POST['bed_rent_amount']) ? trim($_POST['bed_rent_amount']) : '';
    $bed_id_form       = isset($_POST['bed_id']) ? trim($_POST['bed_id']) : '';
    $tenantName        = isset($_POST['tenant_name']) ? trim($_POST['tenant_name']) : '';
    $tenantPhoneNo     = isset($_POST['mobile_number']) ? trim($_POST['mobile_number']) : '';
    $tenantEmail       = isset($_POST['tenant_email']) ? trim($_POST['tenant_email']) : '';
    $rentStartDate     = isset($_POST['rental_start_date']) ? trim($_POST['rental_start_date']) : '';
    $passport          = isset($_POST['ic_passport']) ? trim($_POST['ic_passport']) : '';
    $address           = isset($_POST['address']) ? trim($_POST['address']) : '';
    $unitNumber        = isset($_POST['bed_number_display']) ? trim($_POST['bed_number_display']) : '';

    // Log collected form data
    logMessage("Form Submission Data:", $debugLog);
    logMessage("Tenant Name: $tenantName", $debugLog);
    logMessage("Passport: $passport", $debugLog);
    logMessage("Start Date: $rentStartDate", $debugLog);
    logMessage("Bed Rent Amount: $bed_rent_amount", $debugLog);
    logMessage("Bed ID Form: $bed_id_form", $debugLog);
    logMessage("Tenant Phone No: $tenantPhoneNo", $debugLog);
    logMessage("Tenant Email: $tenantEmail", $debugLog);
    logMessage("Address: $address", $debugLog);
    logMessage("Unit Number: $unitNumber", $debugLog);

    // Validate bed_rent_amount
    if (!is_numeric($bed_rent_amount)) {
        $error = "Please enter a valid number for Rental Amount.";
        logMessage("Validation Error: $error", $errorLog);
    } elseif ($bed_rent_amount < $bed_data['BedRentAmount']) { 
        $error = "Rental Amount cannot be less than the original amount: " . $bed_data['BedRentAmount'];
        logMessage("Validation Error: $error", $errorLog);
    }

    // Proceed if no validation errors
    if (empty($error)) {
        // Calculate rent expiry date
        $rentExpiryDate = date('Y-m-d', strtotime("$rentStartDate +1 year"));
        logMessage("Calculated Rent Expiry Date: $rentExpiryDate", $debugLog);

        // Retrieve AgentEmail from session
        if (isset($_SESSION['auser']['AgentEmail'])) { 
            $agentEmail = $_SESSION['auser']['AgentEmail'];
            logMessage("Agent Email: $agentEmail", $debugLog);
        } else {
            $error = "Agent information not found in the session.";
            logMessage("Error: $error", $errorLog);
        }

        // Set bed_status to "booking"
        $bed_status = "booking";
        logMessage("Set Bed Status to: $bed_status", $debugLog);
    }

    // Insert tenant information
    if (empty($error)) {
        // Fetch AgentID based on AgentEmail
        $stmt_agent = $conn->prepare("SELECT AgentID FROM agent WHERE AgentEmail = ?");
        if ($stmt_agent === false) {
            $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
            logMessage("Error: $error", $errorLog);
        } else {
            $stmt_agent->bind_param("s", $agentEmail);
            if ($stmt_agent->execute()) {
                $result_agent = $stmt_agent->get_result();
                if ($row_agent = $result_agent->fetch_assoc()) {
                    $agentID = $row_agent['AgentID'];
                    logMessage("Fetched AgentID: $agentID for AgentEmail: $agentEmail", $debugLog);
                } else {
                    $error = "Agent not found."; 
                    logMessage("Error: $error", $errorLog);
                }
                $stmt_agent->close();
            } else {
                $error = "Error executing agent query: " . $stmt_agent->error;
                logMessage("Error: $error", $errorLog);
                $stmt_agent->close();
            }
        }
    }

    // Proceed with tenant insertion
    if (empty($error)) { 
        // Generate TenantID with format T-xxxx
        do {
            $tenantID = 'T' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            // Check for uniqueness
            $stmt_check = $conn->prepare("SELECT TenantID FROM tenant WHERE TenantID = ?");
            if ($stmt_check === false) {
                $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                logMessage("Error: $error", $errorLog);
                break;
            }
            $stmt_check->bind_param("s", $tenantID);
            if ($stmt_check->execute()) {
                $stmt_check->store_result();
                $isUnique = ($stmt_check->num_rows === 0);
                $stmt_check->close();
            } else {
                $error = "Error executing TenantID uniqueness check: " . $stmt_check->error;
                $stmt_check->close();
                logMessage("Error: $error", $errorLog);
                break;
            }
        } while (!$isUnique);

        logMessage("Generated TenantID: $tenantID", $debugLog);

        if ($isUnique) {
            // Insert into tenant table
            $stmt_tenant = $conn->prepare("
                INSERT INTO tenant (TenantID, UnitID, RoomID, BedID, AgentID, TenantName, TenantPhoneNo, TenantEmail, RentStartDate, RentExpiryDate, TenantStatus) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            if ($stmt_tenant === false) {
                $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                logMessage("Error: $error", $errorLog);
            } else {
                $stmt_tenant->bind_param("ssssssssss", $tenantID, $bed_data['UnitID'], $bed_data['RoomID'], $bed_data['BedID'], $agentID, $tenantName, $tenantPhoneNo, $tenantEmail, $rentStartDate, $rentExpiryDate); 
                if ($stmt_tenant->execute()) {
                    logMessage("Inserted tenant data for TenantID: $tenantID", $debugLog);

                    // Update bed status to booking
                    $stmt_update_bed = $conn->prepare("UPDATE Bed SET BedStatus = ? WHERE BedID = ?");
                    if ($stmt_update_bed === false) {
                        $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                        logMessage("Error: $error", $errorLog);
                    } else {
                        $stmt_update_bed->bind_param("ss", $bed_status, $bed_id_form);
                        if ($stmt_update_bed->execute()) {
                            logMessage("Updated BedStatus to '$bed_status' for BedID: $bed_id_form", $debugLog);
                        } else {
                            $error = "Error updating bed status: " . $stmt_update_bed->error;
                            logMessage("Error: $error", $errorLog);
                        }
                        $stmt_update_bed->close();
                    }
                } else {
                    $error = "Error inserting tenant data: " . $stmt_tenant->error;
                    logMessage("Error: $error", $errorLog);
                }
                $stmt_tenant->close();
            }
        }
    }

    // Insert tenancy agreement
    if (empty($error)) {
        // Generate AgreementID with format agreement_xxxx
        do {
            $agreementID = 'agreement_' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            // Check for uniqueness
            $stmt_check = $conn->prepare("SELECT AgreementID FROM tenancyagreement WHERE AgreementID = ?");
            if ($stmt_check === false) {
                $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                logMessage("Error: $error", $errorLog);
                break;
            }
            $stmt_check->bind_param("s", $agreementID);
            if ($stmt_check->execute()) {
                $stmt_check->store_result();
                $isUnique = ($stmt_check->num_rows === 0);
                $stmt_check->close();
            } else {
                $error = "Error executing AgreementID uniqueness check: " . $stmt_check->error;
                $stmt_check->close();
                logMessage("Error: $error", $errorLog);
                break;
            }
        } while (!$isUnique);

        logMessage("Generated AgreementID: $agreementID", $debugLog);

        if ($isUnique) {
            $propertyDetails = $bed_data['UnitNo']; 
            $monthlyRent = $bed_rent_amount;
            $depositAmount = $bed_rent_amount; 
            $startDate = $rentStartDate;
            $endDate = $rentExpiryDate; 

            // Define paths
            $agreementDirectory = __DIR__ . "/../../tenantagreement/";
            $relativeAgreementPath = "/tenantagreement/"; // Adjusted to relative path from web root

            // Ensure the agreement directory exists
            if (!is_dir($agreementDirectory)) {
                if (!mkdir($agreementDirectory, 0755, true)) {
                    $error = "Failed to create directory: " . $agreementDirectory;
                    logMessage("Error: $error", $errorLog);
                } else {
                    logMessage("Created directory: $agreementDirectory", $debugLog);
                }
            }

            // Sanitize tenant name for filename
            $safeTenantName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tenantName);
            logMessage("Sanitized Tenant Name for Filename: $safeTenantName", $debugLog);

            // Define the agreement file path
            $agreementFileName = "Tenancy_Agreement_$safeTenantName.docx";
            $agreementPath = $relativeAgreementPath . $agreementFileName;
            logMessage("Agreement File Name: $agreementFileName", $debugLog);

            // Insert into tenancyagreement table
            $stmt_agreement = $conn->prepare("
                INSERT INTO tenancyagreement (AgreementID, TenantID, PropertyDetails, MonthlyRent, DepositAmount, RentStartDate, RentExpiryDate, AgreementPath) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt_agreement === false) {
                $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
                logMessage("Error: $error", $errorLog);
            } else {
                $stmt_agreement->bind_param("ssssssss", $agreementID, $tenantID, $propertyDetails, $monthlyRent, $depositAmount, $startDate, $endDate, $agreementPath);
                if (!$stmt_agreement->execute()) {
                    $error = "Error inserting tenancy agreement data: " . $stmt_agreement->error;
                    logMessage("Error: $error", $errorLog);
                } else {
                    $msg .= "Tenancy agreement inserted successfully.<br>";
                    logMessage("Inserted tenancy agreement data for AgreementID: $agreementID", $debugLog);
                }
                $stmt_agreement->close();
            }
        }
    }

    // Generate Word document
    if (empty($error)) {
        // Get the room number 
        $roomNumber = $bed_data['RoomNo']; 
        logMessage("Room Number: $roomNumber", $debugLog);

        // Get the address based on the unit number
        $rentAddress = rentAddress($unitNumber); 
        logMessage("Rent Address: " . implode(", ", $rentAddress), $debugLog);

        // Define template path
        $templatePath = __DIR__ . "/../../templates/tenancy_agreement_template.docx";
        logMessage("Template Path: $templatePath", $debugLog);

        if (!file_exists($templatePath)) {
            $error = "Tenancy agreement template not found at: " . $templatePath;
            logMessage("Error: $error", $errorLog);
        } else {
            // Create TemplateProcessor instance
            try {
                $templateProcessor = new TemplateProcessor($templatePath);
                logMessage("Loaded Word template successfully.", $debugLog);

                // Replace placeholders in the template with actual data
                $templateProcessor->setValue('{{Name}}', htmlspecialchars($tenantName));
                $templateProcessor->setValue('{{IC}}', htmlspecialchars($passport)); // Updated placeholder
                $templateProcessor->setValue('{{Address}}', htmlspecialchars($address));
                $templateProcessor->setValue('{{Unit}}', htmlspecialchars($unitNumber));
                $templateProcessor->setValue('{{Room}}', htmlspecialchars($roomNumber)); 
                $templateProcessor->setValue('{{StartDate}}', htmlspecialchars($startDate)); // Changed to underscores
                $templateProcessor->setValue('{{EndDate}}', htmlspecialchars($endDate)); // Changed to underscores
                $templateProcessor->setValue('{{RentAddress1}}', htmlspecialchars($rentAddress[0]));
                $templateProcessor->setValue('{{RentAddress2}}', htmlspecialchars($rentAddress[1]));
                $templateProcessor->setValue('{{RentAddress3}}', htmlspecialchars($rentAddress[2]));
                logMessage("Replaced placeholders in the template.", $debugLog);

                // Define the output file path
                $outputFile = $agreementDirectory . $agreementFileName; 
                logMessage("Output File Path: $outputFile", $debugLog);

                // Check if the directory is writable
                if (!is_writable($agreementDirectory)) {
                    $error = "Output directory is not writable: " . $agreementDirectory;
                    logMessage("Error: $error", $errorLog);
                } else {
                    // Save the generated document  
                    try {
                        $templateProcessor->saveAs($outputFile);
                        $msg .= "File generated successfully!<br>";
                        logMessage("Saved generated document to: $outputFile", $debugLog);
                    } catch (Exception $e) {
                        $error = "File generation failed: " . $e->getMessage();
                        // Log the error
                        logMessage("File generation failed for TenantID $tenantID: " . $e->getMessage(), $errorLog);
                    }
                }
            } catch (Exception $e) {
                $error = "Error processing template: " . $e->getMessage();
                // Log the error
                logMessage("Template processing error for TenantID $tenantID: " . $e->getMessage(), $errorLog);
            }
        } 
    }

    // Send the generated document via email
    if (empty($error)) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;                   
            $mail->Username   = getenv('MAIL_USERNAME'); 
            $mail->Password   = getenv('MAIL_PASSWORD'); 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;                       

            // Recipients
            $mail->setFrom(getenv('MAIL_USERNAME'), 'Rentronics'); // Replace with a valid "From" address 
            $mail->addAddress($tenantEmail, $tenantName);            

            // Attachments
            $mail->addAttachment($outputFile, 'TenancyAgreement.docx');

            // Content
            $mail->isHTML(true);                                  
            $mail->Subject = 'Your Tenancy Agreement';
            $mail->Body    = 'Dear ' . htmlspecialchars($tenantName) . ',<br><br>Please find attached your tenancy agreement.<br><br>Regards,<br>Rentronics';

            if ($debugMode) {
                // In debug mode, do not send the email. Instead, log the email details.
                $msg .= "Debug Mode: Email not sent.<br>";
                logMessage("Debug Mode: Email prepared but not sent to $tenantEmail.", $debugLog);
            } else {
                // Send the email
                $mail->send();
                $msg .= "Tenancy agreement has been sent to your email.<br>"; 
                logMessage("Email sent to $tenantEmail successfully.", $debugLog);
            }
        } catch (PHPMailerException $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            // Log the error
            logMessage("Email sending failed for TenantID $tenantID: " . $mail->ErrorInfo, $errorLog);
        }
    }

    // Redirect or display messages
    if (empty($error)) {
        // Display success messages on the same page
        $msg = "<div class='alert alert-success'>{$msg}</div>";
        logMessage("Success Message: $msg", $debugLog);
        // Optionally, reset form fields or perform other actions
    } else {
        // Display error message on the same page
        $error = "<div class='alert alert-danger'>{$error}</div>";
        logMessage("Error Message: $error", $errorLog);
    }
}

// Prepare data for the form
if (isset($bed_data)) {
    $propertyID    = $bed_data['PropertyID'];
    $unitID        = $bed_data['UnitID'];
    $floorPlan     = $bed_data['FloorPlan'];
    $floorPlanURL  = $floorPlan . "?propertyID=" . $propertyID . "&unitID=" . $unitID;
}

// rentAddress function remains the same
function rentAddress($unitNumber) {
    $address = [];

    switch ($unitNumber) {
        case "C-15-10":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            break;
        case "C-16-09":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            break;
        case "E-22-B":
            $address[0] = "CYBERIA SMARTHOMES,";
            $address[1] = "63000";
            $address[2] = "CYBERJAYA";
            break;
        // ... add all your other case statements here ...

        default:
            // Handle cases where the unit number is not found
            $address[0] = "Address Line 1"; 
            $address[1] = "Address Line 2";
            $address[2] = "Address Line 3";
            break;
    }

    return $address;
}
?>
