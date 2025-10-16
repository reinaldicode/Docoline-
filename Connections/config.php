<?php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$hostname_config = "localhost"; 
$database_config = "doc";
$username_config = "admin";
$password_config = "asdf123!";    

$config = mysqli_connect($hostname_config, $username_config, $password_config, $database_config);

if (!$config) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($config, "utf8mb4");
?>