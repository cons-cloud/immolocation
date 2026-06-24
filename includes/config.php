<?php

$host = "localhost";
$user = "root";
$password = "";
$dbname = "immo_location";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}
?>