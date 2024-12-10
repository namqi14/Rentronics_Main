<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Tenant details
$tenantName = 'Muhammad Sumbul';
$passport = '01233325123';
$address = '12, KL';
$unitNumber = 'C-15-10';
$roomNumber = 'R4';
$startDate = '1/12/2024';
$endDate = '1/12/2025';

// Function to determine rent address
function rentAddress($unitNo) {
    $address = [];

    switch ($unitNo) {
        case "PV9, C-15-10":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            break;
        case "PV9, C-16-09":
            $address[0] = "VISTA WIRAJAYA 2, TAMAN MELATI,";
            $address[1] = "53100";
            $address[2] = "KUALA LUMPUR";
            break;
        case "Cyber, E-22-B":
            $address[0] = "CYBERIA SMARTHOMES,";
            $address[1] = "63000";
            $address[2] = "CYBERJAYA";
            break;
        default:
            $address[0] = "Address Line 1";
            $address[1] = "Address Line 2";
            $address[2] = "Address Line 3";
            break;
    }
    return $address;
}

$rentAddress = rentAddress($unitNumber);

// Load the template (ensure the path is correct)
$templatePath = __DIR__ . "/templates/tenancy_agreement_template.docx";

$templateProcessor = new TemplateProcessor($templatePath);

// Replace placeholders with actual data
$templateProcessor->setValue('{Name}', $tenantName);
$templateProcessor->setValue('{IC/Passport}', $passport);
$templateProcessor->setValue('{Address}', $address);
$templateProcessor->setValue('{Unit}', $unitNumber);
$templateProcessor->setValue('{Room}', $roomNumber);
$templateProcessor->setValue('{Start Date}', $startDate);
$templateProcessor->setValue('{End Date}', $endDate);
$templateProcessor->setValue('{RentAddress1}', $rentAddress[0]);
$templateProcessor->setValue('{RentAddress2}', $rentAddress[1]);
$templateProcessor->setValue('{RentAddress3}', $rentAddress[2]);

// Save the modified document to a new file
$outputFilePath = __DIR__ . "/tenantagreement/tenancy_agreement_$tenantName.docx";
$templateProcessor->saveAs($outputFilePath);

echo "Document generated successfully and saved as: " . $outputFilePath;
?>
