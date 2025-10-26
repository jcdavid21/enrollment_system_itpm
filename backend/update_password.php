<?php 

require_once "./config.php";
header('Content-Type: application/json');

session_start();

if(!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if(isset($_POST["current_password"]) && isset($_POST["new_password"]) && isset($_POST["confirm_password"])) {
    $user_id = $_SESSION["user_id"];
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    if($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
        exit();
    }

    if(strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']);
        exit();
    }

    // Fetch current password from database
    $query = "SELECT password FROM tbl_account WHERE acc_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit();
    }

    $user = $result->fetch_assoc();

    if(!password_verify($current_password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    // Hash new password
    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password in database
    $update_query = "UPDATE tbl_account SET password = ? WHERE acc_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $hashed_new_password, $user_id);

    if($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

?>