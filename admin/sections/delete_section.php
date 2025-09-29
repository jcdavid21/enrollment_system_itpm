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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['sec_id']) || empty($input['sec_id'])) {
        throw new Exception('Section ID is required');
    }
    
    $sec_id = (int)$input['sec_id'];
    
    // Check if section exists
    $section_check = $conn->prepare("SELECT sec_id, sec_name FROM tbl_sections WHERE sec_id = ?");
    $section_check->bind_param("i", $sec_id);
    $section_check->execute();
    $section_result = $section_check->get_result();
    
    if ($section_result->num_rows === 0) {
        throw new Exception('Section not found');
    }
    
    $section_data = $section_result->fetch_assoc();
    
    // Check if there are enrolled students in this section
    $enrolled_check_query = "
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
    
    $enrolled_stmt = $conn->prepare($enrolled_check_query);
    $enrolled_stmt->bind_param("iii", $sec_id, $sec_id, $sec_id);
    $enrolled_stmt->execute();
    $enrolled_result = $enrolled_stmt->get_result();
    $enrolled_data = $enrolled_result->fetch_assoc();
    $enrolled_count = (int)$enrolled_data['total_enrolled'];
    
    if ($enrolled_count > 0) {
        throw new Exception("Cannot delete section with enrolled students. Please transfer all $enrolled_count students to other sections first.");
    }
    
    // Delete the section
    $delete_stmt = $conn->prepare("DELETE FROM tbl_sections WHERE sec_id = ?");
    $delete_stmt->bind_param("i", $sec_id);
    
    if ($delete_stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $sec_name = $section_data['sec_name'];
        $action = "Deleted section: $sec_name (ID: $sec_id)";
        logAction($conn, $admin_id, $action);
        echo json_encode([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete section');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>