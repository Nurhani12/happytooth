<?php
session_start();
include __DIR__ . '/../auth/connection.php'; // Establish connection first

if (!isset($_SESSION['Member_IC'])) {
    header("Location: ../auth/login.php");
    exit();
}

$member_ic = $_SESSION['Member_IC'];

// Fetch user data including package details
$sql = "SELECT m.*, p.Package_Type, p.Package_Price, d.Status AS Package_Status,
                d.Activation_Date AS Package_ActivationDate,
                d.Expired_Date AS Package_ExpiredDate
        FROM member m
        LEFT JOIN detail d ON m.Member_IC = d.Member_IC
        LEFT JOIN package p ON d.Package_ID = p.Package_ID
        WHERE m.Member_IC = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $member_ic);
$stmt->execute();
$result = $stmt->get_result();
$user_data_db = $result->fetch_assoc();
$stmt->close(); // Close the statement, but keep the connection open

// Default values
$default_image = '../img/default-icon.jpg';

if (!$user_data_db) {
    $user_data = [
        'profile_image_path' => $default_image,
        'username' => 'Guest User',
        'membership' => 'N/A',
        'full_name' => 'Not Available',
        'mobile' => '-',
        'email' => '-',
        'package_price' => 'N/A',
        'package_status' => 'N/A',
        'package_activation_date' => 'N/A',
        'package_expired_date' => 'N/A'
    ];
} else {
    // Check if the image path from DB is valid and exists, otherwise use default
    $image_path = (!empty($user_data_db['Member_Image']) && file_exists($user_data_db['Member_Image'])) ? $user_data_db['Member_Image'] : $default_image;

    $user_data = [
        'profile_image_path' => $image_path,
        'username' => $user_data_db['Member_Name'] ?? 'N/A',
        'full_name' => $user_data_db['Member_Name'] ?? 'Not Available',
        'mobile' => $user_data_db['Member_PhoneNo'] ?? '-',
        'email' => $user_data_db['Member_Email'] ?? '-',
        'membership' => $user_data_db['Package_Type'] ?? 'N/A',
        'package_price' => isset($user_data_db['Package_Price']) ? $user_data_db['Package_Price'] : 'N/A',
        'package_status' => $user_data_db['Package_Status'] ?? 'N/A',
        'package_activation_date' => $user_data_db['Package_ActivationDate'] ?? 'N/A',
        'package_expired_date' => $user_data_db['Package_ExpiredDate'] ?? 'N/A'
    ];

    // Set profile data for navbar use (this is good)
    $_SESSION['Member_Name'] = $user_data['full_name'];
    $_SESSION['Member_Image'] = $user_data['profile_image_path']; // Ensure this matches what navbar.php expects
}

// Profile messages and warnings
$display_messages = [];
if (isset($_SESSION['profile_messages'])) {
    $display_messages = $_SESSION['profile_messages'];
    unset($_SESSION['profile_messages']);
}

if (empty($user_data_db['Member_Name']) || empty($user_data_db['Member_PhoneNo']) || ($user_data_db['Member_PhoneNo'] ?? '-') === '-') {
    $display_messages[] = ['type' => 'info', 'text' => 'Please complete your profile by adding your full name and phone number.'];
}

// DO NOT CLOSE THE CONNECTION HERE!
// $conn->close(); // This line caused the error in navbar.php

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile - HappyTooth Dental Clinic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .profile-summary-gradient {
            background: linear-gradient(to right, #00bcd4, #4caf50);
            color: #fff;
        }

        .rounded-circle {
            object-fit: cover;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container py-4">
        <h2 class="mb-4 fw-bold">My Profile</h2>

        <?php if (!empty($display_messages)): ?>
            <div class="messages mb-3">
                <?php foreach ($display_messages as $msg): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo htmlspecialchars($msg['text']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section
            class="profile-summary-gradient p-4 rounded shadow-sm d-flex flex-column flex-md-row align-items-center mb-4">
            <div class="d-flex align-items-center flex-grow-1 mb-3 mb-md-0">
                <img src="<?php echo htmlspecialchars($user_data['profile_image_path']); ?>" alt="User Avatar"
                    class="rounded-circle border border-white border-3 me-4" style="width: 80px; height: 80px;">
                <div>
                    <h3 class="fw-bold fs-4 mb-1"><?php echo htmlspecialchars($user_data['full_name']); ?></h3>
                    <p class="fs-6 mb-0 opacity-75">Package Type:
                        <?php echo htmlspecialchars($user_data['membership']); ?>
                    </p>
                </div>
            </div>
            <div class="ms-md-auto">
                <a href="editProfile.php" class="btn btn-light rounded-circle px-3 py-2 fw-semibold"
                    title="Edit Profile">
                    <i class="fas fa-pencil-alt"></i>
                    <span class="visually-hidden">Edit Profile</span>
                </a>
            </div>
        </section>

        <section class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="text-info fw-bold fs-5 border-bottom pb-2 mb-3">Personal Information</h3>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user_data['full_name']); ?></p>
                        <p><strong>Mobile:</strong> +60<?php echo htmlspecialchars($user_data['mobile']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h3 class="text-info fw-bold fs-5 border-bottom pb-2 mb-3">Membership Package Details</h3>
                        <p><strong>Package Type:</strong> <?php echo htmlspecialchars($user_data['membership']); ?></p>
                        <p><strong>Package Price:</strong> RM
                            <?php echo htmlspecialchars(number_format((float) $user_data['package_price'], 2)); ?>
                        </p>
                        <p><strong>Package Status:</strong>
                            <?php
                            $status = strtolower($user_data['package_status']);
                            $color_class = $status === 'active' ? 'text-success' : ($status === 'inactive' ? 'text-danger' : '');
                            echo '<span class="' . $color_class . '">' . htmlspecialchars($user_data['package_status']) . '</span>';
                            ?>
                        </p>
                        <p><strong>Activation Date:</strong>
                            <?php echo htmlspecialchars($user_data['package_activation_date']); ?></p>
                        <p><strong>Expiry Date:</strong>
                            <?php echo htmlspecialchars($user_data['package_expired_date']); ?></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
        });
    </script>
</body>

</html>
?>