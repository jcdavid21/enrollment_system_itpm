<?php
session_start();
require_once "../backend/config.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);

        $update_personal = "UPDATE tbl_personal_details SET 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    date_of_birth = ?, 
                    gender = ?, 
                    address = ? 
                    WHERE acc_id = ?";

        $stmt = $conn->prepare($update_personal);
        $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $date_of_birth, $gender, $address, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating profile.']);
        }
    }
    elseif ($action === 'update_parent') {
        $parent_full_name = trim($_POST['parent_full_name']);
        $contact_num = trim($_POST['contact_num']);
        $relationship = $_POST['relationship'];

        $get_personal_id = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
        $stmt = $conn->prepare($get_personal_id);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_data = $result->fetch_assoc();
        $personal_id = $student_data['personal_id'];

        $check_parent = "SELECT parent_id FROM tbl_parents_details WHERE child_id = ?";
        $stmt = $conn->prepare($check_parent);
        $stmt->bind_param("i", $personal_id);
        $stmt->execute();
        $parent_result = $stmt->get_result();

        if ($parent_result->num_rows > 0) {
            $update_parent = "UPDATE tbl_parents_details SET 
                        parent_full_name = ?, 
                        contact_num = ?, 
                        relationship = ? 
                        WHERE child_id = ?";
            $stmt = $conn->prepare($update_parent);
            $stmt->bind_param("sssi", $parent_full_name, $contact_num, $relationship, $personal_id);
        } else {
            $insert_parent = "INSERT INTO tbl_parents_details (child_id, parent_full_name, contact_num, relationship) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_parent);
            $stmt->bind_param("isss", $personal_id, $parent_full_name, $contact_num, $relationship);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Parent information updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating parent information.']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>