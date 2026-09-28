<?php
session_start();
include __DIR__ . '/../auth/connection.php';

// Suppress PHP error output for production, but allow logging
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

// Debug: Log the current Member_IC
error_log("Current Member_IC: " . $_SESSION['Member_IC']);

// Get member details
$member_ic = $_SESSION['Member_IC'];
$member_query = "SELECT Member_Name, Member_Email, Member_PhoneNo FROM Member WHERE Member_IC = ?";
$stmt = $conn->prepare($member_query);
if ($stmt === false) {
    error_log("Error preparing member query: " . $conn->error);
    $member_name = 'Not available';
    $member_email = 'Not available';
    $member_phone = 'Not available';
} else {
    $stmt->bind_param("s", $member_ic);
    if (!$stmt->execute()) {
        error_log("Member query execution failed: " . $stmt->error);
        $member_name = 'Not available';
        $member_email = 'Not available';
        $member_phone = 'Not available';
    } else {
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
    }
    $stmt->close();
}

// Get appointment history
$history_query = "SELECT a.Appointment_ID, a.Appointment_Date, a.Appointment_Time, a.Appointment_Status, 
                 t.Treatment_Type, s.Staff_Name 
                 FROM Appointment a 
                 LEFT JOIN Treatment t ON a.Treatment_ID = t.Treatment_ID 
                 LEFT JOIN Staff s ON a.Staff_IC = s.Staff_IC 
                 WHERE a.Member_IC = ? 
                 ORDER BY a.Appointment_Date DESC, a.Appointment_Time DESC";
$stmt = $conn->prepare($history_query);
$appointments = [];
if ($stmt === false) {
    error_log("Error preparing history query: " . $conn->error);
    echo "<!-- Debug: Query preparation failed: " . htmlspecialchars($conn->error) . " -->";
} else {
    $stmt->bind_param("s", $member_ic);
    if (!$stmt->execute()) {
        error_log("History query execution failed: " . $stmt->error);
        echo "<!-- Debug: Query execution failed: " . htmlspecialchars($stmt->error) . " -->";
    } else {
        $result = $stmt->get_result();
        $appointments = $result->fetch_all(MYSQLI_ASSOC);
        error_log("Fetched " . count($appointments) . " appointments for Member_IC: $member_ic");
        if (empty($appointments)) {
            error_log("No appointments found for Member_IC: $member_ic. Possible columns: " . implode(", ", array_keys($result->fetch_fields())));
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment History</title>
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
        .history-card {
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
        .history-card .row {
            margin-bottom: 15px;
        }
        .history-card strong {
            color: #343a40;
            min-width: 120px;
            display: inline-block;
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
            .history-card { padding: 20px; }
            .history-card strong { min-width: 100px; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <header class="page-header">
        <div class="container">
            <h1>View Appointment History</h1>
            <p>Review your past appointments with our dental professionals. Check details and status here.</p>
        </div>
    </header>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="form-container">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle-fill me-2" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34-.34.658-.73.658-.552 0-1-.448-1-1 0-.154.02-.295.053-.435l.542-2.705C7.261 5.922 7.7 5.727 8 5.727c.421 0 .8.225 1.05.507zM8 4a1 1 0 1 1 0-2 1 1 0 0 1 0 2" />
                            </svg>
                            No appointment history found. Book an appointment to get started!
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $index => $appointment): ?>
                            <div class="history-card" style="animation-delay: <?php echo $index * 0.2; ?>s;">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Treatment:</strong> <?php echo htmlspecialchars($appointment['Treatment_Type'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Provider:</strong> <?php echo htmlspecialchars($appointment['Staff_Name'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Date:</strong> <?php echo htmlspecialchars($appointment['Appointment_Date'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Time:</strong> <?php echo htmlspecialchars($appointment['Appointment_Time'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <p class="mb-0"><strong>Status:</strong> <?php echo htmlspecialchars($appointment['Appointment_Status'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="homepage.php" class="btn btn-primary">Back to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>