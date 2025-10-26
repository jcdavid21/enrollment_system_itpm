<?php
session_start();
require_once "../backend/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['parent_id'])) {
    $file = $_FILES['parent_id'];
    
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
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB.']);
        exit();
    }
    
    // Get personal_id
    $get_personal_id = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
    $stmt = $conn->prepare($get_personal_id);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $personal_data = $result->fetch_assoc();
    $personal_id = $personal_data['personal_id'];
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../assets/enrollment_img/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'parent_id_' . $personal_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Get current parent_temp_id to delete old one
    $get_current = "SELECT parent_temp_id FROM tbl_parents_details WHERE child_id = ?";
    $stmt = $conn->prepare($get_current);
    $stmt->bind_param("i", $personal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_data = $result->fetch_assoc();
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Update database
        $update_query = "UPDATE tbl_parents_details SET parent_temp_id = ? WHERE child_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_filename, $personal_id);
        
        if ($stmt->execute()) {
            // Delete old ID if exists
            if ($current_data && $current_data['parent_temp_id'] && 
                $current_data['parent_temp_id'] != 'No image uploaded yet' && 
                file_exists($upload_dir . $current_data['parent_temp_id'])) {
                unlink($upload_dir . $current_data['parent_temp_id']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Parent ID uploaded successfully!',
                'image_url' => $upload_dir . $new_filename
            ]);
        } else {
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