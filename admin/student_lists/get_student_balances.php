<?php
header('Content-Type: application/json');
require_once "../../backend/config.php";

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

$grade_filter = $_GET['grade'] ?? 'all';
$payment_status_filter = $_GET['payment_status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build the main query
$where_conditions = ["a.role = 'Student'", "a.enrollment_status = 'Enrolled'"];
$params = [];

// Grade filter
if ($grade_filter !== 'all') {
    $where_conditions[] = "e.current_level_id = ?";
    $params[] = $grade_filter;
}

// Payment status filter
if ($payment_status_filter === 'unpaid') {
    $where_conditions[] = "COALESCE(paid_amounts.total_paid, 0) = 0";
} elseif ($payment_status_filter === 'partial') {
    $where_conditions[] = "COALESCE(paid_amounts.total_paid, 0) > 0 AND COALESCE(paid_amounts.total_paid, 0) < (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee)";
} elseif ($payment_status_filter === 'full') {
    $where_conditions[] = "COALESCE(paid_amounts.total_paid, 0) >= (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee)";
}

// Search filter
if (!empty($search_query)) {
    $where_conditions[] = "(CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) LIKE ? 
                          OR a.username LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Count total records
$count_query = "SELECT COUNT(DISTINCT a.acc_id) as total
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.status IN ('Pending', 'Enrolled', 'Completed')
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                LEFT JOIN (
                    SELECT enrollment_id, SUM(amount) as total_paid
                    FROM tbl_payments
                    GROUP BY enrollment_id
                ) paid_amounts ON e.enrollment_id = paid_amounts.enrollment_id
                $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
} else {
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
}

// Main balance query
$balances_query = "SELECT 
                    a.acc_id,
                    CONCAT(COALESCE(pd.first_name, ''), ' ', COALESCE(pd.middle_name, ''), ' ', COALESCE(pd.last_name, '')) as full_name,
                    pd.personal_id,
                    
                    e.enrollment_id,
                    e.school_year,
                    
                    f.level as current_grade_level,
                    f.registration_fee,
                    f.miscellaneous_fee,
                    f.books_fee,
                    f.tuition_fee,
                    (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as total_fee,
                    
                    COALESCE(paid_amounts.total_paid, 0) as total_paid,
                    COALESCE((f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) - paid_amounts.total_paid, 
                            f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as remaining_balance,
                    
                    parent.parent_full_name,
                    parent.contact_num
                    
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_parents_details parent ON pd.personal_id = parent.child_id
                
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.enrollment_id = (
                        SELECT MAX(enrollment_id) 
                        FROM tbl_enrollments 
                        WHERE student_id = pd.personal_id 
                        AND status IN ('Pending', 'Enrolled', 'Completed')
                    )
                
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                
                LEFT JOIN (
                    SELECT enrollment_id, SUM(amount) as total_paid
                    FROM tbl_payments
                    GROUP BY enrollment_id
                ) paid_amounts ON e.enrollment_id = paid_amounts.enrollment_id
                
                $where_clause
                ORDER BY remaining_balance DESC
                LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;

if (!empty($params)) {
    $stmt = $conn->prepare($balances_query);
    $types = str_repeat('s', count($params) - 2) . 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $balances_result = $stmt->get_result();
} else {
    $balances_result = $conn->query($balances_query);
}

$balances = [];
while ($row = $balances_result->fetch_assoc()) {
    $row['payment_status'] = 'Unpaid';
    if ($row['total_paid'] > 0) {
        if ($row['remaining_balance'] <= 0) {
            $row['payment_status'] = 'Fully Paid';
        } else {
            $row['payment_status'] = 'Partially Paid';
        }
    }
    
    $row['payment_percentage'] = $row['total_fee'] > 0 ? 
        round(($row['total_paid'] / $row['total_fee']) * 100, 2) : 0;
    
    $row['total_fee'] = number_format($row['total_fee'], 2);
    $row['total_paid'] = number_format($row['total_paid'], 2);
    $row['remaining_balance'] = number_format(max(0, $row['remaining_balance']), 2);
    
    $balances[] = $row;
}

// Calculate summary stats
$stats_query = "SELECT 
                COUNT(DISTINCT a.acc_id) as total_students,
                SUM(COALESCE(f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee, 0)) as total_expected,
                SUM(COALESCE(paid_amounts.total_paid, 0)) as total_collected,
                SUM(CASE WHEN COALESCE(paid_amounts.total_paid, 0) = 0 THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN COALESCE(paid_amounts.total_paid, 0) > 0 
                    AND COALESCE(paid_amounts.total_paid, 0) < (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) 
                    THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN COALESCE(paid_amounts.total_paid, 0) >= (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) 
                    THEN 1 ELSE 0 END) as full_count
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.enrollment_id = (
                        SELECT MAX(enrollment_id) 
                        FROM tbl_enrollments 
                        WHERE student_id = pd.personal_id 
                        AND status IN ('Pending', 'Enrolled', 'Completed')
                    )
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                LEFT JOIN (
                    SELECT enrollment_id, SUM(amount) as total_paid
                    FROM tbl_payments
                    GROUP BY enrollment_id
                ) paid_amounts ON e.enrollment_id = paid_amounts.enrollment_id
                $where_clause";

$stats_params = array_slice($params, 0, -2);

if (!empty($stats_params)) {
    $stats_stmt = $conn->prepare($stats_query);
    $stats_types = str_repeat('s', count($stats_params));
    $stats_stmt->bind_param($stats_types, ...$stats_params);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
} else {
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
}

$total_pages = ceil($total_records / $limit);

$response = [
    'success' => true,
    'balances' => $balances,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => intval($total_records),
        'per_page' => $limit,
        'has_next' => $page < $total_pages,
        'has_prev' => $page > 1
    ],
    'stats' => [
        'total_students' => intval($stats['total_students']),
        'total_expected' => number_format($stats['total_expected'], 2),
        'total_collected' => number_format($stats['total_collected'], 2),
        'total_outstanding' => number_format($stats['total_expected'] - $stats['total_collected'], 2),
        'unpaid_count' => intval($stats['unpaid_count']),
        'partial_count' => intval($stats['partial_count']),
        'full_count' => intval($stats['full_count'])
    ]
];

echo json_encode($response);