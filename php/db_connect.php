<?php


    $hostname = "localhost";
    $user = "";
    $pwd = "";
    $db = "";

    $con = mysqli_connect($hostname, $user, $pwd, $db) or die(mysqli_error());
    
    function closeConnection($con){
        mysqli_close($con);
    }

?>
