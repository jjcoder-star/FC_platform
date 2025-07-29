<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "futurecoders_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
