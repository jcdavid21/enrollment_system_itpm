<?php
header('Content-Type: application/json');
require_once "../../backend/config.php";

if (!isset($_GET['student_id']) || !isset($_GET['current_section'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Student ID and current section are required'
    ]);
    exit;
}

try {
    $student_id = (int)$_GET['student_id'];
    $current_section_id = (int)$_GET['current_section'];
    
    // Get student information and current grade level
    $student_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            pd.first_name,
            pd.middle_name,
            pd.last_name,
            CONCAT_WS(' ', pd.first_name, pd.middle_name, pd.last_name) as full_name,
            CASE 
                WHEN ns.std_id IS NOT NULL THEN 'New Student'
                WHEN st.std_id IS NOT NULL THEN 'Transferee'
                ELSE 'Unknown'
            END as student_type,
            COALESCE(ns.level_id, st.level_id) as level_id,
            f.level as grade_level
        FROM tbl_account a
        INNER JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN tbl_new_old_students ns ON pd.personal_id = ns.personal_id
        LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
        LEFT JOIN tbl_fees f ON COALESCE(ns.level_id, st.level_id) = f.fee_id
        WHERE a.acc_id = ? AND a.enrollment_status = 'Enrolled'
    ";
    
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $student_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        throw new Exception('Student not found or not enrolled');
    }
    
    $student = $student_result->fetch_assoc();
    $student_level_id = $student['level_id'];
    
    // Get available sections in the same grade level (excluding current section)
    $sections_query = "
        SELECT 
            s.sec_id,
            s.sec_name,
            s.sec_capacity,
            s.sec_adviser,
            f.level as grade_level,
            COALESCE(enrolled_new.count, 0) + COALESCE(enrolled_transferee.count, 0) as enrolled_count,
            (s.sec_capacity - (COALESCE(enrolled_new.count, 0) + COALESCE(enrolled_transferee.count, 0))) as available_slots
        FROM tbl_sections s
        LEFT JOIN tbl_fees f ON s.level_id = f.fee_id
        LEFT JOIN (
            SELECT 
                ns.section_id,
                COUNT(*) as count
            FROM tbl_new_old_students ns
            INNER JOIN tbl_personal_details pd ON ns.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY ns.section_id
        ) enrolled_new ON s.sec_id = enrolled_new.section_id
        LEFT JOIN (
            SELECT 
                st.section_id,
                COUNT(*) as count
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY st.section_id
        ) enrolled_transferee ON s.sec_id = enrolled_transferee.section_id
        WHERE s.level_id = ? AND s.sec_id != ?
        HAVING available_slots > 0
        ORDER BY s.sec_name
    ";
    
    $sections_stmt = $conn->prepare($sections_query);
    $sections_stmt->bind_param("ii", $student_level_id, $current_section_id);
    $sections_stmt->execute();
    $sections_result = $sections_stmt->get_result();
    
    $available_sections = [];
    while ($row = $sections_result->fetch_assoc()) {
        $available_sections[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'student' => $student,
        'available_sections' => $available_sections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>