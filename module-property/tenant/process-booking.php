<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Dotenv\Dotenv;
use Ilovepdf\Ilovepdf;

// Initialize variables
$publicKey = 'project_public_7eb5e26b7d4ce6b08dc7bade0dfcaef6_GrJYwe59a711698213267753aca26cf02761c';
$secretKey = 'secret_key_2d7a1650124701067b091065d35472d3_mlXcl03a179ca9e3786a8afddcb2e98204ab6';
$error = '';
$msg = '';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Redirect to login if not authenticated
if (!isset($_SESSION['auser'])) {
    $error = "Session not found. Please log in again.";
} else {

    if (isset($_SESSION['auser']['AgentID'])) {
        $agentID = $_SESSION['auser']['AgentID'];
    } else {
        $error = "Agent information not found in the session. Please log in again.";
    }
}

// Only proceed if we have an AgentID
if (empty($error)) {
    // Create AgreementProcessor instance
    $agreementProcessor = new AgreementProcessor($conn);
    
    // Retrieve BedID or RoomID from URL and set rental type
    $bed_id = isset($_GET['bedID']) ? $_GET['bedID'] : null;
    $room_id = isset($_GET['roomID']) ? $_GET['roomID'] : null;

    // Create the SQL query based on rental type
    $stmt_property = $conn->prepare("
        SELECT 
            b.BedID,
            b.BedNo,
            b.BaseRentAmount as BedBaseRentAmount,
            b.BedRentAmount,
            b.BedStatus,
            r.RoomID,
            r.RoomNo,
            r.RoomRentAmount,
            r.RoomStatus,
            r.BaseRentAmount as RoomBaseRentAmount,
            r.Katil,
            u.UnitID,
            u.UnitNo,
            u.PropertyID,
            u.FloorPlan
        FROM room r
        INNER JOIN unit u ON r.UnitID = u.UnitID
        LEFT JOIN bed b ON b.RoomID = r.RoomID
        WHERE " . ($bed_id ? "b.BedID = ?" : "r.RoomID = ?")
    );

    if ($stmt_property === false) {
        $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
    } else {
        $property_id = $bed_id ?: $room_id; // Use bed_id if available, otherwise use room_id
        $stmt_property->bind_param("s", $property_id);
        if ($stmt_property->execute()) {
            $result = $stmt_property->get_result();
            if ($result->num_rows > 0) {
                $property_data = $result->fetch_assoc();
                // Determine if this is a bed or room rental
                $is_bed_rental = !empty($property_data['BedID']);
            } else {
                $error = "No property found with the provided ID.";
            }
            $stmt_property->close();
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
        $tenantSignature   = isset($_POST['tenant_signature']) ? $_POST['tenant_signature'] : '';
        //$rental_type       = isset($_POST['rental_type']) ? $_POST['rental_type'] : '';

        // Validate rental start date 
        if (empty($rentStartDate)) {
            $error = "Rental start date is required.";
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $rentStartDate);
            if (!$date || $date->format('Y-m-d') !== $rentStartDate) {
                $error = "Invalid rental start date format. Please use YYYY-MM-DD format.";
            }
        }

        // Calculate rent expiry date
        $rentExpiryDate = date('Y-m-d', strtotime($rentStartDate . ' + 1 year - 1 day'));
        if (!$rentExpiryDate) {
            $error = "Failed to calculate rental expiry date.";
        }

        // Create safe tenant name and directory
        $safeTenantName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tenantName);
        $tenantDirectory = __DIR__ . "/tenant_documents/" . $safeTenantName;
        
        // Defer directory creation until after validation
        $needToCreateDirectory = !is_dir($tenantDirectory);

        // Validate signature
        if (empty($tenantSignature)) {
            $error = "Tenant signature is required.";
        }

        // Handle and validate file uploads - just validate first
        $uploadedFiles = [];
        $filesToMove = [];

        // Validate IC/Passport upload
        if (!isset($_FILES['ic_passport_file']) || $_FILES['ic_passport_file']['error'] != 0) {
            $error = "IC/Passport file is required.";
        } else {
            $icPassportFile = $_FILES['ic_passport_file'];
            $fileExt = pathinfo($icPassportFile['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($fileExt), ['pdf', 'jpg', 'jpeg', 'png'])) {
                $error = "Invalid IC/Passport file type. Allowed types: PDF, JPG, JPEG, PNG";
            } else {
                $icPassportFileName = "IC_Passport_" . $safeTenantName . "_" . date('d-m-Y') . "." . $fileExt;
                $icPassportPath = $tenantDirectory . "/" . $icPassportFileName;
                $filesToMove['ic_passport'] = [
                    'tmp_name' => $icPassportFile['tmp_name'],
                    'destination' => $icPassportPath,
                    'name' => $icPassportFileName
                ];
            }
        }

        // Validate Bank Statement upload
        if (!isset($_FILES['bank_statement']) || $_FILES['bank_statement']['error'] != 0) {
            $error = "Bank statement file is required.";
        } else {
            $bankStatementFile = $_FILES['bank_statement'];
            $fileExt = pathinfo($bankStatementFile['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($fileExt), ['pdf', 'jpg', 'jpeg', 'png'])) {
                $error = "Invalid bank statement file type. Allowed types: PDF, JPG, JPEG, PNG";
            } else {
                $bankStatementFileName = "Bank_Statement_" . $safeTenantName . "_" . date('d-m-Y') . "." . $fileExt;
                $bankStatementPath = $tenantDirectory . "/" . $bankStatementFileName;
                $filesToMove['bank_statement'] = [
                    'tmp_name' => $bankStatementFile['tmp_name'],
                    'destination' => $bankStatementPath,
                    'name' => $bankStatementFileName
                ];
            }
        }

        // Only validate rent amount and availability if we're processing a direct booking
        // (not when generating an agreement for an existing tenant)
        if (isset($is_bed_rental) && isset($property_data)) {
            // Validate rent amount based on rental type
            $base_amount = $is_bed_rental ? 
                $property_data['BedBaseRentAmount'] : 
                $property_data['RoomBaseRentAmount'];
    
            if (!is_numeric($bed_rent_amount)) {
                $error = "Please enter a valid number for Rental Amount.";
            } elseif ($bed_rent_amount < $base_amount) {
                $error = "Rental Amount cannot be less than the base amount: " . $base_amount;
            }
    
            // Check availability based on rental type
            if ($is_bed_rental && $property_data['BedStatus'] !== 'Available') {
                $error = "This bed is not available for booking.";
            } elseif (!$is_bed_rental && $property_data['RoomStatus'] !== 'Available') {
                $error = "This room is not available for booking.";
            }
        }

        // If all validations pass, then create directory and move files
        if (empty($error)) {
            // Create directory if needed
            if ($needToCreateDirectory) {
                if (!mkdir($tenantDirectory, 0755, true)) {
                    $error = "Failed to create tenant directory: " . $tenantDirectory;
                }
            }

            if (empty($error)) {
                // Save signature
                $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $tenantSignature));
                $signatureFileName = "signature_" . $safeTenantName . "_" . date('d-m-Y') . ".png";
                $signaturePath = $tenantDirectory . "/" . $signatureFileName;

                if (!file_put_contents($signaturePath, $signatureImage)) {
                    $error = "Failed to save signature";
                } else {
                    // Move all validated files
                    foreach ($filesToMove as $type => $fileInfo) {
                        if (move_uploaded_file($fileInfo['tmp_name'], $fileInfo['destination'])) {
                            $uploadedFiles[$type] = [
                                'path' => $fileInfo['destination'],
                                'name' => $fileInfo['name']
                            ];
                        } else {
                            $error = "Failed to save " . $type . " file";
                            break;
                        }
                    }
                }
            }
        }

        // If all validations pass, process agreement and store data in session
        if (empty($error)) {
            try {
                // Generate agreement file names
                $agreementFileName = "agreement_" . $safeTenantName . "_" . date('d-m-Y');
                $docxPath = $tenantDirectory . "/" . $agreementFileName . ".docx";
                $pdfPath = $agreementFileName . ".pdf";
                $agreementPathPdf = $tenantDirectory . "/" . $pdfPath;

                // Prepare data for agreement generation
                $agreementData = [
                    'tenantName' => $tenantName,
                    'passport' => $passport,
                    'address' => $address,
                    'unitNumber' => $unitNumber,
                    'roomNumber' => $property_data['RoomNo'],
                    'startDate' => $rentStartDate,
                    'endDate' => $rentExpiryDate,
                    'signaturePath' => $signaturePath,
                    'docxPath' => $docxPath,
                    'pdfPath' => $pdfPath,
                    'agreementDirectory' => $tenantDirectory
                ];

                // Store data in session
                $_SESSION['booking_data'] = [
                    'tenant_info' => [
                        'tenantID' => $passport,
                        'tenantName' => $tenantName,
                        'tenantPhoneNo' => $tenantPhoneNo,
                        'tenantEmail' => $tenantEmail,
                        'passport' => $passport,
                        'address' => $address,
                        'rentStartDate' => $rentStartDate,
                        'rentExpiryDate' => $rentExpiryDate
                    ],
                    'property_info' => [
                        'unitID' => $property_data['UnitID'],
                        'roomID' => $property_data['RoomID'],
                        'bedID' => $is_bed_rental ? $property_data['BedID'] : null,
                        'rental_type' => $is_bed_rental ? 'bed' : 'room',
                        'agentID' => $agentID,
                        'unitNumber' => $unitNumber,
                        'roomNumber' => $property_data['RoomNo']
                    ],
                    'payment_info' => [
                        'amount' => $bed_rent_amount,
                        'baseAmount' => $base_amount,
                        'rental_type' => $is_bed_rental ? 'bed' : 'room'
                    ],
                    'files' => [
                        'uploadedFiles' => $uploadedFiles,
                        'signaturePath' => $signaturePath,
                        'tenantDirectory' => $tenantDirectory,
                        'agreementData' => $agreementData  // Store agreement data instead of generating now
                    ],
                    'agent_info' => [
                        'agentID' => $agentID
                    ]
                ];

                // Modify the redirect to include the correct ID parameter
                $redirect_param = $is_bed_rental ? "bedID=" . $property_data['BedID'] : "roomID=" . $property_data['RoomID'];
                header("Location: bookingcheckout.php?{$redirect_param}&tenantID=" . $passport);
                exit;

            } catch (Exception $e) {
                $error = "Error processing agreement: " . $e->getMessage();
            }
        }

        // Display messages
        if (empty($error)) {
            $msg = "<div class='alert alert-success'>{$msg}</div>";
        } else {
            $error = "<div class='alert alert-danger'>{$error}</div>";
        }
    }

    // Prepare data for the form
    if (isset($property_data)) {
        $propertyID    = $property_data['PropertyID'];
        $unitID        = $property_data['UnitID'];
        $floorPlan     = $property_data['FloorPlan'];
        $floorPlanURL  = $floorPlan . "?propertyID=" . $propertyID . "&unitID=" . $unitID;
    }
}

// Move class definition outside the main if block
class AgreementProcessor {
    private $conn;
    private $templatePaths = [
        'rules' => '/templates/tenancy_agreement_rules.docx',
        'agreement' => '/templates/tenancy_agreement_template.docx'
    ];
    
    // Directory to store tenant documents
    private $tenantDocumentsDir = '/tenant_documents';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function rentAddress($unitNumber) {
        $mainUnit = explode('-R', $unitNumber)[0];
        $address = [];

        switch ($mainUnit) {
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
            case "A-9-7":
                $address[0] = "PV3,JALAN MELATI UTAMA,";
                $address[1] = "TAMAN MELATI";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "A-3-8":
                $address[0] = "PV3,JALAN MELATI UTAMA,";
                $address[1] = "TAMAN MELATI";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "50302":
                $address[0] = "LORONG PENAGA 2, TAMAN MAK CHILI,";
                $address[1] = "24000";
                $address[2] = "CHUKAI, TERENGGANU";
                break;
            case "50317":
                $address[0] = "LORONG PENAGA 2, TAMAN MAK CHILI,";
                $address[1] = "24000";
                $address[2] = "CHUKAI, TERENGGANU";
                break;
            case "50312":
                $address[0] = "LORONG PENAGA 2, TAMAN MAK CHILI,";
                $address[1] = "24000";
                $address[2] = "CHUKAI, TERENGGANU";
                break;
            case "B-16-3A":
                $address[0] = "PV2, JALAN TAMAN MELATI 1,";
                $address[1] = "TAMAN MELATI,";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "B-13-9":
                $address[0] = "PV2, JALAN TAMAN MELATI 1,";
                $address[1] = "TAMAN MELATI,";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "A-29-6":
                $address[0] = "PV2, JALAN TAMAN MELATI 1,";
                $address[1] = "TAMAN MELATI,";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "A-11-11":
                $address[0] = "PV5,JALAN MELATI UTAMA,";
                $address[1] = "TAMAN MELATI";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            case "A-17-2":
                $address[0] = "PV5,JALAN MELATI UTAMA,";
                $address[1] = "TAMAN MELATI";
                $address[2] = "53100 SETAPAK, KUALA LUMPUR";
                break;
            default:
                $address[0] = "Address Line 1";
                $address[1] = "Address Line 2";
                $address[2] = "Address Line 3";
                break;
        }

        return $address;
    }

    public function generateAgreement($data) {
        // Get the address using the class method
        $rentAddress = $this->rentAddress($data['unitNumber']);
        $data['rentAddress'] = $rentAddress;

        // Generate both agreement types
        foreach ($this->templatePaths as $type => $templatePath) {
            $fullTemplatePath = __DIR__ . $templatePath;
            
            if (!file_exists($fullTemplatePath)) {
                throw new Exception("Tenancy agreement template not found at: " . $fullTemplatePath);
            }

            // Create file names with type suffix
            $docxPath = str_replace('.docx', "_{$type}.docx", $data['docxPath']);
            $pdfPath = str_replace('.pdf', "_{$type}.pdf", $data['pdfPath']);
            
            // Create TemplateProcessor instance
            $templateProcessor = new TemplateProcessor($fullTemplatePath);

            // Replace placeholders in the template with actual data
            $templateProcessor->setValue('{{Name}}', htmlspecialchars($data['tenantName']));
            $templateProcessor->setValue('{{IC}}', htmlspecialchars($data['passport']));
            $templateProcessor->setValue('{{Address}}', htmlspecialchars($data['address']));
            $templateProcessor->setValue('{{Unit}}', htmlspecialchars($data['unitNumber']));
            $templateProcessor->setValue('{{Room}}', htmlspecialchars($data['roomNumber']));
            $templateProcessor->setValue('{{StartDate}}', date('F, Y', strtotime($data['startDate'])));
            $templateProcessor->setValue('{{End}}', date('F, Y', strtotime($data['endDate'])));
            $templateProcessor->setValue('{{RentAddress1}}', htmlspecialchars($data['rentAddress'][0]));
            $templateProcessor->setValue('{{RentAddress2}}', htmlspecialchars($data['rentAddress'][1]));
            $templateProcessor->setValue('{{RentAddress3}}', htmlspecialchars($data['rentAddress'][2]));

            // Add signature to the template
            $templateProcessor->setImageValue('TenantSignature', [
                'path' => $data['signaturePath'],
                'width' => 150,
                'height' => 75,
                'ratio' => false
            ]);

            // Save the DOCX file
            $templateProcessor->saveAs($docxPath);

            // Skip PDF conversion completely - just use DOCX files directly
            // This avoids the long wait times from the external API
            
            // Create the final DOCX path
            $finalDocxPath = $data['agreementDirectory'] . '/' . str_replace('.pdf', "_{$type}.docx", $data['docxPath']);
            
            // Make a copy of the DOCX file to the final location
            if (file_exists($docxPath)) {
                copy($docxPath, $finalDocxPath);
            }
            
            // Store the path in the return data
            $data['agreementPaths'][$type] = $finalDocxPath;
            
            // No need to delete the original since we're using it directly

            // Paths are now stored inside the try/catch block above
        }

        return $data['agreementPaths'];
    }

}
