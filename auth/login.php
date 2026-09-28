<?php
// Start a session to store user data across different pages.
// This MUST be the very first line of code in any PHP file that uses session variables.
session_start();

// Include the database connection file.
// Assuming 'connection.php' is in the same directory as 'login.php'.
include 'connection.php';

/**
 * This function tries to log in a user (either a staff member or a regular member).
 * It checks the provided email and password against the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $email The email address the user entered.
 * @param string $password The password the user entered (plain text).
 * @param bool $is_staff True if the email suggests a staff login (@clinic.com), false otherwise.
 * @param array &$user_data_out This variable will be filled with the user's details if login is successful.
 * @return string|null Returns an error message string if login fails, or null if login is successful.
 */
function authenticate_user($conn, $email, $password, $is_staff, &$user_data_out)
{
    // Choose the correct table and columns based on whether it's a staff or member login attempt
    if ($is_staff) {
        // SQL query to get staff details
        $sql = "SELECT Staff_IC, Staff_Password, Staff_Name, Staff_Specialization, Staff_PhoneNo, Staff_Email, Staff_Image FROM Staff WHERE Staff_Email = ?";
    } else {
        // SQL query to get member details
        $sql = "SELECT Member_IC, Member_Password, Member_Name, Member_Email, Member_PhoneNo, Member_Image FROM Member WHERE Member_Email = ?";
    }

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare($sql);

    // If the SQL statement couldn't be prepared, there's a database error
    if (!$stmt) {
        // Log the error for debugging purposes (this won't show to the user)
        error_log("Database statement preparation failed for login: " . $conn->error);
        return "An internal error occurred. Please try again.";
    }

    // Bind the email parameter to the prepared statement
    $stmt->bind_param("s", $email);
    // Execute the prepared statement
    $stmt->execute();
    // Get the result of the query
    $result = $stmt->get_result();

    // Check if exactly one user was found with that email
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc(); // Get the user's data from the database
        // Get the stored (hashed) password from the retrieved user data
        $password_in_db = $is_staff ? $user_data['Staff_Password'] : $user_data['Member_Password'];

        // Verify the user's entered password against the hashed password from the database
        // password_verify() is crucial for security as it safely compares plain text with hashes
        if (password_verify($password, $password_in_db)) {
            // Passwords match! Authentication successful.
            $user_data_out = $user_data; // Store the fetched user data in the output variable
            $stmt->close(); // Close the statement
            return null; // Return null to indicate success (no error message)
        } else {
            // Password does not match
            $stmt->close(); // Close the statement
            return "Invalid credentials. Please check your email and password.";
        }
    } else {
        // No user found with that email, or more than one (which shouldn't happen if emails are unique)
        $stmt->close(); // Close the statement
        return "Invalid credentials. Please check your email and password.";
    }
}

// --- Main Script Logic ---

// Get any previous login error message from the session and then clear it
$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Check if the form has been submitted (i.e., if the request method is POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the email and password from the form, removing any leading/trailing whitespace
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // If either email or password is empty, set an error message and redirect back to login
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit(); // Stop script execution
    }

    // Determine if the user is trying to log in as staff based on their email domain
    // (e.g., using '@clinic.com' for staff emails)
    $is_staff = str_ends_with($email, '@clinic.com');
    $user_data = []; // Initialize an empty array to store user data if authentication succeeds

    // Call the authentication function to verify credentials
    $auth_error = authenticate_user($conn, $email, $password, $is_staff, $user_data);

    // Check the result of the authentication attempt
    if ($auth_error === null) {
        // If $auth_error is null, it means authentication was successful
        if ($is_staff) {
            // Set session variables specific to staff members
            $_SESSION['Staff_IC'] = $user_data['Staff_IC'];
            $_SESSION['Staff_Name'] = $user_data['Staff_Name'];
            $_SESSION['Staff_Email'] = $user_data['Staff_Email'];
            $_SESSION['Staff_PhoneNo'] = $user_data['Staff_PhoneNo'];
            $_SESSION['Staff_Specialization'] = $user_data['Staff_Specialization'] ?? 'N/A';
            $_SESSION['Staff_Image'] = $user_data['Staff_Image'] ?? '../img/default-profile.jpg'; // Consistent default image path

            // Redirect staff to their dashboard
            header("Location: ../staff/dashboard.php");
            exit(); // Stop script execution
        } else {
            // If not staff, it's a member login. Set session variables specific to members.
            $_SESSION['Member_IC'] = $user_data['Member_IC'];
            $_SESSION['Member_Name'] = $user_data['Member_Name'];
            $_SESSION['Member_Email'] = $user_data['Member_Email'];
            $_SESSION['Member_PhoneNo'] = $user_data['Member_PhoneNo'];
            $_SESSION['Member_Image'] = $user_data['Member_Image'] ?? '../img/default-profile.jpg'; // Consistent default image path

            // Redirect members to their homepage
            header("Location: ../member/homepage.php");
            exit(); // Stop script execution
        }
    } else {
        // If authentication failed ($auth_error is not null), store the error message in session
        $_SESSION['login_error'] = $auth_error;
        // Redirect back to the login page to display the error
        header("Location: login.php");
        exit(); // Stop script execution
    }
}

// Close the database connection when the script finishes (important for resource management)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - HappyTooth Dental Clinic</title>
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
            max-width: 900px;
            width: 100%;
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

        .btn-login-custom {
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

        .btn-login-custom:hover {
            background-color: white !important;
            color: #00c4cc !important;
            border-color: #00c4cc !important;
            transform: translateY(-2px);
        }

        .btn-login-custom:active {
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

        @media (max-width: 768px) {
            .container-wrapper {
                flex-direction: column;
                max-width: 450px;
                height: auto;
                min-height: unset;
            }

            .left-panel,
            .right-panel {
                flex: none;
                width: 100%;
                height: auto;
            }

            .left-panel {
                padding: 30px 20px;
            }

            .right-panel {
                padding: 30px 25px;
                max-height: 70vh;
                overflow-y: auto;
            }

            body {
                align-items: flex-start;
                padding: 20px 0;
                overflow-y: auto;
            }

            .logo {
                height: 40px;
            }

            .right-panel h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="container-wrapper">
            <div class="right-panel">
                <img src="../img/logo.png" alt="HappyTooth Logo" class="logo">
                <h2>LOGIN</h2>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                            required>
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <i id="toggleIcon" class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-login-custom">LOGIN</button>
                    </div>
                    <div class="text-end mt-3">
                        <a href="forgotPwd.php" class="text-decoration-none small" style="color: #00c4cc;">Forgot
                            Password?</a>
                    </div>
                </form>
            </div>

            <div class="left-panel">
                <h3>Start your dental journey today</h3>
                <p>Don’t have an account yet? Sign up now and schedule your first appointment with ease</p>
                <a href="signup.php" class="btn btn-outline-light" role="button">SIGN UP</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
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
    </script>
</body>

</html>