<?php
// Start the session to manage user data across requests
session_start();

// Include database connection file. Assumes 'connection.php' defines and connects to a $conn variable.
include 'connection.php';

// --- Helper Functions for Validation and Database Checks ---

/**
 * Validates user input fields (IC, email, password).
 * @param string $ic_number The IC number provided by the user.
 * @param string $email The email provided by the user.
 * @param string $password The password provided by the user.
 * @param string $confirm_password The confirmed password provided by the user.
 * @return array An array of validation error messages.
 */
function validate_input($ic_number, $email, $password, $confirm_password)
{
    $errors = [];

    // Validate IC Number
    if (empty($ic_number)) {
        $errors[] = "IC Number is required.";
    } elseif (!preg_match('/^\d{6}-\d{2}-\d{4}$/', $ic_number)) {
        $errors[] = "Invalid IC Number format. Please use YYMMDD-XX-XXXX.";
    }

    // Validate Email Address
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate Password strength
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must include at least one lowercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must include at least one special character.";
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    return $errors;
}

/**
 * Checks if the given IC number or email already exists in member or staff tables.
 * @param mysqli $conn The database connection object.
 * @param string $ic_number The IC number to check.
 * @param string $email The email to check.
 * @return array An associative array with boolean flags for existence in member and staff tables.
 */
function check_existing_users($conn, $ic_number, $email)
{
    $existence_flags = [
        'email_exists_in_member' => false,
        'ic_exists_in_member' => false,
        'email_exists_in_staff' => false,
        'ic_exists_in_staff' => false,
        'db_errors' => []
    ];

    // Check in 'member' table
    $stmt_check_member = $conn->prepare("SELECT Member_IC, Member_Email FROM member WHERE Member_IC = ? OR Member_Email = ?");
    if ($stmt_check_member) {
        $stmt_check_member->bind_param("ss", $ic_number, $email);
        $stmt_check_member->execute();
        $result_member = $stmt_check_member->get_result();
        if ($result_member->num_rows > 0) {
            while ($row = $result_member->fetch_assoc()) {
                if ($row['Member_IC'] === $ic_number) {
                    $existence_flags['ic_exists_in_member'] = true;
                }
                if ($row['Member_Email'] === $email) {
                    $existence_flags['email_exists_in_member'] = true;
                }
            }
        }
        $stmt_check_member->close();
    } else {
        $existence_flags['db_errors'][] = "Database error during member existence check. Please try again later.";
        error_log("Database preparation error for member existence check: " . $conn->error);
    }

    // Check in 'staff' table
    $stmt_check_staff = $conn->prepare("SELECT Staff_IC, Staff_Email FROM staff WHERE Staff_IC = ? OR Staff_Email = ?");
    if ($stmt_check_staff) {
        $stmt_check_staff->bind_param("ss", $ic_number, $email);
        $stmt_check_staff->execute();
        $result_staff = $stmt_check_staff->get_result();
        if ($result_staff->num_rows > 0) {
            while ($row = $result_staff->fetch_assoc()) {
                if ($row['Staff_IC'] === $ic_number) {
                    $existence_flags['ic_exists_in_staff'] = true;
                }
                if ($row['Staff_Email'] === $email) {
                    $existence_flags['email_exists_in_staff'] = true;
                }
            }
        }
        $stmt_check_staff->close();
    } else {
        $existence_flags['db_errors'][] = "Database error during staff existence check. Please try again later.";
        error_log("Database preparation error for staff existence check: " . $conn->error);
    }

    return $existence_flags;
}

/**
 * Applies cross-table registration rules based on email domain and existing user flags.
 * @param bool $is_staff_email True if the email suggests staff registration, false otherwise.
 * @param array $existence_flags Associative array from check_existing_users function.
 * @return array An array of registration rule violation error messages.
 */
function apply_registration_rules($is_staff_email, $existence_flags)
{
    $errors = [];

    if ($is_staff_email) { // If email suggests staff registration
        if ($existence_flags['ic_exists_in_staff']) {
            $errors[] = "IC Number is already registered for staff.";
        }
        if ($existence_flags['email_exists_in_staff']) {
            $errors[] = "Email is already registered for staff.";
        }
        if ($existence_flags['ic_exists_in_member']) {
            $errors[] = "IC Number is already registered as a member. Staff accounts cannot use member ICs.";
        }
        if ($existence_flags['email_exists_in_member']) {
            $errors[] = "Email is already registered as a member. Staff accounts cannot use member emails.";
        }
    } else { // If email suggests member registration
        if ($existence_flags['ic_exists_in_member']) {
            $errors[] = "IC Number is already registered for member.";
        }
        if ($existence_flags['email_exists_in_member']) {
            $errors[] = "Email is already registered for member.";
        }
        if ($existence_flags['ic_exists_in_staff']) {
            $errors[] = "IC Number is already registered as staff. Member accounts cannot use staff ICs.";
        }
        if ($existence_flags['email_exists_in_staff']) {
            $errors[] = "Email is already registered as staff. Member accounts cannot use staff emails.";
        }
    }
    return $errors;
}

// --- Main Script Execution ---

// Initialize form data, errors, and success messages from session variables.
$member_ic_value = $_SESSION['form_data']['ic_number'] ?? '';
$email_value = $_SESSION['form_data']['email'] ?? '';
$display_errors = $_SESSION['signup_errors'] ?? [];
$success_message = $_SESSION['message'] ?? '';

// Clear session variables to prevent them from persisting on page refresh
unset($_SESSION['signup_errors']);
unset($_SESSION['form_data']);
unset($_SESSION['message']);

// Process form submission when the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve input data from the POST request
    $ic_number = trim($_POST['ic_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = []; // Array to collect all validation errors

    // Step 1: Validate input fields
    $errors = array_merge($errors, validate_input($ic_number, $email, $password, $confirm_password));

    // Step 2: If no input validation errors, proceed to database checks
    if (empty($errors)) {
        $is_staff_email = str_ends_with($email, '@clinic.com');
        $existence_flags = check_existing_users($conn, $ic_number, $email);

        // Add any database-related errors from existence check
        $errors = array_merge($errors, $existence_flags['db_errors']);

        // Step 3: Apply cross-table existence rules
        $errors = array_merge($errors, apply_registration_rules($is_staff_email, $existence_flags));
    }

    // Step 4: If all validations and existence checks pass, proceed with database insertion
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password for security
        $default_image_path = '../img/default-icon.jpg'; // Default profile image path

        if ($is_staff_email) {
            // Default values for staff fields (as per your schema, these are NOT NULL)
            $default_staff_name = "New Staff";
            $default_specialization = "General";
            $default_phone_no = "N/A";

            $insert_sql = "INSERT INTO staff (Staff_IC, Staff_Name, Staff_Specialization, Staff_PhoneNo, Staff_Email, Staff_Password, Staff_Image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt) {
                // Bind parameters for staff insertion
                $insert_stmt->bind_param("sssssss", $ic_number, $default_staff_name, $default_specialization, $default_phone_no, $email, $hashed_password, $default_image_path);

                if ($insert_stmt->execute()) {
                    $_SESSION['message'] = "Staff account created successfully! Please log in.";
                    header("Location: login.php"); // Redirect to login page on success
                    exit();
                } else {
                    $errors[] = "Staff registration failed. Please try again. Error: " . $insert_stmt->error;
                    error_log("Staff signup failed: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            } else {
                $errors[] = "Database insert preparation error for staff: " . $conn->error;
                error_log("Database insert preparation error for staff: " . $conn->error);
            }
        } else {
            // Default values for member fields (as per your schema, these are NOT NULL)
            $default_member_name = "New Member";
            $default_member_phone_no = "N/A";

            // Corrected INSERT statement for 'member' table based on your schema.
            // Member_Name and Member_PhoneNo added.
            $insert_sql = "INSERT INTO member (Member_IC, Member_Name, Member_PhoneNo, Member_Email, Member_Password, Member_Image) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);

            if ($insert_stmt) {
                // Bind parameters for member insertion
                $insert_stmt->bind_param("ssssss", $ic_number, $default_member_name, $default_member_phone_no, $email, $hashed_password, $default_image_path);

                if ($insert_stmt->execute()) {
                    $_SESSION['message'] = "Member account created successfully! Please log in.";
                    header("Location: login.php"); // Redirect to login page on success
                    exit();
                } else {
                    $errors[] = "Member registration failed. Please try again. Error: " . $insert_stmt->error;
                    error_log("Member signup failed: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            } else {
                $errors[] = "Database insert preparation error for member: " . $conn->error;
                error_log("Database insert preparation error for member: " . $conn->error);
            }
        }
    }

    // If there are any errors at this point, store them in the session and redirect
    // back to the signup page to display them to the user.
    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['form_data'] = [
            'ic_number' => $ic_number,
            'email' => $email
        ];
        header("Location: signup.php");
        exit();
    }
}
// Close the database connection when the script finishes
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #00c6cf, #ffffff);
            background-size: 200% 100%;
            animation: gradientAnimation 3s infinite alternate;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 100% 50%;
            }
        }

        .container-wrapper {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            width: 900px;
            height: 600px;
            overflow: hidden;
        }

        .left-panel {
            flex: 1;
            position: relative;
            background: url('../img/image1.jpeg') center/cover no-repeat;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .left-panel>* {
            z-index: 2;
            position: relative;
        }

        .left-panel h3 {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 2rem;
        }

        .left-panel p {
            margin-bottom: 30px;
            font-size: 1rem;
            line-height: 1.5;
            opacity: 0.9;
        }

        .left-panel .btn-outline-light {
            border: 2px solid white;
            color: white;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .left-panel .btn-outline-light:hover {
            background-color: white;
            color: #00c6cf;
        }

        .right-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: hidden;
            /* Default to hidden, show scroll bar if content overflows */
        }

        .right-panel.scroll-active {
            overflow-y: auto;
            /* Enable scroll if content is too long */
        }

        .logo {
            height: 50px;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .right-panel h2 {
            color: #00c4cc;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.2rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 12px 15px 12px 45px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #00c4cc;
            box-shadow: 0 0 0 0.25rem rgba(0, 196, 204, 0.25);
            outline: none;
        }

        .form-control::placeholder {
            color: #999;
            opacity: 0.8;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            font-size: 1.1rem;
            z-index: 2;
        }

        #passwordStrength {
            height: 6px;
            width: 100%;
            background-color: #e9ecef;
            margin-top: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
            overflow: hidden;
        }

        #strengthIndicator {
            height: 100%;
            width: 0%;
            border-radius: 3px;
            transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
        }

        #strengthIndicator.bg-danger {
            background-color: #dc3545 !important;
        }

        #strengthIndicator.bg-warning {
            background-color: #ffc107 !important;
        }

        #strengthIndicator.bg-success {
            background-color: #28a745 !important;
        }


        #passwordRequirementsFeedback {
            font-size: 0.9rem;
            margin-top: 0px;
            margin-bottom: 15px;
            padding-left: 5px;
            min-height: 20px;
            /* Ensures consistent layout even when empty */
        }

        #passwordRequirementsFeedback.text-danger {
            color: #dc3545 !important;
        }

        #passwordRequirementsFeedback.text-warning {
            color: #ffc107 !important;
        }

        #passwordRequirementsFeedback.text-success {
            color: #28a745 !important;
        }

        .password-match-feedback {
            font-size: 0.9rem;
            margin-top: -10px;
            /* Adjust to pull closer to input */
            margin-bottom: 20px;
            padding-left: 5px;
            min-height: 20px;
            /* Ensures consistent layout even when empty */
        }

        .password-match-feedback.text-danger {
            color: #dc3545;
        }

        .password-match-feedback.text-success {
            color: #28a745;
        }

        .btn-signup-custom {
            background-color: #00c4cc !important;
            color: white !important;
            border: 1px solid #00c4cc !important;
            font-weight: bold;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-signup-custom:hover {
            background-color: white !important;
            color: #00c4cc !important;
            border-color: #00c4cc !important;
            transform: translateY(-2px);
        }

        .btn-signup-custom:active {
            transform: translateY(0);
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .alert ul {
            padding-left: 0;
            margin-bottom: 0;
            list-style-type: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-wrapper {
                flex-direction: column;
                /* Stack panels vertically on smaller screens */
                width: 90%;
                max-width: 450px;
                /* Constrain width on small screens */
                height: auto;
                /* Allow height to adjust based on content */
                min-height: unset;
                /* Remove min-height constraint for better adaptability */
            }

            .left-panel,
            .right-panel {
                flex: none;
                /* Disable flex growth on stacked panels */
                width: 100%;
                height: auto;
            }

            .left-panel {
                padding: 30px 20px;
            }

            .right-panel {
                padding: 30px 25px;
            }

            .right-panel.scroll-active {
                max-height: 100vh;
                /* Allow scrolling on right panel if content overflows */
            }

            body {
                align-items: flex-start;
                /* Align content to top on small screens to accommodate scrolling */
                padding: 20px 0;
                /* Add vertical padding to body */
                overflow-y: auto;
                /* Enable body scroll if entire content exceeds viewport height */
            }

            .logo {
                height: 40px;
                /* Adjust logo size for smaller screens */
            }

            .right-panel h2 {
                font-size: 1.8rem;
                /* Adjust heading size */
            }
        }
    </style>
</head>

<body>
    <div class="container-wrapper">
        <div class="left-panel">
            <h3>Welcome Back!</h3>
            <p>Already have an account? Log in now and book your next visit with ease.</p>
            <a href="login.php" class="btn btn-outline-light" role="button">LOG IN</a>
        </div>

        <div class="right-panel" id="rightPanel">
            <!-- Company Logo -->
            <img src="../img/logo.png" alt="HappyTooth Logo" class="logo">
            <h2>SIGN UP</h2>

            <!-- Display PHP errors if any -->
            <?php if (!empty($display_errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($display_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Display PHP success message if any -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <!-- IC Number Input -->
                <div class="form-group">
                    <span class="input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" name="ic_number" class="form-control" placeholder="IC Number (YYMMDD-XX-XXXX)"
                        value="<?php echo htmlspecialchars($member_ic_value); ?>" required>
                </div>

                <!-- Email Input -->
                <div class="form-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Email Address"
                        value="<?php echo htmlspecialchars($email_value); ?>" required>
                </div>

                <!-- Password Input with Toggle and Strength Indicator -->
                <div class="form-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                        required>
                    <span class="password-toggle" onclick="togglePasswordVisibility('password', 'toggleIcon')">
                        <i id="toggleIcon" class="fas fa-eye-slash"></i>
                    </span>
                </div>
                <!-- Password Strength Bar -->
                <div id="passwordStrength">
                    <div id="strengthIndicator"></div>
                </div>
                <!-- Password Requirements Feedback -->
                <small class="text-muted" id="passwordRequirementsFeedback"></small>


                <!-- Confirm Password Input with Toggle and Match Feedback -->
                <div class="form-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-control"
                        placeholder="Confirm Password" required>
                    <span class="password-toggle"
                        onclick="togglePasswordVisibility('confirmPassword', 'toggleConfirmIcon')">
                        <i id="toggleConfirmIcon" class="fas fa-eye-slash"></i>
                    </span>
                </div>
                <!-- Password Match Feedback -->
                <div id="passwordMatchFeedback" class="password-match-feedback"></div>

                <!-- Sign Up Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-signup-custom">SIGN UP</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        /**
         * Toggles the visibility of a password input field and changes its associated icon.
         * @param {string} fieldId The ID of the password input field.
         * @param {string} iconId The ID of the icon element (e.g., Font Awesome eye icon).
         */
        function togglePasswordVisibility(fieldId, iconId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            }
        }

        // Get DOM elements for password input, strength indicator, confirm password, and feedback
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.getElementById('strengthIndicator');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMatchFeedback = document.getElementById('passwordMatchFeedback');
        const passwordRequirementsFeedback = document.getElementById('passwordRequirementsFeedback');
        const rightPanel = document.getElementById('rightPanel'); // Used for scroll-active class

        // Add event listeners for real-time validation feedback
        passwordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
        passwordInput.addEventListener('input', checkPasswordsMatch);

        /**
         * Updates the password strength indicator and provides feedback on requirements.
         */
        function updatePasswordStrength() {
            const password = passwordInput.value;
            let strength = 0;
            let feedback = [];

            // Reset strength indicator and feedback classes
            strengthIndicator.classList.remove('bg-danger', 'bg-warning', 'bg-success');
            passwordRequirementsFeedback.classList.remove('text-danger', 'text-warning', 'text-success');
            passwordRequirementsFeedback.classList.add('text-muted'); // Default text color

            // Check password length
            if (password.length >= 8) {
                strength += 25;
            } else {
                feedback.push('At least 8 characters');
            }
            // Check for uppercase letters
            if (/[A-Z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('At least one uppercase letter');
            }
            // Check for lowercase letters
            if (/[a-z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('At least one lowercase letter');
            }
            // Check for numbers
            if (/[0-9]/.test(password)) {
                strength += 15;
            } else {
                feedback.push('At least one number');
            }
            // Check for special characters
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 10;
            } else {
                feedback.push('At least one special character');
            }

            // Update strength bar width
            strengthIndicator.style.width = strength + '%';

            // Provide visual feedback based on strength
            if (password.length === 0) {
                strengthIndicator.style.width = '0%';
                passwordRequirementsFeedback.textContent = '';
                passwordRequirementsFeedback.classList.remove('text-danger', 'text-warning', 'text-success');
                // Remove scroll-active if no alerts and both password fields are empty
                if (document.querySelectorAll('.alert').length === 0 && confirmPasswordInput.value.length === 0) {
                    rightPanel.classList.remove('scroll-active');
                }
            } else if (strength < 50) {
                strengthIndicator.classList.add('bg-danger');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-danger');
                passwordRequirementsFeedback.textContent = 'Requirements: ' + feedback.join(', ');
                rightPanel.classList.add('scroll-active'); // Ensure scroll if feedback is shown
            } else if (strength < 100) {
                strengthIndicator.classList.add('bg-warning');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-warning');
                passwordRequirementsFeedback.textContent = 'Requirements: ' + feedback.join(', ');
                rightPanel.classList.add('scroll-active'); // Ensure scroll if feedback is shown
            } else {
                strengthIndicator.classList.add('bg-success');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-success');
                passwordRequirementsFeedback.textContent = 'Password meets all requirements.';
                rightPanel.classList.add('scroll-active'); // Ensure scroll if feedback is shown
            }

            // Always re-check password match when password input changes
            checkPasswordsMatch();
        }

        /**
         * Checks if the password and confirm password fields match and provides feedback.
         */
        function checkPasswordsMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Reset feedback
            passwordMatchFeedback.textContent = '';
            passwordMatchFeedback.classList.remove('text-danger', 'text-success');
            confirmPasswordInput.setCustomValidity(''); // Clear custom validation message

            if (confirmPassword.length === 0) {
                // If confirm password is empty, clear feedback and reset scroll
                if (passwordInput.value.length === 0 && document.querySelectorAll('.alert').length === 0) {
                    rightPanel.classList.remove('scroll-active');
                }
            } else if (password === confirmPassword) {
                passwordMatchFeedback.textContent = 'Passwords match!';
                passwordMatchFeedback.classList.add('text-success');
                rightPanel.classList.add('scroll-active'); // Ensure scroll if feedback is shown
            } else {
                passwordMatchFeedback.textContent = 'Passwords do not match.';
                passwordMatchFeedback.classList.add('text-danger');
                confirmPasswordInput.setCustomValidity('Passwords do not match'); // Set custom validation for form submission
                rightPanel.classList.add('scroll-active'); // Ensure scroll if feedback is shown
            }
        }

        // Initialize feedback and scroll state on page load
        document.addEventListener('DOMContentLoaded', () => {
            updatePasswordStrength();
            checkPasswordsMatch();

            // If there are PHP-generated alerts (errors or success messages), enable scrolling
            if (document.querySelectorAll('.alert').length > 0) {
                rightPanel.classList.add('scroll-active');
            }
        });

        // Handle password input blur event (when user clicks out of the field)
        passwordInput.addEventListener('blur', function () {
            if (passwordInput.value.length === 0) {
                strengthIndicator.style.width = '0%';
                strengthIndicator.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                passwordRequirementsFeedback.textContent = '';
                passwordRequirementsFeedback.classList.remove('text-success', 'text-danger', 'text-warning');
                passwordRequirementsFeedback.classList.add('text-muted');
                // If no alerts and both password fields are empty, remove scroll
                if (document.querySelectorAll('.alert').length === 0 && confirmPasswordInput.value.length === 0) {
                    rightPanel.classList.remove('scroll-active');
                }
            }
        });

        // Handle confirm password input blur event
        confirmPasswordInput.addEventListener('blur', function () {
            // If both password fields are empty and no alerts, remove scroll
            if (confirmPasswordInput.value.length === 0 && passwordInput.value.length === 0 && document.querySelectorAll('.alert').length === 0) {
                passwordMatchFeedback.textContent = '';
                passwordMatchFeedback.classList.remove('text-danger', 'text-success');
                rightPanel.classList.remove('scroll-active');
            }
        });
    </script>
</body>

</html>