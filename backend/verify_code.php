<?php
// backend/verify_code.php

require_once "./config.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    
    // Validate input
    if (empty($email) || empty($code)) {
        echo json_encode([
            "success" => false, 
            "message" => "Email and verification code are required."
        ]);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid email format."
        ]);
        exit;
    }
    
    // Validate code format (6 digits)
    if (!preg_match('/^\d{6}$/', $code)) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid verification code format."
        ]);
        exit;
    }
    
    // Check verification record
    $query = "SELECT * FROM tbl_email_verification WHERE email = ? AND verification_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid verification code or email."
        ]);
        exit;
    }
    
    $verificationData = $result->fetch_assoc();
    
    // Check if code has expired
    $currentTime = date('Y-m-d H:i:s');
    if ($currentTime > $verificationData['expires_at']) {
        // Clean up expired record
        $deleteQuery = "DELETE FROM tbl_email_verification WHERE email = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        echo json_encode([
            "success" => false, 
            "message" => "Verification code has expired. Please request a new one."
        ]);
        exit;
    }
    
    // Check if already verified
    if ($verificationData['is_verified'] == 1) {
        echo json_encode([
            "success" => false, 
            "message" => "Email has already been verified."
        ]);
        exit;
    }
    
    // Mark as verified
    $updateQuery = "UPDATE tbl_email_verification SET is_verified = 1, verified_at = NOW() WHERE email = ? AND verification_code = ?";
    $stmt = $conn->prepare($updateQuery);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode([
            "success" => false, 
            "message" => "Database error occurred."
        ]);
        exit;
    }
    
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Failed to verify email. Please try again."
        ]);
        exit;
    }
    
    echo json_encode([
        "success" => true, 
        "message" => "Email verified successfully!"
    ]);
    
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid request method."
    ]);
}
?>