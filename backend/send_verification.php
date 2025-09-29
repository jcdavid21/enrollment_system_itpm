<?php
// backend/send_verification.php

require_once "./config.php";
require_once "./mail_config.php";
header('Content-Type: application/json');

// Validation function
function validateFormData($data, $files)
{
    $errors = [];

    // Required fields validation
    $required_fields = [
        'firstName' => 'First Name',
        'lastName' => 'Last Name',
        'dateOfBirth' => 'Date of Birth',
        'gender' => 'Gender',
        'parentName' => 'Parent Name',
        'parentContact' => 'Parent Contact',
        'relationship' => 'Relationship',
        'address' => 'Address',
        'username' => 'Username',
        'email' => 'Email',
        'registerPassword' => 'Password',
        'confirmPassword' => 'Confirm Password'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($data[$field])) {
            $errors[] = "$label is required.";
        }
    }

    // Password validation
    if (!empty($data['registerPassword']) && !empty($data['confirmPassword'])) {
        if ($data['registerPassword'] !== $data['confirmPassword']) {
            $errors[] = "Passwords do not match.";
        }
        if (strlen($data['registerPassword']) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
    }

    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Username validation
    if (!empty($data['username']) && !preg_match("/^[a-zA-Z0-9_]+$/", $data['username'])) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    // Date validation
    if (!empty($data['dateOfBirth'])) {
        $currentDate = date('Y-m-d');
        if ($data['dateOfBirth'] >= $currentDate) {
            $errors[] = "Date of birth must be in the past.";
        }
    }

    // File validation
    if (empty($files['prev_id_school_file']['name'])) {
        $errors[] = "Valid ID document is required.";
    } else {
        $file = $files['prev_id_school_file'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and PDF files are allowed.";
        }

        if ($file['size'] > $maxSize) {
            $errors[] = "File size must be less than 5MB.";
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error occurred.";
        }
    }

    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $validation_errors = validateFormData($_POST, $_FILES);

    if (!empty($validation_errors)) {
        echo json_encode([
            "success" => false,
            "message" => implode(' ', $validation_errors)
        ]);
        exit;
    }

    $email = $_POST['email'];
    $username = $_POST['username'];
    $firstName = $_POST['firstName'];

    // Check if user already exists
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

    // Handle file upload to ../assets/enrollment_img/
    $uploadDir = '../assets/enrollment_img/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['prev_id_school_file'];
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to upload file. Please try again."
        ]);
        exit;
    }

    // Generate verification code
    $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Store temporary data with uploaded filename (basename only)
    $tempData = $_POST;
    $tempData['uploaded_file'] = $fileName; // Store only basename
    $tempData['facebook'] = $_POST['facebook']; // Explicitly include Facebook link
    unset($tempData['confirmPassword']); // Remove confirm password from storage
    $tempDataJson = json_encode($tempData);

    // Delete existing verification records for this email
    $deleteQuery = "DELETE FROM tbl_email_verification WHERE email = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Insert verification record
    $insertQuery = "INSERT INTO tbl_email_verification (email, verification_code, expires_at, temp_data) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssss", $email, $verificationCode, $expiresAt, $tempDataJson);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Failed to store verification data. Please try again."
        ]);
        exit;
    }

    // Send verification email
    if (sendVerificationEmail($email, $firstName, $verificationCode)) {
        echo json_encode([
            "success" => true,
            "message" => "Verification code sent to your email."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to send verification email. Please try again."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
}
