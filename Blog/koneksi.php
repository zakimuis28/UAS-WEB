<?php
$host = "localhost";
$username = "root";
$password = "230605110146";
$database = "dbcms";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>