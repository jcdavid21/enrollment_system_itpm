<?php
// File: accounting/update_payment.php
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
    // Validate required fields
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $enrollment_id = intval($_POST['enrollment_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = trim($_POST['method'] ?? '');
    $payment_date = $_POST['payment_date'] ?? '';
    $fee_type = trim($_POST['fee_type'] ?? '');
    $remarks = !empty($_POST['remarks']) ? trim($_POST['remarks']) : null;
    
    if ($payment_id <= 0) {
        throw new Exception('Invalid payment ID');
    }
    
    if ($enrollment_id <= 0) {
        throw new Exception('Please select a valid enrollment');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    if (empty($method)) {
        throw new Exception('Please select a payment method');
    }
    
    if (empty($payment_date)) {
        throw new Exception('Please select a payment date');
    }
    
    if (empty($fee_type)) {
        throw new Exception('Please select a fee type');
    }
    
    // Convert datetime-local format to MySQL datetime format
    $formatted_date = date('Y-m-d H:i:s', strtotime($payment_date));
    
    // Verify payment exists
    $check_sql = "SELECT payment_id FROM tbl_payments WHERE payment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $payment_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        throw new Exception('Payment not found');
    }
    
    // Verify enrollment exists
    $enrollment_sql = "SELECT enrollment_id FROM tbl_enrollments WHERE enrollment_id = ?";
    $enrollment_stmt = $conn->prepare($enrollment_sql);
    $enrollment_stmt->bind_param('i', $enrollment_id);
    $enrollment_stmt->execute();
    if ($enrollment_stmt->get_result()->num_rows === 0) {
        throw new Exception('Selected enrollment does not exist');
    }
    
    $conn->begin_transaction();
    
    // Update payment
    $payment_sql = "UPDATE tbl_payments SET enrollment_id = ?, amount = ?, payment_date = ?, method = ?, remarks = ? 
                    WHERE payment_id = ?";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param('idsssi', $enrollment_id, $amount, $formatted_date, $method, $remarks, $payment_id);
    $payment_stmt->execute();
    
    // Update or insert payment details
    $check_detail_sql = "SELECT payment_detail_id FROM tbl_payment_details WHERE payment_id = ?";
    $check_detail_stmt = $conn->prepare($check_detail_sql);
    $check_detail_stmt->bind_param('i', $payment_id);
    $check_detail_stmt->execute();
    $detail_result = $check_detail_stmt->get_result();
    
    if ($detail_result->num_rows > 0) {
        // Update existing payment detail
        $detail_sql = "UPDATE tbl_payment_details SET fee_type = ?, amount = ? WHERE payment_id = ?";
        $detail_stmt = $conn->prepare($detail_sql);
        $detail_stmt->bind_param('sdi', $fee_type, $amount, $payment_id);
    } else {
        // Insert new payment detail
        $detail_sql = "INSERT INTO tbl_payment_details (payment_id, fee_type, amount) VALUES (?, ?, ?)";
        $detail_stmt = $conn->prepare($detail_sql);
        $detail_stmt->bind_param('isd', $payment_id, $fee_type, $amount);
    }
    $detail_stmt->execute();
    
    // Log the activity
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $activity = "Updated payment ID {$payment_id} with amount ₱" . number_format($amount, 2);
        logAction($conn, $user_id, $activity);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>