<?php
session_start();
require_once "../backend/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error']);
        exit();
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit();
    }
    
    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../assets/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Get current profile picture to delete old one
    $get_current = "SELECT profile_picture FROM tbl_personal_details WHERE acc_id = ?";
    $stmt = $conn->prepare($get_current);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_data = $result->fetch_assoc();
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update database
        $update_query = "UPDATE tbl_personal_details SET profile_picture = ? WHERE acc_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_filename, $user_id);
        
        if ($stmt->execute()) {
            // Delete old profile picture if exists
            if ($current_data['profile_picture'] && file_exists($upload_dir . $current_data['profile_picture'])) {
                unlink($upload_dir . $current_data['profile_picture']);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile picture updated successfully!',
                'image_url' => '../assets/profiles/' . $new_filename
            ]);
        } else {
            // Delete uploaded file if database update fails
            unlink($upload_path);
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>