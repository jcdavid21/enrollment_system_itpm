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
    $required_fields = ['sec_id', 'sec_name', 'level_id', 'sec_capacity'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $sec_id = (int)$_POST['sec_id'];
    $sec_name = trim($_POST['sec_name']);
    $level_id = (int)$_POST['level_id'];
    $sec_capacity = (int)$_POST['sec_capacity'];
    $sec_adviser = !empty($_POST['sec_adviser']) ? trim($_POST['sec_adviser']) : null;
    
    // Validate data
    if (strlen($sec_name) < 3) {
        throw new Exception('Section name must be at least 3 characters long');
    }
    
    if ($sec_capacity < 1 || $sec_capacity > 50) {
        throw new Exception('Section capacity must be between 1 and 50');
    }
    
    // Check if section exists
    $section_check = $conn->prepare("SELECT sec_id FROM tbl_sections WHERE sec_id = ?");
    $section_check->bind_param("i", $sec_id);
    $section_check->execute();
    if ($section_check->get_result()->num_rows === 0) {
        throw new Exception('Section not found');
    }
    
    // Check if level_id exists
    $level_check = $conn->prepare("SELECT fee_id FROM tbl_fees WHERE fee_id = ?");
    $level_check->bind_param("i", $level_id);
    $level_check->execute();
    if ($level_check->get_result()->num_rows === 0) {
        throw new Exception('Invalid grade level selected');
    }
    
    // Check current enrolled students count
    $enrolled_count_query = "
        SELECT 
            COALESCE(new_students.count, 0) + COALESCE(transferee_students.count, 0) as total_enrolled
        FROM tbl_sections s
        LEFT JOIN (
            SELECT ns.section_id, COUNT(*) as count
            FROM tbl_new_old_students ns
            INNER JOIN tbl_personal_details pd ON ns.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled' AND ns.section_id = ?
        ) new_students ON s.sec_id = new_students.section_id
        LEFT JOIN (
            SELECT st.section_id, COUNT(*) as count
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled' AND st.section_id = ?
        ) transferee_students ON s.sec_id = transferee_students.section_id
        WHERE s.sec_id = ?
    ";
    
    $enrolled_stmt = $conn->prepare($enrolled_count_query);
    $enrolled_stmt->bind_param("iii", $sec_id, $sec_id, $sec_id);
    $enrolled_stmt->execute();
    $enrolled_result = $enrolled_stmt->get_result();
    $enrolled_data = $enrolled_result->fetch_assoc();
    $current_enrolled = (int)$enrolled_data['total_enrolled'];
    
    // Check if new capacity is sufficient for current enrolled students
    if ($sec_capacity < $current_enrolled) {
        throw new Exception("Cannot reduce capacity below current enrolled students count ($current_enrolled)");
    }
    
    // Check if section name already exists for this level (excluding current section)
    $name_check = $conn->prepare("SELECT sec_id FROM tbl_sections WHERE sec_name = ? AND level_id = ? AND sec_id != ?");
    $name_check->bind_param("sii", $sec_name, $level_id, $sec_id);
    $name_check->execute();
    if ($name_check->get_result()->num_rows > 0) {
        throw new Exception('Section name already exists for this grade level');
    }
    
    // Update section
    $stmt = $conn->prepare("UPDATE tbl_sections SET sec_name = ?, level_id = ?, sec_capacity = ?, sec_adviser = ? WHERE sec_id = ?");
    $stmt->bind_param("siisi", $sec_name, $level_id, $sec_capacity, $sec_adviser, $sec_id);
    
    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Updated section (ID: $sec_id): $sec_name (Level ID: $level_id, Capacity: $sec_capacity)";
        logAction($conn, $admin_id, $action);
        echo json_encode([
            'success' => true,
            'message' => 'Section updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update section');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>