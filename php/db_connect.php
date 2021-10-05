<?php


    $hostname = "localhost";
    $user = "";
    $pwd = "";
    $db = "";

    $con = mysqli_connect($hostname, $user, $pwd, $db);
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        exit();
    }
    
?>
