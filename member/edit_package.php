<?php
session_start();
include __DIR__ . '/../auth/connection.php'; // Fixed include path

if (!isset($_SESSION['Member_IC'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get member details from database
$member_ic = $_SESSION['Member_IC'];
$member_query = "SELECT Member_Name, Member_Email, Member_PhoneNo FROM Member WHERE Member_IC = ?";
$stmt = $conn->prepare($member_query);
$stmt->bind_param("s", $member_ic);
$stmt->execute();
$member_result = $stmt->get_result();

if ($member_result && $member_result->num_rows > 0) {
    $member_data = $member_result->fetch_assoc();
    $member_name = $member_data['Member_Name'];
    $member_email = $member_data['Member_Email'];
    $member_phone = $member_data['Member_PhoneNo'];
} else {
    $member_name = 'Not available';
    $member_email = 'Not available';
    $member_phone = 'Not available';
}
$stmt->close();

// Check for active package
$active_package_query = "SELECT d.*, p.Package_Type, p.Package_Price 
                        FROM Detail d 
                        JOIN Package p ON d.Package_ID = p.Package_ID 
                        WHERE d.Member_IC = ? AND d.Status = 'Active' AND d.Expired_Date >= CURRENT_DATE";
$stmt = $conn->prepare($active_package_query);
$stmt->bind_param("s", $member_ic);
$stmt->execute();
$active_package_result = $stmt->get_result();
$has_active_package = $active_package_result->num_rows > 0;
$active_package = $has_active_package ? $active_package_result->fetch_assoc() : null;
$stmt->close();

// Get package information (for form if no active package)
$package_query = "SELECT Package_ID, Package_Type, Package_Price FROM Package";
$package_result = mysqli_query($conn, $package_query);

// Handle payment submission (only if no active package)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paymentMethod']) && !$has_active_package) {
    $package_id = $_POST['packageId'] ?? '';
    $payment_method = $_POST['paymentMethod'] ?? '';
    $activation_date = $_POST['activateDate'] ?? '';
    $expiration_date = $_POST['expiredDate'] ?? '';
    $amount = $_POST['packageCost'] ?? 0;

    // Fetch package details for receipt
    $package_details_query = "SELECT Package_Type, Package_Price FROM Package WHERE Package_ID = ?";
    $stmt = $conn->prepare($package_details_query);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $package_details = $stmt->get_result()->fetch_assoc();
    $package_type = $package_details['Package_Type'] ?? 'Unknown';
    $package_price = $package_details['Package_Price'] ?? 0;
    $stmt->close();

   

    // Determine plan-specific benefits
    $plan_benefits = '';
    switch (strtolower($package_type)) {
        case 'child':
            $plan_benefits = "Dental check-ups, fluoride treatments, and sealants for children under 12.";
            break;
        case 'adult':
            $plan_benefits = "Comprehensive dental check-ups, cleanings, and X-rays for adults.";
            break;
        case 'family':
            $plan_benefits = "Family-wide dental care including check-ups, cleanings, and discounts on treatments.";
            break;
        default:
            $plan_benefits = "Standard membership benefits.";
    }

    // Insert into Detail table
    $status = 'Active';
    $detail_query = "INSERT INTO Detail (Activation_Date, Expired_Date, Status, Package_ID, Member_IC) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($detail_query);
    $stmt->bind_param("sssis", $activation_date, $expiration_date, $status, $package_id, $member_ic);
    $stmt->execute();
    $stmt->close();

    // Generate receipt
    $receipt = "<div class='receipt-container'>";
    $receipt .= "<div class='receipt-header'>";
    $receipt .= "<h2>Payment Receipt</h2>";
    $receipt .= "<span class='receipt-status'><i class='bi bi-check-circle-fill'></i> Payment Completed</span>";
    $receipt .= "</div>";
    $receipt .= "<div class='receipt-body'>";
    $receipt .= "<div class='receipt-section'>";
    $receipt .= "<h3>Transaction Details</h3>";
    
    $receipt .= "<p><strong>Payment Date:</strong> " . date('Y-m-d') . "</p>";
    $receipt .= "</div>";
    $receipt .= "<div class='receipt-section'>";
    $receipt .= "<h3>Member Information</h3>";
    $receipt .= "<p><strong>Name:</strong> " . htmlspecialchars($member_name) . "</p>";
    $receipt .= "<p><strong>IC Number:</strong> " . htmlspecialchars($member_ic) . "</p>";
    $receipt .= "</div>";
    $receipt .= "<div class='receipt-section'>";
    $receipt .= "<h3>Package Details</h3>";
    $receipt .= "<p><strong>Package Plan:</strong> {$package_type}</p>";
    $receipt .= "<p><strong>Price:</strong> RM " . number_format($package_price, 2) . "</p>";
    
    $receipt .= "<p><strong>Activation Date:</strong> {$activation_date}</p>";
    $receipt .= "<p><strong>Expiration Date:</strong> {$expiration_date}</p>";
    $receipt .= "</div>";
    $receipt .= "<div class='receipt-section'>";
    $receipt .= "<h3>Payment Method</h3>";
    $receipt .= "<p><strong>Method:</strong> " . ($payment_method === 'online_banking' ? 'Online Banking' : 'Credit Card') . "</p>";
    if ($payment_method === 'online_banking') {
        $bank = $_POST['bank'] ?? 'Unknown';
        $receipt .= "<p><strong>Bank Platform:</strong> {$bank}</p>";
    } else {
        $card_type = $_POST['cardType'] ?? 'Unknown';
        $card_number = $_POST['cardNumber'] ?? '**** **** **** ****';
        $receipt .= "<p><strong>Card Type:</strong> {$card_type}</p>";
        $receipt .= "<p><strong>Card Number:</strong> {$card_number}</p>";
    }
    $receipt .= "</div>";
    $receipt .= "</div>";
    $receipt .= "<div class='receipt-footer'>";
    $receipt .= "<p>Thank you for your payment!</p>";
    $receipt .= "<a href='homepage.php' class='btn btn-primary'>Go to Homepage</a>";
    $receipt .= "</div>";
    $receipt .= "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Membership Package</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 600;
            color: #00c4cc;
        }

        .page-header {
            background-color: #00c4cc;
            padding: 40px 0;
            text-align: center;
        }

        .page-header h1 {
            color: white;
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            animation: slideInDown 0.8s ease-out forwards;
        }

        .page-header p {
            font-size: 1rem;
            color: #e0f7fa;
            max-width: 700px;
            margin: 0 auto;
            animation: slideInDown 0.8s ease-out forwards;
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

        .form-select,
        .form-control {
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

        .form-select:focus,
        .form-control:focus {
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
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        .package-summary-card {
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

        .package-summary-card h4 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #005662;
            text-align: center;
        }

        .package-summary-card .row {
            margin-bottom: 15px;
        }

        .package-summary-card strong {
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

        /* Receipt Styling */
        .receipt-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 40px;
            margin: 20px auto;
            max-width: 600px;
            border-left: 5px solid #00c4cc;
            animation: fadeIn 0.5s ease-out;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .receipt-header h2 {
            font-size: 1.8rem;
            color: #00c4cc;
            margin-bottom: 10px;
        }

        .receipt-status {
            display: inline-flex;
            align-items: center;
            background-color: #d4edda;
            color: #155724;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .receipt-status i {
            margin-right: 8px;
            color: #28a745;
        }

        .receipt-body {
            margin-bottom: 30px;
        }

        .receipt-section {
            margin-bottom: 20px;
        }

        .receipt-section h3 {
            font-size: 1.2rem;
            color: #005662;
            margin-bottom: 15px;
            border-left: 3px solid #00c4cc;
            padding-left: 10px;
        }

        .receipt-section p {
            margin: 8px 0;
            font-size: 0.95rem;
            color: #343a40;
        }

        .receipt-section p strong {
            display: inline-block;
            width: 150px;
            font-weight: 600;
            color: #495057;
        }

        .receipt-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px dashed #ced4da;
        }

        .receipt-footer p {
            font-size: 1rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .receipt-footer .btn-primary {
            padding: 10px 25px;
            font-size: 1rem;
        }

        /* Active Package Message Styling */
        .active-package-message {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 12px;
            padding: 30px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        .active-package-message h2 {
            font-size: 1.8rem;
            color: #856404;
            margin-bottom: 20px;
        }

        .active-package-message p {
            font-size: 1rem;
            color: #343a40;
            margin-bottom: 15px;
        }

        .active-package-message p strong {
            display: inline-block;
            width: 150px;
            font-weight: 600;
            color: #495057;
        }

        .active-package-message .btn-primary {
            margin-top: 20px;
            padding: 10px 25px;
            font-size: 1rem;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRise {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .page-header p {
                font-size: 0.95rem;
            }

            .form-container {
                padding: 25px;
                margin-top: -20px;
            }

            .nav-pills {
                flex-wrap: wrap;
                justify-content: center;
                padding-bottom: 10px;
                margin-bottom: 25px !important;
            }

            .nav-pills .nav-item {
                margin-bottom: 8px;
                flex: 0 0 100%;
            }

            .nav-pills .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .package-summary-card,
            .cancellation-policy {
                padding: 20px;
            }

            .package-summary-card strong {
                min-width: 100px;
            }

            .receipt-container,
            .active-package-message {
                padding: 20px;
                max-width: 100%;
            }

            .receipt-section p strong,
            .active-package-message p strong {
                width: 120px;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <div class="container">
            <h1>Upgrade Membership Package</h1>
            <p>Choose the best membership plan that fits your dental care needs. Enjoy exclusive benefits, priority
                appointments, and special discounts with our packages.</p>
        </div>
    </header>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="form-container">
                    <?php if (isset($receipt)): ?>
                        <?php echo $receipt; ?>
                    <?php elseif ($has_active_package): ?>
                        <div class="active-package-message">
                            <h2>Active Package Detected</h2>
                            <p>You currently have an active package and cannot purchase another until it expires.</p>
                            <p><strong>Package Plan:</strong> <?php echo htmlspecialchars($active_package['Package_Type']); ?></p>
                            <p><strong>Price:</strong> RM <?php echo number_format($active_package['Package_Price'], 2); ?></p>
                            <p><strong>Activation Date:</strong> <?php echo htmlspecialchars($active_package['Activation_Date']); ?></p>
                            <p><strong>Expiration Date:</strong> <?php echo htmlspecialchars($active_package['Expired_Date']); ?></p>
                            <a href="homepage.php" class="btn btn-primary">Go to Homepage</a>
                        </div>
                    <?php else: ?>
                    <ul class="nav nav-pills mb-4 justify-content-around" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-package-tabs" data-bs-toggle="pill"
                                data-bs-target="#pills-package" type="button" role="tab" aria-controls="pills-package"
                                aria-selected="true">1. Membership Package</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-confirmation-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-confirmation" type="button" role="tab"
                                aria-controls="pills-confirmation" aria-selected="false" tabindex="-1">2.
                                Confirmation</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-payment-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-payment" type="button" role="tab" aria-controls="pills-payment"
                                aria-selected="false" tabindex="-1">3.
                                Payment</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-package" role="tabpanel"
                            aria-labelledby="pills-package-tabs">
                            <form>
                                <div class="mb-4">
                                    <label for="packageType" class="form-label">
                                        Package Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="packageType" name="packageType" required>
                                        <option value="" disabled selected>Select a package</option>
                                        <?php
                                        mysqli_data_seek($package_result, 0);
                                        if ($package_result && mysqli_num_rows($package_result) > 0) {
                                            while ($row = mysqli_fetch_assoc($package_result)) {
                                                $id = $row['Package_ID'];
                                                $type = htmlspecialchars($row['Package_Type']);
                                                $cost = htmlspecialchars($row['Package_Price']);
                                                echo "<option value='$id' data-type='$type' data-cost='$cost'>$type</option>";
                                            }
                                        } else {
                                            echo "<option disabled>No packages found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label for="activateDate" class="form-label">Activation Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="activateDate" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="expiredDate" class="form-label">Expiration Date <span
                                                class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="expiredDate" readonly required>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-primary" onclick="goToInfoTab()">Confirm</button>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="pills-confirmation" role="tabpanel"
                            aria-labelledby="pills-confirmation-tab">
                            <div class="package-summary-card mb-4">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Package Plan:</strong> <span id="summaryPlan">-</span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <h5 class="text-success">Price: RM <span id="summaryPrice">-</span></h5>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Activation Date:</strong> <span
                                                id="summaryActDate">-</span></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Expiration Date:</strong> <span
                                                id="summaryExpDate">-</span></p>
                                    </div>
                                </div>

                                <div class="patient-info">
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>IC No:</strong> <span
                                            id="summaryPatientIC"><?php echo htmlspecialchars($member_ic); ?></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Name:</strong> <span
                                            id="summaryPatientName"><?php echo htmlspecialchars($member_name); ?></span></p>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Email:</strong> <span
                                            id="summaryPatientEmail"><?php echo htmlspecialchars($member_email); ?></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-0"><strong>Phone:</strong> <span
                                            id="summaryPatientPhone"><?php echo htmlspecialchars($member_phone); ?></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="cancellation-policy mb-4">
                                <h4>Cancellation Policy</h4>
                                <p>We understand that plans can change. To cancel your membership package, please notify
                                    us at least <strong>7 days before the renewal date</strong>.</p>
                                <ul>
                                    <li>No refunds will be issued after the membership has started.</li>
                                    <li>Early cancellation will not result in a partial refund.</li>
                                    <li>Membership benefits remain active until the end of the current billing cycle.
                                    </li>
                                </ul>
                                <p>For assistance, please contact our support team.</p>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary"
                                    onclick="goBackToService()">Back</button>
                                <button type="button" class="btn btn-primary" onclick="goToPaymentTab()">Make
                                    Payment</button>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="pills-payment" role="tabpanel"
                            aria-labelledby="pills-payment-tab">
                            <form method="POST" id="paymentForm">
                                <input type="hidden" name="packageId" id="packageId">
                                <input type="hidden" name="packageCost" id="packageCost">
                                <input type="hidden" name="activateDate" id="paymentActivateDate">
                                <input type="hidden" name="expiredDate" id="paymentExpiredDate">
                                <div class="payment-card mb-4">
                                    <label for="paymentMethod" class="form-label">
                                        Payment Method <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex flex-wrap gap-4 mb-4">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="paymentMethod"
                                                id="onlineBanking" value="online_banking" checked>
                                            <label class="form-check-label" for="onlineBanking">
                                                Online Banking </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="paymentMethod"
                                                id="creditCard" value="credit_card">
                                            <label class="form-check-label" for="creditCard">
                                                Credit Card </label>
                                        </div>
                                    </div>
                                    <hr style="width:100%;">

                                    <div id="onlineBankingDetails">
                                        <label for="banktype" class="form-label">
                                            Online Banking Platform <span class="text-danger">*</span>
                                        </label>
                                        <div class="mb-3">
                                            <select class="form-select" id="bankSelect" name="bank"
                                                aria-label="Select your bank for online banking">
                                                <option value="" disabled selected>Choose a platform</option>
                                                <option value="Maybank2u">Maybank2u</option>
                                                <option value="CIMB Clicks">CIMB Clicks</option>
                                                <option value="RHB Now">RHB Now</option>
                                                <option value="Bank Islam">Bank Islam</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="creditCardDetails" style="display:none;">
                                        <h5 class="mb-3">Credit Card Details</h5>
                                        <div class="d-flex flex-wrap gap-3 mb-3">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cardType" id="cardVisa"
                                                    value="Visa">
                                                <label class="form-check-label" for="cardVisa">
                                                    Visa
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cardType"
                                                    id="cardMastercard" value="Mastercard">
                                                <label class="form-check-label" for="cardMastercard">
                                                    Mastercard
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cardNumber" class="form-label">Credit Card Number</label>
                                            <input type="text" class="form-control" id="cardNumber"
                                                name="cardNumber" placeholder="•••• •••• •••• ••••" maxlength="19"
                                                aria-label="Credit card number">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiryMonth" class="form-label">Expiration Date</label>
                                                <div class="d-flex">
                                                    <input type="text" class="form-control me-2" id="expiryMonth"
                                                        name="expiryMonth" placeholder="MM" maxlength="2" aria-label="Expiration month">
                                                    <span class="align-self-center">/</span>
                                                    <input type="text" class="form-control ms-2" id="expiryYear"
                                                        name="expiryYear" placeholder="YY" maxlength="2" aria-label="Expiration year">
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" placeholder="•••"
                                                    maxlength="4" aria-label="CVV or CVC code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary"
                                        onclick="goBackToInfoFromPayment()">Back</button>
                                    <button type="submit" class="btn btn-success">Proceed to Payment</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const packageTypeSelect = document.getElementById('packageType');
            const activateDateInput = document.getElementById('activateDate');
            const expiredDateInput = document.getElementById('expiredDate');

            const summaryPlan = document.getElementById('summaryPlan');
            const summaryPrice = document.getElementById('summaryPrice');
            const summaryActDate = document.getElementById('summaryActDate');
            const summaryExpDate = document.getElementById('summaryExpDate');

            const onlineBankingRadio = document.getElementById('onlineBanking');
            const creditCardRadio = document.getElementById('creditCard');
            const onlineBankingDetails = document.getElementById('onlineBankingDetails');
            const creditCardDetails = document.getElementById('creditCardDetails');

            // Hidden inputs for payment form
            const packageIdInput = document.getElementById('packageId');
            const packageCostInput = document.getElementById('packageCost');
            const paymentActivateDateInput = document.getElementById('paymentActivateDate');
            const paymentExpiredDateInput = document.getElementById('paymentExpiredDate');

            // Set today's date as minimum for activation date
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            const minDate = `${year}-${month}-${day}`;
            activateDateInput.min = minDate;
            activateDateInput.value = minDate; // Set default to today

            function updateExpirationDate() {
                const activationDate = activateDateInput.value;
                if (activationDate) {
                    const date = new Date(activationDate);
                    date.setFullYear(date.getFullYear() + 1);
                    const expYear = date.getFullYear();
                    const expMonth = String(date.getMonth() + 1).padStart(2, '0');
                    const expDay = String(date.getDate()).padStart(2, '0');
                    expiredDateInput.value = `${expYear}-${expMonth}-${expDay}`;
                } else {
                    expiredDateInput.value = '';
                }
            }

            activateDateInput.addEventListener('change', updateExpirationDate);
            updateExpirationDate(); // Call on load to set initial expiration date

            onlineBankingRadio.addEventListener('change', function () {
                if (this.checked) {
                    onlineBankingDetails.style.display = 'block';
                    creditCardDetails.style.display = 'none';
                }
            });

            creditCardRadio.addEventListener('change', function () {
                if (this.checked) {
                    creditCardDetails.style.display = 'block';
                    onlineBankingDetails.style.display = 'none';
                }
            });

            // Handle card number formatting
            const cardNumberInput = document.getElementById('cardNumber');
            cardNumberInput.addEventListener('input', function (e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 16) value = value.slice(0, 16);
                let formatted = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formatted += ' ';
                    formatted += value[i];
                }
                e.target.value = formatted;
            });

            // Basic client-side validation for payment form
            document.getElementById('paymentForm').addEventListener('submit', function (e) {
                const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                if (paymentMethod === 'online_banking') {
                    const bankSelect = document.getElementById('bankSelect');
                    if (!bankSelect.value) {
                        e.preventDefault();
                        alert('Please select a banking platform.');
                    }
                } else if (paymentMethod === 'credit_card') {
                    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                    const expiryMonth = document.getElementById('expiryMonth').value;
                    const expiryYear = document.getElementById('expiryYear').value;
                    const cvv = document.getElementById('cvv').value;
                    const cardType = document.querySelector('input[name="cardType"]:checked');

                    if (!cardType) {
                        e.preventDefault();
                        alert('Please select a card type.');
                    } else if (!/^\d{16}$/.test(cardNumber)) {
                        e.preventDefault();
                        alert('Please enter a valid 16-digit card number.');
                    } else if (!/^\d{2}$/.test(expiryMonth) || parseInt(expiryMonth) < 1 || parseInt(expiryMonth) > 12) {
                        e.preventDefault();
                        alert('Please enter a valid month (01-12).');
                    } else if (!/^\d{2}$/.test(expiryYear)) {
                        e.preventDefault();
                        alert('Please enter a valid year (e.g., 25).');
                    } else if (!/^\d{3,4}$/.test(cvv)) {
                        e.preventDefault();
                        alert('Please enter a valid CVV (3 or 4 digits).');
                    }
                }
            });
        });

        function goToInfoTab() {
            const packageTypeSelect = document.getElementById('packageType');
            const activateDateInput = document.getElementById('activateDate');
            const expiredDateInput = document.getElementById('expiredDate');

            if (!packageTypeSelect.value || !activateDateInput.value || !expiredDateInput.value) {
                alert('Please select a package and enter activation and expiration dates.');
                return;
            }

            const selectedOption = packageTypeSelect.options[packageTypeSelect.selectedIndex];
            const packageName = selectedOption.getAttribute('data-type');
            const packageCost = selectedOption.getAttribute('data-cost');

            document.getElementById('summaryPlan').textContent = packageName;
            document.getElementById('summaryPrice').textContent = parseFloat(packageCost).toFixed(2);
            document.getElementById('summaryActDate').textContent = activateDateInput.value;
            document.getElementById('summaryExpDate').textContent = expiredDateInput.value;

            // Populate hidden inputs for payment form
            document.getElementById('packageId').value = packageTypeSelect.value;
            document.getElementById('packageCost').value = packageCost;
            document.getElementById('paymentActivateDate').value = activateDateInput.value;
            document.getElementById('paymentExpiredDate').value = expiredDateInput.value;

            const tab = new bootstrap.Tab(document.getElementById('pills-confirmation-tab'));
            tab.show();
        }

        function goToPaymentTab() {
            const tab = new bootstrap.Tab(document.getElementById('pills-payment-tab'));
            tab.show();
        }

        function goBackToService() {
            const tab = new bootstrap.Tab(document.getElementById('pills-package-tabs'));
            tab.show();
        }

        function goBackToInfoFromPayment() {
            const tab = new bootstrap.Tab(document.getElementById('pills-confirmation-tab'));
            tab.show();
        }
    </script>
</body>

</html>