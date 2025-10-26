<?php
session_start();
require_once "../backend/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get current profile picture
    $get_current = "SELECT profile_picture FROM tbl_personal_details WHERE acc_id = ?";
    $stmt = $conn->prepare($get_current);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_data = $result->fetch_assoc();
    
    // Update database to remove profile picture
    $update_query = "UPDATE tbl_personal_details SET profile_picture = NULL WHERE acc_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Delete file if exists
        if ($current_data['profile_picture']) {
            $file_path = '../assets/profiles/' . $current_data['profile_picture'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove profile picture']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>