<?php
// ============================================================
//  CONFIG BASE DE DONNÉES — Immo-Location
//  Variables d'environnement > valeurs locales
//  Supporte DB_* (local) et MYSQL* (Railway)
// ============================================================

$host     = getenv('DB_HOST')     ?: getenv('MYSQLHOST')     ?: '127.0.0.1';
$user     = getenv('DB_USER')     ?: getenv('MYSQLUSER')     ?: 'root';
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$dbname   = getenv('DB_NAME')     ?: getenv('MYSQLDATABASE') ?: 'immo_location';
$port     = (int)(getenv('DB_PORT') ?: getenv('MYSQLPORT')   ?: 3306);

$conn = mysqli_connect($host, $user, $password, $dbname, $port);

if (!$conn) {
    die("Erreur de connexion : " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
?>