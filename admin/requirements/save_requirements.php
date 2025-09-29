<?php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $requirement_id = isset($input['requirement_id']) && !empty($input['requirement_id']) ? $input['requirement_id'] : null;
    $requirement_name = trim($input['requirement_name']);
    
    if (empty($requirement_name)) {
        throw new Exception('Requirement name is required');
    }
    
    if ($requirement_id) {
        // Update existing requirement
        $query = "UPDATE tbl_requirements SET requirement_name = ? WHERE requirement_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $requirement_name, $requirement_id);
    } else {
        // Add new requirement
        $query = "INSERT INTO tbl_requirements (requirement_name) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $requirement_name);
        
        // Also create requirement entries for all existing students
        if ($stmt->execute()) {
            $new_req_id = $conn->insert_id;
            
            // Get all student accounts
            $student_query = "SELECT acc_id FROM tbl_account WHERE role = 'Student'";
            $student_result = $conn->query($student_query);
            
            if ($student_result->num_rows > 0) {
                $insert_req_query = "INSERT INTO tbl_student_requirements (acc_id, requirement_id, requirement_status) VALUES (?, ?, 'Pending')";
                $req_stmt = $conn->prepare($insert_req_query);
                
                while ($student = $student_result->fetch_assoc()) {
                    $req_stmt->bind_param("ii", $student['acc_id'], $new_req_id);
                    $req_stmt->execute();
                }
                $req_stmt->close();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Requirement saved successfully'
            ]);
            
            $stmt->close();
            $conn->close();
            return;
        }
    }
    
    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = $requirement_id ? "Updated requirement (ID: $requirement_id)" : "Added new requirement (ID: $conn->insert_id)";
        logAction($conn, $admin_id, $action);
        
        echo json_encode([
            'success' => true,
            'message' => 'Requirement saved successfully'
        ]);
    } else {
        throw new Exception('Error saving requirement');
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