<?php
class PaymentHandler {
    private $conn;
    
    // Constants for validation
    private const VALID_PAYMENT_TYPES = ['Rent Payment', 'Deposit Payment', 'Booking Fee'];
    private const VALID_PAYMENT_STATUSES = ['Successful', 'Failed', 'Pending'];
    private const VALID_TENANT_STATUSES = ['Active', 'Inactive', 'Booked', 'Rented'];
    private const VALID_PROPERTY_STATUSES = ['Available', 'Booked', 'Rented', 'Maintenance'];
    private const VALID_RENTAL_TYPES = ['Bed', 'Room'];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Validation Methods
    private function validateString($value, $maxLength, $fieldName) {
        if (empty($value)) {
            throw new Exception("$fieldName cannot be empty");
        }
        if (strlen($value) > $maxLength) {
            throw new Exception("$fieldName exceeds maximum length of $maxLength characters");
        }
        return $value;
    }

    private function validateDecimal($value, $fieldName) {
        if (!is_numeric($value)) {
            throw new Exception("$fieldName must be a number");
        }
        return number_format((float)$value, 2, '.', '');
    }

    private function validateDate($date, $format = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    private function validatePaymentType($type) {
        if (!in_array($type, self::VALID_PAYMENT_TYPES)) {
            throw new Exception("Invalid payment type: $type");
        }
        return $type;
    }

    private function validateStatus($status, $type) {
        switch ($type) {
            case 'payment':
                $valid = self::VALID_PAYMENT_STATUSES;
                break;
            case 'tenant':
                $valid = self::VALID_TENANT_STATUSES;
                break;
            case 'property':
                $valid = self::VALID_PROPERTY_STATUSES;
                break;
            default:
                throw new Exception("Invalid status type");
        }
        
        if (!in_array($status, $valid)) {
            throw new Exception("Invalid $type status: $status");
        }
        return $status;
    }

    private function validateRentalType($type) {
        if (!in_array($type, self::VALID_RENTAL_TYPES)) {
            throw new Exception("Invalid rental type: $type");
        }
        return $type;
    }

    // Core Payment Methods
    public function handlePayment($payment_type, $payment_data, $billplz_id, $payment_date) {
        try {
            switch ($payment_type) {
                case 'lumpsum':
                    return $this->handleLumpsumPayment($billplz_id, $payment_date, $payment_data);
                case 'booking':
                    return $this->handleBookingPayment($billplz_id, $payment_date, $payment_data);
                case 'flashpay':
                    return $this->handleFlashPayment($billplz_id, $payment_date, $payment_data);
                default:
                    throw new Exception("Unknown payment type: " . $payment_type);
            }
        } catch (Exception $e) {
            error_log("Error in handlePayment: " . $e->getMessage());
            throw $e;
        }
    }

    // Database Operations
    private function insertPayment($payment_id, $tenant_id, $bed_id, $room_id, $agent_id, $amount, $payment_type, $rental_type, $remarks, $payment_month, $payment_year) {
        error_log("Starting insertPayment with amount: " . $amount);
        
        // Validate inputs
        $payment_id = $this->validateString($payment_id, 50, 'PaymentID');
        $tenant_id = $this->validateString($tenant_id, 255, 'TenantID');
        $agent_id = $this->validateString($agent_id, 255, 'AgentID');
        $amount = $this->validateDecimal($amount, 'Amount');
        $payment_type = $this->validatePaymentType($payment_type);
        $rental_type = $this->validateRentalType($rental_type);
        $remarks = $this->validateString($remarks, 255, 'Remarks');

        if ($rental_type === 'Bed') {
            $bed_id = $this->validateString($bed_id, 6, 'BedID');
            $room_id = null;
        } else {
            $room_id = $this->validateString($room_id, 6, 'RoomID');
            $bed_id = null;
        }
        
        $current_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
        $payment_month = $this->validateString($payment_month, 50, 'PaymentMonth');
        $payment_year = $this->validateString($payment_year, 4, 'PaymentYear');
        $charge_fee = $this->validateDecimal(1.10, 'ChargeFee');
        $payment_status = $this->validateStatus('Successful', 'payment');
        $overpayment = 0.00;
        $claimed = 0;
        $claimer = null;

        $stmt = $this->conn->prepare("
            INSERT INTO payment (
                PaymentID, TenantID, BedID, RoomID, AgentID,
                Amount, PaymentType, PaymentStatus, DateCreated,
                Month, Year, ChargeFee, Remarks, Overpayment, Claimed, Claimer
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssdssssdsdis",
            $payment_id,
            $tenant_id,
            $bed_id,
            $room_id,
            $agent_id,
            $amount,
            $payment_type,
            $payment_status,
            $payment_month,
            $payment_year,
            $charge_fee,
            $remarks,
            $overpayment,
            $claimed,
            $claimer
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert payment: " . $stmt->error);
        }

        $this->insertPaymentHistory($payment_id, $tenant_id, $agent_id, $amount, $payment_type, $remarks);
        return true;
    }

    private function insertPaymentHistory($payment_id, $tenant_id, $agent_id, $amount, $payment_type, $remarks) {
        // Validate inputs
        $payment_id = $this->validateString($payment_id, 50, 'PaymentHistoryID');
        $tenant_id = $this->validateString($tenant_id, 255, 'TenantID');
        $agent_id = $this->validateString($agent_id, 6, 'AgentID');
        $amount = $this->validateDecimal($amount, 'Amount');
        $payment_type = $this->validatePaymentType($payment_type);
        $remarks = $this->validateString($remarks, 255, 'Remarks');
        
        $payment_date = date('Y-m-d H:i:s');
        if (!$this->validateDate($payment_date)) {
            throw new Exception("Invalid payment date format");
        }

        $stmt = $this->conn->prepare("
            INSERT INTO paymenthistory (
                PaymentHistoryID,
                TenantID,
                AgentID,
                Amount,
                PaymentDate,
                PaymentType,
                Remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssdsss",
            $payment_id,
            $tenant_id,
            $agent_id,
            $amount,
            $payment_date,
            $payment_type,
            $remarks
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert payment history: " . $stmt->error);
        }
    }

    private function insertTenant($booking_data) {
        // Validate booking data structure
        if (!isset($booking_data['tenant_info']) || !isset($booking_data['property_info']) || !isset($booking_data['agent_info'])) {
            throw new Exception("Invalid booking data structure");
        }

        // Validate rental type and IDs
        $rental_type = isset($booking_data['property_info']['bedID']) && !empty($booking_data['property_info']['bedID']) 
            ? 'Bed' 
            : 'Room';
        $rental_type = $this->validateRentalType($rental_type);

        // Validate property IDs
        $unit_id = $this->validateString($booking_data['property_info']['unitID'], 6, 'UnitID');
        $bed_id = ($rental_type === 'Bed') ? $this->validateString($booking_data['property_info']['bedID'], 6, 'BedID') : NULL;
        $room_id = ($rental_type === 'Room') ? $this->validateString($booking_data['property_info']['roomID'], 6, 'RoomID') : NULL;
        
        // Validate tenant info
        $tenant_id = $this->validateString($booking_data['tenant_info']['tenantID'], 255, 'TenantID');
        $tenant_name = $this->validateString($booking_data['tenant_info']['tenantName'], 255, 'TenantName');
        $tenant_phone = $this->validateString($booking_data['tenant_info']['tenantPhoneNo'], 20, 'TenantPhoneNo');
        $tenant_email = $this->validateString($booking_data['tenant_info']['tenantEmail'], 255, 'TenantEmail');
        
        // Validate agent ID
        $agent_id = $this->validateString($booking_data['agent_info']['agentID'], 6, 'AgentID');

        // Calculate dates
        $rent_start_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
        $start_day = (int)$rent_start_date->format('d');
        if ($start_day > 15) {
            $rent_start_date->modify('first day of next month');
        }
        $rent_start_formatted = $rent_start_date->format('Y-m-d');
        
        $rent_expiry_date = clone $rent_start_date;
        $rent_expiry_date->modify('+1 year');
        $rent_expiry_formatted = $rent_expiry_date->format('Y-m-d');

        // Validate dates
        if (!$this->validateDate($rent_start_formatted, 'Y-m-d') || !$this->validateDate($rent_expiry_formatted, 'Y-m-d')) {
            throw new Exception("Invalid date format");
        }

        // Validate tenant status
        $tenant_status = $this->validateStatus('Booked', 'tenant');

        $stmt = $this->conn->prepare("
            INSERT INTO tenant (
                TenantID, UnitID, RoomID, BedID, AgentID,
                TenantName, TenantPhoneNo, TenantEmail,
                RentStartDate, RentExpiryDate,
                TenantStatus, CreatedAt, UpdatedAt, RentalType
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ");

        $stmt->bind_param(
            "ssssssssssss",
            $tenant_id,
            $unit_id,
            $room_id,
            $bed_id,
            $agent_id,
            $tenant_name,
            $tenant_phone,
            $tenant_email,
            $rent_start_formatted,
            $rent_expiry_formatted,
            $tenant_status,
            $rental_type
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert tenant: " . $stmt->error);
        }
    }

    private function insertDeposit($tenant_id, $property_id, $agent_id, $total_amount, $paid_amount, $remaining_amount, $property_type) {
        // Validate inputs
        $deposit_id = 'DEP' . time() . rand(1000, 9999);
        $deposit_id = $this->validateString($deposit_id, 255, 'DepositID');
        $tenant_id = $this->validateString($tenant_id, 255, 'TenantID');
        $property_type = $this->validateRentalType($property_type);
        $agent_id = $this->validateString($agent_id, 6, 'AgentID');
        
        // Validate property ID
        $property_id = $this->validateString($property_id, 6, ($property_type === 'Bed' ? 'BedID' : 'RoomID'));
        
        // Validate amounts
        $total_amount = $this->validateDecimal($total_amount, 'DepositAmount');
        $paid_amount = $this->validateDecimal($paid_amount, 'PaidAmount');
        $remaining_amount = $this->validateDecimal($remaining_amount, 'RemainingAmount');
        
        // Get rent amount
        $rent_stmt = $this->conn->prepare("SELECT " . ($property_type === 'Bed' ? "BedRentAmount" : "RoomRentAmount") . " FROM " . 
                                    ($property_type === 'Bed' ? "bed" : "room") . " WHERE " . 
                                    ($property_type === 'Bed' ? "BedID" : "RoomID") . " = ?");
        $rent_stmt->bind_param("s", $property_id);
        $rent_stmt->execute();
        $result = $rent_stmt->get_result();
        $rent_row = $result->fetch_assoc();
        
        if (!$rent_row) {
            throw new Exception("Property not found");
        }
        
        $rent_amount = $this->validateDecimal(
            $rent_row[$property_type === 'Bed' ? "BedRentAmount" : "RoomRentAmount"],
            'RentAmount'
        );

        $stmt = $this->conn->prepare("
            INSERT INTO deposit (
                DepositID, TenantID, " . ($property_type === 'Bed' ? "BedID" : "RoomID") . ", AgentID,
                BedRentAmount, DepositAmount, PaidAmount, RemainingAmount, PaymentMadeDate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssdddd",
            $deposit_id,
            $tenant_id,
            $property_id,
            $agent_id,
            $rent_amount,
            $total_amount,
            $paid_amount,
            $remaining_amount
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert deposit: " . $stmt->error);
        }
    }

    // Payment Type Handlers
    private function handleLumpsumPayment($billplz_id, $payment_date, $payment_data) {
        error_log("Starting handleLumpsumPayment with data: " . print_r($payment_data, true));
        
        try {
            // Get agent info from session
            if (!isset($_SESSION['auser']) || empty($_SESSION['auser']['AgentID'])) {
                error_log("Agent info not found in session: " . print_r($_SESSION, true));
                throw new Exception("Agent information not found in session");
            }
            $agent_id = $_SESSION['auser']['AgentID'];
            
            // Get the details from metadata
            $payment_details = $payment_data['metadata']['payment_details'] ?? null;
            if (empty($payment_details) || !is_array($payment_details)) {
                error_log("Payment details not found in metadata: " . print_r($payment_data['metadata'] ?? [], true));
                throw new Exception("Invalid payment details structure");
            }

            // Begin transaction
            $this->conn->begin_transaction();

            $payment_records = [];
            $total_amount = 0;

            // Get payment month and year from metadata
            $payment_month = $payment_data['metadata']['payment_info']['selected_month'] ?? date('M');
            $payment_year = $payment_data['metadata']['payment_info']['selected_year'] ?? date('Y');

            // Validate month format
            $valid_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (!in_array($payment_month, $valid_months)) {
                throw new Exception("Invalid month format. Expected three-letter month abbreviation.");
            }

            // Process each tenant payment
            foreach ($payment_details as $payment) {
                $tenant_id = $payment['tenant_id'] ?? null;
                $amount = $payment['amount'] ?? 0;
                $months = $payment['months'] ?? 1;

                if (!$tenant_id || !$amount) {
                    throw new Exception("Invalid payment record structure");
                }

                // Get tenant details
                $tenant_stmt = $this->conn->prepare("
                    SELECT t.*, 
                           COALESCE(b.BedID, r.RoomID) as PropertyID,
                           t.RentalType
                    FROM tenant t
                    LEFT JOIN bed b ON t.BedID = b.BedID
                    LEFT JOIN room r ON t.RoomID = r.RoomID
                    WHERE t.TenantID = ?
                ");
                $tenant_stmt->bind_param("s", $tenant_id);
                $tenant_stmt->execute();
                $tenant_result = $tenant_stmt->get_result();
                $tenant_data = $tenant_result->fetch_assoc();

                if (!$tenant_data) {
                    throw new Exception("Tenant not found: " . $tenant_id);
                }

                // Insert payment record
                $payment_id = $billplz_id . '_' . $tenant_id;
                $remarks = "Lumpsum rent payment for " . $payment_month . " " . $payment_year;
                
                $this->insertPayment(
                    $payment_id,
                    $tenant_id,
                    $tenant_data['RentalType'] === 'Bed' ? $tenant_data['PropertyID'] : null,
                    $tenant_data['RentalType'] === 'Room' ? $tenant_data['PropertyID'] : null,
                    $agent_id,
                    $amount,
                    'Rent Payment',
                    $tenant_data['RentalType'],
                    $remarks,
                    $payment_month,
                    $payment_year
                );

                $total_amount += $amount;
                $payment_records[] = [
                    'tenant_id' => $tenant_id,
                    'amount' => $amount,
                    'payment_id' => $payment_id
                ];
            }

            // Store receipt data
            $_SESSION['payment_data'] = [
                'PaymentID' => $billplz_id,
                'Amount' => $total_amount,
                'DateCreated' => date('Y-m-d H:i:s'),
                'PaymentType' => 'Lumpsum Rent Payment',
                'PaymentStatus' => 'Successful',
                'PaymentRecords' => $payment_records,
                'StartMonth' => $payment_month,
                'StartYear' => $payment_year,
                'Months' => $payment_data['metadata']['months'] ?? 1,
                'AgentName' => $_SESSION['auser']['AgentName'] ?? 'N/A'
            ];

            error_log("Payment data stored in session: " . print_r($_SESSION['payment_data'], true));

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in handleLumpsumPayment: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleBookingPayment($billplz_id, $payment_date, $payment_data) {
        try {
            // Get current date for payment month/year
            $current_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
            $payment_month = $current_date->format('M'); // This will give us the three-letter month
            $payment_year = $current_date->format('Y');

            // Validate month format
            $valid_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (!in_array($payment_month, $valid_months)) {
                throw new Exception("Invalid month format. Expected three-letter month abbreviation.");
            }

            // Get booking data from session
            $booking_data = $_SESSION['payment_success']['metadata']['booking_data'] ?? null;
            if (!$booking_data) {
                error_log("Session payment_success data: " . print_r($_SESSION['payment_success'], true));
                throw new Exception("Booking data not found in payment details");
            }

            // Extract required data
            $tenant_id = $booking_data['tenant_info']['tenantID'];
            $bed_id = $booking_data['property_info']['bedID'] ?? null;
            $room_id = $booking_data['property_info']['roomID'] ?? null;
            $agent_id = $booking_data['agent_info']['agentID'];
            $rental_type = !empty($bed_id) ? 'Bed' : 'Room';
            
            // Get rent amount from the appropriate table
            if ($rental_type === 'Bed') {
                $rent_stmt = $this->conn->prepare("SELECT BedRentAmount FROM bed WHERE BedID = ?");
                $rent_stmt->bind_param("s", $bed_id);
            } else {
                $rent_stmt = $this->conn->prepare("SELECT RoomRentAmount FROM room WHERE RoomID = ?");
                $rent_stmt->bind_param("s", $room_id);
            }
            $rent_stmt->execute();
            $rent_result = $rent_stmt->get_result();
            $rent_row = $rent_result->fetch_assoc();
            $rent_amount = $rental_type === 'Bed' ? $rent_row['BedRentAmount'] : $rent_row['RoomRentAmount'];
            
            // Calculate deposit (2 months rent + processing fee)
            $processing_fee = $booking_data['payment_info']['processing_fee'];
            $total_deposit = ($rent_amount * 2) + $processing_fee;
            
            // Begin transaction
            $this->conn->begin_transaction();

            // 1. Insert tenant record
            $this->insertTenant($booking_data);

            // 2. Insert deposit record
            if ($rental_type === 'Bed') {
                $this->insertDeposit($tenant_id, $bed_id, $agent_id, $total_deposit, $total_deposit, 0, 'Bed');
            } else {
                $this->insertDeposit($tenant_id, $room_id, $agent_id, $total_deposit, $total_deposit, 0, 'Room');
            }

            // 3. Insert deposit payment record
            $deposit_remarks = "Initial Deposit Payment (Bill ID: {$billplz_id})";
            $this->insertPayment(
                $billplz_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $total_deposit,
                'Deposit Payment',
                $rental_type,
                $deposit_remarks,
                $payment_month,
                $payment_year
            );

            // 4. Insert first month's rent payment record
            $rent_payment_id = $billplz_id . '_RENT';
            $rent_remarks = "First Month Rent Payment (Bill ID: {$billplz_id})";
            $this->insertPayment(
                $rent_payment_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $rent_amount,
                'Rent Payment',
                $rental_type,
                $rent_remarks,
                $payment_month,
                $payment_year
            );

            // 5. Update statuses
            $this->updatePropertyAndTenantStatus($tenant_id, $rental_type === 'Bed' ? $bed_id : $room_id, $rental_type, 'Rented');

            // Commit transaction
            $this->conn->commit();

            // Get payment data for receipt
            $payment_data = $this->prepareReceiptData($billplz_id);
            if (!$payment_data) {
                throw new Exception("Failed to retrieve payment data for receipt");
            }

            // Store receipt data
            $_SESSION['payment_data'] = [
                'PaymentID' => $billplz_id,
                'Amount' => $total_deposit + $rent_amount,
                'DateCreated' => date('Y-m-d H:i:s'),
                'AgentName' => $booking_data['agent_info']['agentName'],
                'TenantName' => $booking_data['tenant_info']['tenantName'],
                'TenantEmail' => $booking_data['tenant_info']['tenantEmail'],
                'TenantPhoneNo' => $booking_data['tenant_info']['tenantPhoneNo'],
                'BedID' => $bed_id,
                'BedNo' => $booking_data['property_info']['bedNo'],
                'RoomID' => $room_id,
                'RoomNo' => $booking_data['property_info']['roomNo'],
                'UnitNo' => $booking_data['property_info']['unitNo'],
                'PaymentType' => 'Booking Fee',
                'PaymentStatus' => 'Successful',
                'RentalType' => $rental_type
            ];

            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in handleBookingPayment: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleFlashPayment($billplz_id, $payment_date, $payment_data) {
        try {
            error_log("Starting handleFlashPayment with data: " . print_r($payment_data, true));
            error_log("Session before payment processing: " . print_r($_SESSION, true));

            // Begin transaction
            $this->conn->begin_transaction();

            $payment_amount = $payment_data['amount'] ?? 0;
            if ($payment_amount > 1000) {
                $payment_amount = $payment_amount / 100;
            }

            $metadata = $payment_data['metadata'] ?? null;
            if (!$metadata) {
                throw new Exception("Missing payment metadata");
            }

            // Get payment month and year from metadata or use current date
            $month_name = $metadata['payment_info']['selected_month'] ?? date('M');
            $payment_year = $metadata['payment_info']['selected_year'] ?? date('Y');
            
            // Validate month format (should be three letters)
            $valid_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (!in_array($month_name, $valid_months)) {
                throw new Exception("Invalid month format. Expected three-letter month abbreviation.");
            }

            // Get agent information from payment_success metadata
            $flashpay_data = $_SESSION['payment_success']['metadata']['flashpay_data'] ?? null;
            if (!$flashpay_data) {
                error_log("Missing flashpay_data in session: " . print_r($_SESSION['payment_success'] ?? [], true));
                throw new Exception("Missing flashpay data");
            }

            // Extract tenant ID from flashpay data
            $tenant_id = $flashpay_data['tenant_info']['id'] ?? null;
            if (!$tenant_id) {
                error_log("Missing tenant ID in flashpay_data: " . print_r($flashpay_data, true));
                throw new Exception("Missing tenant ID");
            }

            $agent_info = $flashpay_data['agent_info'] ?? null;
            if (!$agent_info || !isset($agent_info['agentID'])) {
                error_log("Missing agent_info in flashpay_data: " . print_r($flashpay_data, true));
                throw new Exception("Missing agent information");
            }
            $agent_id = $agent_info['agentID'];

            $property_info = $metadata['property_info'] ?? null;
            $payment_info = $metadata['payment_info'] ?? null;
            
            if (!$property_info || !$payment_info) {
                throw new Exception("Missing required payment information");
            }

            $bed_id = $property_info['bedID'] ?? null;
            $room_id = $property_info['roomID'] ?? null;
            $rental_type = $payment_info['rental_type'] ?? 'Bed';

            $remarks = "Rent payment for {$month_name} {$payment_year} (Bill ID: {$billplz_id})";
            
            error_log("Inserting flashpay payment with data: " . print_r([
                'payment_id' => $billplz_id,
                'tenant_id' => $tenant_id,
                'bed_id' => $bed_id,
                'room_id' => $room_id,
                'agent_id' => $agent_id,
                'amount' => $payment_amount,
                'rental_type' => $rental_type,
                'remarks' => $remarks,
                'month' => $month_name,
                'year' => $payment_year
            ], true));

            $result = $this->insertPayment(
                $billplz_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $payment_amount,
                'Rent Payment',
                $rental_type,
                $remarks,
                $month_name,
                $payment_year
            );

            if (!$result) {
                throw new Exception("Failed to insert payment record");
            }

            // Store receipt data
            $_SESSION['payment_data'] = [
                'PaymentID' => $billplz_id,
                'Amount' => $payment_amount,
                'DateCreated' => date('Y-m-d H:i:s'),
                'AgentName' => $agent_info['agentName'],
                'TenantName' => $flashpay_data['tenant_info']['name'] ?? 'N/A',
                'TenantEmail' => $flashpay_data['tenant_info']['email'] ?? 'N/A',
                'TenantPhoneNo' => $flashpay_data['tenant_info']['phone'] ?? 'N/A',
                'BedID' => $bed_id ?? 'N/A',
                'BedNo' => $property_info['bedNo'] ?? 'N/A',
                'RoomID' => $room_id ?? 'N/A',
                'RoomNo' => $property_info['roomNo'] ?? 'N/A',
                'UnitNo' => $property_info['unitNo'] ?? 'N/A',
                'PaymentType' => 'Rent Payment',
                'PaymentStatus' => 'Successful',
                'RentalType' => $rental_type
            ];

            error_log("Payment data stored in session: " . print_r($_SESSION['payment_data'], true));

            // Commit transaction
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in handleFlashPayment: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleRentPayment($payment_details, $flashpay_data, $payment_amount) {
        error_log("Starting handleRentPayment");
        error_log("Payment Details: " . print_r($payment_details, true));
        error_log("Flashpay Data: " . print_r($flashpay_data, true));
        
        try {
            // Get payment month and year from flashpay data or use current date
            $payment_month = $flashpay_data['payment_info']['selected_month'] ?? date('M');
            $payment_year = $flashpay_data['payment_info']['selected_year'] ?? date('Y');

            $tenant_id = $flashpay_data['tenant_info']['id'];
            $rental_type = $flashpay_data['payment_info']['rental_type'];
            $bed_id = ($rental_type === 'Bed') ? $flashpay_data['property_info']['bedID'] : null;
            $room_id = ($rental_type === 'Room') ? $flashpay_data['property_info']['roomID'] : null;
            $agent_id = $flashpay_data['agent_info']['agentID'];
            
            $payment_id = $payment_details['id'];
            
            $this->conn->begin_transaction();

            $remarks = sprintf("Rent payment for %s %s", $payment_month, $payment_year);
            
            $result = $this->insertPayment(
                $payment_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $payment_amount,
                'Rent Payment',
                $rental_type,
                $remarks,
                $payment_month,
                $payment_year
            );
            
            if ($rental_type === 'Room' && $room_id) {
                $this->updatePropertyAndTenantStatus($tenant_id, $room_id, 'Room', 'Active');
            } else if ($rental_type === 'Bed' && $bed_id) {
                $this->updatePropertyAndTenantStatus($tenant_id, $bed_id, 'Bed', 'Active');
            }
            
            $this->conn->commit();
            
            return $payment_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in handleRentPayment: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleDepositPayment($payment_details, $flashpay_data, $payment_amount, $deposit_data) {
        try {
            // Begin transaction
            $this->conn->begin_transaction();

            // Get current date for payment month/year
            $current_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
            $payment_month = $current_date->format('M');
            $payment_year = $current_date->format('Y');

            $tenant_id = $flashpay_data['tenant_info']['id'];
            $rental_type = $flashpay_data['payment_info']['rental_type'];
            $bed_id = ($rental_type === 'Bed') ? $flashpay_data['property_info']['bedID'] : null;
            $room_id = ($rental_type === 'Room') ? $flashpay_data['property_info']['roomID'] : null;
            $agent_id = $flashpay_data['agent_info']['agentID'];

            // Validate and get rent amount
            $rent_amount = $deposit_data['BedRentAmount'] ?? $deposit_data['RoomRentAmount'];
            if (!$rent_amount) {
                throw new Exception("Could not determine rent amount");
            }
            $rent_amount = $this->validateDecimal($rent_amount, 'RentAmount');

            if ($deposit_data) {
                $deposit_remaining = $deposit_data['RemainingAmount'];
                $deposit_payment = min($payment_amount, $deposit_remaining);
                $rent_payment = $payment_amount - $deposit_payment;

                $new_remaining = $deposit_remaining - $deposit_payment;
                $new_paid = $deposit_data['PaidAmount'] + $deposit_payment;
                
                // Update deposit record
                $stmt = $this->conn->prepare("
                    UPDATE deposit 
                    SET PaidAmount = ?, 
                        RemainingAmount = ? 
                    WHERE TenantID = ?
                ");
                $stmt->bind_param("dds", $new_paid, $new_remaining, $tenant_id);
                $stmt->execute();

                // Insert deposit payment record
                if ($deposit_payment > 0) {
                    $deposit_payment_id = $payment_details['id'] . '_DEP';
                    $this->insertPayment(
                        $deposit_payment_id,
                        $tenant_id,
                        $bed_id,
                        $room_id,
                        $agent_id,
                        $deposit_payment,
                        'Deposit Payment',
                        $rental_type,
                        "Remaining deposit payment",
                        $payment_month,
                        $payment_year
                    );
                }

                // If deposit is now fully paid
                if ($new_remaining <= 0) {
                    // Update statuses
                    $this->updatePropertyAndTenantStatus(
                        $tenant_id,
                        $rental_type === 'Room' ? $room_id : $bed_id,
                        $rental_type,
                        'Rented'
                    );

                    // Check if first month's rent payment already exists
                    $current_month = date('M');
                    $current_year = date('Y');
                    
                    $check_stmt = $this->conn->prepare("
                        SELECT PaymentID 
                        FROM payment 
                        WHERE TenantID = ? 
                        AND PaymentType = 'Rent Payment'
                        AND Month = ? 
                        AND Year = ?
                    ");
                    
                    $check_stmt->bind_param("sss", $tenant_id, $current_month, $current_year);
                    $check_stmt->execute();
                    
                    // If no rent payment exists for current month, create one
                    if ($check_stmt->get_result()->num_rows === 0) {
                        $rent_payment_id = $payment_details['id'] . '_RENT';
                        $this->insertPayment(
                            $rent_payment_id,
                            $tenant_id,
                            $bed_id,
                            $room_id,
                            $agent_id,
                            $rent_amount,
                            'Rent Payment',
                            $rental_type,
                            "First month rent payment (included in deposit)",
                            $current_month,
                            $current_year
                        );
                    }
                }

                // If there's remaining payment amount after deposit
                if ($rent_payment > 0) {
                    $next_month = new DateTime('first day of next month');
                    $this->insertPayment(
                        $payment_details['id'],
                        $tenant_id,
                        $bed_id,
                        $room_id,
                        $agent_id,
                        $rent_payment,
                        'Rent Payment',
                        $rental_type,
                        "Advance rent payment for " . $next_month->format('M Y'),
                        $next_month->format('M'),
                        $next_month->format('Y')
                    );
                }
            }

            // Commit transaction
            $this->conn->commit();

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error in handleDepositPayment: " . $e->getMessage());
            throw $e;
        }
    }

    private function handleFirstRentPayment($booking_data) {
        $rent_payment_id = 'RENT' . time() . rand(1000, 9999);
        $advance_rental = $booking_data['payment_info']['advance_rental'];
        
        $current_month = date('M');
        $current_year = date('Y');
        
        $check_stmt = $this->conn->prepare("
            SELECT PaymentID 
            FROM payment 
            WHERE TenantID = ? 
            AND PaymentType = 'Rent Payment'
            AND Month = ? 
            AND Year = ?
        ");
        
        $check_stmt->bind_param("sss", 
            $booking_data['tenant_info']['tenantID'],
            $current_month,
            $current_year
        );
        
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows === 0) {
            $this->insertPayment(
                $rent_payment_id,
                $booking_data['tenant_info']['tenantID'],
                $booking_data['property_info']['bedID'],
                $booking_data['property_info']['roomID'],
                $booking_data['agent_info']['agentID'],
                $advance_rental,
                'Rent Payment',
                $booking_data['payment_info']['rental_type'],
                "First month rent payment",
                $current_month,
                $current_year
            );
        }
    }

    // Status Management
    private function updatePropertyAndTenantStatus($tenant_id, $property_id, $property_type, $status) {
        // Update tenant status
        $stmt = $this->conn->prepare("
            UPDATE tenant 
            SET TenantStatus = ?, UpdatedAt = NOW() 
            WHERE TenantID = ?
        ");
        
        $stmt->bind_param("ss", $status, $tenant_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update tenant status");
        }

        // Get AgentID from tenant table
        $agent_stmt = $this->conn->prepare("
            SELECT AgentID 
            FROM tenant 
            WHERE TenantID = ?
        ");
        $agent_stmt->bind_param("s", $tenant_id);
        $agent_stmt->execute();
        $agent_result = $agent_stmt->get_result();
        $agent_row = $agent_result->fetch_assoc();
        $agent_id = $agent_row['AgentID'];

        // Update property status and AgentID
        $property_table = strtolower($property_type);
        $property_id_column = $property_type . "ID";
        $status_column = $property_type . "Status";
        
        $stmt = $this->conn->prepare("
            UPDATE {$property_table}
            SET {$status_column} = ?, 
                AgentID = ?,
                UpdatedAt = NOW()
            WHERE {$property_id_column} = ?
        ");
        
        $stmt->bind_param("sss", $status, $agent_id, $property_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update {$property_type} status");
        }
    }

    // Utility Methods
    public function checkDepositStatus($tenant_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                DepositID, DepositAmount, PaidAmount, RemainingAmount,
                PaymentMadeDate, BedRentAmount, RoomID, BedID
            FROM deposit 
            WHERE TenantID = ?
            ORDER BY PaymentMadeDate DESC
            LIMIT 1
        ");
        
        $stmt->bind_param("s", $tenant_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to check deposit status: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $deposit = $result->fetch_assoc();

        if (!$deposit) {
            throw new Exception("No deposit record found for tenant: " . $tenant_id);
        }

        return [
            'is_fully_paid' => $deposit['RemainingAmount'] <= 0,
            'remaining_amount' => $deposit['RemainingAmount'],
            'total_amount' => $deposit['DepositAmount'],
            'paid_amount' => $deposit['PaidAmount'],
            'deposit_id' => $deposit['DepositID'],
            'payment_date' => $deposit['PaymentMadeDate'],
            'bed_rent_amount' => $deposit['BedRentAmount'],
            'room_id' => $deposit['RoomID'],
            'bed_id' => $deposit['BedID']
        ];
    }

    private function generatePaymentID() {
        $max_attempts = 5;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            $prefix = 'RENT';
            $timestamp = time();
            $random = mt_rand(1000, 9999);
            $payment_id = $prefix . $timestamp . $random;
            
            $stmt = $this->conn->prepare("SELECT PaymentID FROM payment WHERE PaymentID = ?");
            $stmt->bind_param("s", $payment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return $payment_id;
            }
            
            $attempt++;
            usleep(100000);
        }
        
        throw new Exception("Could not generate unique payment ID after {$max_attempts} attempts");
    }

    public function prepareReceiptData($payment_id) {
        $max_retries = 3;
        $retry_count = 0;
        
        while ($retry_count < $max_retries) {
            try {
                // Check if this is a lumpsum payment by looking for payment records with the base ID as prefix
                $lumpsum_check = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM payment 
                    WHERE PaymentID LIKE CONCAT(?, '_%')
                ");
                $lumpsum_check->bind_param("s", $payment_id);
                $lumpsum_check->execute();
                $count_result = $lumpsum_check->get_result();
                $count_row = $count_result->fetch_assoc();

                if ($count_row['count'] > 0) {
                    // This is a lumpsum payment, get all related payments
                    $stmt = $this->conn->prepare("
                        SELECT 
                            p.*,
                            t.TenantName,
                            t.TenantEmail,
                            t.TenantPhoneNo,
                            t.RentalType,
                            COALESCE(b.BedID, '') as BedID,
                            COALESCE(b.BedNo, '') as BedNo,
                            COALESCE(r.RoomID, '') as RoomID,
                            COALESCE(r.RoomNo, '') as RoomNo,
                            COALESCE(u.UnitNo, '') as UnitNo,
                            a.AgentName
                        FROM payment p
                        INNER JOIN tenant t ON t.TenantID = p.TenantID
                        LEFT JOIN bed b ON b.BedID = p.BedID
                        LEFT JOIN room r ON r.RoomID = COALESCE(p.RoomID, b.RoomID)
                        LEFT JOIN unit u ON u.UnitID = r.UnitID
                        LEFT JOIN agent a ON a.AgentID = p.AgentID
                        WHERE p.PaymentID LIKE CONCAT(?, '_%')
                    ");
                    $stmt->bind_param("s", $payment_id);
                } else {
                    // Single payment
                    $stmt = $this->conn->prepare("
                        SELECT 
                            p.*,
                            t.TenantName,
                            t.TenantEmail,
                            t.TenantPhoneNo,
                            t.RentalType,
                            COALESCE(b.BedID, '') as BedID,
                            COALESCE(b.BedNo, '') as BedNo,
                            COALESCE(r.RoomID, '') as RoomID,
                            COALESCE(r.RoomNo, '') as RoomNo,
                            COALESCE(u.UnitNo, '') as UnitNo,
                            a.AgentName
                        FROM payment p
                        INNER JOIN tenant t ON t.TenantID = p.TenantID
                        LEFT JOIN bed b ON b.BedID = p.BedID
                        LEFT JOIN room r ON r.RoomID = COALESCE(p.RoomID, b.RoomID)
                        LEFT JOIN unit u ON u.UnitID = r.UnitID
                        LEFT JOIN agent a ON a.AgentID = p.AgentID
                        WHERE p.PaymentID = ?
                    ");
                    $stmt->bind_param("s", $payment_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to execute payment query: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                
                if ($count_row['count'] > 0) {
                    // For lumpsum payments, return all payment records
                    $payments = [];
                    $total_amount = 0;
                    $agent_name = '';
                    
                    while ($row = $result->fetch_assoc()) {
                        $payments[] = $row;
                        $total_amount += $row['Amount'];
                        $agent_name = $row['AgentName']; // All payments should have the same agent
                    }
                    
                    if (!empty($payments)) {
                        // Return a consolidated payment record for lumpsum
                        return [
                            'PaymentID' => $payment_id,
                            'Amount' => $total_amount,
                            'DateCreated' => $payments[0]['DateCreated'],
                            'PaymentType' => 'Lumpsum Rent Payment',
                            'PaymentStatus' => 'Successful',
                            'AgentName' => $agent_name,
                            'Payments' => $payments
                        ];
                    }
                } else {
                    // For single payments, return the payment record
                    $payment_data = $result->fetch_assoc();
                    if ($payment_data) {
                        return $payment_data;
                    }
                }
                
                $retry_count++;
                usleep(500000);
                
            } catch (Exception $e) {
                error_log("Error in prepareReceiptData: " . $e->getMessage());
                $retry_count++;
                if ($retry_count >= $max_retries) {
                    throw $e;
                }
                usleep(500000);
            }
        }
        
        throw new Exception("Failed to retrieve payment data after {$max_retries} attempts");
    }

    private function validateAndExtract($data, $path, $optional = false) {
        $parts = explode('.', $path);
        $current = $data;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                if ($optional) {
                    return null;
                }
                throw new Exception("Missing required field: $path");
            }
            $current = $current[$part];
        }
        
        return $current;
    }

    // Session Management
    public static function clearPaymentSession() {
        unset($_SESSION['payment_details']);
        unset($_SESSION['billplz_bill_id']);
        unset($_SESSION['lumpsum_payment']);
        unset($_SESSION['booking_data']);
        unset($_SESSION['flashpay_data']);
    }

    public static function getErrorRedirect() {
        return "../module-payment/successpayment.php";
    }
}
