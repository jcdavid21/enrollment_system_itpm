<?php
session_start();
require_once "../../backend/config.php";

// Check if user is admin
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin"){
    header("Location: ../../components/logout.php");
    exit;
}

// Get filter parameters
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

// Main balance query - No LIMIT for export
$balances_query = "SELECT 
                    a.acc_id,
                    a.username,
                    a.email,
                    CONCAT(COALESCE(pd.first_name, ''), ' ', COALESCE(pd.middle_name, ''), ' ', COALESCE(pd.last_name, '')) as full_name,
                    pd.first_name,
                    pd.middle_name,
                    pd.last_name,
                    pd.gender,
                    pd.address,
                    
                    e.enrollment_id,
                    e.school_year,
                    DATE_FORMAT(e.enrollment_date, '%Y-%m-%d') as enrollment_date,
                    
                    f.level as current_grade_level,
                    f.registration_fee,
                    f.miscellaneous_fee,
                    f.books_fee,
                    f.tuition_fee,
                    f.monthly_fee,
                    (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as total_fee,
                    
                    COALESCE(paid_amounts.total_paid, 0) as total_paid,
                    COALESCE((f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) - paid_amounts.total_paid, 
                            f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as remaining_balance,
                    
                    parent.parent_full_name,
                    parent.contact_num,
                    parent.relationship,
                    
                    CASE 
                        WHEN COALESCE(paid_amounts.total_paid, 0) = 0 THEN 'Unpaid'
                        WHEN COALESCE(paid_amounts.total_paid, 0) >= (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) THEN 'Fully Paid'
                        ELSE 'Partially Paid'
                    END as payment_status,
                    
                    CASE 
                        WHEN (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) > 0 
                        THEN ROUND((COALESCE(paid_amounts.total_paid, 0) / (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee)) * 100, 2)
                        ELSE 0
                    END as payment_percentage
                    
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
                ORDER BY remaining_balance DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($balances_query);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($balances_query);
}

// Set headers for CSV download
$filename = "student_balances_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM to fix UTF-8 in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Student ID',
    'Username',
    'Email',
    'Full Name',
    'First Name',
    'Middle Name',
    'Last Name',
    'Gender',
    'Address',
    'Grade Level',
    'School Year',
    'Enrollment Date',
    'Parent Name',
    'Parent Contact',
    'Relationship',
    'Registration Fee',
    'Miscellaneous Fee',
    'Books Fee',
    'Tuition Fee',
    'Monthly Fee',
    'Total Fee',
    'Total Paid',
    'Remaining Balance',
    'Payment Status',
    'Payment Percentage (%)'
];

fputcsv($output, $headers);

// Add data rows
$total_expected = 0;
$total_collected = 0;
$total_outstanding = 0;

while ($row = $result->fetch_assoc()) {
    $data = [
        $row['acc_id'],
        $row['username'],
        $row['email'],
        $row['full_name'],
        $row['first_name'],
        $row['middle_name'],
        $row['last_name'],
        $row['gender'],
        $row['address'],
        $row['current_grade_level'],
        $row['school_year'],
        $row['enrollment_date'],
        $row['parent_full_name'],
        $row['contact_num'],
        $row['relationship'],
        number_format($row['registration_fee'], 2, '.', ''),
        number_format($row['miscellaneous_fee'], 2, '.', ''),
        number_format($row['books_fee'], 2, '.', ''),
        number_format($row['tuition_fee'], 2, '.', ''),
        number_format($row['monthly_fee'], 2, '.', ''),
        number_format($row['total_fee'], 2, '.', ''),
        number_format($row['total_paid'], 2, '.', ''),
        number_format($row['remaining_balance'], 2, '.', ''),
        $row['payment_status'],
        $row['payment_percentage']
    ];
    
    fputcsv($output, $data);
    
    // Calculate totals
    $total_expected += $row['total_fee'];
    $total_collected += $row['total_paid'];
    $total_outstanding += $row['remaining_balance'];
}

// Add summary rows
fputcsv($output, []); // Empty row
fputcsv($output, ['SUMMARY']);
fputcsv($output, ['Total Expected Revenue', number_format($total_expected, 2, '.', '')]);
fputcsv($output, ['Total Collected', number_format($total_collected, 2, '.', '')]);
fputcsv($output, ['Total Outstanding Balance', number_format($total_outstanding, 2, '.', '')]);
fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
fputcsv($output, ['Exported By', $_SESSION['username'] ?? 'Admin']);

fclose($output);
exit;