<?php
// update_enrollment_status.php
session_start();
header('Content-Type: application/json');
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

// Start transaction for data consistency
$conn->autocommit(false);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['acc_id']) || !isset($input['enrollment_status'])) {
        throw new Exception('Account ID and enrollment status are required');
    }
    
    $acc_id = intval($input['acc_id']);
    $enrollment_status = $input['enrollment_status'];
    $level_id = isset($input['level_id']) ? intval($input['level_id']) : null;
    
    // Get current enrollment status and level_id before updating
    $current_status_query = "SELECT enrollment_status FROM tbl_account WHERE acc_id = ?";
    $stmt = $conn->prepare($current_status_query);
    $stmt->bind_param("i", $acc_id);
    $stmt->execute();
    $current_result = $stmt->get_result();
    
    if ($current_result->num_rows === 0) {
        throw new Exception('Account not found');
    }
    
    $current_data = $current_result->fetch_assoc();
    $current_enrollment_status = $current_data['enrollment_status'];
    
    // Get current level_id from existing enrollment record if exists
    $current_level_query = "SELECT current_level_id FROM tbl_enrollments WHERE student_id = (
        SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?
    ) ORDER BY enrollment_date DESC LIMIT 1";
    $stmt = $conn->prepare($current_level_query);
    $stmt->bind_param("i", $acc_id);
    $stmt->execute();
    $level_result = $stmt->get_result();
    $current_level_id = null;
    
    if ($level_result->num_rows > 0) {
        $current_level_id = $level_result->fetch_assoc()['current_level_id'];
    }
    
    // Check if trying to enroll in a lower level than current level
    if ($enrollment_status === 'Enrolled' && $level_id && $current_level_id) {
        if ($level_id < $current_level_id) {
            throw new Exception('Cannot enroll student in a lower grade level than their current level');
        }
    }
    
    // Update account enrollment status
    $update_account_query = "UPDATE tbl_account SET enrollment_status = ?";
    $params = [$enrollment_status];
    $types = "s";
    
    // If enrolling, set enrollment date
    if ($enrollment_status === 'Enrolled') {
        $update_account_query .= ", date_enrolled = NOW()";
    }
    
    $update_account_query .= " WHERE acc_id = ?";
    $params[] = $acc_id;
    $types .= "i";
    
    $stmt = $conn->prepare($update_account_query);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update account status');
    }
    
    // If enrolling, handle section assignment
    if ($enrollment_status === 'Enrolled' && $level_id) {
        // Find available section for this level
        $section_id = findAvailableSection($conn, $level_id);
        
        if (!$section_id) {
            throw new Exception('No available sections for the selected grade level');
        }
        
        // Get personal_id for this account
        $personal_query = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
        $stmt = $conn->prepare($personal_query);
        $stmt->bind_param("i", $acc_id);
        $stmt->execute();
        $personal_result = $stmt->get_result();
        
        if ($personal_result->num_rows === 0) {
            throw new Exception('Personal details not found for this student');
        }
        
        $personal_id = $personal_result->fetch_assoc()['personal_id'];
        
        // Check if student exists in transferee table
        $transferee_check = "SELECT std_id FROM tbl_student_transferee WHERE personal_id = ?";
        $stmt = $conn->prepare($transferee_check);
        $stmt->bind_param("i", $personal_id);
        $stmt->execute();
        $transferee_result = $stmt->get_result();
        
        if ($transferee_result->num_rows > 0) {
            // Update transferee record
            $update_transferee = "UPDATE tbl_student_transferee SET level_id = ?, section_id = ? WHERE personal_id = ?";
            $stmt = $conn->prepare($update_transferee);
            $stmt->bind_param("iii", $level_id, $section_id, $personal_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update transferee section assignment');
            }
        } else {
            // Check if exists in new students table
            $new_student_check = "SELECT std_id FROM tbl_new_old_students WHERE personal_id = ?";
            $stmt = $conn->prepare($new_student_check);
            $stmt->bind_param("i", $personal_id);
            $stmt->execute();
            $new_result = $stmt->get_result();
            
            if ($new_result->num_rows > 0) {
                // Update new student record
                $update_new = "UPDATE tbl_new_old_students SET level_id = ?, section_id = ? WHERE personal_id = ?";
                $stmt = $conn->prepare($update_new);
                $stmt->bind_param("iii", $level_id, $section_id, $personal_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update new student section assignment');
                }
            } else {
                // Create new student record (assuming they have uploaded required documents)
                $insert_new = "INSERT INTO tbl_new_old_students (personal_id, level_id, section_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($insert_new);
                $stmt->bind_param("iii", $personal_id, $level_id, $section_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create student enrollment record');
                }
            }
        }

        // Get section name for response
        $section_query = "SELECT sec_name FROM tbl_sections WHERE sec_id = ?";
        $stmt = $conn->prepare($section_query);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        $section_name = $stmt->get_result()->fetch_assoc()['sec_name'];
        
        $response_message = "Student enrolled successfully and assigned to section: " . $section_name;
    } else {
        $response_message = "Student status updated successfully";
    }

    // Check if we need to create a new enrollment record
    if ($enrollment_status == 'Enrolled') {
        // Only insert if status is changing to 'Enrolled' OR level_id is different
        if ($current_enrollment_status !== 'Enrolled' || $current_level_id !== $level_id) {
            //get total fee for the level
            $fee_query = "SELECT SUM(registration_fee + miscellaneous_fee + books_fee + tuition_fee) as total_fee FROM tbl_fees WHERE fee_id = ?";
            $stmt = $conn->prepare($fee_query);
            $stmt->bind_param("i", $level_id);
            $stmt->execute();
            $fee_result = $stmt->get_result();
            $total_fee = $fee_result->fetch_assoc()['total_fee'] ?? 0;
            
            date_default_timezone_set('Asia/Manila');
            $current_date = date('Y-m-d');
            // insert into tbl_enrollments
            $enrollment_insert = "INSERT INTO tbl_enrollments (student_id, school_year, enrollment_date, status, total_fee, current_level_id) VALUES (?, ?, ?, ?, ?, ?)";
            $school_year = date("Y") . "-" . (date("Y") + 1);
            $status = 'Pending';
            $stmt = $conn->prepare($enrollment_insert);
            $stmt->bind_param("isssdi", $personal_id, $school_year, $current_date, $status, $total_fee, $level_id);

            if (!$stmt->execute()) {
                throw new Exception('Failed to create enrollment record');
            }
        }
    }
    
    // Log the activity
    if (isset($_SESSION['acc_id'])) {
        $log_query = "INSERT INTO tbl_audit_log (acc_id, activity) VALUES (?, ?)";
        $activity = "Updated student enrollment status for account ID: " . $acc_id;
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("is", $_SESSION['acc_id'], $activity);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $response_message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    $conn->autocommit(true);
    $conn->close();
}

function findAvailableSection($conn, $level_id) {
    // Get all sections for this level
    $sections_query = "SELECT sec_id, sec_capacity FROM tbl_sections WHERE level_id = ? ORDER BY sec_id";
    $stmt = $conn->prepare($sections_query);
    $stmt->bind_param("i", $level_id);
    $stmt->execute();
    $sections_result = $stmt->get_result();
    
    while ($section = $sections_result->fetch_assoc()) {
        $sec_id = $section['sec_id'];
        $capacity = $section['sec_capacity'];
        
        // Count current enrollment in this section
        // Count from new students table
        $new_count_query = "
            SELECT COUNT(*) as count 
            FROM tbl_new_old_students nos
            INNER JOIN tbl_personal_details pd ON nos.personal_id = pd.personal_id
            INNER JOIN tbl_account acc ON pd.acc_id = acc.acc_id
            WHERE nos.section_id = ? 
            AND acc.enrollment_status = 'Enrolled'
            AND nos.level_id = ?
        ";
        
        $stmt_new = $conn->prepare($new_count_query);
        $stmt_new->bind_param("ii", $sec_id, $level_id);
        $stmt_new->execute();
        $new_count = $stmt_new->get_result()->fetch_assoc()['count'];
        
        // Count from transferee table
        $transferee_count_query = "
            SELECT COUNT(*) as count 
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account acc ON pd.acc_id = acc.acc_id
            WHERE st.section_id = ? 
            AND acc.enrollment_status = 'Enrolled'
            AND st.level_id = ?
        ";
        
        $stmt_transferee = $conn->prepare($transferee_count_query);
        $stmt_transferee->bind_param("ii", $sec_id, $level_id);
        $stmt_transferee->execute();
        $transferee_count = $stmt_transferee->get_result()->fetch_assoc()['count'];
        
        $total_enrolled = $new_count + $transferee_count;
        
        // If section has space, return it
        if ($total_enrolled < $capacity) {
            return $sec_id;
        }
    }
    
    // No available sections found
    return null;
}
?>