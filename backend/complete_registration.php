<?php
// backend/complete_registration.php

require_once "./config.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email) && !isset($_POST['email'])) {
        echo json_encode([
            "success" => false, 
            "message" => "Email is required."
        ]);
        exit;
    }
    
    // Get verified registration data
    $query = "SELECT * FROM tbl_email_verification WHERE email = ? AND is_verified = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Email verification not found or not verified."
        ]);
        exit;
    }
    
    $verificationData = $result->fetch_assoc();
    $tempData = json_decode($verificationData['temp_data'], true);

    $address = $tempData['address'] ?? '';
    $dateOfBirth = $tempData['dateOfBirth'] ?? '';
    $firstName = $tempData['firstName'] ?? '';
    $gender = $tempData['gender'] ?? '';
    $lastName = $tempData['lastName'] ?? '';
    $middleName = $tempData['middleName'] ?? '';
    $parentContact = $tempData['parentContact'] ?? '';
    $parentName = $tempData['parentName'] ?? '';
    $relationship = $tempData['relationship'] ?? '';
    $username = $tempData['username'] ?? '';
    $uploaded_file = $tempData['uploaded_file'] ?? '';
    $facebook = $tempData['facebook'] ?? '';
    $registerPassword = $tempData['registerPassword'] ?? '';
    
    if (!$tempData) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid registration data."
        ]);
        exit;
    }
    
    if (strlen($registerPassword) < 8) {
        echo json_encode([
            "success" => false, 
            "message" => "Password must be at least 8 characters long."
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false, 
            "message" => "Invalid email format."
        ]);
        exit;
    }
    
    // Check for duplicates one more time
    $checkUserQuery = "SELECT * FROM tbl_account WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkUserQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Username or email already exists."
        ]);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Hash password
        $hashedPassword = password_hash($registerPassword, PASSWORD_BCRYPT);
        $role = "Student";

        date_default_timezone_set('Asia/Manila');
        $current_date_time = date('Y-m-d H:i:s');

        
        // Insert account
        $insert_tbl_account = "INSERT INTO tbl_account (username, email, password, role, enrollment_status, date_registered) VALUES (?, ?, ?, ?, 'Newly Registered', ?)";
        $stmt = $conn->prepare($insert_tbl_account);
        $stmt->bind_param("sssss", $username, $email, $hashedPassword, $role, $current_date_time);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to create account."
            ]);
            exit;
        }
        
        $account_id = $stmt->insert_id;
        
        // Insert personal details
        $insert_query_details = "INSERT INTO tbl_personal_details (acc_id, first_name, middle_name, last_name, date_of_birth, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_personal = $conn->prepare($insert_query_details);
        $stmt_personal->bind_param("issssss", 
            $account_id, 
            $firstName, 
            $middleName, 
            $lastName, 
            $dateOfBirth, 
            $gender, 
            $address
        );
        $stmt_personal->execute();
        
        if ($stmt_personal->affected_rows === 0) {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to save personal details."
            ]);
            exit;
        }
        
        $personal_id = $stmt_personal->insert_id;
        
        // Insert parent details with image basename in parent_temp_id
        $insert_query_parent = "INSERT INTO tbl_parents_details (child_id, parent_full_name, contact_num, relationship, parent_temp_id, fb_account) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_parent = $conn->prepare($insert_query_parent);
        $stmt_parent->bind_param("isssss", 
            $personal_id, 
            $parentName, 
            $parentContact, 
            $relationship,
            $uploaded_file,
            $facebook
        );
        $stmt_parent->execute();
        
        if ($stmt_parent->affected_rows === 0) {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to save parent details."
            ]);
            exit;
        }
        
        // Clean up verification record
        $deleteVerification = "DELETE FROM tbl_email_verification WHERE email = ?";
        $stmt = $conn->prepare($deleteVerification);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            "success" => true, 
            "message" => "Registration completed successfully! You can now login with your credentials."
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        
        echo json_encode([
            "success" => false, 
            "message" => "Registration failed. Please try again."
        ]);
    }
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Invalid request method."
    ]);
}
?>