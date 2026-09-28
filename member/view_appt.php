<?php
session_start();
include __DIR__ . '/../auth/connection.php';

// Suppress PHP error output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

// Check session
if (!isset($_SESSION['Member_IC'])) {
    error_log("Session Member_IC not set. Redirecting to login.");
    header("Location: ../auth/login.php");
    exit();
}

// Get member details
$member_ic = $_SESSION['Member_IC'];
$member_query = "SELECT Member_Name, Member_Email, Member_PhoneNo FROM Member WHERE Member_IC = ?";
$stmt = $conn->prepare($member_query);
if ($stmt === false) {
    error_log("Error preparing member query: " . $conn->error);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error. Unable to fetch member details.']);
    exit();
}
$stmt->bind_param("s", $member_ic);
if (!$stmt->execute()) {
    error_log("Member query execution failed: " . $stmt->error);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error. Unable to execute member query.']);
    exit();
}
$member_result = $stmt->get_result();
if ($member_result && $member_result->num_rows > 0) {
    $member_data = $member_result->fetch_assoc();
    $member_name = $member_data['Member_Name'] ?? 'Not available';
    $member_email = $member_data['Member_Email'] ?? 'Not available';
    $member_phone = $member_data['Member_PhoneNo'] ?? 'Not available';
} else {
    error_log("No member found for Member_IC: $member_ic");
    $member_name = 'Not available';
    $member_email = 'Not available';
    $member_phone = 'Not available';
}
$stmt->close();

// Check for active package
$active_package_query = "SELECT d.Package_ID, p.Package_Type 
                        FROM Detail d 
                        JOIN Package p ON d.Package_ID = p.Package_ID 
                        WHERE d.Member_IC = ? AND d.Status = 'Active' AND d.Expired_Date >= CURRENT_DATE";
$stmt = $conn->prepare($active_package_query);
if ($stmt === false) {
    error_log("Error preparing package query: " . $conn->error);
    $has_active_package = false;
    $active_package = null;
    $discount_percentage = 0;
} else {
    $stmt->bind_param("s", $member_ic);
    if (!$stmt->execute()) {
        error_log("Package query execution failed: " . $stmt->error);
        $has_active_package = false;
        $active_package = null;
        $discount_percentage = 0;
    } else {
        $active_package_result = $stmt->get_result();
        $has_active_package = $active_package_result->num_rows > 0;
        $active_package = $has_active_package ? $active_package_result->fetch_assoc() : null;
        $discount_percentage = 0;
        if ($has_active_package) {
            switch ($active_package['Package_ID']) {
                case 1: $discount_percentage = 5; break;
                case 2: $discount_percentage = 10; break;
                case 3: $discount_percentage = 15; break;
            }
        }
    }
    $stmt->close();
}

// Get treatment and staff information
$treatment_query = "SELECT Treatment_ID, Treatment_Type, Treatment_Desc, Treatment_Cost FROM Treatment";
$treatment_result = mysqli_query($conn, $treatment_query);
if (!$treatment_result) {
    error_log("Treatment query failed: " . mysqli_error($conn));
    $treatment_result = false;
}

$staff_query = "SELECT Staff_IC, Staff_Name FROM Staff";
$staff_result = mysqli_query($conn, $staff_query);
if (!$staff_result) {
    error_log("Staff query failed: " . mysqli_error($conn));
    $staff_result = false;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paymentMethod'])) {
    try {
        // Sanitize inputs
        $treatment_id = filter_input(INPUT_POST, 'treatmentId', FILTER_SANITIZE_NUMBER_INT);
        $staff_ic = filter_input(INPUT_POST, 'staffIc', FILTER_SANITIZE_STRING);
        $appointment_date = filter_input(INPUT_POST, 'appointmentDate', FILTER_SANITIZE_STRING);
        $appointment_time = filter_input(INPUT_POST, 'appointmentTime', FILTER_SANITIZE_STRING);
        $treatment_cost = filter_input(INPUT_POST, 'treatmentCost', FILTER_VALIDATE_FLOAT);
        $payment_method = filter_input(INPUT_POST, 'paymentMethod', FILTER_SANITIZE_STRING);
        $patient_type = filter_input(INPUT_POST, 'patientType', FILTER_SANITIZE_STRING);
        $dental_exp = filter_input(INPUT_POST, 'dentalExp', FILTER_SANITIZE_STRING);

        // Log POST data
        error_log("Payment POST data: " . json_encode($_POST));

        // Validate inputs
        if (empty($treatment_id) || empty($staff_ic) || empty($appointment_date) || empty($appointment_time) || 
            $treatment_cost === false || $treatment_cost <= 0 || empty($payment_method) || empty($patient_type) || empty($dental_exp)) {
            throw new Exception("Invalid or missing form data: treatment_id=$treatment_id, staff_ic=$staff_ic, date=$appointment_date, time=$appointment_time, cost=$treatment_cost, method=$payment_method, patient_type=$patient_type, dental_exp=$dental_exp");
        }

        // Validate date and time
        if (!DateTime::createFromFormat('Y-m-d', $appointment_date)) {
            throw new Exception("Invalid appointment date format: $appointment_date");
        }
        if (!DateTime::createFromFormat('H:i', $appointment_time)) {
            throw new Exception("Invalid appointment time format: $appointment_time");
        }

        // Validate payment method
        if (!in_array($payment_method, ['online_banking', 'credit_card'])) {
            throw new Exception("Invalid payment method: $payment_method");
        }

        // Fetch treatment details
        $treatment_details_query = "SELECT Treatment_Type, Treatment_Cost FROM Treatment WHERE Treatment_ID = ?";
        $stmt = $conn->prepare($treatment_details_query);
        if ($stmt === false) {
            throw new Exception("Treatment details query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("i", $treatment_id);
        if (!$stmt->execute()) {
            throw new Exception("Treatment details query execution failed: " . $stmt->error);
        }
        $treatment_details = $stmt->get_result()->fetch_assoc();
        if (!$treatment_details) {
            throw new Exception("No treatment found for Treatment_ID: $treatment_id");
        }
        $treatment_type = $treatment_details['Treatment_Type'] ?? 'Unknown';
        $original_cost = floatval($treatment_details['Treatment_Cost'] ?? 0);
        $stmt->close();

        // Fetch staff details
        $staff_details_query = "SELECT Staff_Name FROM Staff WHERE Staff_IC = ?";
        $stmt = $conn->prepare($staff_details_query);
        if ($stmt === false) {
            throw new Exception("Staff details query preparation failed: " . $conn->error);
        }
        $stmt->bind_param("s", $staff_ic);
        if (!$stmt->execute()) {
            throw new Exception("Staff details query execution failed: " . $stmt->error);
        }
        $staff_details = $stmt->get_result()->fetch_assoc();
        if (!$staff_details) {
            throw new Exception("No staff found for Staff_IC: $staff_ic");
        }
        $staff_name = $staff_details['Staff_Name'] ?? 'Unknown';
        $stmt->close();

        // Calculate discount
        $discount_amount = ($discount_percentage / 100) * $original_cost;
        $final_cost = $original_cost - $discount_amount;

        // Start transaction
        $conn->begin_transaction();
        try {
            // Insert appointment
            $appointment_status = 'Pending';
            $appointment_query = "INSERT INTO Appointment (Appointment_Date, Appointment_Time, Appointment_Status, Treatment_ID, Staff_IC, Member_IC) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($appointment_query);
            if ($stmt === false) {
                throw new Exception("Appointment query preparation failed: " . $conn->error);
            }
            $stmt->bind_param("sssiss", $appointment_date, $appointment_time, $appointment_status, $treatment_id, $staff_ic, $member_ic);
            if (!$stmt->execute()) {
                throw new Exception("Appointment insertion failed: " . $stmt->error);
            }
            $appointment_id = $conn->insert_id;
            $stmt->close();

            // Insert payment
            $payment_date = date('Y-m-d');
            $payment_method_db = $payment_method === 'online_banking' ? 'Online Banking' : 'Credit Card';
            $payment_status = 'Completed';
            $payment_query = "INSERT INTO Payment (Payment_Amount, Payment_Method, Payment_Status, Payment_Date, Appointment_ID) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($payment_query);
            if ($stmt === false) {
                throw new Exception("Payment query preparation failed: " . $conn->error);
            }
            $stmt->bind_param("dsssi", $final_cost, $payment_method_db, $payment_status, $payment_date, $appointment_id);
            if (!$stmt->execute()) {
                throw new Exception("Payment insertion failed: " . $stmt->error);
            }
            $stmt->close();

            // Commit transaction
            $conn->commit();

            // Generate receipt
            $receipt = "<div class='receipt-container'>";
            $receipt .= "<h2>Appointment Confirmation</h2>";
            $receipt .= "<div class='receipt-section'>";
            $receipt .= "<h3>Transaction Details</h3>";
            $receipt .= "<p><strong>Payment Date:</strong> {$payment_date}</p>";
            $receipt .= "</div>";
            $receipt .= "<div class='receipt-section'>";
            $receipt .= "<h3>Member Information</h3>";
            $receipt .= "<p><strong>Name:</strong> " . htmlspecialchars($member_name) . "</p>";
            $receipt .= "<p><strong>IC Number:</strong> " . htmlspecialchars($member_ic) . "</p>";
            $receipt .= "</div>";
            $receipt .= "<div class='receipt-section'>";
            $receipt .= "<h3>Appointment Details</h3>";
            $receipt .= "<p><strong>Treatment:</strong> {$treatment_type}</p>";
            $receipt .= "<p><strong>Provider:</strong> " . htmlspecialchars($staff_name) . "</p>";
            $receipt .= "<p><strong>Date:</strong> {$appointment_date}</p>";
            $receipt .= "<p><strong>Time:</strong> {$appointment_time}</p>";
            $receipt .= "</div>";
            $receipt .= "<div class='receipt-section'>";
            $receipt .= "<h3>Pricing</h3>";
            $receipt .= "<p><strong>Actual Price:</strong> RM " . number_format($original_cost, 2) . "</p>";
            if ($has_active_package) {
                $receipt .= "<p><strong>Package:</strong> {$active_package['Package_Type']} ({$discount_percentage}% discount)</p>";
                $receipt .= "<p><strong>Discount:</strong> RM " . number_format($discount_amount, 2) . "</p>";
            }
            $receipt .= "<p><strong>Final Price:</strong> RM " . number_format($final_cost, 2) . "</p>";
            $receipt .= "</div>";
            $receipt .= "<div class='receipt-section'>";
            $receipt .= "<h3>Payment Method</h3>";
            $receipt .= "<p><strong>Method:</strong> {$payment_method_db}</p>";
            if ($payment_method === 'online_banking') {
                $bank = filter_input(INPUT_POST, 'bank', FILTER_SANITIZE_STRING) ?? 'Unknown';
                $receipt .= "<p><strong>Bank Platform:</strong> {$bank}</p>";
            } else {
                $card_type = filter_input(INPUT_POST, 'cardType', FILTER_SANITIZE_STRING) ?? 'Unknown';
                $card_number = filter_input(INPUT_POST, 'cardNumber', FILTER_SANITIZE_STRING) ?? '**** **** **** ****';
                $receipt .= "<p><strong>Card Type:</strong> {$card_type}</p>";
                $receipt .= "<p><strong>Card Number:</strong> {$card_number}</p>";
            }
            $receipt .= "</div>";
            $receipt .= "<div class='receipt-footer'>";
            $receipt .= "<p>Thank you for your appointment!</p>";
            $receipt .= "<a href='homepage.php' class='btn btn-primary'>Go to Homepage</a>";
            $receipt .= "</div>";
            $receipt .= "</div>";
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Transaction failed: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Payment processing error: " . $e->getMessage());
        $error_message = "Payment processing failed. Please try again or contact support.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Dental Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        h1, h2, h3, h4, h6 {
            font-weight: 600;
            color: #00c4cc;
        }
        p {
            margin-bottom: 1rem;
        }
        .page-header {
            background-color: #00c4cc;
            padding: 40px 0;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        .page-header h1 {
            font-size: 2.2rem;
            color: white;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(-20px);
            animation: slideInDown 0.8s ease-out forwards;
        }
        .page-header p {
            font-size: 1rem;
            color: #f0f0f0;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0;
            transform: translateY(-20px);
            animation: slideInDown 0.8s ease-out forwards 0.2s;
        }
        .form-container {
            margin-top: -30px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            z-index: 1;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInRise 1s ease-out forwards 0.4s;
        }
        .nav-pills {
            margin-bottom: 40px !important;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .nav-pills .nav-link {
            border-radius: 10px;
            padding: 12px 25px;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 1.05rem;
            text-align: center;
        }
        .nav-pills .nav-link.active {
            background-color: #00c4cc;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0, 196, 204, 0.3);
            transform: translateY(-2px);
        }
        .nav-pills .nav-link:not(.active):hover {
            color: #009ea6;
            background-color: #f0f7fa;
            transform: translateY(-1px);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-select, .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        input[readonly] {
            background-color: #e9ecef !important;
            cursor: not-allowed;
            color: #495057;
        }
        .form-select:focus, .form-control:focus {
            border-color: #00c4cc;
            box-shadow: 0 0 0 0.25rem rgba(0, 196, 204, 0.25);
        }
        .btn-primary {
            background-color: #00c4cc;
            border-color: #00c4cc;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #009ea6;
            border-color: #009ea6;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 196, 204, 0.3);
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
        }
        #treatmentPreview {
            background-color: #e0f7fa;
            border: 1px solid #cceeff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 196, 204, 0.1);
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInRise 0.5s ease-out forwards;
        }
        #treatmentPreview p {
            margin-bottom: 5px;
        }
        #treatmentPreview p strong {
            color: #005662;
        }
        .form-check-inline {
            margin-right: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .form-check-input:checked {
            background-color: #00c4cc;
            border-color: #00c4cc;
        }
        .appointment-summary-card {
            background-color: #f0f8ff;
            border: 1px solid #cceeff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInRise 0.8s ease-out forwards;
        }
        .appointment-summary-card .row {
            margin-bottom: 15px;
        }
        .appointment-summary-card strong {
            color: #343a40;
            min-width: 120px;
            display: inline-block;
        }
        .patient-info {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px dashed #ced4da;
        }
        .patient-info p {
            margin-bottom: 8px;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
            border-radius: 8px;
            font-size: 0.95rem;
            padding: 15px 20px;
            animation: fadeIn 0.8s ease-out forwards 0.3s;
        }
        .cancellation-policy {
            margin-top: 30px;
            padding: 25px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            color: #856404;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.1);
            animation: fadeIn 0.8s ease-out forwards 0.5s;
        }
        .cancellation-policy h4 {
            color: #856404;
            margin-bottom: 15px;
            text-align: center;
        }
        .cancellation-policy p {
            margin-bottom: 10px;
        }
        .receipt-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin: 20px auto;
            max-width: 500px;
            border-left: 5px solid #00c4cc;
            animation: fadeIn 0.5s ease-out;
        }
        .receipt-container h2 {
            font-size: 1.8rem;
            color: #00c4cc;
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-section {
            margin-bottom: 15px;
        }
        .receipt-section h3 {
            font-size: 1.1rem;
            color: #005662;
            margin-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 5px;
        }
        .receipt-section p {
            margin: 5px 0;
            font-size: 0.95rem;
            color: #343a40;
        }
        .receipt-section p strong {
            display: inline-block;
            width: 120px;
            font-weight: 600;
            color: #495057;
        }
        .receipt-footer {
            text-align: center;
            padding-top: 15px;
            border-top: 1px dashed #ced4da;
            margin-top: 20px;
        }
        .receipt-footer p {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInRise {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @media (max-width: 768px) {
            .page-header h1 { font-size: 2rem; }
            .page-header p { font-size: 0.95rem; }
            .form-container { padding: 25px; margin-top: -20px; }
            .nav-pills { flex-wrap: wrap; justify-content: center; padding-bottom: 10px; margin-bottom: 25px !important; }
            .nav-pills .nav-item { margin-bottom: 8px; flex: 0 0 100%; }
            .nav-pills .nav-link { padding: 10px 15px; font-size: 0.9rem; }
            .appointment-summary-card, .cancellation-policy { padding: 20px; }
            .appointment-summary-card strong { min-width: 100px; }
            .receipt-container { padding: 20px; max-width: 100%; }
            .receipt-section p strong { width: 100px; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <header class="page-header">
        <div class="container">
            <h1>Book Your Appointment</h1>
            <p>Schedule your visit with our experienced dental professionals. We're committed to providing you with exceptional care in a comfortable environment.</p>
        </div>
    </header>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="form-container">
                    <?php if (isset($receipt)): ?>
                        <?php echo $receipt; ?>
                    <?php elseif (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php else: ?>
                    <ul class="nav nav-pills mb-4 justify-content-around" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-service-tab" data-bs-toggle="pill" data-bs-target="#pills-service" type="button" role="tab" aria-controls="pills-service" aria-selected="true">1. Treatment</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-info-tab" data-bs-toggle="pill" data-bs-target="#pills-info" type="button" role="tab" aria-controls="pills-info" aria-selected="false">2. Information</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-confirmation-tab" data-bs-toggle="pill" data-bs-target="#pills-confirmation" type="button" role="tab" aria-controls="pills-confirmation" aria-selected="false">3. Confirmation</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-payment-tab" data-bs-toggle="pill" data-bs-target="#pills-payment" type="button" role="tab" aria-controls="pills-payment" aria-selected="false">4. Payment</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <!-- Step 1: Treatment & Provider -->
                        <div class="tab-pane fade show active" id="pills-service" role="tabpanel" aria-labelledby="pills-service-tab">
                            <form>
                                <div class="mb-4">
                                    <label for="treatmentType" class="form-label">Treatment Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="treatmentType" name="treatmentType" required>
                                        <option value="" disabled selected>Select a treatment</option>
                                        <?php
                                        if ($treatment_result && mysqli_num_rows($treatment_result) > 0) {
                                            mysqli_data_seek($treatment_result, 0);
                                            while ($row = mysqli_fetch_assoc($treatment_result)) {
                                                $id = $row['Treatment_ID'];
                                                $type = htmlspecialchars($row['Treatment_Type']);
                                                $desc = htmlspecialchars($row['Treatment_Desc']);
                                                $cost = $row['Treatment_Cost'];
                                                $discounted_cost = $cost * (1 - $discount_percentage / 100);
                                                echo "<option value='$id' data-desc='$desc' data-cost='$cost' data-discounted-cost='$discounted_cost'>$type" . ($has_active_package ? " (RM " . number_format($discounted_cost, 2) . ")" : "") . "</option>";
                                            }
                                        } else {
                                            echo "<option disabled>No treatments found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div id="treatmentPreview" class="p-3 mb-4 border rounded" style="display: none;">
                                    <div class="d-flex flex-column flex-md-row justify-content-between">
                                        <p class="mb-2 mb-md-0"><strong>Description:</strong> <span id="previewDesc"></span></p>
                                        <p class="mb-0 text-md-end"><strong>Estimated Cost:</strong> RM <span id="previewCost"></span><?php if ($has_active_package): ?> <span id="previewDiscount"> (<?php echo $discount_percentage; ?>% off)</span><?php endif; ?></p>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="selectProvider" class="form-label">Dental Provider <span class="text-danger">*</span></label>
                                    <select class="form-select" id="selectProvider" name="selectProvider" required>
                                        <option value="" disabled selected>Select a dental provider</option>
                                        <?php
                                        if ($staff_result && mysqli_num_rows($staff_result) > 0) {
                                            mysqli_data_seek($staff_result, 0);
                                            while ($row = mysqli_fetch_assoc($staff_result)) {
                                                $id = $row['Staff_IC'];
                                                $name = htmlspecialchars($row['Staff_Name']);
                                                echo "<option value='$id'>$name</option>";
                                            }
                                        } else {
                                            echo "<option disabled>No dental providers found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="appointmentDate" class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="appointmentDate" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="appointmentTime" class="form-label">Appointment Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="appointmentTime" required>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-primary" onclick="goToInfoTab()">Continue to Your Information</button>
                                </div>
                            </form>
                        </div>
                        <!-- Step 2: Your Information -->
                        <div class="tab-pane fade" id="pills-info" role="tabpanel" aria-labelledby="pills-info-tab">
                            <form id="infoForm">
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="ic" class="form-label">IC No. <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ic" name="ic" value="<?php echo htmlspecialchars($member_ic); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($member_name); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="emailAddress" class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="emailAddress" name="emailAddress" value="<?php echo htmlspecialchars($member_email); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($member_phone); ?>" readonly>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label d-block">Have you visited our dental clinic before?<span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="patientType" id="regularPatient" value="regular" required>
                                            <label class="form-check-label" for="regularPatient">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="patientType" id="newPatient" value="new">
                                            <label class="form-check-label" for="newPatient">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label d-block">Have you experienced dental illnesses/surgeries in the past?<span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="dentalExp" id="expYes" value="yes" required>
                                            <label class="form-check-label" for="expYes">Yes</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="dentalExp" id="expNo" value="no">
                                            <label class="form-check-label" for="expNo">No</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="termsAndConditions" name="termsAndConditions" required>
                                    <label class="form-check-label" for="termsAndConditions">I agree to the <a href="#" class="text-decoration-none text-info">terms and conditions</a> and <a href="#" class="text-decoration-none text-info">privacy policy</a>.</label>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="goBackToService()">Back</button>
                                    <button type="button" class="btn btn-primary" onclick="goToConfirmation()">Review & Confirm</button>
                                </div>
                            </form>
                        </div>
                        <!-- Step 3: Confirmation Tab -->
                        <div class="tab-pane fade" id="pills-confirmation" role="tabpanel" aria-labelledby="pills-confirmation-tab">
                            <div class="appointment-summary-card mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Treatment:</strong> <span id="summaryService">-</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Provider:</strong> <span id="summaryProvider">-</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Date:</strong> <span id="summaryDate">-</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Time:</strong> <span id="summaryTime">-</span></p>
                                    </div>
                                    <?php if ($has_active_package): ?>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Package:</strong> <span id="summaryPackage"><?php echo htmlspecialchars($active_package['Package_Type']); ?> (<?php echo $discount_percentage; ?>% off)</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Original Cost:</strong> RM <span id="summaryOriginalCost">-</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Discount:</strong> RM <span id="summaryDiscount">-</span></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Final Cost:</strong> RM <span id="summaryCost">-</span></p>
                                    </div>
                                </div>
                                <div class="patient-info">
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>IC No:</strong> <span id="summaryPatientIC"><?php echo htmlspecialchars($member_ic); ?></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Name:</strong> <span id="summaryPatientName"><?php echo htmlspecialchars($member_name); ?></span></p>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Email:</strong> <span id="summaryPatientEmail"><?php echo htmlspecialchars($member_email); ?></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Phone:</strong> <span id="summaryPatientPhone"><?php echo htmlspecialchars($member_phone); ?></span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-4" role="alert">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill me-2" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34-.34.658-.73.658-.552 0-1-.448-1-1 0-.154.02-.295.053-.435l.542-2.705C7.261 5.922 7.7 5.727 8 5.727c.421 0 .8.225 1.05.507zM8 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
                                    </svg>
                                    Please arrive 15 minutes before your appointment time.
                                </div>
                            </div>
                            <div class="cancellation-policy mb-4">
                                <h4>Cancellation Policy</h4>
                                <p>Please notify us at least 24 hours in advance for cancellations. Late cancellations or no-shows may incur a RM 50 fee.</p>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" onclick="goBackToInfo()">Back</button>
                                <button type="button" class="btn btn-primary" onclick="goToPaymentTab()">Make Payment</button>
                            </div>
                        </div>
                        <!-- Step 4: Payment -->
                        <div class="tab-pane fade" id="pills-payment" role="tabpanel" aria-labelledby="pills-payment-tab">
                            <form method="POST" id="paymentForm">
                                <input type="hidden" name="treatmentId" id="treatmentId">
                                <input type="hidden" name="staffIc" id="staffIc">
                                <input type="hidden" name="treatmentCost" id="treatmentCost">
                                <input type="hidden" name="appointmentDate" id="paymentAppointmentDate">
                                <input type="hidden" name="appointmentTime" id="paymentAppointmentTime">
                                <input type="hidden" name="patientType" id="patientTypeHidden">
                                <input type="hidden" name="dentalExp" id="dentalExpHidden">
                                <div class="payment-card mb-4">
                                    <label for="paymentMethod" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-4 mb-4">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="paymentMethod" id="onlineBanking" value="online_banking" checked>
                                            <label class="form-check-label" for="onlineBanking">Online Banking</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="paymentMethod" id="creditCard" value="credit_card">
                                            <label class="form-check-label" for="creditCard">Credit Card</label>
                                        </div>
                                    </div>
                                    <hr style="width:100%;">
                                    <div id="onlineBankingDetails">
                                        <label for="banktype" class="form-label">Online Banking Platform <span class="text-danger">*</span></label>
                                        <div class="mb-3">
                                            <select class="form-select" id="bankSelect" name="bank" aria-label="Select your bank for online banking">
                                                <option value="">Choose a platform</option>
                                                <option value="Maybank2u">Maybank</option>
                                                <option value="CIMB Clicks">CIMB</option>
                                                <option value="RHB Now">RHB Bank</option>
                                                <option value="Bank Islam">Bank Islam</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="creditCardDetails" style="display:none;">
                                        <h5 class="mb-3">Credit Card Details</h5>
                                        <div class="d-flex flex-wrap gap-3 mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cardType" id="cardVisa" value="Visa">
                                                <label class="form-check-label" for="cardVisa">Visa</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cardType" id="cardMastercard" value="Mastercard">
                                                <label class="form-check-label" for="cardMastercard">Mastercard</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cardNumber" class="form-label">Credit Card Number</label>
                                            <input type="text" class="form-control" id="cardNumber" name="cardNumber" placeholder="•••• •••• •••• ••••" maxlength="19" aria-label="Credit card number">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiryMonth" class="form-label">Expiration Date</label>
                                                <div class="d-flex">
                                                    <input type="text" class="form-control me-2" id="expiryMonth" name="expiryMonth" placeholder="MM" maxlength="2" aria-label="Expiration month">
                                                    <span class="align-self-center">/</span>
                                                    <input type="text" class="form-control ms-2" id="expiryYear" name="expiryYear" placeholder="YY" maxlength="2" aria-label="Expiration year">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" placeholder="•••" maxlength="4" aria-label="CVV or CVC code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="goBackToInfoFromPayment()">Back</button>
                                    <button type="submit" class="btn btn-success">Confirm Appointment & Pay</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <!-- Custom Alert Modal -->
    <div class="modal fade" id="customAlertModal" tabindex="-1" aria-labelledby="customAlertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customAlertModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customAlertModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('view_appt.php JavaScript loaded');
        function showCustomAlert(message) {
            console.log('Showing alert:', message);
            document.getElementById('customAlertModalBody').textContent = message;
            const customAlertModal = new bootstrap.Modal(document.getElementById('customAlertModal'));
            customAlertModal.show();
        }
        function goToInfoTab() {
            console.log('goToInfoTab called');
            try {
                const treatmentTypeSelect = document.getElementById('treatmentType');
                const selectProvider = document.getElementById('selectProvider');
                const appointmentDate = document.getElementById('appointmentDate');
                const appointmentTimeInput = document.getElementById('appointmentTime');
                if (!treatmentTypeSelect.value || !selectProvider.value || !appointmentDate.value || !appointmentTimeInput.value) {
                    showCustomAlert('Please fill in all fields in the Treatment section.');
                    console.log('Validation failed: Treatment fields incomplete');
                    return;
                }
                const tab = new bootstrap.Tab(document.getElementById('pills-info-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goToInfoTab:', e);
            }
        }
        function goToConfirmation() {
            console.log('goToConfirmation called');
            try {
                const icInput = document.getElementById('ic');
                const nameInput = document.getElementById('name');
                const emailInput = document.getElementById('emailAddress');
                const phoneInput = document.getElementById('phoneNumber');
                const patientTypeRadios = document.querySelector('input[name="patientType"]:checked');
                const dentalExpRadios = document.querySelector('input[name="dentalExp"]:checked');
                const termsAndConditionsCheckbox = document.getElementById('termsAndConditions');
                console.log('IC:', icInput.value);
                console.log('Name:', nameInput.value);
                console.log('Email:', emailInput.value);
                console.log('Phone:', phoneInput.value);
                console.log('Patient Type:', patientTypeRadios ? patientTypeRadios.value : 'None');
                console.log('Dental Exp:', dentalExpRadios ? dentalExpRadios.value : 'None');
                console.log('Terms:', termsAndConditionsCheckbox.checked);
                if (!icInput.value || !nameInput.value || !emailInput.value || !phoneInput.value ||
                    !patientTypeRadios || !dentalExpRadios || !termsAndConditionsCheckbox.checked) {
                    showCustomAlert('Please fill in all fields and agree to the terms and conditions.');
                    console.log('Validation failed: Information fields incomplete');
                    return;
                }
                const treatmentTypeSelect = document.getElementById('treatmentType');
                const selectedOption = treatmentTypeSelect.options[treatmentTypeSelect.selectedIndex];
                const treatmentCost = parseFloat(selectedOption.dataset.cost);
                const discountedCost = parseFloat(selectedOption.dataset.discountedCost || treatmentCost);
                const discountAmount = treatmentCost - discountedCost;
                document.getElementById('summaryService').textContent = selectedOption.textContent.replace(/ \(.*\)/, '');
                document.getElementById('summaryProvider').textContent = document.getElementById('selectProvider').options[document.getElementById('selectProvider').selectedIndex].textContent;
                document.getElementById('summaryDate').textContent = document.getElementById('appointmentDate').value;
                document.getElementById('summaryTime').textContent = document.getElementById('appointmentTime').value;
                document.getElementById('summaryCost').textContent = discountedCost.toFixed(2);
                const hasPackage = <?php echo json_encode($has_active_package); ?>;
                if (hasPackage && document.getElementById('summaryOriginalCost') && document.getElementById('summaryDiscount')) {
                    document.getElementById('summaryOriginalCost').textContent = treatmentCost.toFixed(2);
                    document.getElementById('summaryDiscount').textContent = discountAmount.toFixed(2);
                }
                document.getElementById('treatmentId').value = treatmentTypeSelect.value;
                document.getElementById('staffIc').value = document.getElementById('selectProvider').value;
                document.getElementById('treatmentCost').value = discountedCost;
                document.getElementById('paymentAppointmentDate').value = document.getElementById('appointmentDate').value;
                document.getElementById('paymentAppointmentTime').value = document.getElementById('appointmentTime').value;
                document.getElementById('patientTypeHidden').value = patientTypeRadios.value;
                document.getElementById('dentalExpHidden').value = dentalExpRadios.value;
                const tab = new bootstrap.Tab(document.getElementById('pills-confirmation-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goToConfirmation:', e);
            }
        }
        function goToPaymentTab() {
            console.log('goToPaymentTab called');
            try {
                const tab = new bootstrap.Tab(document.getElementById('pills-payment-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goToPaymentTab:', e);
            }
        }
        function goBackToService() {
            console.log('goBackToService called');
            try {
                const tab = new bootstrap.Tab(document.getElementById('pills-service-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goBackToService:', e);
            }
        }
        function goBackToInfo() {
            console.log('goBackToInfo called');
            try {
                const tab = new bootstrap.Tab(document.getElementById('pills-info-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goBackToInfo:', e);
            }
        }
        function goBackToInfoFromPayment() {
            console.log('goBackToInfoFromPayment called');
            try {
                const tab = new bootstrap.Tab(document.getElementById('pills-confirmation-tab'));
                tab.show();
            } catch (e) {
                console.error('Error in goBackToInfoFromPayment:', e);
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded');
            try {
                const treatmentTypeSelect = document.getElementById('treatmentType');
                const previewDesc = document.getElementById('previewDesc');
                const previewCost = document.getElementById('previewCost');
                const treatmentPreviewDiv = document.getElementById('treatmentPreview');
                const selectProvider = document.getElementById('selectProvider');
                const appointmentDate = document.getElementById('appointmentDate');
                const appointmentTime = document.getElementById('appointmentTime');
                const onlineBanking = document.getElementById('onlineBanking');
                const creditCardRadio = document.getElementById('creditCard');
                const onlineBankingDetails = document.getElementById('onlineBankingDetails');
                const creditCardDetails = document.getElementById('creditCardDetails');
                const cardNumberInput = document.getElementById('cardNumber');
                const paymentForm = document.getElementById('paymentForm');
                const treatmentIdInput = document.getElementById('treatmentId');
                const staffIcInput = document.getElementById('staffIc');
                const treatmentCostInput = document.getElementById('treatmentCost');
                const paymentAppointmentDateInput = document.getElementById('paymentAppointmentDate');
                const paymentAppointmentTimeInput = document.getElementById('paymentAppointmentTime');
                const patientTypeInput = document.getElementById('patientTypeHidden');
                const dentalExpInput = document.getElementById('dentalExpHidden');
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                const minDate = `${year}-${month}-${day}`;
                appointmentDate.min = minDate;
                appointmentDate.value = minDate;
                treatmentTypeSelect.addEventListener('change', function() {
                    console.log('Treatment selected:', this.value);
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        previewDesc.textContent = selectedOption.dataset.desc;
                        previewCost.textContent = parseFloat(selectedOption.dataset.discountedCost || selectedOption.dataset.cost).toFixed(2);
                        treatmentPreviewDiv.style.display = 'block';
                    } else {
                        treatmentPreviewDiv.style.display = 'none';
                    }
                });
                if (onlineBanking.checked) {
                    onlineBankingDetails.style.display = 'block';
                    creditCardDetails.style.display = 'none';
                }
                onlineBanking.addEventListener('change', function() {
                    console.log('Payment method: Online Banking');
                    if (this.checked) {
                        onlineBankingDetails.style.display = 'block';
                        creditCardDetails.style.display = 'none';
                    }
                });
                creditCardRadio.addEventListener('change', function() {
                    console.log('Payment method: Credit Card');
                    if (this.checked) {
                        creditCardDetails.style.display = 'block';
                        onlineBankingDetails.style.display = 'none';
                    }
                });
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 16) value = value.slice(0, 16);
                    let formatted = '';
                    for (let i = 0; i < value.length; i++) {
                        if (i > 0 && i % 4 === 0) formatted += ' ';
                        formatted += value[i];
                    }
                    e.target.value = formatted;
                });
                paymentForm.addEventListener('submit', function(e) {
                    console.log('Payment form submitted');
                    const submitButton = paymentForm.querySelector('button[type="submit"]');
                    if (submitButton.disabled) {
                        e.preventDefault();
                        console.log('Submit prevented: Button disabled');
                        return;
                    }
                    submitButton.disabled = true;
                    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                    if (paymentMethod === 'online_banking') {
                        const bankSelect = document.getElementById('bankSelect');
                        if (!bankSelect.value) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please select a banking platform.');
                            console.log('Validation failed: No bank selected');
                            return;
                        }
                    } else if (paymentMethod === 'credit_card') {
                        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                        const expiryMonth = document.getElementById('expiryMonth').value;
                        const expiryYear = document.getElementById('expiryYear').value;
                        const cvv = document.getElementById('cvv').value;
                        const cardType = document.querySelector('input[name="cardType"]:checked');
                        if (!cardType) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please select a card type.');
                            console.log('Validation failed: No card type selected');
                            return;
                        } else if (!/^\d{16}$/.test(cardNumber)) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please enter a valid 16-digit card number.');
                            console.log('Validation failed: Invalid card number');
                            return;
                        } else if (!/^\d{2}$/.test(expiryMonth) || parseInt(expiryMonth) < 1 || parseInt(expiryMonth) > 12) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please enter a valid month (01-12).');
                            console.log('Validation failed: Invalid expiry month');
                            return;
                        } else if (!/^\d{2}$/.test(expiryYear)) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please enter a valid year (e.g., 25).');
                            console.log('Validation failed: Invalid expiry year');
                            return;
                        } else if (!/^\d{3,4}$/.test(cvv)) {
                            e.preventDefault();
                            submitButton.disabled = false;
                            showCustomAlert('Please enter a valid CVV (3 or 4 digits).');
                            console.log('Validation failed: Invalid CVV');
                            return;
                        }
                    }
                    console.log('Payment form validation passed');
                });
                if (treatmentTypeSelect.value) {
                    treatmentTypeSelect.dispatchEvent(new Event('change'));
                }
            } catch (e) {
                console.error('Error in DOMContentLoaded:', e);
            }
        });
    </script>
</body>
</html>