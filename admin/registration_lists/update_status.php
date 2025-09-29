<?php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $acc_id = intval($input['acc_id'] ?? 0);
    $status = intval($input['status'] ?? -1);
    
    // Validate input
    if ($acc_id <= 0) {
        throw new Exception('Invalid account ID');
    }
    
    if (!in_array($status, [0, 1, 2])) {
        throw new Exception('Invalid status value. Must be 0 (declined), 1 (pending), or 2 (accepted)');
    }
    
    // Check if the account exists and is a student
    $check_query = "SELECT acc_id, reg_acc_status FROM tbl_account WHERE acc_id = ? AND role = 'Student'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $acc_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Student account not found');
    }
    
    $current_data = $check_result->fetch_assoc();
    $current_status = $current_data['reg_acc_status'];
    
    // Update the registration status
    $update_query = "UPDATE tbl_account SET reg_acc_status = ? WHERE acc_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ii', $status, $acc_id);
    
    if ($update_stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Updated registration status for student (Acc ID: $acc_id) from $current_status to $status";
        logAction($conn, $admin_id, $action);

        $status_text = [
            0 => 'declined',
            1 => 'pending',
            2 => 'accepted'
        ];
        
        echo json_encode([
            'success' => true,
            'message' => "Registration status updated to {$status_text[$status]} successfully",
            'data' => [
                'acc_id' => $acc_id,
                'old_status' => $current_status,
                'new_status' => $status
            ]
        ]);
    } else {
        throw new Exception('Failed to update registration status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}