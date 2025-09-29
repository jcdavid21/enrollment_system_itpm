<?php
session_start();
header('Content-Type: application/json');
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['student_id', 'current_section_id', 'new_section_id', 'student_type'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $student_id = (int)$_POST['student_id'];
    $current_section_id = (int)$_POST['current_section_id'];
    $new_section_id = (int)$_POST['new_section_id'];
    $student_type = $_POST['student_type'];
    $transfer_reason = !empty($_POST['transfer_reason']) ? trim($_POST['transfer_reason']) : 'Section transfer';
    
    // Validate that sections are different
    if ($current_section_id === $new_section_id) {
        throw new Exception('Cannot transfer student to the same section');
    }
    
    // Get student's personal_id and verify enrollment
    $student_query = "
        SELECT pd.personal_id, a.enrollment_status
        FROM tbl_account a
        INNER JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        WHERE a.acc_id = ?
    ";
    
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        throw new Exception('Student not found');
    }
    
    $student_data = $student_result->fetch_assoc();
    $personal_id = $student_data['personal_id'];
    
    if ($student_data['enrollment_status'] !== 'Enrolled') {
        throw new Exception('Student is not enrolled');
    }
    
    // Verify both sections exist and get their grade levels
    $sections_query = "
        SELECT sec_id, level_id, sec_capacity,
               COALESCE(enrolled_new.count, 0) + COALESCE(enrolled_transferee.count, 0) as enrolled_count
        FROM tbl_sections s
        LEFT JOIN (
            SELECT ns.section_id, COUNT(*) as count
            FROM tbl_new_old_students ns
            INNER JOIN tbl_personal_details pd ON ns.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY ns.section_id
        ) enrolled_new ON s.sec_id = enrolled_new.section_id
        LEFT JOIN (
            SELECT st.section_id, COUNT(*) as count
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY st.section_id
        ) enrolled_transferee ON s.sec_id = enrolled_transferee.section_id
        WHERE s.sec_id IN (?, ?)
    ";
    
    $sections_stmt = $conn->prepare($sections_query);
    $sections_stmt->bind_param("ii", $current_section_id, $new_section_id);
    $sections_stmt->execute();
    $sections_result = $sections_stmt->get_result();
    
    if ($sections_result->num_rows !== 2) {
        throw new Exception('One or both sections not found');
    }
    
    $sections_data = [];
    while ($row = $sections_result->fetch_assoc()) {
        $sections_data[$row['sec_id']] = $row;
    }
    
    // Verify sections are in the same grade level
    if ($sections_data[$current_section_id]['level_id'] !== $sections_data[$new_section_id]['level_id']) {
        throw new Exception('Cannot transfer student between different grade levels');
    }
    
    // Check if new section has available capacity
    $new_section = $sections_data[$new_section_id];
    if ($new_section['enrolled_count'] >= $new_section['sec_capacity']) {
        throw new Exception('New section is at full capacity');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update section based on student type
        if ($student_type === 'New Student') {
            // Update tbl_new_old_students
            $update_stmt = $conn->prepare("UPDATE tbl_new_old_students SET section_id = ? WHERE personal_id = ?");
            $update_stmt->bind_param("ii", $new_section_id, $personal_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception('Failed to update new student section');
            }
            
        } elseif ($student_type === 'Transferee') {
            // Update tbl_student_transferee
            $update_stmt = $conn->prepare("UPDATE tbl_student_transferee SET section_id = ? WHERE personal_id = ?");
            $update_stmt->bind_param("ii", $new_section_id, $personal_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception('Failed to update transferee student section');
            }
            
        } else {
            throw new Exception('Invalid student type');
        }
        
        // Log the transfer action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Transferred student (Acc ID: $student_id) from section ID $current_section_id to section ID $new_section_id. Reason: $transfer_reason";
        logAction($conn, $admin_id, $action);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student transferred successfully'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>