<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if booking data is provided
if (!isset($_POST['booking_data'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Booking data is required']);
    exit;
}

try {
    // Decode the JSON data
    $bookingData = json_decode($_POST['booking_data'], true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    // Validate the booking data structure
    if (!isset($bookingData['tenant_info']) || !isset($bookingData['property_info']) || !isset($bookingData['payment_info'])) {
        throw new Exception('Invalid booking data structure');
    }
    
    // Process tenant information
    $tenants = $bookingData['tenant_info'];
    $primaryTenant = $tenants[0]; // Get the first tenant as primary
    
    // Generate a temporary tenant ID (will be replaced with actual ID after registration)
    $tempTenantID = 'TMP_' . time() . '_' . rand(1000, 9999);
    
    // Create a safe directory name for tenant documents
    $safeTenantName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $primaryTenant['tenantName']);
    
    // Prepare the data for bookingcheckout.php
    $processedData = [
        'tenant_info' => [
            'tenantName' => $primaryTenant['tenantName'],
            'tenantEmail' => $primaryTenant['tenantEmail'],
            'tenantPhoneNo' => $primaryTenant['tenantPhoneNo'],
            'passport' => $primaryTenant['passport'],
            'rentStartDate' => $bookingData['rentStartDate'] ?? date('Y-m-d'),
            'safeTenantName' => $safeTenantName // For document storage
        ],
        'property_info' => $bookingData['property_info'],
        'payment_info' => [
            'amount' => $bookingData['payment_info']['amount'],
            'duration' => $bookingData['payment_info']['duration'],
            'rental_type' => 'Room' // Since this is from roomform.php
        ],
        'special_requests' => $bookingData['special_requests'] ?? ''
    ];
    
    // If there are additional tenants, include them
    if (count($tenants) > 1) {
        $processedData['additional_tenants'] = array_slice($tenants, 1);
    }
    
    // Store the processed data in the session
    $_SESSION['booking_data'] = $processedData;
    
    // Return success response
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
