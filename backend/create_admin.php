<?php 
    require 'config.php';

    $select_admin = "SELECT * FROM tbl_account WHERE acc_id = 1";
    $stmt = $conn->prepare($select_admin);
    $stmt->execute();


    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $admin_password = $admin['password'];
    $hashedPassword = password_hash($admin_password, PASSWORD_BCRYPT);
    

    $update_admin = "UPDATE tbl_account SET password = '$hashedPassword' WHERE acc_id = 1";
    $stmt_update = $conn->prepare($update_admin);
    $stmt_update->execute();
    echo "Admin password has been hashed successfully.";


?>