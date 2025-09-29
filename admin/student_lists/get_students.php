<?php
header('Content-Type: application/json');
require_once "../../backend/config.php";

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? 'all';
$grade_filter = $_GET['grade'] ?? 'all';
$year_filter = $_GET['year'] ?? 'all';
$student_type_filter = $_GET['student_type'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build the main query
$where_conditions = ["a.role = 'Student'"];
$params = [];

// Status filter
if ($status_filter !== 'all') {
    $where_conditions[] = "a.enrollment_status = ?";
    $params[] = $status_filter;
}

// Grade filter - Now using current_level_id from enrollments
if ($grade_filter !== 'all') {
    $where_conditions[] = "e.current_level_id = ?";
    $params[] = $grade_filter;
}

// Year filter (based on registration year)
if ($year_filter !== 'all') {
    $where_conditions[] = "YEAR(a.date_registered) = ?";
    $params[] = $year_filter;
}

// Student type filter
if ($student_type_filter === 'new') {
    $where_conditions[] = "nos.std_id IS NOT NULL";
} elseif ($student_type_filter === 'transferee') {
    $where_conditions[] = "st.std_id IS NOT NULL";
}

// Search filter
if (!empty($search_query)) {
    $where_conditions[] = "(CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) LIKE ? 
                          OR a.username LIKE ? 
                          OR a.email LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Count total records with proper joins
$count_query = "SELECT COUNT(DISTINCT a.acc_id) as total
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_parents_details parent ON pd.personal_id = parent.child_id
                LEFT JOIN tbl_new_old_students nos ON pd.personal_id = nos.personal_id
                LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.status IN ('Pending', 'Enrolled', 'Completed')
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
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

// Main student query with correct fee calculation
$students_query = "SELECT 
                    a.acc_id,
                    a.username,
                    a.email,
                    a.enrollment_status,
                    a.date_registered,
                    a.date_enrolled,
                    CONCAT(COALESCE(pd.first_name, ''), ' ', COALESCE(pd.middle_name, ''), ' ', COALESCE(pd.last_name, '')) as full_name,
                    pd.personal_id,
                    pd.first_name,
                    pd.middle_name,
                    pd.last_name,
                    pd.date_of_birth,
                    pd.gender,
                    pd.address,
                    parent.parent_full_name,
                    parent.contact_num,
                    parent.relationship,
                    parent.fb_account,

                    s.sec_id,
                    s.sec_name as current_section,
                    
                    -- Get current level from either new students or transferee students
                    COALESCE(nos.level_id, st.level_id) as current_level,
                    
                    -- Current enrollment and fee information
                    e.enrollment_id,
                    e.school_year,
                    e.enrollment_date,
                    e.status as enrollment_status_detail,
                    e.total_fee as enrollment_total_fee,
                    e.current_level_id,

                    -- Fee structure from current level
                    f.level as current_grade_level,
                    f.registration_fee,
                    f.miscellaneous_fee,
                    f.books_fee,
                    f.tuition_fee,
                    f.monthly_fee,
                    (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as calculated_total_fee,
                    
                    -- Student images and documents
                    nos.student_image as new_student_image,
                    nos.parents_valid_id as new_parents_id,
                    st.prev_school,
                    st.prev_address_school,
                    st.prev_id_school_file,
                    st.prev_school_card,
                    
                    -- Student type identification
                    st.std_id as transferee_id,
                    nos.std_id as new_student_id,
                    CASE 
                        WHEN st.std_id IS NOT NULL THEN 'Transferee'
                        WHEN nos.std_id IS NOT NULL THEN 'New Student'
                        ELSE 'Unknown'
                    END as student_type,
                    
                    -- Payment information
                    COALESCE(paid_amounts.total_paid, 0) as total_paid,
                    COALESCE((f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) - paid_amounts.total_paid, 
                            f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as remaining_balance
                    
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_parents_details parent ON pd.personal_id = parent.child_id
                LEFT JOIN tbl_new_old_students nos ON pd.personal_id = nos.personal_id
                LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id

                -- Get the most recent enrollment record
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.enrollment_id = (
                        SELECT MAX(enrollment_id) 
                        FROM tbl_enrollments 
                        WHERE student_id = pd.personal_id 
                        AND status IN ('Pending', 'Enrolled', 'Completed')
                    )

                -- Get fee structure based on current enrollment level
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                LEFT JOIN tbl_sections s ON e.current_level_id = s.level_id AND (st.section_id = s.sec_id OR nos.section_id = s.sec_id)

                -- Calculate total payments for this enrollment
                LEFT JOIN (
                    SELECT 
                        p.enrollment_id,
                        SUM(p.amount) as total_paid
                    FROM tbl_payments p
                    GROUP BY p.enrollment_id
                ) paid_amounts ON e.enrollment_id = paid_amounts.enrollment_id
                
                $where_clause
                ORDER BY a.date_registered DESC
                LIMIT ? OFFSET ?";

// Add pagination parameters
$params[] = $limit;
$params[] = $offset;

if (!empty($params)) {
    $stmt = $conn->prepare($students_query);
    $types = str_repeat('s', count($params) - 2) . 'ii'; // Last two params are integers
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $students_result = $stmt->get_result();
} else {
    // This shouldn't happen since we always have role = 'Student' condition
    $students_result = $conn->query($students_query);
}

$students = [];
while ($row = $students_result->fetch_assoc()) {
    // Add some calculated fields for better data presentation
    $row['payment_status'] = 'Unpaid';
    if ($row['total_paid'] > 0) {
        if ($row['remaining_balance'] <= 0) {
            $row['payment_status'] = 'Fully Paid';
        } else {
            $row['payment_status'] = 'Partially Paid';
        }
    }

    // Format monetary values
    $row['calculated_total_fee'] = number_format($row['calculated_total_fee'], 2);
    $row['total_paid'] = number_format($row['total_paid'], 2);
    $row['remaining_balance'] = number_format(max(0, $row['remaining_balance']), 2);

    $students[] = $row;
}

// Calculate statistics based on current filters (without pagination) - Updated with proper joins
$stats_query = "SELECT 
                COUNT(DISTINCT a.acc_id) as total_students,
                SUM(CASE WHEN a.enrollment_status = 'Enrolled' THEN 1 ELSE 0 END) as enrolled_count,
                SUM(CASE WHEN a.enrollment_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN nos.std_id IS NOT NULL THEN 1 ELSE 0 END) as new_students_count,
                SUM(CASE WHEN st.std_id IS NOT NULL THEN 1 ELSE 0 END) as transferee_count,
                SUM(COALESCE(f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee, 0)) as total_expected_revenue,
                SUM(COALESCE(paid_amounts.total_paid, 0)) as total_collected_revenue
                FROM tbl_account a
                LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                LEFT JOIN tbl_parents_details parent ON pd.personal_id = parent.child_id
                LEFT JOIN tbl_new_old_students nos ON pd.personal_id = nos.personal_id
                LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
                LEFT JOIN tbl_enrollments e ON pd.personal_id = e.student_id 
                    AND e.enrollment_id = (
                        SELECT MAX(enrollment_id) 
                        FROM tbl_enrollments 
                        WHERE student_id = pd.personal_id 
                        AND status IN ('Pending', 'Enrolled', 'Completed')
                    )
                LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                LEFT JOIN (
                    SELECT 
                        p.enrollment_id,
                        SUM(p.amount) as total_paid
                    FROM tbl_payments p
                    GROUP BY p.enrollment_id
                ) paid_amounts ON e.enrollment_id = paid_amounts.enrollment_id
                $where_clause";

// Remove pagination params for stats
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

// Calculate pagination info
$total_pages = ceil($total_records / $limit);

$response = [
    'success' => true,
    'students' => $students,
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
        'enrolled_count' => intval($stats['enrolled_count']),
        'pending_count' => intval($stats['pending_count']),
        'new_students_count' => intval($stats['new_students_count']),
        'transferee_count' => intval($stats['transferee_count']),
        'total_expected_revenue' => number_format($stats['total_expected_revenue'], 2),
        'total_collected_revenue' => number_format($stats['total_collected_revenue'], 2),
        'outstanding_balance' => number_format($stats['total_expected_revenue'] - $stats['total_collected_revenue'], 2)
    ]
];

echo json_encode($response);
