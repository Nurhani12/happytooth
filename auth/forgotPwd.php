<?php
session_start();
include 'connection.php';

$error_message = '';
$success_message = '';

if (isset($_SESSION['reset_error'])) {
    $error_message = $_SESSION['reset_error'];
    unset($_SESSION['reset_error']);
}
if (isset($_SESSION['reset_success'])) {
    $success_message = $_SESSION['reset_success'];
    unset($_SESSION['reset_success']);
}

$member_ic_prefill = '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password - HappyTooth Dental Clinic</title>
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
            height: 650px;
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
            overflow-y: auto;
        }

        .logo {
            height: 50px;
            margin-bottom: 30px;
            display: block;
            margin-left: auto;
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

        #passwordRequirementsFeedback.text-muted {
            color: #6c757d !important;
        }


        .password-match-feedback {
            font-size: 0.9rem;
            margin-top: -10px;
            margin-bottom: 20px;
            padding-left: 5px;
            min-height: 20px;
        }

        .password-match-feedback.text-danger {
            color: #dc3545;
        }

        .password-match-feedback.text-success {
            color: #28a745;
        }

        .btn-reset-custom {
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

        .btn-reset-custom:hover {
            background-color: white !important;
            color: #00c4cc !important;
            border-color: #00c4cc !important;
            transform: translateY(-2px);
        }

        .btn-reset-custom:active {
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
                <h2>RESET PASSWORD</h2>

                <?php if (!empty($error_message)) : ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="phpErrorMessage">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)) : ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" id="phpSuccessMessage">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div id="dynamicAlertArea">
                </div>

                <form id="emailVerificationForm">
                    <div class="form-group" id="emailInputGroup">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="memberEmail" name="member_email" class="form-control" placeholder="Your Registered Email" required>
                    </div>
                    <div class="d-grid" id="verifyEmailButtonContainer">
                        <button type="submit" class="btn btn-reset-custom">VERIFY EMAIL</button>
                    </div>
                </form>

                <form action="saveNewPassword.php" method="POST" id="resetPasswordForm" style="display: none;">
                    <input type="hidden" id="memberEmailHidden" name="member_email_for_update" value="">

                    <div class="form-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="newPassword" name="new_password" class="form-control" placeholder="New Password" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility('newPassword', 'toggleNewIcon')">
                            <i id="toggleNewIcon" class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                    <div id="passwordStrength">
                        <div id="strengthIndicator"></div>
                    </div>
                    <small class="text-muted" id="passwordRequirementsFeedback"></small>

                    <div class="form-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility('confirmPassword', 'toggleConfirmIcon')">
                            <i id="toggleConfirmIcon" class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                    <div id="passwordMatchFeedback" class="password-match-feedback"></div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-reset-custom">RESET PASSWORD</button>
                    </div>
                </form>

                <div class="text-end mt-3">
                    <a href="login.php" class="text-decoration-none small" style="color: #00c4cc;">Back to Login</a>
                </div>
            </div>

            <div class="left-panel">
                <h3>Create your new password</h3>
                <p>Choose a strong and secure password to protect your account</p>
                <a href="signup.php" class="btn btn-outline-light" role="button">SIGN UP</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
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

        const newPasswordInput = document.getElementById('newPassword');
        const strengthIndicator = document.getElementById('strengthIndicator');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMatchFeedback = document.getElementById('passwordMatchFeedback');
        const passwordRequirementsFeedback = document.getElementById('passwordRequirementsFeedback');

        newPasswordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);
        newPasswordInput.addEventListener('input', checkPasswordsMatch);

        function updatePasswordStrength() {
            const password = newPasswordInput.value;
            let strength = 0;
            let feedback = [];

            strengthIndicator.classList.remove('bg-danger', 'bg-warning', 'bg-success');
            passwordRequirementsFeedback.classList.remove('text-danger', 'text-warning', 'text-success');
            passwordRequirementsFeedback.classList.add('text-muted');

            if (password.length >= 8) {
                strength += 25;
            } else {
                feedback.push('At least 8 characters');
            }
            if (/[A-Z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('At least one uppercase letter');
            }
            if (/[a-z]/.test(password)) {
                strength += 25;
            } else {
                feedback.push('At least one lowercase letter');
            }
            if (/[0-9]/.test(password)) {
                strength += 15;
            } else {
                feedback.push('At least one number');
            }
            if (/[^A-Za-z0-9\s]/.test(password)) {
                strength += 10;
            } else {
                feedback.push('At least one special character');
            }

            strengthIndicator.style.width = strength + '%';

            if (password.length === 0) {
                strengthIndicator.style.width = '0%';
                passwordRequirementsFeedback.textContent = '';
                passwordRequirementsFeedback.classList.remove('text-danger', 'text-warning', 'text-success');
            } else if (strength < 50) {
                strengthIndicator.classList.add('bg-danger');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-danger');
                passwordRequirementsFeedback.textContent = 'Requirements: ' + feedback.join(', ');
            } else if (strength < 100) {
                strengthIndicator.classList.add('bg-warning');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-warning');
                passwordRequirementsFeedback.textContent = 'Requirements: ' + feedback.join(', ');
            } else {
                strengthIndicator.classList.add('bg-success');
                passwordRequirementsFeedback.classList.remove('text-muted');
                passwordRequirementsFeedback.classList.add('text-success');
                passwordRequirementsFeedback.textContent = 'Password meets all requirements.';
            }

            checkPasswordsMatch();
        }

        function checkPasswordsMatch() {
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword.length === 0) {
                passwordMatchFeedback.textContent = '';
                passwordMatchFeedback.classList.remove('text-danger', 'text-success');
                confirmPasswordInput.setCustomValidity(''); 
            } else if (newPassword === confirmPassword) {
                passwordMatchFeedback.textContent = 'Passwords match!';
                passwordMatchFeedback.classList.remove('text-danger');
                passwordMatchFeedback.classList.add('text-success');
                confirmPasswordInput.setCustomValidity('');
            } else {
                passwordMatchFeedback.textContent = 'Passwords do not match.';
                passwordMatchFeedback.classList.remove('text-success');
                passwordMatchFeedback.classList.add('text-danger');
                confirmPasswordInput.setCustomValidity('New passwords do not match');
            }
        }

        confirmPasswordInput.addEventListener('blur', function() {
            if (confirmPasswordInput.value.length === 0) {
                passwordMatchFeedback.textContent = '';
                passwordMatchFeedback.classList.remove('text-danger', 'text-success');
                confirmPasswordInput.setCustomValidity('');
            }
        });


        const emailVerificationForm = document.getElementById('emailVerificationForm');
        const memberEmailInput = document.getElementById('memberEmail');
        const resetPasswordForm = document.getElementById('resetPasswordForm');
        const memberEmailHiddenInput = document.getElementById('memberEmailHidden');
        const dynamicAlertArea = document.getElementById('dynamicAlertArea');

        emailVerificationForm.addEventListener('submit', function(event) {
            event.preventDefault(); 

            const email = memberEmailInput.value;
            dynamicAlertArea.innerHTML = '';

            fetch('verifyEmail.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'member_email=' + encodeURIComponent(email)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        memberEmailHiddenInput.value = email;
                        emailVerificationForm.style.display = 'none';
                        resetPasswordForm.style.display = 'block';
                        showAlert('success', 'Email verified! Please set your new password.');
                        const phpSuccessAlert = document.getElementById('phpSuccessMessage');
                        if (phpSuccessAlert) phpSuccessAlert.style.display = 'none';
                        const phpErrorAlert = document.getElementById('phpErrorMessage');
                        if (phpErrorAlert) phpErrorAlert.style.display = 'none';

                    } else {
                        showAlert('danger', data.message || 'Error verifying email. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An unexpected error occurred. Please try again later.');
                });
        });

        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            dynamicAlertArea.innerHTML = alertHtml;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (<?php echo json_encode(!empty($error_message) || !empty($success_message)); ?>) {
                emailVerificationForm.style.display = 'block';
                resetPasswordForm.style.display = 'none';
            } else {
                emailVerificationForm.style.display = 'block';
                resetPasswordForm.style.display = 'none';
            }
        });
    </script>
</body>

</html>