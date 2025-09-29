<?php
session_start();
require_once "../config.php";
include "../audit_logs.php";

// Set content type to JSON
header('Content-Type: application/json');


// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get form data
    $acc_id = $_POST['acc_id'] ?? null;
    $personal_id = $_POST['personal_id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $enrollment_status = $_POST['enrollment_status'] ?? '';
    $level_id = $_POST['level_id'] ?? null;
    
    // Personal details
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $address = trim($_POST['address'] ?? '');
    
    // Parent details
    $parent_full_name = trim($_POST['parent_full_name'] ?? '');
    $contact_num = trim($_POST['contact_num'] ?? '');
    $relationship = $_POST['relationship'] ?? null;
    $fb_account = trim($_POST['fb_account'] ?? '');
    
    // Transferee details (if applicable)
    $prev_school = trim($_POST['prev_school'] ?? '');
    $prev_address_school = trim($_POST['prev_address_school'] ?? '');
    
    // Validate required fields
    if (empty($acc_id) || empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
        throw new Exception('Required fields are missing');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if username already exists for another user
    $check_username = $conn->prepare("SELECT acc_id FROM tbl_account WHERE username = ? AND acc_id != ?");
    $check_username->bind_param("si", $username, $acc_id);
    $check_username->execute();
    if ($check_username->get_result()->num_rows > 0) {
        throw new Exception('Username already exists');
    }
    
    // Check if email already exists for another user
    $check_email = $conn->prepare("SELECT acc_id FROM tbl_account WHERE email = ? AND acc_id != ?");
    $check_email->bind_param("si", $email, $acc_id);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }
    
    // Update account table
    $update_account = $conn->prepare("
        UPDATE tbl_account 
        SET username = ?, email = ?, enrollment_status = ?
        WHERE acc_id = ?
    ");
    $update_account->bind_param("sssi", $username, $email, $enrollment_status, $acc_id);
    
    if (!$update_account->execute()) {
        throw new Exception('Failed to update account information');
    }
    
    // Update personal details
    $update_personal = $conn->prepare("
        UPDATE tbl_personal_details 
        SET first_name = ?, middle_name = ?, last_name = ?, 
            date_of_birth = ?, gender = ?, address = ?
        WHERE personal_id = ?
    ");
    
    // Handle null values
    $date_of_birth = empty($date_of_birth) ? null : $date_of_birth;
    $gender = empty($gender) ? null : $gender;
    $middle_name = empty($middle_name) ? null : $middle_name;
    
    $update_personal->bind_param("ssssssi", 
        $first_name, $middle_name, $last_name, 
        $date_of_birth, $gender, $address, $personal_id
    );
    
    if (!$update_personal->execute()) {
        throw new Exception('Failed to update personal details');
    }
    
    // Update or insert parent details
    $check_parent = $conn->prepare("SELECT parent_id FROM tbl_parents_details WHERE child_id = ?");
    $check_parent->bind_param("i", $personal_id);
    $check_parent->execute();
    $parent_exists = $check_parent->get_result()->num_rows > 0;
    
    if ($parent_exists) {
        // Update existing parent record
        $update_parent = $conn->prepare("
            UPDATE tbl_parents_details 
            SET parent_full_name = ?, contact_num = ?, relationship = ?, fb_account = ?
            WHERE child_id = ?
        ");
        
        $fb_account = empty($fb_account) ? 'No provided link' : $fb_account;
        $relationship = empty($relationship) ? null : $relationship;
        
        $update_parent->bind_param("ssssi", 
            $parent_full_name, $contact_num, $relationship, $fb_account, $personal_id
        );
        
        if (!$update_parent->execute()) {
            throw new Exception('Failed to update parent details');
        }
    } else {
        // Insert new parent record if details are provided
        if (!empty($parent_full_name) || !empty($contact_num)) {
            $insert_parent = $conn->prepare("
                INSERT INTO tbl_parents_details (child_id, parent_full_name, contact_num, relationship, fb_account)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $fb_account = empty($fb_account) ? 'No provided link' : $fb_account;
            $relationship = empty($relationship) ? null : $relationship;
            
            $insert_parent->bind_param("issss", 
                $personal_id, $parent_full_name, $contact_num, $relationship, $fb_account
            );
            
            if (!$insert_parent->execute()) {
                throw new Exception('Failed to insert parent details');
            }
        }
    }
    
    // Update level information for new students
    if (!empty($level_id)) {
        $check_new_student = $conn->prepare("SELECT std_id FROM tbl_new_old_students WHERE personal_id = ?");
        $check_new_student->bind_param("i", $personal_id);
        $check_new_student->execute();
        $new_student_exists = $check_new_student->get_result()->num_rows > 0;
        
        if ($new_student_exists) {
            $update_new_level = $conn->prepare("
                UPDATE tbl_new_old_students 
                SET level_id = ?
                WHERE personal_id = ?
            ");
            $update_new_level->bind_param("ii", $level_id, $personal_id);
            
            if (!$update_new_level->execute()) {
                throw new Exception('Failed to update grade level for new student');
            }
        }
        
        // Update level information for transferee students
        $check_transferee = $conn->prepare("SELECT std_id FROM tbl_student_transferee WHERE personal_id = ?");
        $check_transferee->bind_param("i", $personal_id);
        $check_transferee->execute();
        $transferee_exists = $check_transferee->get_result()->num_rows > 0;
        
        if ($transferee_exists) {
            $update_transferee_level = $conn->prepare("
                UPDATE tbl_student_transferee 
                SET level_id = ?, prev_school = ?, prev_address_school = ?
                WHERE personal_id = ?
            ");
            $update_transferee_level->bind_param("issi", 
                $level_id, $prev_school, $prev_address_school, $personal_id
            );
            
            if (!$update_transferee_level->execute()) {
                throw new Exception('Failed to update transferee information');
            }
        }
    }
    
    // Update enrollment status and date if changed to "Enrolled"
    if ($enrollment_status === 'Enrolled') {
        $check_enrolled_date = $conn->prepare("SELECT date_enrolled FROM tbl_account WHERE acc_id = ?");
        $check_enrolled_date->bind_param("i", $acc_id);
        $check_enrolled_date->execute();
        $result = $check_enrolled_date->get_result()->fetch_assoc();
        
        if (empty($result['date_enrolled'])) {
            $update_enrolled_date = $conn->prepare("
                UPDATE tbl_account 
                SET date_enrolled = NOW()
                WHERE acc_id = ?
            ");
            $update_enrolled_date->bind_param("i", $acc_id);
            $update_enrolled_date->execute();
        }
    }
    
    // Log the activity
    $activity = "Updated student information for account ID: " . $acc_id;
    $admin_id = $_SESSION['user_id'] ?? 1; // Get admin ID from session
    
    $log_query = $conn->prepare("INSERT INTO tbl_audit_log (acc_id, activity) VALUES (?, ?)");
    $log_query->bind_param("is", $admin_id, $activity);
    $log_query->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Student information updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Student update error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    
} finally {
    // Close prepared statements
    if (isset($check_username)) $check_username->close();
    if (isset($check_email)) $check_email->close();
    if (isset($update_account)) $update_account->close();
    if (isset($update_personal)) $update_personal->close();
    if (isset($check_parent)) $check_parent->close();
    if (isset($update_parent)) $update_parent->close();
    if (isset($insert_parent)) $insert_parent->close();
    if (isset($check_new_student)) $check_new_student->close();
    if (isset($update_new_level)) $update_new_level->close();
    if (isset($check_transferee)) $check_transferee->close();
    if (isset($update_transferee_level)) $update_transferee_level->close();
    if (isset($check_enrolled_date)) $check_enrolled_date->close();
    if (isset($update_enrolled_date)) $update_enrolled_date->close();
    if (isset($log_query)) $log_query->close();
}
?>