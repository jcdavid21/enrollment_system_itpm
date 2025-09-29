<?php 
    include_once "./config.php";
    header('Content-Type: application/json');

    if(isset($_POST["loginEmail"]) && isset($_POST["loginPassword"])) {
        $loginEmail = $_POST["loginEmail"];
        $loginPassword = $_POST["loginPassword"];

        // check if email or username exists
        $checkUserQuery = "SELECT * FROM tbl_account WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($checkUserQuery);
        $stmt->bind_param("ss", $loginEmail, $loginEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if($user["enrollment_status"] == "Dropped Out") {
                echo json_encode(["success" => false, "message" => "Your account has been dropped. Please contact the administrator."]);
                exit;
            }

            if(password_verify($loginPassword, $user["password"])) {
                if($user["reg_acc_status"] == 2){
                    // Password is correct, start a session
                    session_start();
                    $_SESSION["user_id"] = $user["acc_id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["enrollment_status"] = $user["enrollment_status"];

                    echo json_encode(["success" => true, "message" => "Login successful.", "role" => $user["role"]]);
                }else{
                    echo json_encode(["success" => false, "message" => "Account not yet verified. Please wait for admin approval."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "No account found with that email or username."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Email/Username and Password are required."]);
    }

?>