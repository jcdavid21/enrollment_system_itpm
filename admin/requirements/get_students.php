<?php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = isset($_GET['status']) ? $_GET['status'] : 'incomplete';
    $year = isset($_GET['year']) ? $_GET['year'] : 'all';
    $grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : 'all';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $offset = ($page - 1) * $limit;

    // Base query to get students with their requirements status
    $base_query = "
        FROM tbl_account a
        INNER JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN (
            SELECT 
                nst.personal_id, 
                f.level, 
                s.sec_name as section,
                f.fee_id as level_id
            FROM tbl_new_old_students nst 
            LEFT JOIN tbl_fees f ON nst.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON nst.section_id = s.sec_id
            UNION
            SELECT 
                st.personal_id, 
                f.level, 
                s.sec_name as section,
                f.fee_id as level_id
            FROM tbl_student_transferee st 
            LEFT JOIN tbl_fees f ON st.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON st.section_id = s.sec_id
        ) student_info ON pd.personal_id = student_info.personal_id
        LEFT JOIN (
            SELECT 
                a_sub.acc_id,
                COUNT(r.requirement_id) as total_requirements,
                SUM(CASE WHEN sr.requirement_status = 'Accepted' THEN 1 ELSE 0 END) as completed_requirements,
                SUM(CASE WHEN sr.requirement_status IN ('Pending', 'Verifying') THEN 1 ELSE 0 END) as pending_requirements,
                MAX(sr.submitted_at) as last_updated
            FROM tbl_account a_sub
            CROSS JOIN tbl_requirements r
            LEFT JOIN tbl_student_requirements sr ON a_sub.acc_id = sr.acc_id AND r.requirement_id = sr.requirement_id
            WHERE a_sub.role = 'Student'
            GROUP BY a_sub.acc_id
        ) req_status ON a.acc_id = req_status.acc_id
        WHERE a.role = 'Student'
    ";

    // Add filters
    $conditions = [];
    $params = [];
    $param_types = '';

    // Status filter
    if ($status !== 'all') {
        if ($status === 'complete') {
            $conditions[] = "req_status.completed_requirements = req_status.total_requirements AND req_status.total_requirements > 0";
        } elseif ($status === 'incomplete') {
            $conditions[] = "(req_status.completed_requirements < req_status.total_requirements OR req_status.total_requirements IS NULL OR req_status.total_requirements = 0)";
        } elseif ($status === 'pending') {
            $conditions[] = "req_status.pending_requirements > 0";
        }
    }

    // Year filter
    if ($year !== 'all') {
        $conditions[] = "YEAR(a.date_registered) = ?";
        $params[] = $year;
        $param_types .= 'i';
    }

    // Grade level filter
    if ($grade_level !== 'all') {
        $conditions[] = "student_info.level_id = ?";
        $params[] = $grade_level;
        $param_types .= 'i';
    }

    // Search filter
    if (!empty($search)) {
        $conditions[] = "(CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) LIKE ? OR a.username LIKE ?)";
        $search_term = '%' . $search . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= 'ss';
    }

    // Add conditions to query
    if (!empty($conditions)) {
        $base_query .= " AND " . implode(' AND ', $conditions);
    }

    // Count total records
    $count_query = "SELECT COUNT(*) as total " . $base_query;
    
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_query);
        if (!empty($param_types)) {
            $count_stmt->bind_param($param_types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_records = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $conn->query($count_query);
        $total_records = $count_result->fetch_assoc()['total'];
    }

    // Get paginated records
    $select_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
            student_info.level,
            student_info.section,
            COALESCE(req_status.total_requirements, 0) as total_requirements,
            COALESCE(req_status.completed_requirements, 0) as completed_requirements,
            COALESCE(req_status.pending_requirements, 0) as pending_requirements,
            req_status.last_updated
        " . $base_query . "
        ORDER BY a.acc_id DESC
        LIMIT ? OFFSET ?
    ";

    // Add limit and offset to parameters
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= 'ii';

    if (!empty($params)) {
        $select_stmt = $conn->prepare($select_query);
        $select_stmt->bind_param($param_types, ...$params);
        $select_stmt->execute();
        $result = $select_stmt->get_result();
    } else {
        $result = $conn->query($select_query);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    // Calculate pagination
    $total_pages = ceil($total_records / $limit);
    $has_prev = $page > 1;
    $has_next = $page < $total_pages;

    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_students,
            SUM(CASE WHEN req_status.completed_requirements = req_status.total_requirements AND req_status.total_requirements > 0 THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN req_status.pending_requirements > 0 THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN req_status.completed_requirements < req_status.total_requirements OR req_status.total_requirements IS NULL OR req_status.total_requirements = 0 THEN 1 ELSE 0 END) as incomplete_count
        " . $base_query;

    if (!empty($conditions)) {
        // Remove limit params for stats query
        $stats_params = array_slice($params, 0, -2);
        $stats_param_types = substr($param_types, 0, -2);
        
        if (!empty($stats_params)) {
            $stats_stmt = $conn->prepare($stats_query);
            $stats_stmt->bind_param($stats_param_types, ...$stats_params);
            $stats_stmt->execute();
            $stats_result = $stats_stmt->get_result();
            $stats = $stats_result->fetch_assoc();
            $stats_stmt->close();
        } else {
            $stats_result = $conn->query($stats_query);
            $stats = $stats_result->fetch_assoc();
        }
    } else {
        $stats_result = $conn->query($stats_query);
        $stats = $stats_result->fetch_assoc();
    }

    echo json_encode([
        'success' => true,
        'students' => $students,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => (int)$total_records,
            'total_pages' => $total_pages,
            'has_prev' => $has_prev,
            'has_next' => $has_next
        ],
        'stats' => [
            'total_students' => (int)$stats['total_students'],
            'completed_count' => (int)$stats['completed_count'],
            'pending_count' => (int)$stats['pending_count'],
            'incomplete_count' => (int)$stats['incomplete_count']
        ]
    ]);

    if (isset($select_stmt)) $select_stmt->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching students: ' . $e->getMessage()
    ]);
}

$conn->close();
?>