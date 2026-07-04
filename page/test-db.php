<?php
header('Content-Type: text/plain');
include '../includes/config.php';

echo "=== DB Connection Info ===\n";
echo "Host: " . $host . "\n";
echo "DB Name: " . $dbname . "\n";
echo "Port: " . $port . "\n";
echo "Connection status: " . ($conn ? "Connected successfully" : "Connection failed") . "\n\n";

if ($conn) {
    echo "=== Tables in $dbname ===\n";
    $q = mysqli_query($conn, "SHOW TABLES");
    if ($q) {
        while ($r = mysqli_fetch_row($q)) {
            echo "- " . $r[0] . "\n";
        }
    } else {
        echo "Error listing tables: " . mysqli_error($conn) . "\n";
    }
    echo "\n";

    echo "=== Users count ===\n";
    $q = mysqli_query($conn, "SELECT COUNT(*) as c FROM utilisateurs");
    if ($q) {
        $r = mysqli_fetch_assoc($q);
        echo "Total users: " . $r['c'] . "\n";
    } else {
        echo "Error counting users: " . mysqli_error($conn) . "\n";
    }
    echo "\n";

    echo "=== Last 10 Users ===\n";
    $q = mysqli_query($conn, "SELECT id, prenom, nom, email, type_compte, statut, date_creation FROM utilisateurs ORDER BY id DESC LIMIT 10");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            echo "ID: {$r['id']} | Name: {$r['prenom']} {$r['nom']} | Email: {$r['email']} | Role: {$r['type_compte']} | Status: {$r['statut']} | Created: {$r['date_creation']}\n";
        }
    } else {
        echo "Error fetching users: " . mysqli_error($conn) . "\n";
    }
}
?>
