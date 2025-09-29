<?php
header('Content-Type: application/json');
require_once "../../backend/config.php";

if (!isset($_GET['sec_id']) || empty($_GET['sec_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Section ID is required'
    ]);
    exit;
}

try {
    $sec_id = (int)$_GET['sec_id'];
    
    // Get section information
    $section_query = "
        SELECT s.*, f.level as grade_level
        FROM tbl_sections s
        LEFT JOIN tbl_fees f ON s.level_id = f.fee_id
        WHERE s.sec_id = ?
    ";
    
    $section_stmt = $conn->prepare($section_query);
    $section_stmt->bind_param("i", $sec_id);
    $section_stmt->execute();
    $section_result = $section_stmt->get_result();
    
    if ($section_result->num_rows === 0) {
        throw new Exception('Section not found');
    }
    
    $section = $section_result->fetch_assoc();
    
    // Get students enrolled in this section (both new students and transferees)
    $students_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            a.enrollment_status,
            a.date_enrolled,
            pd.first_name,
            pd.middle_name,
            pd.last_name,
            CONCAT_WS(' ', pd.first_name, pd.middle_name, pd.last_name) as full_name,
            'New Student' as student_type
        FROM tbl_new_old_students ns
        INNER JOIN tbl_personal_details pd ON ns.personal_id = pd.personal_id
        INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
        WHERE ns.section_id = ? AND a.enrollment_status = 'Enrolled'
        
        UNION ALL
        
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            a.enrollment_status,
            a.date_enrolled,
            pd.first_name,
            pd.middle_name,
            pd.last_name,
            CONCAT_WS(' ', pd.first_name, pd.middle_name, pd.last_name) as full_name,
            'Transferee' as student_type
        FROM tbl_student_transferee st
        INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
        INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
        WHERE st.section_id = ? AND a.enrollment_status = 'Enrolled'
        
        ORDER BY full_name
    ";
    
    $students_stmt = $conn->prepare($students_query);
    $students_stmt->bind_param("ii", $sec_id, $sec_id);
    $students_stmt->execute();
    $students_result = $students_stmt->get_result();
    
    $students = [];
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'section' => $section,
        'students' => $students
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>