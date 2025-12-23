<?php
session_start();
require 'db.php';

// Sécurité Admin Stricte
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Rucher École</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-arrow-left"></i> Retour Site</a>
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme"><i class="fas fa-sun"></i></button>
            <span style="color:var(--accent); font-weight:bold; font-size:0.9rem;">ADMIN</span>
        </div>
    </header>

    <div class="container">
        <h1>Administration</h1>
        <p style="color:var(--text-muted); margin-bottom:30px;">Gérez l'école depuis votre mobile ou votre PC.</p>
        
        <div class="grid-admin">
            
            <div class="card">
                <div class="card-img" style="background:var(--accent);">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="card-body">
                    <h3>Bibliothèque</h3>
                    <p class="card-desc">Uploader des cours (PDF/Vidéo) et gérer le contenu pédagogique.</p>
                    <a href="admin_cours.php" class="btn-main">Gérer les cours</a>
                </div>
            </div>

            <div class="card">
                <div class="card-img" style="background:#27ae60;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="card-body">
                    <h3>Promotions</h3>
                    <p class="card-desc">Créer les classes (Promos) et inscrire les élèves manuellement.</p>
                    <a href="admin_promos.php" class="btn-main">Gérer les promos</a>
                </div>
            </div>

            <div class="card">
                <div class="card-img" style="background:#2980b9;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-body">
                    <h3>Élèves</h3>
                    <p class="card-desc">Liste des inscrits, modification des mots de passe.</p>
                    <a href="#" class="btn-main" style="background:var(--text-muted); cursor:not-allowed;">Bientôt disponible</a>
                </div>
            </div>

        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>