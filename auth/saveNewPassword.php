<?php
session_start();
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_for_update = $_POST['member_email_for_update'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $error_messages = [];

    if (empty($email_for_update) || !filter_var($email_for_update, FILTER_VALIDATE_EMAIL)) {
        $error_messages[] = "Valid email address is missing or invalid.";
    }
    if (empty($new_password)) {
        $error_messages[] = "New password is required.";
    }
    if (empty($confirm_password)) {
        $error_messages[] = "Confirm password is required.";
    }
    if ($new_password !== $confirm_password) {
        $error_messages[] = "New password and confirm password do not match.";
    }

    if (strlen($new_password) < 8) {
        $error_messages[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match("/[A-Z]/", $new_password)) {
        $error_messages[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match("/[a-z]/", $new_password)) {
        $error_messages[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match("/[0-9]/", $new_password)) {
        $error_messages[] = "Password must contain at least one number.";
    }
    if (!preg_match("/[^A-Za-z0-9\s]/", $new_password)) {
        $error_messages[] = "Password must contain at least one special character.";
    }

    if (empty($error_messages)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $account_type = null;

        $stmt_check_member = $conn->prepare("SELECT Member_IC FROM member WHERE Member_Email = ?");
        if ($stmt_check_member) {
            $stmt_check_member->bind_param("s", $email_for_update);
            $stmt_check_member->execute();
            $stmt_check_member->store_result();
            if ($stmt_check_member->num_rows > 0) {
                $account_type = 'member';
            }
            $stmt_check_member->close();
        } else {
            $error_messages[] = "Database error: Could not prepare statement for member email check.";
        }

        if (!$account_type) {
            $stmt_check_staff = $conn->prepare("SELECT Staff_IC FROM staff WHERE Staff_Email = ?");
            if ($stmt_check_staff) {
                $stmt_check_staff->bind_param("s", $email_for_update);
                $stmt_check_staff->execute();
                $stmt_check_staff->store_result();
                if ($stmt_check_staff->num_rows > 0) {
                    $account_type = 'staff';
                }
                $stmt_check_staff->close();
            } else {
                $error_messages[] = "Database error: Could not prepare statement for staff email check.";
            }
        }

        if (!$account_type) {
            $error_messages[] = "Email address not found in our records.";
        }

        if (empty($error_messages) && $account_type) {
            $update_sql = "";
            if ($account_type === 'member') {
                $update_sql = "UPDATE member SET Member_Password = ? WHERE Member_Email = ?";
            } elseif ($account_type === 'staff') {
                $update_sql = "UPDATE staff SET Staff_Password = ? WHERE Staff_Email = ?";
            }

            $stmt_update = $conn->prepare($update_sql);
            if ($stmt_update) {
                $stmt_update->bind_param("ss", $hashed_password, $email_for_update);
                if ($stmt_update->execute()) {
                    $_SESSION['reset_success'] = "Your password has been successfully reset. You can now log in with your new password.";
                    header("Location: login.php"); // Redirect to login page after successful reset
                    exit();
                } else {
                    $error_messages[] = "Failed to update password. Please try again. " . $stmt_update->error;
                }
                $stmt_update->close();
            } else {
                $error_messages[] = "Database error: Could not prepare password update statement.";
            }
        }
    }

    if (!empty($error_messages)) {
        $_SESSION['reset_error'] = implode(" ", $error_messages);
        header("Location: forgotPwd.php"); // Redirect back to reset page with error
        exit();
    }
} else {
    header("Location: forgotPwd.php");
    exit();
}
$conn->close();
?>