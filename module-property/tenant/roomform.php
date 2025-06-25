<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

$error = '';
$success = '';

// Check if room ID is provided
if (!isset($_GET['roomID'])) {
    header("Location: index.php");
    exit;
}

$room_id = $_GET['roomID'];

// Fetch room details
$stmt = $conn->prepare("
    SELECT r.*, u.UnitNo, u.UnitID,
           (SELECT COUNT(*) FROM bed b WHERE b.RoomID = r.RoomID) as total_beds,
           r.BaseRentAmount as RoomRentAmount
    FROM room r
    JOIN unit u ON r.UnitID = u.UnitID
    WHERE r.RoomID = ?
");

$stmt->bind_param("s", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room_data = $result->fetch_assoc();
$stmt->close();

if (!$room_data) {
    $error = "Room not found";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Your existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentronics</title>
    <link href="/img/favicon.ico" rel="icon">
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
    <style>
        .booking-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .room-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
        }

        .price-tag {
            font-size: 1.5rem;
            color: #dc3545;
            font-weight: bold;
        }

        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <!-- Navbar Start -->
    <?php include('../../nav_sidebar.php'); ?>
    <!-- Navbar End -->

    <div class="container mt-5">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="booking-form">
                <h2 class="text-center mb-4">Room Booking Form</h2>

                <form id="roomBookingForm">
                <!-- Room Details Section -->
                <div class="room-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Unit <?php echo htmlspecialchars($room_data['UnitNo']); ?></h5>
                            <p>Room <?php echo htmlspecialchars($room_data['RoomNo']); ?></p>
                            <p>Total Beds: <?php echo htmlspecialchars($room_data['total_beds']); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-1">Monthly Rent:</p>
                            <div class="price-tag">RM <?php echo number_format($room_data['RoomRentAmount'], 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Add tenant count selector -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="tenantCount" class="form-label required-field">Number of Tenants</label>
                        <select class="form-control" id="tenantCount" name="tenantCount" required>
                            <option value="1" selected>1 Tenant</option>
                            <option value="2">2 Tenants</option>
                            <option value="3">3 Tenants</option>
                        </select>
                    </div>
                </div>

                <!-- Tenant Information Section - Will be duplicated -->
                <div id="tenantsContainer">
                    <!-- Tenant 1 -->
                    <div class="tenant-section" data-tenant="1">
                        <h4 class="mb-3">Tenant 1 Information</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tenantName_1" class="form-label required-field">Full Name</label>
                                <input type="text" class="form-control" id="tenantName_1" name="tenants[1][tenantName]" required>
                            </div>
                            <div class="col-md-6">
                                <label for="passport_1" class="form-label required-field">IC/Passport Number</label>
                                <input type="text" class="form-control" id="passport_1" name="tenants[1][passport]" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tenantEmail_1" class="form-label required-field">Email</label>
                                <input type="email" class="form-control" id="tenantEmail_1" name="tenants[1][tenantEmail]" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tenantPhoneNo_1" class="form-label required-field">Phone Number</label>
                                <input type="tel" class="form-control" id="tenantPhoneNo_1" name="tenants[1][tenantPhoneNo]" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rental Details -->
                <div class="col-md-6">
                    <label for="rentStartDate" class="form-label required-field">Rental Start Date</label>
                    <input type="date" class="form-control" id="rentStartDate" name="rentStartDate" required
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label for="duration" class="form-label required-field">Rental Duration (months)</label>
                    <select class="form-control" id="duration" name="duration" required>
                        <option value="6">6 months</option>
                        <option value="12">12 months</option>
                    </select>
                </div>

                <!-- Additional Information -->
                <div class="col-12">
                    <label for="specialRequests" class="form-label">Special Requests/Notes</label>
                    <textarea class="form-control" id="specialRequests" name="specialRequests" rows="3"></textarea>
                </div>

                <!-- Terms and Conditions -->
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5">Proceed to Payment</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Booking Terms:</h6>
                    <ul>
                        <li>Minimum rental period is 6 months</li>
                        <li>Security deposit equivalent to 1 month's rent</li>
                        <li>Advance rental payment of 1 month required</li>
                        <li>Processing fee of RM 50</li>
                        <li>Utilities not included in rental price</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../../js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Add console log to verify the event handler is attached
        console.log('Document ready');

        $('#tenantCount').on('change', function() {
            console.log('Tenant count changed to:', $(this).val());  // Debug log
            
            const count = parseInt($(this).val());
            const container = $('#tenantsContainer');
            
            // Remove existing extra tenant sections
            $('.tenant-section:not(:first)').remove();
            
            // Add new tenant sections as needed
            for(let i = 2; i <= count; i++) {
                console.log('Adding tenant section:', i);  // Debug log
                const newSection = $('.tenant-section:first').clone();
                newSection.attr('data-tenant', i);
                newSection.find('h4').text(`Tenant ${i} Information`);
                
                // Update IDs and names
                newSection.find('input').each(function() {
                    const field = $(this);
                    const oldId = field.attr('id');
                    const newId = oldId.replace('_1', `_${i}`);
                    field.attr('id', newId);
                    field.attr('name', `tenants[${i}][${oldId.split('_')[0]}]`);
                    field.val(''); // Clear values
                });
                
                container.append(newSection);
            }
        });

        // Update form submission handling
        $('#roomBookingForm').on('submit', function(e) {
            e.preventDefault();
            
            // Collect all tenant information
            let tenants = [];
            $('.tenant-section').each(function(index) {
                const tenantNum = index + 1;
                tenants.push({
                    tenantName: $(`#tenantName_${tenantNum}`).val(),
                    tenantEmail: $(`#tenantEmail_${tenantNum}`).val(),
                    tenantPhoneNo: $(`#tenantPhoneNo_${tenantNum}`).val(),
                    passport: $(`#passport_${tenantNum}`).val()
                });
            });

            let formData = {
                tenant_info: tenants,
                property_info: {
                    roomID: '<?php echo $room_id; ?>',
                    unitID: '<?php echo $room_data['UnitID']; ?>',
                    roomNo: '<?php echo $room_data['RoomNo']; ?>',
                    unitNo: '<?php echo $room_data['UnitNo']; ?>'
                },
                payment_info: {
                    amount: <?php echo $room_data['RoomRentAmount']; ?>,
                    duration: $('#duration').val()
                },
                special_requests: $('#specialRequests').val()
            };

            // Store booking data in session using AJAX
            $.ajax({
                url: 'store_booking_session.php',
                method: 'POST',
                data: { booking_data: JSON.stringify(formData) },
                success: function(response) {
                    // Parse the response
                    let responseData = JSON.parse(response);
                    
                    if (responseData.success) {
                        // Redirect to bookingcheckout.php with the correct parameters
                        // Since this is a room booking, we need to adjust parameters for compatibility
                        // with the existing checkout page
                        window.location.href = 'bookingcheckout.php?roomID=<?php echo $room_id; ?>&tenantID=' + 
                            (formData.tenant_info[0].passport || 'TEMP_' + Date.now());
                    } else {
                        alert('Error: ' + (responseData.error || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred. Please try again.');
                    console.error('AJAX Error:', error);
                }
            });
        });

        // Date validation
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('rentStartDate').setAttribute('min', today);
    });
    </script>
    <script src="../../js/main.js"></script>
</body>
</html> 