<?php
include 'koneksi.php';
// Establishing Connection with Server by passing server_name, user_id and password as a parameter
// $connection = mysqli_connect("192.168.132.130", "root", "123456789","doc");
//include ('koneksi.php');
// Selecting Database
//$db = mysql_select_db("doc", $connection);
session_start();// Starting Session
// Storing Session
$user_check=$_SESSION['username'];
// SQL Query To Fetch Complete Information Of User
$ses_sql=mysqli_query($link, "select * from users where username='$user_check'");
$row = mysqli_fetch_array($ses_sql);
$name =$row['name'];
$state =$row['state'];
$nrp=$row['username'];
$sec=$row['section'];
$email=$row['email'];
$pass=$row['password'];
$nrp2=$row['username'];
$pass2=$row['password'];
if(!isset($name)){
mysqli_close($link); // Closing Connection
header('Location: login.php'); // Redirecting To Home Page
}
?>