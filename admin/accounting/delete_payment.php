<?php
// File: accounting/delete_payment.php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        throw new Exception('Invalid JSON input');
    }
    
    $payment_id = intval($input['payment_id'] ?? 0);
    
    if ($payment_id <= 0) {
        throw new Exception('Invalid payment ID');
    }
    
    // Verify payment exists and get details for logging
    $check_sql = "SELECT p.payment_id, p.amount, p.enrollment_id,
                         CONCAT(pd.first_name, ' ', IFNULL(pd.middle_name, ''), ' ', pd.last_name) as student_name
                  FROM tbl_payments p 
                  JOIN tbl_enrollments e ON p.enrollment_id = e.enrollment_id 
                  JOIN tbl_personal_details pd ON e.student_id = pd.personal_id
                  WHERE p.payment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $payment_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Payment not found');
    }
    
    $payment_data = $result->fetch_assoc();
    
    $conn->begin_transaction();
    
    // Delete payment details first (foreign key constraint)
    $delete_details_sql = "DELETE FROM tbl_payment_details WHERE payment_id = ?";
    $delete_details_stmt = $conn->prepare($delete_details_sql);
    $delete_details_stmt->bind_param('i', $payment_id);
    $delete_details_stmt->execute();
    
    // Delete payment
    $delete_payment_sql = "DELETE FROM tbl_payments WHERE payment_id = ?";
    $delete_payment_stmt = $conn->prepare($delete_payment_sql);
    $delete_payment_stmt->bind_param('i', $payment_id);
    $delete_payment_stmt->execute();
    
    if ($delete_payment_stmt->affected_rows === 0) {
        throw new Exception('Failed to delete payment - payment may not exist');
    }
    
    // Log the activity
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $activity = "Deleted payment ID {$payment_data['payment_id']} of amount {$payment_data['amount']} for student {$payment_data['student_name']} (Enrollment ID: {$payment_data['enrollment_id']})";
        logAction($conn, $user_id, $activity);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment deleted successfully'
    ]);
    
} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>