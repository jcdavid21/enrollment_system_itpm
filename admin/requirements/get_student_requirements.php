<?php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

try {
    $acc_id = isset($_GET['acc_id']) ? (int)$_GET['acc_id'] : 0;
    
    if (!$acc_id) {
        throw new Exception('Student ID is required');
    }
    
    // Get student information
    $student_query = "
        SELECT 
            a.acc_id,
            a.username,
            a.email,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
            student_info.level,
            student_info.section
        FROM tbl_account a
        INNER JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN (
            SELECT 
                nst.personal_id, 
                f.level, 
                s.sec_name as section
            FROM tbl_new_old_students nst 
            LEFT JOIN tbl_fees f ON nst.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON nst.section_id = s.sec_id
            UNION
            SELECT 
                st.personal_id, 
                f.level, 
                s.sec_name as section
            FROM tbl_student_transferee st 
            LEFT JOIN tbl_fees f ON st.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON st.section_id = s.sec_id
        ) student_info ON pd.personal_id = student_info.personal_id
        WHERE a.acc_id = ?
    ";
    
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $acc_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows === 0) {
        throw new Exception('Student not found');
    }
    
    $student = $student_result->fetch_assoc();
    $student_stmt->close();
    
    // Get student requirements with status
    $requirements_query = "
        SELECT 
            r.requirement_id,
            r.requirement_name,
            COALESCE(sr.requirement_status, 'Pending') as status,
            sr.submitted_at
        FROM tbl_requirements r
        LEFT JOIN tbl_student_requirements sr ON r.requirement_id = sr.requirement_id AND sr.acc_id = ?
        ORDER BY r.requirement_id
    ";
    
    $req_stmt = $conn->prepare($requirements_query);
    $req_stmt->bind_param("i", $acc_id);
    $req_stmt->execute();
    $req_result = $req_stmt->get_result();
    
    $requirements = [];
    while ($row = $req_result->fetch_assoc()) {
        $requirements[] = $row;
    }
    $req_stmt->close();
    
    echo json_encode([
        'success' => true,
        'student' => $student,
        'requirements' => $requirements
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>