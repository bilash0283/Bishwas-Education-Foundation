<?php 

    // ==========================================
    // ১. ডাটাবেজ কানেকশন (PHP 7.4 Simple mysqli)
    // ==========================================
    $host = "localhost";
    $user = "root";          // ডাটাবেজ ইউজারনেম
    $pass = "";              // ডাটাবেজ পাসওয়ার্ড
    $dbname = "bishwas";     // আপনার ডাটাবেজের নাম দিন

    $db = mysqli_connect($host, $user, $pass, $dbname);

    // কানেকশন চেক
    if (!$db) {
        die("ডাটাবেজ কানেকশন ব্যর্থ হয়েছে: " . mysqli_connect_error());
    }

?>