<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once "../../backend/config.php";

try {
    // Get parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $status = $_GET['status'] ?? 'all';
    $grade = $_GET['grade'] ?? 'all';
    $year = $_GET['year'] ?? 'all';
    $balance_filter = $_GET['balance_filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build the main query with joins
    $base_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            a.enrollment_status,
            a.date_registered,
            a.date_enrolled,
            pd.first_name,
            pd.middle_name,
            pd.last_name,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
            pd.date_of_birth,
            pd.gender,
            pd.address,
            pd.personal_id,
            f.level as grade_level,
            f.fee_id as level_id,
            prnt.parent_full_name,
            prnt.contact_num,
            prnt.relationship,
            prnt.fb_account,
            -- Transferee data
            st.prev_school,
            st.prev_address_school,
            st.prev_id_school_file,
            st.prev_school_card,
            -- New student data
            ns.student_image as new_student_image,
            ns.parents_valid_id as new_parents_id,
            -- Student type determination
            CASE 
                WHEN st.std_id IS NOT NULL THEN 'Transferee'
                WHEN ns.std_id IS NOT NULL THEN 'New Student'
                ELSE 'Regular Student'
            END as student_type,
            -- Financial data
            COALESCE((
                SELECT SUM(p.amount)
                FROM tbl_enrollments e
                LEFT JOIN tbl_payments p ON e.enrollment_id = p.enrollment_id
                WHERE e.student_id = pd.personal_id
            ), 0) as total_paid,
            COALESCE((
                SELECT SUM(
                    f2.registration_fee + f2.miscellaneous_fee + f2.books_fee + f2.tuition_fee + f2.monthly_fee
                )
                FROM tbl_enrollments e2
                LEFT JOIN tbl_fees f2 ON e2.current_level_id = f2.fee_id
                WHERE e2.student_id = pd.personal_id
            ), 0) as total_fees,
            -- Current section
            CASE 
                WHEN st.section_id IS NOT NULL THEN CONCAT(f.level, ' - ', sec1.sec_name)
                WHEN ns.section_id IS NOT NULL THEN CONCAT(f.level, ' - ', sec2.sec_name)
                ELSE NULL
            END as current_section
        FROM tbl_account a
        LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN tbl_parents_details prnt ON pd.personal_id = prnt.child_id
        LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
        LEFT JOIN tbl_new_old_students ns ON pd.personal_id = ns.personal_id
        LEFT JOIN tbl_fees f ON st.level_id = f.fee_id OR ns.level_id = f.fee_id
        LEFT JOIN tbl_sections sec1 ON st.section_id = sec1.sec_id
        LEFT JOIN tbl_sections sec2 ON ns.section_id = sec2.sec_id
        WHERE a.role = 'Student'
    ";
    
    // Add filters
    $where_conditions = [];
    $params = [];
    
    if ($status !== 'all') {
        $where_conditions[] = "a.enrollment_status = ?";
        $params[] = $status;
    }
    
    if ($grade !== 'all') {
        $where_conditions[] = "(st.level_id = ? OR ns.level_id = ?)";
        $params[] = $grade;
        $params[] = $grade;
    }
    
    if ($year !== 'all') {
        $where_conditions[] = "YEAR(a.date_registered) = ?";
        $params[] = $year;
    }
    
    if (!empty($search)) {
        $search_condition = "(pd.first_name LIKE ? OR pd.last_name LIKE ? OR a.username LIKE ? OR a.email LIKE ?)";
        $where_conditions[] = $search_condition;
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if (!empty($where_conditions)) {
        $base_query .= " AND " . implode(" AND ", $where_conditions);
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) as total FROM ($base_query) as count_table";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Add pagination to main query
    $main_query = $base_query . " ORDER BY a.date_registered DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($main_query);
    if (!empty($params)) {
        $types = str_repeat('s', count($params) - 2) . 'ii'; // last two are integers (limit, offset)
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate balance
        $row['total_balance'] = max(0, $row['total_fees'] - $row['total_paid']);
        
        // Apply balance filter if needed
        if ($balance_filter === 'with_balance' && $row['total_balance'] == 0) continue;
        if ($balance_filter === 'no_balance' && $row['total_balance'] > 0) continue;
        
        $students[] = $row;
    }
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN a.enrollment_status = 'Enrolled' THEN 1 ELSE 0 END) as enrolled_count,
            SUM(CASE WHEN a.enrollment_status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN ns.std_id IS NOT NULL THEN 1 ELSE 0 END) as new_students_count,
            SUM(CASE WHEN st.std_id IS NOT NULL THEN 1 ELSE 0 END) as transferee_count,
            SUM(CASE WHEN (
                COALESCE((
                    SELECT SUM(f2.registration_fee + f2.miscellaneous_fee + f2.books_fee + f2.tuition_fee + f2.monthly_fee)
                    FROM tbl_enrollments e2
                    LEFT JOIN tbl_fees f2 ON e2.current_level_id = f2.fee_id
                    WHERE e2.student_id = pd.personal_id
                ), 0) - COALESCE((
                    SELECT SUM(p.amount)
                    FROM tbl_enrollments e
                    LEFT JOIN tbl_payments p ON e.enrollment_id = p.enrollment_id
                    WHERE e.student_id = pd.personal_id
                ), 0)
            ) > 0 THEN 1 ELSE 0 END) as with_balance_count
        FROM tbl_account a
        LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
        LEFT JOIN tbl_new_old_students ns ON pd.personal_id = ns.personal_id
        WHERE a.role = 'Student'
    ";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // Prepare pagination info
    $total_pages = ceil($total_records / $limit);
    $pagination = [
        'current_page' => $page,
        'per_page' => $limit,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    ];
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'pagination' => $pagination,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>