
<?php
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "bishwas";
$port   = '3307'; 

// PHP 8.1+ এ mysqli ডিফল্টে exception ছোঁড়ে, তাই try/catch
try {
    $db = mysqli_connect($host, $user, $pass, $dbname, $port);

    // বাংলা টেক্সটের জন্য সবচেয়ে গুরুত্বপূর্ণ লাইন
    mysqli_set_charset($db, "utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed.");
}

?>


<!-- live server db connection  -->

<?php
// $host   = "localhost";
// $user   = "global19_bishwas";
// $pass   = "Contacts@123";
// $dbname = "global19_bishwas";

// // PHP 8.1+ এ mysqli ডিফল্টে exception ছোঁড়ে, তাই try/catch
// try {
//     $db = mysqli_connect($host, $user, $pass, $dbname);

//     // বাংলা টেক্সটের জন্য সবচেয়ে গুরুত্বপূর্ণ লাইন
//     mysqli_set_charset($db, "utf8mb4");
// } catch (mysqli_sql_exception $e) {
//     error_log("Database connection failed: " . $e->getMessage());
//     die("Database connection failed.");
// }

?>