<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once "../../backend/config.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $end_date = $input['end_date'] ?? null;
    
    if (!$end_date) {
        throw new Exception('End date is required');
    }
    
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $end_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $end_date) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }
    
    $conn->begin_transaction();
    
    // Get count of enrolled students before update
    $count_query = "SELECT COUNT(*) as enrolled_count FROM tbl_account WHERE role = 'Student' AND enrollment_status = 'Enrolled'";
    $count_result = $conn->query($count_query);
    $count_data = $count_result->fetch_assoc();
    $enrolled_count = $count_data['enrolled_count'];
    
    // Update all enrolled students to "Not Enrolled"
    $update_query = "UPDATE tbl_account SET enrollment_status = 'Not Enrolled' WHERE role = 'Student' AND enrollment_status = 'Enrolled'";
    $stmt = $conn->prepare($update_query);
    $stmt->execute();
    
    $affected_rows = $stmt->affected_rows;
    
    
    // Clear section assignments by setting section_id to NULL
    $clear_transferee_sections = "UPDATE tbl_student_transferee SET section_id = NULL";
    $conn->query($clear_transferee_sections);
    
    $clear_new_student_sections = "UPDATE tbl_new_old_students SET section_id = NULL";
    $conn->query($clear_new_student_sections);
    
    // Log the activity
    if (isset($_SESSION['acc_id'])) {
        $admin_id = $_SESSION['acc_id'];
        $activity = "Ended school year on $end_date. Updated $affected_rows students to 'Not Enrolled' status";
        $log_query = "INSERT INTO tbl_audit_log (acc_id, activity) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param('is', $admin_id, $activity);
        $log_stmt->execute();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'School year ended successfully',
        'data' => [
            'end_date' => $end_date,
            'students_affected' => $affected_rows,
            'previously_enrolled' => $enrolled_count
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>