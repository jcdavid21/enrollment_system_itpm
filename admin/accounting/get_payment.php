<?php
// File: accounting/get_payment.php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $payment_id = intval($_GET['id'] ?? 0);
    
    if ($payment_id <= 0) {
        throw new Exception('Invalid payment ID');
    }
    
    $sql = "SELECT p.payment_id, p.enrollment_id, p.amount, p.payment_date, p.method, p.remarks,
                   pd.fee_type,
                   CONCAT(per.first_name, ' ', IFNULL(per.middle_name, ''), ' ', per.last_name) as student_name,
                   f.level, e.school_year
            FROM tbl_payments p
            LEFT JOIN tbl_payment_details pd ON p.payment_id = pd.payment_id
            JOIN tbl_enrollments e ON p.enrollment_id = e.enrollment_id
            JOIN tbl_personal_details per ON e.student_id = per.personal_id
            JOIN tbl_fees f ON e.current_level_id = f.fee_id
            WHERE p.payment_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Payment not found');
    }
    
    $payment = $result->fetch_assoc();
    
    // Clean up the payment data
    $payment['student_name'] = trim($payment['student_name']);
    $payment['fee_type'] = $payment['fee_type'] ?? 'Monthly';
    
    echo json_encode([
        'success' => true,
        'payment' => $payment
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>