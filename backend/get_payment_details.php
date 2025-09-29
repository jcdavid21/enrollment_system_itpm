<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || !isset($_GET['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$payment_id = (int)$_GET['payment_id'];
$user_id = $_SESSION["user_id"];

try {
    // Get payment details with enrollment info
    $query = "SELECT p.*, e.school_year, f.level
              FROM tbl_payments p
              LEFT JOIN tbl_enrollments e ON p.enrollment_id = e.enrollment_id
              LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
              LEFT JOIN tbl_personal_details pd ON e.student_id = pd.personal_id
              WHERE p.payment_id = ? AND pd.acc_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($payment = $result->fetch_assoc()) {
        // Get payment details breakdown
        $detail_query = "SELECT * FROM tbl_payment_details WHERE payment_id = ?";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->bind_param("i", $payment_id);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->get_result();
        
        $payment_details = [];
        while ($detail = $detail_result->fetch_assoc()) {
            $payment_details[] = $detail;
        }
        
        echo json_encode([
            'success' => true,
            'payment' => $payment,
            'payment_details' => $payment_details,
            'enrollment' => [
                'school_year' => $payment['school_year'],
                'level' => $payment['level']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>