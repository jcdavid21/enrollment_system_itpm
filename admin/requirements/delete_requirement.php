<?php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $requirement_id = $input['requirement_id'];
    
    if (!$requirement_id) {
        throw new Exception('Requirement ID is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete student requirements first (due to foreign key constraint)
    $delete_student_req = "DELETE FROM tbl_student_requirements WHERE requirement_id = ?";
    $stmt1 = $conn->prepare($delete_student_req);
    $stmt1->bind_param("i", $requirement_id);
    $stmt1->execute();
    $stmt1->close();
    
    // Delete the requirement
    $delete_req = "DELETE FROM tbl_requirements WHERE requirement_id = ?";
    $stmt2 = $conn->prepare($delete_req);
    $stmt2->bind_param("i", $requirement_id);
    $stmt2->execute();
    $stmt2->close();
    
    // Commit transaction
    $conn->commit();

    // Log the action
    $admin_id = $_SESSION["user_id"] ?? null;
    $action = "Deleted requirement (ID: $requirement_id)";
    logAction($conn, $admin_id, $action);
    
    echo json_encode([
        'success' => true,
        'message' => 'Requirement deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>