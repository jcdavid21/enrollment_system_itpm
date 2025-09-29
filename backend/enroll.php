<?php 
require_once "./config.php";
header('Content-Type: application/json');

if(isset($_POST["address"]) && isset($_POST["registerPassword"]) && isset($_POST["email"]) && isset($_POST["firstName"]) && isset($_POST["lastName"]) && isset($_POST["dateOfBirth"]) && isset($_POST["confirmPassword"]) && isset($_POST["gender"]) && isset($_POST["parentContact"]) && isset($_POST["parentName"]) && isset($_POST["relationship"]) && isset($_POST["username"])) {
    date_default_timezone_set('Asia/Manila');
    $address = $_POST["address"];
    $registerPassword = $_POST["registerPassword"];
    $confirmPassword = $_POST["confirmPassword"];
    $email = $_POST["email"];
    $firstName = $_POST["firstName"];
    $middleName = $_POST["middleName"];
    $lastName = $_POST["lastName"];
    $dateOfBirth = $_POST["dateOfBirth"];
    $gender = $_POST["gender"];
    $parentContact = $_POST["parentContact"];
    $parentName = $_POST["parentName"];
    $relationship = $_POST["relationship"];
    $username = $_POST["username"];
    $hashedPassword = password_hash($registerPassword, PASSWORD_BCRYPT);

    // validations
    if($registerPassword !== $confirmPassword) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }
    if(strlen($registerPassword) < 8) {
        echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long."]);
        exit;
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit;
    }
    if(!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        echo json_encode(["success" => false, "message" => "Username can only contain letters, numbers, and underscores."]);
        exit;
    }
    if($firstName === "" || $lastName === "" || $dateOfBirth === "" || $gender === "" || $parentContact === "" || $parentName === "" || $relationship === "" || $address === "") {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    $currentDate = date('Y-m-d');
    if($dateOfBirth >= $currentDate) {
        echo json_encode(["success" => false, "message" => "Date of birth must be in the past."]);
        exit;
    }

    // check duplicates
    $checkUserQuery = "SELECT * FROM tbl_account WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($checkUserQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "Username or email already exists."]);
        exit;
    }

    $role = isset($_POST["role"]) ? $_POST["role"] : "Student";

    // insert account
    $insert_tbl_account = "INSERT INTO tbl_account (username, email, password, role, enrollment_status) VALUES (?, ?, ?, ?, 'Newly Registered')";
    $stmt = $conn->prepare($insert_tbl_account);
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    $stmt->execute();

    if($stmt->affected_rows > 0) {
        $account_id = $stmt->insert_id;

        // insert personal details
        $insert_query_details = "INSERT INTO tbl_personal_details (acc_id, first_name, middle_name, last_name, date_of_birth, gender, address) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_personal = $conn->prepare($insert_query_details);
        $stmt_personal->bind_param("issssss", $account_id, $firstName, $middleName, $lastName, $dateOfBirth, $gender, $address);
        $stmt_personal->execute();

        $personal_id = $stmt_personal->insert_id;

        // insert parent details
        $insert_query_parent = "INSERT INTO tbl_parents_details (child_id, parent_full_name, contact_num, relationship) VALUES (?, ?, ?, ?)";
        $stmt_parent = $conn->prepare($insert_query_parent);
        $stmt_parent->bind_param("isss", $personal_id, $parentName, $parentContact, $relationship);
        $stmt_parent->execute();

        echo json_encode(["success" => true, "message" => "Registration successful."]);
    } else {
        echo json_encode(["success" => false, "message" => "Registration failed."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "All fields are required.", "postData" => $_POST]);
}
?>
