<?php
// ============================================================
//  CONFIG BASE DE DONNÉES — Immo-Location
//  Variables d'environnement > valeurs locales
// ============================================================

$host     = getenv('DB_HOST')     ?: '127.0.0.1';
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname   = getenv('DB_NAME')     ?: 'immo_location';
$port     = (int)(getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($host, $user, $password, $dbname, $port);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>