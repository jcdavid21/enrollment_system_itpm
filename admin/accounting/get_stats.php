<?php
// File: accounting/get_stats.php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $stats = [];
    
    // Total revenue
    $sql = "SELECT IFNULL(SUM(amount), 0) as total_revenue FROM tbl_payments";
    $result = $conn->query($sql);
    $stats['total_revenue'] = $result->fetch_assoc()['total_revenue'];
    
    // Monthly revenue (current month)
    $sql = "SELECT IFNULL(SUM(amount), 0) as monthly_revenue FROM tbl_payments 
            WHERE YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())";
    $result = $conn->query($sql);
    $stats['monthly_revenue'] = $result->fetch_assoc()['monthly_revenue'];
    
    // Total payments count
    $sql = "SELECT COUNT(*) as total_payments FROM tbl_payments";
    $result = $conn->query($sql);
    $stats['total_payments'] = $result->fetch_assoc()['total_payments'];
    
    // Average payment
    $sql = "SELECT IFNULL(AVG(amount), 0) as avg_payment FROM tbl_payments";
    $result = $conn->query($sql);
    $stats['avg_payment'] = $result->fetch_assoc()['avg_payment'];
    
    // Active enrollments (enrollments that have payments)
    $sql = "SELECT COUNT(DISTINCT enrollment_id) as active_enrollments FROM tbl_payments";
    $result = $conn->query($sql);
    $stats['active_enrollments'] = $result->fetch_assoc()['active_enrollments'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>