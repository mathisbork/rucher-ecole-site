<?php
session_start();
require 'db.php';

// Sécurité Admin stricte
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - Rucher École</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-arrow-left"></i> Retour Site</a>
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme"><i class="fas fa-sun"></i></button>
            <span style="color:var(--accent); font-weight:bold;">MODE ADMINISTRATEUR</span>
        </div>
    </header>

    <div class="container">
        <h1>Gestion de l'École</h1>
        
        <div class="grid-admin">
            <div class="card">
                <div class="card-img"><i class="fas fa-book-open"></i></div>
                <div class="card-body">
                    <h3>Bibliothèque</h3>
                    <p class="card-desc">Ajouter des vidéos ou des PDF. Modifier les descriptions.</p>
                    <a href="admin_cours.php" class="btn-main">Gérer les cours</a>
                </div>
            </div>

            <div class="card">
                <div class="card-img"><i class="fas fa-graduation-cap"></i></div>
                <div class="card-body">
                    <h3>Promotions</h3>
                    <p class="card-desc">Créer les promos (ex: 2025) et ouvrir l'accès aux cours.</p>
                    <a href="#" class="btn-main" style="opacity:0.5">Bientôt disponible</a>
                </div>
            </div>

            <div class="card">
                <div class="card-img"><i class="fas fa-users"></i></div>
                <div class="card-body">
                    <h3>Élèves</h3>
                    <p class="card-desc">Valider les inscriptions manuelles et gérer les accès.</p>
                    <a href="#" class="btn-main" style="opacity:0.5">Bientôt disponible</a>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>