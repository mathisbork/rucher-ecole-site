<?php
// db.php

// Récupération des variables d'environnement de Docker (.env)
$host = getenv('MYSQL_HOST') ?: 'db';
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');
$charset = 'utf8mb4';

// Si les variables sont vides, on arrête tout (Sécurité)
if (!$db || !$user || !$pass) {
    die("Erreur critique : Configuration de la base de données manquante.");
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En production, ne jamais afficher l'erreur brute à l'utilisateur
    die("Impossible de se connecter à la base de données.");
}
?>
