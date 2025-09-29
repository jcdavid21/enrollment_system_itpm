<?php 
// backend/enrollment_form.php
session_start();
require_once "config.php";
require_once "audit_logs.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in."]);
    exit();
}

header('Content-Type: application/json');

// File validation function
function validateFileUpload($file, $allowedTypes, $maxSize, $fieldName) {
    if (!$file || !isset($file['error'])) {
        return ['success' => false, 'message' => "$fieldName is required."];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File is too large (server limit).',
            UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit).',
            UPLOAD_ERR_PARTIAL => 'File upload was interrupted.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
        ];
        
        $message = $errorMessages[$file['error']] ?? 'Unknown upload error.';
        return ['success' => false, 'message' => "$fieldName: $message"];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => "$fieldName has invalid file type. Only " . implode(', ', $allowedTypes) . " are allowed."];
    }
    
    if ($file['size'] > $maxSize) {
        $maxSizeMB = round($maxSize / (1024 * 1024), 1);
        return ['success' => false, 'message' => "$fieldName is too large. Maximum size is {$maxSizeMB}MB."];
    }
    
    return ['success' => true];
}

// Check if required POST fields are present
if (isset($_POST["address"]) && isset($_POST["contact_num"]) && isset($_POST["date_of_birth"]) && 
    isset($_POST["first_name"]) && isset($_POST["last_name"]) && isset($_POST["grade_level"]) && 
    isset($_POST["parent_full_name"])) {
    
    // Sanitize input data
    $address = trim($_POST["address"]);
    $parent_contact_num = trim($_POST["contact_num"]);
    $date_of_birth = trim($_POST["date_of_birth"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $grade_level = intval($_POST["grade_level"]); // This is fee_id
    $parent_full_name = trim($_POST["parent_full_name"]);
    $relationship = trim($_POST["relationship"] ?? '');
    $middle_name = trim($_POST["middle_name"] ?? '');
    $gender = trim($_POST["gender"] ?? '');
    $fb_account = trim($_POST["fb_account"] ?? '');
    $prev_address_school = trim($_POST["prev_address_school"] ?? '');
    $prev_school = trim($_POST["prev_school"] ?? '');
    $acc_id = $_SESSION['user_id'];
    $actions = "";

    // Validate required fields
    $required_fields = [
        'Address' => $address,
        'Contact Number' => $parent_contact_num,
        'Date of Birth' => $date_of_birth,
        'First Name' => $first_name,
        'Last Name' => $last_name,
        'Grade Level' => $grade_level,
        'Parent Full Name' => $parent_full_name,
        'Gender' => $gender,
        'Relationship' => $relationship
    ];

    foreach ($required_fields as $field_name => $field_value) {
        if (empty($field_value)) {
            echo json_encode(["status" => "error", "message" => "$field_name is required."]);
            exit();
        }
    }

    // Validate date of birth
    $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
    if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
        echo json_encode(["status" => "error", "message" => "Invalid date of birth format."]);
        exit();
    }

    // Check if date is not in the future
    if ($dob > new DateTime()) {
        echo json_encode(["status" => "error", "message" => "Date of birth cannot be in the future."]);
        exit();
    }

    // Get actual grade level from fee_id
    $get_level_query = "SELECT level FROM tbl_fees WHERE fee_id = ?";
    $level_stmt = $conn->prepare($get_level_query);
    if (!$level_stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    
    $level_stmt->bind_param("i", $grade_level);
    $level_stmt->execute();
    $level_result = $level_stmt->get_result();
    
    if ($level_result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid grade level selected."]);
        exit();
    }
    
    $level_row = $level_result->fetch_assoc();
    $actual_level = $level_row['level'];
    $level_stmt->close();

    // Fetch personal_id using acc_id
    $fetch_personal_id = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
    $stmt = $conn->prepare($fetch_personal_id);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $acc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $personal_id = $row['personal_id'];
    } else {
        echo json_encode(["status" => "error", "message" => "Personal details not found."]);
        exit();
    }
    $stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if this is a transferee (not Kinder 1)
        if ($actual_level !== 'Kinder 1') {
            // Validate transferee required fields
            if (empty($prev_address_school)) {
                throw new Exception("Previous School Address is required for transferee students.");
            }
            if (empty($prev_school)) {
                throw new Exception("Previous School Name is required for transferee students.");
            }

            // Validate file uploads for transferee
            if (!isset($_FILES['prev_id_school_file']) || !isset($_FILES['prev_school_card'])) {
                throw new Exception("Required documents are missing for transferee students.");
            }

            $prev_id_school_file = $_FILES['prev_id_school_file'];
            $prev_school_card = $_FILES['prev_school_card'];

            // Validate school ID file
            $id_validation = validateFileUpload(
                $prev_id_school_file, 
                ['image/jpeg', 'image/png', 'application/pdf'], 
                5 * 1024 * 1024, 
                'School ID/Certificate'
            );
            if (!$id_validation['success']) {
                throw new Exception($id_validation['message']);
            }

            // Validate report card file
            $card_validation = validateFileUpload(
                $prev_school_card, 
                ['image/jpeg', 'image/png', 'application/pdf'], 
                5 * 1024 * 1024, 
                'Report Card'
            );
            if (!$card_validation['success']) {
                throw new Exception($card_validation['message']);
            }

            // Create upload directory if it doesn't exist
            $uploadDir = '../assets/transferee/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }

            // Upload school ID file
            $filExtension_id = pathinfo($prev_id_school_file['name'], PATHINFO_EXTENSION);
            $newFileName_id = uniqid('id_') . '.' . strtolower($filExtension_id);
            $uploadPath_id = $uploadDir . $newFileName_id;

            if (!move_uploaded_file($prev_id_school_file['tmp_name'], $uploadPath_id)) {
                throw new Exception("Failed to upload School ID document.");
            }

            // Upload report card file
            $filExtension = pathinfo($prev_school_card['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('school_card_') . '.' . strtolower($filExtension);
            $uploadPath = $uploadDir . $newFileName;

            if (!move_uploaded_file($prev_school_card['tmp_name'], $uploadPath)) {
                // Remove the first uploaded file if second upload fails
                if (file_exists($uploadPath_id)) {
                    unlink($uploadPath_id);
                }
                throw new Exception("Failed to upload Report Card.");
            }

            // Insert into tbl_transferee_details
            $insert_transferee = "INSERT INTO tbl_student_transferee (personal_id, prev_school, prev_address_school, prev_id_school_file, prev_school_card, level_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_transferee);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("issssi", $personal_id, $prev_school, $prev_address_school, $newFileName_id, $newFileName, $grade_level);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to save transferee details: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to insert transferee details.");
            }
            $stmt->close();

            $actions .= "Submitted transferee details. ";

        } else {
            // New student (Kinder 1) logic
            if (!isset($_FILES['student_photo']) || !isset($_FILES['parent_id'])) {
                throw new Exception("Required documents are missing for new students.");
            }

            $student_photo = $_FILES['student_photo'];
            $parent_id = $_FILES['parent_id'];

            // Validate student photo
            $photo_validation = validateFileUpload(
                $student_photo, 
                ['image/jpeg', 'image/png'], 
                2 * 1024 * 1024, 
                'Student Photo'
            );
            if (!$photo_validation['success']) {
                throw new Exception($photo_validation['message']);
            }

            // Validate parent ID
            $parent_id_validation = validateFileUpload(
                $parent_id, 
                ['image/jpeg', 'image/png'], 
                3 * 1024 * 1024, 
                'Parent ID'
            );
            if (!$parent_id_validation['success']) {
                throw new Exception($parent_id_validation['message']);
            }

            // Create upload directory if it doesn't exist
            $uploadDir = '../assets/new_student/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }

            // Upload student photo
            $filExtension_photo = pathinfo($student_photo['name'], PATHINFO_EXTENSION);
            $newFileName_photo = uniqid('photo_') . '.' . strtolower($filExtension_photo);
            $uploadPath_photo = $uploadDir . $newFileName_photo;

            if (!move_uploaded_file($student_photo['tmp_name'], $uploadPath_photo)) {
                throw new Exception("Failed to upload Student Photo.");
            }

            // Upload parent ID
            $filExtension_id = pathinfo($parent_id['name'], PATHINFO_EXTENSION);
            $newFileName_id = uniqid('id_') . '.' . strtolower($filExtension_id);
            $uploadPath_id = $uploadDir . $newFileName_id;

            if (!move_uploaded_file($parent_id['tmp_name'], $uploadPath_id)) {
                // Remove the first uploaded file if second upload fails
                if (file_exists($uploadPath_photo)) {
                    unlink($uploadPath_photo);
                }
                throw new Exception("Failed to upload Parent ID.");
            }

            // Insert into tbl_new_old_student
            $insert_new_student = "INSERT INTO tbl_new_old_students (personal_id, level_id, student_image, parents_valid_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_new_student);
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("iiss", $personal_id, $grade_level, $newFileName_photo, $newFileName_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to save new student details: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception("Failed to insert new student details.");
            }
            $stmt->close();

            $actions .= "Submitted new student details. ";
        }

        // Update tbl_personal_details
        $update_personal = "UPDATE tbl_personal_details SET first_name = ?, middle_name = ?, last_name = ?, date_of_birth = ?, gender = ?, address = ? WHERE personal_id = ?";
        $stmt = $conn->prepare($update_personal);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $date_of_birth, $gender, $address, $personal_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update personal details: " . $stmt->error);
        }
        $stmt->close();

        // Update tbl_parents_details
        $update_parent = "UPDATE tbl_parents_details SET parent_full_name = ?, contact_num = ?, relationship = ?, fb_account = ? WHERE child_id = ?";
        $stmt = $conn->prepare($update_parent);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ssssi", $parent_full_name, $parent_contact_num, $relationship, $fb_account, $personal_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update parent details: " . $stmt->error);
        }
        $stmt->close();

        // Update tbl_account enrollment status
        $update_account = "UPDATE tbl_account SET enrollment_status = 'Pending' WHERE acc_id = ?";
        $stmt = $conn->prepare($update_account);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $acc_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update account status: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Log the action
        $actions .= "Updated personal details and parent information. Changed enrollment status to Pending.";
        logAction($conn, $acc_id, $actions);

        echo json_encode([
            "status" => "success", 
            "message" => "Enrollment submitted successfully! Your application is now pending review."
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Clean up uploaded files if they exist
        if (isset($uploadPath_photo) && file_exists($uploadPath_photo)) {
            unlink($uploadPath_photo);
        }
        if (isset($uploadPath_id) && file_exists($uploadPath_id)) {
            unlink($uploadPath_id);
        }
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        echo json_encode([
            "status" => "error", 
            "message" => $e->getMessage()
        ]);
    }

} else {
    echo json_encode([
        "status" => "error", 
        "message" => "All required fields must be filled out."
    ]);
}

exit();
?>