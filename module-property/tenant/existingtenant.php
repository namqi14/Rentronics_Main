<?php
// Include database connection
require_once __DIR__ . '/../../module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: /rentronics/index.php");
    exit();
}

// Initialize variables for messages
$msg = '';
$error = '';

// Add this near the top of your file with other configurations
define('UPLOAD_DIR', 'uploads/documents/');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get form data (only convert tenant_name to uppercase)
        $tenant_id = $_POST['ic_passport'];
        $agent_id = $_POST['agent_id'];
        $rental_type = $_POST['rental_type'];
        $tenant_name = strtoupper($_POST['tenant_name']);
        $mobile_number = $_POST['mobile_number'];
        $tenant_email = $_POST['tenant_email'];
        $rent_start_date = $_POST['rent_start_date'];
        $rent_expiry_date = $_POST['rent_expiry_date'];
        
        // Get the appropriate ID and prepare update data based on rental type
        if ($rental_type === 'Room') {
            $room_id = $_POST['room_id'];
            $room_rent_amount = $_POST['room_rent_amount'];
            
            // Fetch UnitID based on RoomID
            $room_query = "SELECT UnitID FROM room WHERE RoomID = ?";
            $room_stmt = $conn->prepare($room_query);
            $room_stmt->bind_param("s", $room_id);
            $room_stmt->execute();
            $room_result = $room_stmt->get_result();
            $room_info = $room_result->fetch_assoc();
            $unit_id = $room_info['UnitID'];
            
            $rent_amount = $room_rent_amount;
        } else {
            $bed_id = $_POST['bed_id'];
            $bed_rent_amount = $_POST['bed_rent_amount'];
            
            // Fetch RoomID and UnitID based on BedID
            $bed_query = "SELECT r.RoomID, r.UnitID FROM bed b 
                         JOIN room r ON b.RoomID = r.RoomID 
                         WHERE b.BedID = ?";
            $bed_stmt = $conn->prepare($bed_query);
            $bed_stmt->bind_param("s", $bed_id);
            $bed_stmt->execute();
            $bed_result = $bed_stmt->get_result();
            $bed_info = $bed_result->fetch_assoc();
            $room_id = $bed_info['RoomID'];
            $unit_id = $bed_info['UnitID'];
            
            $rent_amount = $bed_rent_amount;
        }

        // Set the bed_id_value before the query
        $bed_id_value = ($rental_type === 'Bed' ? $bed_id : null);

        // Insert tenant first
        $sql = "INSERT INTO tenant (
            TenantID, UnitID, RoomID, BedID, AgentID, 
            TenantName, TenantPhoneNo, TenantEmail, 
            RentStartDate, RentExpiryDate, TenantStatus, RentalType
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Rented', ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssss",
            $tenant_id, $unit_id, $room_id, $bed_id_value, $agent_id,
            $tenant_name, $mobile_number, $tenant_email,
            $rent_start_date, $rent_expiry_date, $rental_type
        );

        if ($stmt->execute()) {
            // Only after successful tenant insertion, update room/bed status
            if ($rental_type === 'room') {
                $update_room_sql = "UPDATE room SET 
                    RoomStatus = 'Rented', 
                    AgentID = ?,
                    RoomRentAmount = ? 
                    WHERE RoomID = ?";
                $update_room_stmt = $conn->prepare($update_room_sql);
                $update_room_stmt->bind_param("sds", $agent_id, $room_rent_amount, $room_id);
                $update_room_stmt->execute();
            } else {
                $update_bed_sql = "UPDATE bed SET 
                    BedStatus = 'Rented',
                    AgentID = ?,
                    BedRentAmount = ?
                    WHERE BedID = ?";
                $update_bed_stmt = $conn->prepare($update_bed_sql);
                $update_bed_stmt->bind_param("sds", $agent_id, $bed_rent_amount, $bed_id);
                $update_bed_stmt->execute();
            }
            
            // If we got here, commit the transaction
            $conn->commit();
            $msg = "Tenant added successfully!";
        } else {
            throw new Exception("Error adding tenant: " . $stmt->error);
        }
    } catch (Exception $e) {
        // Rollback the transaction on any error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch bed data if bedID is provided in URL
if (isset($_GET['bedID'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM bed WHERE BedID = ?");
        $stmt->execute([$_GET['bedID']]);
        $bed_data = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all available rooms instead of beds
try {
    // First, let's make the query simpler to test
    $stmt = $conn->query("SELECT r.RoomID, r.RoomNo, u.UnitNo, p.PropertyName 
                         FROM room r 
                         JOIN unit u ON r.UnitID = u.UnitID 
                         JOIN property p ON u.PropertyID = p.PropertyID");
                         // Temporarily removed WHERE clause to see all rooms
    
    if (!$stmt) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $available_rooms = $stmt->fetch_all(MYSQLI_ASSOC);
    
    // Add debug output
    if (empty($available_rooms)) {
        error_log("No rooms found in the database");
    } else {
        error_log("Found " . count($available_rooms) . " rooms");
    }
} catch (Exception $e) {
    $error = "Error fetching rooms: " . $e->getMessage();
    error_log($error);
}

// Add this near the top where you fetch other data
try {
    // Fetch all agents
    $agent_query = "SELECT AgentID, AgentName FROM agent WHERE Status = 'Active'";
    $agent_result = $conn->query($agent_query);
    $agents = $agent_result->fetch_all(MYSQLI_ASSOC);

    // Fetch available rooms
    $room_stmt = $conn->query("SELECT r.RoomID, r.RoomNo, u.UnitNo, p.PropertyName 
                              FROM room r 
                              JOIN unit u ON r.UnitID = u.UnitID 
                              JOIN property p ON u.PropertyID = p.PropertyID 
                              WHERE r.RoomStatus = 'Available'");
    $available_rooms = $room_stmt->fetch_all(MYSQLI_ASSOC);

    // Add debug output
    echo "<!-- Debug: Number of rooms found: " . count($available_rooms) . " -->";
    echo "<!-- Debug: Room query: ";
    var_dump($available_rooms);
    echo " -->";

    // Fetch available beds
    $bed_stmt = $conn->query("SELECT b.BedID, b.BedNo, r.RoomNo, u.UnitNo, p.PropertyName 
                             FROM bed b
                             JOIN room r ON b.RoomID = r.RoomID 
                             JOIN unit u ON r.UnitID = u.UnitID 
                             JOIN property p ON u.PropertyID = p.PropertyID 
                             WHERE b.BedStatus = 'Available'");
    $available_beds = $bed_stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching data: " . $e->getMessage();
    error_log($error);
}

// First, fetch the agents (add this with your other queries)
try {
    // Fetch active agents
    $agent_query = "SELECT AgentID, AgentName FROM agent WHERE Status = 'Active'";
    $agent_result = $conn->query($agent_query);
    if (!$agent_result) {
        throw new Exception("Error fetching agents: " . $conn->error);
    }
    $agents = $agent_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    error_log($error);
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
                            <h3 class="page-title">Existing Tenant Overview</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/rentronics/module-property/admin/dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Tenant</li>
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
                                <a href="tenanttable.php"
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
                                                    placeholder="Enter Tenant Name" style="text-transform: uppercase;">
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
                                        <h5>Rental Details</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Agent</label>
                                            <div class="col-lg-9">
                                                <select class="form-control" name="agent_id" required>
                                                    <option value="">Select an agent</option>
                                                    <?php foreach ($agents as $agent): ?>
                                                        <option value="<?php echo htmlspecialchars($agent['AgentID']); ?>">
                                                            <?php echo htmlspecialchars($agent['AgentName']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rental Type</label>
                                            <div class="col-lg-9">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="rental_type" id="room_checkbox" value="Room">
                                                    <label class="form-check-label" for="room_checkbox">Room</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="rental_type" id="bed_checkbox" value="Bed">
                                                    <label class="form-check-label" for="bed_checkbox">Bed</label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Room selection (initially hidden) -->
                                        <div class="form-group row" id="room_selection" style="display: none;">
                                            <label class="col-lg-3 col-form-label">Room Number</label>
                                            <div class="col-lg-9">
                                                <select class="form-control" name="room_id">
                                                    <option value="">Select a room</option>
                                                    <?php foreach ($available_rooms as $room): ?>
                                                        <option value="<?php echo htmlspecialchars($room['RoomID']); ?>">
                                                            <?php echo htmlspecialchars($room['PropertyName'] . ' - Unit ' . $room['UnitNo'] . ' - Room ' . $room['RoomNo']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Bed selection (initially hidden) -->
                                        <div class="form-group row" id="bed_selection" style="display: none;">
                                            <label class="col-lg-3 col-form-label">Bed Number</label>
                                            <div class="col-lg-9">
                                                <select class="form-control" name="bed_id">
                                                    <option value="">Select a bed</option>
                                                    <?php foreach ($available_beds as $bed): ?>
                                                        <option value="<?php echo htmlspecialchars($bed['BedID']); ?>">
                                                            <?php echo htmlspecialchars($bed['PropertyName'] . ' - Unit ' . $bed['UnitNo'] . ' - Room ' . $bed['RoomNo'] . ' - Bed ' . $bed['BedNo']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Add this after the room/bed selection fields -->
                                        <div class="form-group row" id="room_rent_amount" style="display: none;">
                                            <label class="col-lg-3 col-form-label">Room Rental Amount</label>
                                            <div class="col-lg-9">
                                                <input type="number" class="form-control" name="room_rent_amount" step="0.01" min="0"
                                                    placeholder="Enter Room Rental Amount">
                                            </div>
                                        </div>

                                        <div class="form-group row" id="bed_rent_amount" style="display: none;">
                                            <label class="col-lg-3 col-form-label">Bed Rental Amount</label>
                                            <div class="col-lg-9">
                                                <input type="number" class="form-control" name="bed_rent_amount" step="0.01" min="0"
                                                    placeholder="Enter Bed Rental Amount">
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rent Start Date</label>
                                            <div class="col-lg-9">
                                                <input type="date" class="form-control" name="rent_start_date" id="rent_start_date" required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Rent Expiry Date</label>
                                            <div class="col-lg-9">
                                                <input type="date" class="form-control" name="rent_expiry_date" id="rent_expiry_date" required readonly>
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
        <script>
        // Replace the rental type dropdown event listener with checkbox event listeners
        document.getElementById('room_checkbox').addEventListener('change', function() {
            const bedCheckbox = document.getElementById('bed_checkbox');
            const roomSelection = document.getElementById('room_selection');
            const bedSelection = document.getElementById('bed_selection');
            const roomRentAmount = document.getElementById('room_rent_amount');
            const bedRentAmount = document.getElementById('bed_rent_amount');
            const roomIdSelect = document.querySelector('select[name="room_id"]');
            const bedIdSelect = document.querySelector('select[name="bed_id"]');
            const roomRentInput = document.querySelector('input[name="room_rent_amount"]');
            const bedRentInput = document.querySelector('input[name="bed_rent_amount"]');

            if (this.checked) {
                bedCheckbox.checked = false;
                roomSelection.style.display = 'flex';
                bedSelection.style.display = 'none';
                roomRentAmount.style.display = 'flex';
                bedRentAmount.style.display = 'none';
                roomIdSelect.required = true;
                bedIdSelect.required = false;
                roomRentInput.required = true;
                bedRentInput.required = false;
                bedIdSelect.value = '';
                bedRentInput.value = '';
            } else {
                roomSelection.style.display = 'none';
                roomRentAmount.style.display = 'none';
                roomIdSelect.required = false;
                roomRentInput.required = false;
                roomIdSelect.value = '';
                roomRentInput.value = '';
            }
        });

        document.getElementById('bed_checkbox').addEventListener('change', function() {
            const roomCheckbox = document.getElementById('room_checkbox');
            const roomSelection = document.getElementById('room_selection');
            const bedSelection = document.getElementById('bed_selection');
            const roomRentAmount = document.getElementById('room_rent_amount');
            const bedRentAmount = document.getElementById('bed_rent_amount');
            const roomIdSelect = document.querySelector('select[name="room_id"]');
            const bedIdSelect = document.querySelector('select[name="bed_id"]');
            const roomRentInput = document.querySelector('input[name="room_rent_amount"]');
            const bedRentInput = document.querySelector('input[name="bed_rent_amount"]');

            if (this.checked) {
                roomCheckbox.checked = false;
                roomSelection.style.display = 'none';
                bedSelection.style.display = 'flex';
                roomRentAmount.style.display = 'none';
                bedRentAmount.style.display = 'flex';
                roomIdSelect.required = false;
                bedIdSelect.required = true;
                roomRentInput.required = false;
                bedRentInput.required = true;
                roomIdSelect.value = '';
                roomRentInput.value = '';
            } else {
                bedSelection.style.display = 'none';
                bedRentAmount.style.display = 'none';
                bedIdSelect.required = false;
                bedRentInput.required = false;
                bedIdSelect.value = '';
                bedRentInput.value = '';
            }
        });
        </script>
        <script>
        document.getElementById('rent_start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const expiryDate = new Date(startDate);
            expiryDate.setFullYear(startDate.getFullYear() + 1);
            
            // Format the date as YYYY-MM-DD
            const formattedDate = expiryDate.toISOString().split('T')[0];
            document.getElementById('rent_expiry_date').value = formattedDate;
        });

        // Remove the minimum date restriction
        // document.getElementById('rent_start_date').min = today;
        </script>
</body>

</html>