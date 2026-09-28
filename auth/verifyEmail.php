<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['member_email']) && !empty($_POST['member_email'])) {
        $input_email = trim($_POST['member_email']);

        $input_email = filter_var($input_email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
            echo json_encode($response);
            $conn->close();
            exit();
        }

        $email_found = false;

        $stmt_member = $conn->prepare("SELECT Member_IC FROM member WHERE Member_Email = ?");
        if ($stmt_member) {
            $stmt_member->bind_param("s", $input_email);
            $stmt_member->execute();
            $stmt_member->store_result();

            if ($stmt_member->num_rows > 0) {
                $email_found = true;
                $response['account_type'] = 'member';
            }
            $stmt_member->close();
        } else {
            $response['message'] = 'Database error (member check): Could not prepare statement.';
            echo json_encode($response);
            $conn->close();
            exit();
        }

        if (!$email_found) {
            $stmt_staff = $conn->prepare("SELECT Staff_IC FROM staff WHERE Staff_Email = ?");
            if ($stmt_staff) {
                $stmt_staff->bind_param("s", $input_email);
                $stmt_staff->execute();
                $stmt_staff->store_result();

                if ($stmt_staff->num_rows > 0) {
                    $email_found = true;
                    $response['account_type'] = 'staff';
                }
                $stmt_staff->close();
            } else {
                $response['message'] = 'Database error (staff check): Could not prepare statement.';
                echo json_encode($response);
                $conn->close();
                exit();
            }
        }

        if ($email_found) {
            $response['success'] = true;
            $response['message'] = 'Email found.';
        } else {
            $response['message'] = 'Email not registered. Please enter a valid email.';
        }

    } else {
        $response['message'] = 'Email address is required.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
$conn->close();
?>