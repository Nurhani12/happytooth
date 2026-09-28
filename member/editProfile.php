<?php
session_start();
// Replace line 3 in edit_package.php with:
include __DIR__ . '/../auth/connection.php'; // Establish connection first

// 1. Consistent Login Check and Member_IC retrieval
if (!isset($_SESSION['Member_IC'])) { // Use Member_IC for session validation
    header("Location: ../auth/login.php");
    exit();
}

$member_ic = $_SESSION['Member_IC'];
$default_image_path = '../img/default-icon.jpg';

// Fetch user data
$query = "SELECT Member_Name, Member_PhoneNo, Member_Email, Member_Image, Member_Password FROM member WHERE Member_IC = ?";
$stmt = $conn->prepare($query);

// Check if statement preparation was successful
if (!$stmt) {
    $_SESSION['profile_messages'][] = ['type' => 'danger', 'text' => 'Database error: ' . $conn->error];
    header("Location: profile.php");
    exit;
}

$stmt->bind_param("s", $member_ic);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    // 2. Handle "User not found." error gracefully
    $_SESSION['profile_messages'][] = ['type' => 'danger', 'text' => 'User not found. Please log in again.'];
    // Clear session and redirect to login if user not found in DB
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}

$user_data = [
    'name' => $row['Member_Name'],
    'mobile' => $row['Member_PhoneNo'],
    'email' => $row['Member_Email'],
    // Use the consistent default image path
    'image' => (!empty($row['Member_Image']) && file_exists($row['Member_Image'])) ? $row['Member_Image'] : $default_image_path,
    'hashed_password' => $row['Member_Password']
];
$stmt->close(); // Close the statement after fetching data


$errors = [];
// Initialize display_messages from session, then unset the session variable
$display_messages = $_SESSION['edit_profile_messages'] ?? [];
unset($_SESSION['edit_profile_messages']);

// Password verification (AJAX) - This block should be *before* other POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_password') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => password_verify($_POST['current_password'] ?? '', $user_data['hashed_password']),
        'message' => 'Incorrect current password.'
    ]);
    exit;
}

// Handle main form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_new_password'] ?? '';
    $remove_image = $_POST['remove_image'] ?? '0';

    // Validation for personal information
    if (strlen($name) > 255) {
        $errors[] = "Name too long.";
    }
    // Basic mobile number validation, adjust regex if needed for specific country codes/lengths
    if (!preg_match('/^\d{7,10}$/', $mobile)) { // Assumes 7 to 10 digits for Malaysian numbers without leading 0/60
        $errors[] = "Invalid mobile number. Please enter a 7-10 digit number.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Email uniqueness check
    if ($email !== $user_data['email']) {
        $stmt_email_check = $conn->prepare("SELECT Member_IC FROM member WHERE Member_Email = ? AND Member_IC != ?");
        if ($stmt_email_check) {
            $stmt_email_check->bind_param("ss", $email, $member_ic);
            $stmt_email_check->execute();
            if ($stmt_email_check->get_result()->num_rows) {
                $errors[] = "Email already in use by another account.";
            }
            $stmt_email_check->close();
        } else {
            $errors[] = "Database error checking email uniqueness.";
        }
    }

    // Handle profile image upload
    $image_path = $user_data['image']; // Start with the current image path (already resolved to default if applicable)

    if ($remove_image === '1') {
        // Only unlink if it's not the default image and actually exists
        if ($image_path !== $default_image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $image_path = $default_image_path; // Set to default after removal
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed_ext)) {
            $errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.";
        } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
            $errors[] = "File too large. Maximum size is 5MB.";
        } else {
            $upload_dir = 'uploads/'; // Make sure this directory exists and is writable
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create directory if it doesn't exist
            }
            $new_path = $upload_dir . uniqid() . '_' . basename($_FILES['profile_picture']['name']); // Use uniqid for unique filenames

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $new_path)) {
                // Delete old image if it's not the default and exists
                if ($user_data['image'] !== $default_image_path && file_exists($user_data['image'])) {
                    unlink($user_data['image']);
                }
                $image_path = $new_path;
            } else {
                $errors[] = "Failed to upload new profile image. Error code: " . $_FILES['profile_picture']['error'];
            }
        }
    }


    // Handle password change - only if new password fields are provided
    $password_updated = false;
    $password_change_attempted = !empty($old_password) || !empty($new_password) || !empty($confirm_password);

    if ($password_change_attempted) {
        if (!password_verify($old_password, $user_data['hashed_password'])) {
            $errors[] = "Incorrect current password.";
        } elseif (empty($new_password)) {
            $errors[] = "New password cannot be empty.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match.";
        } elseif (strlen($new_password) < 8 || !preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/\d/', $new_password) || !preg_match('/[^A-Za-z0-9\s]/', $new_password)) {
            $errors[] = "New password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
        } else {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pass = $conn->prepare("UPDATE member SET Member_Password = ? WHERE Member_IC = ?");
            if ($stmt_pass) {
                $stmt_pass->bind_param("ss", $hashed_new_password, $member_ic);
                $password_updated = $stmt_pass->execute();
                $stmt_pass->close();
                if (!$password_updated) {
                    $errors[] = "Failed to update password.";
                }
            } else {
                $errors[] = "Database error preparing password update.";
            }
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        $stmt_update = $conn->prepare("UPDATE member SET Member_Name=?, Member_PhoneNo=?, Member_Email=?, Member_Image=? WHERE Member_IC=?");
        if ($stmt_update) {
            $stmt_update->bind_param("sssss", $name, $mobile, $email, $image_path, $member_ic);
            if ($stmt_update->execute()) {
                // Update session variables immediately
                $_SESSION['Member_Name'] = $name;
                $_SESSION['Member_Email'] = $email;
                $_SESSION['Member_PhoneNo'] = $mobile;
                $_SESSION['Member_Image'] = $image_path;

                $_SESSION['profile_messages'][] = ['type' => 'success', 'text' => 'Profile updated successfully.'];
                if ($password_updated) {
                    $_SESSION['profile_messages'][] = ['type' => 'success', 'text' => 'Password updated.'];
                }
            } else {
                $_SESSION['profile_messages'][] = ['type' => 'danger', 'text' => 'Failed to update profile: ' . $conn->error];
            }
            $stmt_update->close();
        } else {
            $_SESSION['profile_messages'][] = ['type' => 'danger', 'text' => 'Database error preparing profile update.'];
        }
        header("Location: profile.php");
        exit;
    } else {
        // Store error messages to display on the current page
        foreach ($errors as $error) {
            $display_messages[] = ['type' => 'danger', 'text' => $error];
        }
        $_SESSION['edit_profile_messages'] = $display_messages; // Store messages to display on next load
        // No redirect needed here, the form will just re-render with errors
    }
}

// Close the connection only at the very end of the script or after all database operations are done
// (or leave it open if 'navbar.php' needs it)
// $conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - HappyTooth Dental Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }

        .user-circle-icon-nav {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 15px;
            }

            .nav-right {
                flex-direction: column;
                width: 100%;
                margin-top: 10px;
                gap: 5px;
                align-items: center;
            }

            .menu-button,
            .menu-content a,
            .side-btn {
                width: 100%;
            }

            .menu-content {
                position: static;
                width: 100%;
                box-shadow: none;
                border: none;
                border-radius: 0;
            }
        }

        .input-group-text.custom-prefix {
            background-color: #e9ecef;
            border-right: none;
            border-color: #ced4da;
        }

        .form-control:read-only {
            background-color: #e9ecef;
            opacity: 1;
            cursor: default;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            background-color: #fff;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: border-color 0.3s ease;
        }

        input:focus {
            border-color: #00c4cc;
            box-shadow: 0 0 0 0.15rem rgba(0, 196, 204, 0.25);
        }

        hr {
            border-color: #ddd;
        }

        .password-input-group {
            position: relative;
            width: 100%;
        }

        .password-input-group .form-control {
            padding-right: 2.5rem;
        }

        .password-input-group .input-group-append {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            display: flex;
            align-items: center;
            padding-right: 0.75rem;
            cursor: pointer;
            z-index: 5;
        }

        #strengthIndicator.weak {
            background-color: #dc3545;
        }

        #strengthIndicator.medium {
            background-color: #ffc107;
        }

        #strengthIndicator.strong {
            background-color: #28a745;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <h2 class="mb-4 fw-bold">Edit Profile</h2>

        <?php if (!empty($display_messages)): ?>
            <div class="messages mb-3">
                <?php foreach ($display_messages as $msg): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo htmlspecialchars($msg['text']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm p-4">
            <form action="editProfile.php" method="POST" enctype="multipart/form-data" id="editProfileForm">
                <div class="container">
                    <div class="row mb-4 justify-content-center">
                        <div class="col-md-3 text-center">
                            <img id="profilePreview" src="<?php echo htmlspecialchars($user_data['image']); ?>"
                                alt="Profile Picture" class="rounded-circle mb-3"
                                style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #00c4cc;">

                            <input type="file" class="form-control form-control-sm mx-auto mb-2" id="profile_picture"
                                name="profile_picture" accept="image/*" style="width: fit-content;">

                            <button type="button" class="btn btn-outline-danger btn-sm" id="removeImageBtn">Remove
                                Image</button>

                            <input type="hidden" name="remove_image" id="remove_image" value="0">
                            <small class="form-text text-muted d-block mt-1">Max 5MB. JPG, PNG, GIF.</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="mobile" class="form-label">Mobile Number</label>
                    <div class="input-group">
                        <span class="input-group-text custom-prefix">+60</span>
                        <input type="text" class="form-control" id="mobile" name="mobile"
                            value="<?php echo htmlspecialchars($user_data['mobile']); ?>" required pattern="\d{7,10}"
                            title="Please enter a 7-10 digit mobile number without the +60 prefix">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>

                <hr class="my-4">

                <h4 class="mb-3 text-info fw-bold">Update Password</h4>
                <p class="text-muted">Ensure your account is using a long, random password to stay secure.</p>

                <div class="mb-3">
                    <label for="old_password" class="form-label">Current Password</label>
                    <div class="d-flex align-items-center">
                        <div class="input-group password-input-group flex-grow-1">
                            <input type="password" class="form-control" id="old_password" name="old_password"
                                autocomplete="current-password" placeholder="Enter current password">
                            <span class="input-group-append" id="toggleOldPassword">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        <button type="button" class="btn btn-primary ms-2" id="verifyCurrentPasswordBtn">Verify</button>
                    </div>
                    <small id="oldPasswordFeedback" class="form-text"></small>
                </div>

                <div id="newPasswordFields" style="display: none;">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                autocomplete="new-password" placeholder="Enter new password">
                            <span class="input-group-append" id="toggleNewPassword">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        <div class="progress mt-1" style="height: 5px;">
                            <div id="strengthIndicator" class="progress-bar" role="progressbar" style="width: 0%;"
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="passwordRequirementsFeedback" class="form-text"></small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                        <div class="input-group password-input-group">
                            <input type="password" class="form-control" id="confirm_new_password"
                                name="confirm_new_password" autocomplete="new-password"
                                placeholder="Confirm new password">
                            <span class="input-group-append" id="toggleConfirmNewPassword">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                        <small id="passwordMatchFeedback" class="form-text"></small>
                    </div>
                </div>
                <input type="hidden" id="passwordChangeInitiated" name="password_change_initiated" value="false">

                <div class="d-flex justify-content-end mt-4">
                    <a href="profile.php" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-info text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = (inputId, toggleId) => {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(toggleId).querySelector('i');
                icon.addEventListener('click', () => {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            };

            togglePassword('old_password', 'toggleOldPassword');
            togglePassword('new_password', 'toggleNewPassword');
            togglePassword('confirm_new_password', 'toggleConfirmNewPassword');

            const oldPasswordInput = document.getElementById('old_password');
            const verifyCurrentPasswordBtn = document.getElementById('verifyCurrentPasswordBtn');
            const oldPasswordFeedback = document.getElementById('oldPasswordFeedback');
            const newPasswordFields = document.getElementById('newPasswordFields');
            const newPasswordInput = document.getElementById('new_password');
            const confirmNewPasswordInput = document.getElementById('confirm_new_password');
            const strengthIndicator = document.getElementById('strengthIndicator');
            const passwordRequirementsFeedback = document.getElementById('passwordRequirementsFeedback');
            const passwordMatchFeedback = document.getElementById('passwordMatchFeedback');
            const passwordChangeInitiated = document.getElementById('passwordChangeInitiated');

            verifyCurrentPasswordBtn.addEventListener('click', function () {
                const currentPassword = oldPasswordInput.value;
                if (currentPassword.length === 0) {
                    oldPasswordFeedback.textContent = 'Please enter your current password.';
                    oldPasswordFeedback.className = 'form-text text-danger';
                    return;
                }

                fetch('editProfile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=verify_password&current_password=${encodeURIComponent(currentPassword)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            oldPasswordFeedback.textContent = 'Current password verified!';
                            oldPasswordFeedback.className = 'form-text text-success';
                            oldPasswordInput.readOnly = true;
                            verifyCurrentPasswordBtn.style.display = 'none';
                            newPasswordFields.style.display = 'block';
                            passwordChangeInitiated.value = 'true';
                            newPasswordInput.focus();
                        } else {
                            oldPasswordFeedback.textContent = data.message || 'Incorrect current password.';
                            oldPasswordFeedback.className = 'form-text text-danger';
                            oldPasswordInput.focus();
                            newPasswordFields.style.display = 'none';
                            passwordChangeInitiated.value = 'false';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        oldPasswordFeedback.textContent = 'An error occurred during verification.';
                        oldPasswordFeedback.className = 'form-text text-danger';
                    });
            });

            oldPasswordInput.addEventListener('input', function () {
                // If the old password field was previously readOnly and now it's being typed into, reset the state
                if (this.readOnly) {
                    this.readOnly = false;
                    verifyCurrentPasswordBtn.style.display = 'inline-block';
                    oldPasswordFeedback.textContent = '';
                    oldPasswordFeedback.className = 'form-text';
                    newPasswordFields.style.display = 'none';
                    newPasswordInput.value = '';
                    confirmNewPasswordInput.value = '';
                    strengthIndicator.style.width = '0%';
                    strengthIndicator.className = 'progress-bar'; // Reset class
                    passwordRequirementsFeedback.textContent = '';
                    passwordMatchFeedback.textContent = '';
                    passwordChangeInitiated.value = 'false';
                }
            });


            function updatePasswordStrength() {
                const password = newPasswordInput.value;
                let strength = 0;
                let feedback = [];

                if (password.length >= 8) {
                    strength += 25;
                } else {
                    feedback.push('at least 8 characters');
                }
                if (/[A-Z]/.test(password)) {
                    strength += 25;
                } else {
                    feedback.push('at least one uppercase letter');
                }
                if (/[a-z]/.test(password)) {
                    strength += 25;
                } else {
                    feedback.push('at least one lowercase letter');
                }
                if (/[0-9]/.test(password)) {
                    strength += 15;
                } else {
                    feedback.push('at least one number');
                }
                if (/[^A-Za-z0-9\s]/.test(password)) {
                    strength += 10;
                } else {
                    feedback.push('at least one special character');
                }

                strengthIndicator.style.width = strength + '%';
                if (strength < 50) {
                    strengthIndicator.className = 'progress-bar bg-danger';
                } else if (strength < 100) {
                    strengthIndicator.className = 'progress-bar bg-warning';
                } else {
                    strengthIndicator.className = 'progress-bar bg-success';
                }

                passwordRequirementsFeedback.textContent = feedback.length > 0 ? 'Requirements: ' + feedback.join(', ') + '.' : 'Password meets requirements.';
                if (feedback.length === 0 && password.length > 0) {
                    passwordRequirementsFeedback.classList.remove('text-danger', 'text-muted');
                    passwordRequirementsFeedback.classList.add('text-success');
                } else if (feedback.length > 0 && password.length > 0) {
                    passwordRequirementsFeedback.classList.remove('text-success', 'text-muted');
                    passwordRequirementsFeedback.classList.add('text-danger');
                } else {
                    passwordRequirementsFeedback.textContent = '';
                    passwordRequirementsFeedback.classList.remove('text-success', 'text-danger');
                    passwordRequirementsFeedback.classList.add('text-muted');
                }

                checkNewPasswordsMatch();
            }

            function checkNewPasswordsMatch() {
                const newPassword = newPasswordInput.value;
                const confirmNewPassword = confirmNewPasswordInput.value;

                if (confirmNewPassword.length === 0) {
                    passwordMatchFeedback.textContent = '';
                    passwordMatchFeedback.classList.remove('text-danger', 'text-success');
                    confirmNewPasswordInput.setCustomValidity('');
                } else if (newPassword === confirmNewPassword) {
                    passwordMatchFeedback.textContent = 'Passwords match!';
                    passwordMatchFeedback.classList.remove('text-danger');
                    passwordMatchFeedback.classList.add('text-success');
                    confirmNewPasswordInput.setCustomValidity('');
                } else {
                    passwordMatchFeedback.textContent = 'Passwords do not match.';
                    passwordMatchFeedback.classList.remove('text-success');
                    passwordMatchFeedback.classList.add('text-danger');
                    confirmNewPasswordInput.setCustomValidity('New passwords do not match');
                }
            }

            newPasswordInput.addEventListener('input', updatePasswordStrength);
            confirmNewPasswordInput.addEventListener('input', checkNewPasswordsMatch);

            newPasswordInput.addEventListener('blur', function () {
                if (newPasswordInput.value.length === 0) {
                    strengthIndicator.style.width = '0%';
                    strengthIndicator.className = 'progress-bar'; // Reset class
                    passwordRequirementsFeedback.textContent = '';
                    passwordRequirementsFeedback.classList.remove('text-success', 'text-danger');
                    passwordRequirementsFeedback.classList.add('text-muted');
                }
            });
        });

        // Preview selected image
        document.getElementById('profile_picture').addEventListener('change', function (e) {
            const preview = document.getElementById('profilePreview');
            const file = e.target.files[0];
            const defaultImagePath = '<?php echo $default_image_path; ?>'; // Pass PHP variable to JS
            if (file) {
                preview.src = URL.createObjectURL(file);
                document.getElementById('remove_image').value = '0'; // If a new image is selected, don't remove it
            } else {
                // If the user clears the file input (e.g., by selecting then cancelling), revert to current image
                preview.src = document.getElementById('profilePreview').getAttribute('data-original-src') || defaultImagePath;
                document.getElementById('remove_image').value = '0';
            }
        });

        // Store original image path on load
        document.addEventListener('DOMContentLoaded', function () {
            const profilePreview = document.getElementById('profilePreview');
            profilePreview.setAttribute('data-original-src', profilePreview.src);
        });

        // Handle remove image
        document.getElementById('removeImageBtn').addEventListener('click', function () {
            const defaultImagePath = '<?php echo $default_image_path; ?>'; // Pass PHP variable to JS
            document.getElementById('profilePreview').src = defaultImagePath;
            document.getElementById('profile_picture').value = ''; // Clear the file input
            document.getElementById('remove_image').value = '1'; // Set hidden input to 1 to signal removal
        });

    </script>

</body>

</html>