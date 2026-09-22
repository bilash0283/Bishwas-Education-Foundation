<?php 
    $host = "localhost";
    $user = "root";          
    $pass = "";              
    $dbname = "bishwas";     

    $db = mysqli_connect($host, $user, $pass, $dbname);

    if (!$db) {
        die("Database connection failed: " . mysqli_connect_error());
    }

?>