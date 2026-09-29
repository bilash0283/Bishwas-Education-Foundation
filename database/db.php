<?php 
    $host = "localhost";
    $user = "root";          
    $pass = "";              
    $dbname = "bishwas";   
    $port = '3307';  

    $db = mysqli_connect($host, $user, $pass, $dbname,$port);

    if (!$db) {
        die("Database connection failed: " . mysqli_connect_error());
    }

?>