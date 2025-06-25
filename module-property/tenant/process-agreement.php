<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PhpOffice\PhpWord\TemplateProcessor;
use Ilovepdf\Ilovepdf;

class AgreementProcessor {
    private $conn;

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

        public function insertAgreement($tenantData, $bedData, $agreementPathPdf, $bedRentAmount) {
            // Generate AgreementID
            $agreementID = 'agreement_' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            
            // Debug output
            error_log("Inserting agreement with TenantID: " . $tenantData['TenantID']);
            
            $stmt_agreement = $this->conn->prepare("
                INSERT INTO tenancyagreement (
                    AgreementID, 
                    TenantID,
                    PropertyDetails, 
                    MonthlyRent, 
                    DepositAmount, 
                    RentStartDate, 
                    RentExpiryDate, 
                    AgreementPath
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt_agreement) {
                throw new Exception("Failed to prepare agreement statement");
            }
            
            $stmt_agreement->bind_param("ssssssss", 
                $agreementID, 
                $tenantData['TenantID'],
                $bedData['UnitNo'], 
                $bedRentAmount, 
                $bedRentAmount, 
                $tenantData['RentStartDate'], 
                $tenantData['RentExpiryDate'], 
                $agreementPathPdf
            );
            
            if (!$stmt_agreement->execute()) {
                throw new Exception("Failed to insert agreement: " . $stmt_agreement->error);
            }
            $stmt_agreement->close();

            return $agreementID;
        }
        
        public function generateAgreement($data) {
            // Get the address using the class method
            $rentAddress = $this->rentAddress($data['unitNumber']);
            $data['rentAddress'] = $rentAddress;

            // Define template path
            $templatePath = __DIR__ . "/../../templates/tenancy_agreement_template.docx";

            if (!file_exists($templatePath)) {
                throw new Exception("Tenancy agreement template not found at: " . $templatePath);
            }

            // Create TemplateProcessor instance
            $templateProcessor = new TemplateProcessor($templatePath);

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
            $templateProcessor->saveAs($data['docxPath']);

            // Initialize iLovePDF
            $ilovepdf = new Ilovepdf(
                'project_public_7eb5e26b7d4ce6b08dc7bade0dfcaef6_GrJYwe59a711698213267753aca26cf02761c',
                'secret_key_2d7a1650124701067b091065d35472d3_mlXcl03a179ca9e3786a8afddcb2e98204ab6'
            );

            // Create a new task
            $myTask = $ilovepdf->newTask('officepdf');

            // Add the file
            $file1 = $myTask->addFile($data['docxPath']);

            // Execute the task
            $myTask->execute();

            // Download the PDF to the specific path
            $myTask->download($data['agreementDirectory'], $data['pdfPath']);

            // Delete the temporary DOCX file if it exists
            if (file_exists($data['docxPath'])) {
                unlink($data['docxPath']);
            }
        }

        public function sendEmail($data) {
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'your-email@gmail.com'; // Replace with your email
                $mail->Password = 'your-app-password'; // Replace with your app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('your-email@gmail.com', 'Your Name');
                $mail->addAddress($data['tenantEmail'], $data['tenantName']);

                // Attachments
                $mail->addAttachment($data['agreementPathPdf'], 'Tenancy Agreement.pdf');
                
                // Add uploaded files as attachments
                foreach ($data['uploadedFiles'] as $fileType => $fileInfo) {
                    $mail->addAttachment($fileInfo['path'], $fileInfo['name']);
                }

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Tenancy Agreement Documents';
                $mail->Body = "
                    Dear {$data['tenantName']},<br><br>
                    Please find attached your tenancy agreement and related documents.<br><br>
                    Best regards,<br>
                    Your Property Management Team
                ";

                $mail->send();
            } catch (PHPMailerException $e) {
                throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        }
    }