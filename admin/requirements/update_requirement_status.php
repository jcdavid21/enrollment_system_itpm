<?php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $acc_id = $input['acc_id'];
    $requirement_id = $input['requirement_id'];
    $status = $input['status'];
    
    if (!$acc_id || !$requirement_id || !$status) {
        throw new Exception('Missing required parameters');
    }
    
    if (!in_array($status, ['Pending', 'Verifying', 'Accepted', 'Declined'])) {
        throw new Exception('Invalid status');
    }
    
    // Check if record exists
    $check_query = "SELECT std_rq_id FROM tbl_student_requirements WHERE acc_id = ? AND requirement_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $acc_id, $requirement_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE tbl_student_requirements SET requirement_status = ?, submitted_at = CURRENT_TIMESTAMP WHERE acc_id = ? AND requirement_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $status, $acc_id, $requirement_id);
    } else {
        // Insert new record
        $insert_query = "INSERT INTO tbl_student_requirements (acc_id, requirement_id, requirement_status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $acc_id, $requirement_id, $status);
    }
    
    $check_stmt->close();
    
    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Updated requirement status for student (Acc ID: $acc_id, Requirement ID: $requirement_id) to $status";
        logAction($conn, $admin_id, $action);
        echo json_encode([
            'success' => true,
            'message' => 'Requirement status updated successfully'
        ]);
    } else {
        throw new Exception('Error updating requirement status');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>