<?php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

try {
    // Get parameters
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 10);
    $status = $_GET['status'] ?? 'all';
    $year = $_GET['year'] ?? 'all';
    $student_type = $_GET['student_type'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    
    $offset = ($page - 1) * $limit;
    
    // Base query with joins to get all required data
    $base_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            a.reg_acc_status,
            a.date_registered,
            a.enrollment_status,
            pd.first_name,
            pd.middle_name,
            pd.last_name,
            pd.date_of_birth,
            pd.gender,
            pd.address,
            pr.parent_full_name,
            pr.contact_num,
            pr.relationship,
            pr.fb_account,
            pr.parent_temp_id,
            CASE 
                WHEN a.enrollment_status = 'Newly Registered' THEN 'New Student'
                ELSE 'Transferee'
            END as student_type,
            CONCAT_WS(' ', 
                COALESCE(pd.first_name, ''), 
                CASE WHEN pd.middle_name IS NOT NULL AND pd.middle_name != '' THEN pd.middle_name ELSE '' END, 
                COALESCE(pd.last_name, '')
            ) as full_name
        FROM tbl_account a
        LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN tbl_parents_details pr ON pd.personal_id = pr.child_id
        WHERE a.role = 'Student'
    ";
    
    // Add conditions
    $conditions = [];
    $params = [];
    
    // Status filter
    if ($status !== 'all') {
        $conditions[] = "a.reg_acc_status = ?";
        $params[] = $status;
    }
    
    // Year filter
    if ($year !== 'all') {
        $conditions[] = "YEAR(a.date_registered) = ?";
        $params[] = $year;
    }
    
    // Student type filter
    if ($student_type !== 'all') {
        if ($student_type === 'new') {
            $conditions[] = "a.enrollment_status = 'Newly Registered'";
        } elseif ($student_type === 'transferee') {
            $conditions[] = "a.enrollment_status != 'Newly Registered'";
        }
    }
    
    // Search filter
    if (!empty($search)) {
        $search_condition = "(
            CONCAT_WS(' ', pd.first_name, pd.middle_name, pd.last_name) LIKE ? OR
            a.username LIKE ? OR
            a.email LIKE ? OR
            pr.parent_full_name LIKE ?
        )";
        $conditions[] = $search_condition;
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Build final query
    $where_clause = !empty($conditions) ? " AND " . implode(" AND ", $conditions) : "";
    $final_query = $base_query . $where_clause . " ORDER BY a.date_registered DESC";
    
    // Get total count for pagination
    $count_query = "
        SELECT COUNT(DISTINCT a.acc_id) as total
        FROM tbl_account a
        LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN tbl_parents_details pr ON pd.personal_id = pr.child_id
        WHERE a.role = 'Student'" . $where_clause;
    
    $count_stmt = $conn->prepare($count_query);
    if (!empty($params)) {
        $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    
    // Get paginated results
    $paginated_query = $final_query . " LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($paginated_query);
    
    $all_params = array_merge($params, [$limit, $offset]);
    $param_types = str_repeat('s', count($params)) . 'ii';
    
    if (!empty($all_params)) {
        $stmt->bind_param($param_types, ...$all_params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_registrations,
            SUM(CASE WHEN reg_acc_status = 1 THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN reg_acc_status = 2 THEN 1 ELSE 0 END) as accepted_count,
            SUM(CASE WHEN reg_acc_status = 0 THEN 1 ELSE 0 END) as declined_count
        FROM tbl_account 
        WHERE role = 'Student'
    ";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    $has_prev = $page > 1;
    $has_next = $page < $total_pages;
    
    $response = [
        'success' => true,
        'registrations' => $registrations,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'has_prev' => $has_prev,
            'has_next' => $has_next
        ],
        'stats' => [
            'total_registrations' => intval($stats['total_registrations']),
            'pending_count' => intval($stats['pending_count']),
            'accepted_count' => intval($stats['accepted_count']),
            'declined_count' => intval($stats['declined_count'])
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching registrations: ' . $e->getMessage()
    ]);
}