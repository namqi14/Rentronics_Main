<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

// Handle deletion if bed_id is present in the query string
if (isset($_GET['delete_tenant_id'])) {
    $tenantID = $_GET['delete_tenant_id'];

    // Debugging output
    error_log("Updating tenant status with TenantID: " . $tenantID);

    if (!empty($tenantID)) {
        $tenantID = $conn->real_escape_string($tenantID);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Get the BedID associated with the tenant
            $getBedSQL = "SELECT BedID FROM Tenant WHERE TenantID = '$tenantID'";
            $result = $conn->query($getBedSQL);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $bedID = $row['BedID'];

                // Update the Bed status to available and clear AgentID
                $updateBedSQL = "UPDATE Bed SET BedStatus = 'Available', AgentID = NULL WHERE BedID = '$bedID'";
                $conn->query($updateBedSQL);

                // Update tenant status instead of deleting
                $updateTenantSQL = "UPDATE Tenant SET TenantStatus = 'Moved Out' WHERE TenantID = '$tenantID'";
                if ($conn->query($updateTenantSQL)) {
                    $conn->commit();
                    $_SESSION['msg'] = 'Tenant status updated to Moved Out and bed status updated.';
                } else {
                    throw new Exception("Failed to update tenant status");
                }
            } else {
                throw new Exception("No tenant found with the provided ID");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Invalid TenantID.';
    }
} else {
    $_SESSION['error'] = 'TenantID not provided.';
}

// Redirect back to the tenant table page
header("Location: tenanttable.php");
exit();
?>
