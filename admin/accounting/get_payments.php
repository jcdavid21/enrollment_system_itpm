<?php
// File: accounting/get_payments.php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Get parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    $method = $_GET['method'] ?? 'all';
    $year = $_GET['year'] ?? 'all';
    $amount_range = $_GET['amount_range'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    
    // Build WHERE clause
    $conditions = [];
    $params = [];
    
    if ($method !== 'all') {
        $conditions[] = "p.method = ?";
        $params[] = $method;
    }
    
    if ($year !== 'all') {
        $conditions[] = "YEAR(p.payment_date) = ?";
        $params[] = $year;
    }
    
    if ($amount_range !== 'all') {
        switch ($amount_range) {
            case '0-1000':
                $conditions[] = "p.amount BETWEEN 0 AND 1000";
                break;
            case '1000-5000':
                $conditions[] = "p.amount BETWEEN 1000 AND 5000";
                break;
            case '5000-15000':
                $conditions[] = "p.amount BETWEEN 5000 AND 15000";
                break;
            case '15000+':
                $conditions[] = "p.amount > 15000";
                break;
        }
    }
    
    if (!empty($search)) {
        $conditions[] = "(CONCAT(pd.first_name, ' ', IFNULL(pd.middle_name, ''), ' ', pd.last_name) LIKE ? OR p.remarks LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Count total records
    $count_sql = "SELECT COUNT(*) as total FROM tbl_payments p
                  JOIN tbl_enrollments e ON p.enrollment_id = e.enrollment_id
                  JOIN tbl_personal_details pd ON e.student_id = pd.personal_id
                  JOIN tbl_fees f ON e.current_level_id = f.fee_id
                  $where_clause";
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Get payment data with pagination
    $sql = "SELECT p.payment_id, p.amount, p.payment_date, p.method, p.remarks,
                   pd_details.fee_type,
                   CONCAT(pd.first_name, ' ', IFNULL(pd.middle_name, ''), ' ', pd.last_name) as student_name,
                   f.level, e.school_year
            FROM tbl_payments p
            LEFT JOIN tbl_payment_details pd_details ON p.payment_id = pd_details.payment_id
            JOIN tbl_enrollments e ON p.enrollment_id = e.enrollment_id
            JOIN tbl_personal_details pd ON e.student_id = pd.personal_id
            JOIN tbl_fees f ON e.current_level_id = f.fee_id
            $where_clause
            ORDER BY p.payment_date DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param(str_repeat('s', count($params) - 2) . 'ii', ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = [
            'payment_id' => $row['payment_id'],
            'amount' => $row['amount'],
            'payment_date' => $row['payment_date'],
            'method' => $row['method'],
            'remarks' => $row['remarks'],
            'fee_type' => $row['fee_type'] ?? 'Monthly',
            'student_name' => trim($row['student_name']),
            'level' => $row['level'],
            'school_year' => $row['school_year']
        ];
    }
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    $has_prev = $page > 1;
    $has_next = $page < $total_pages;
    
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $limit,
            'has_prev' => $has_prev,
            'has_next' => $has_next
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>