<?php
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "enrollment_itpm";

    $conn = new mysqli($server, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


?>