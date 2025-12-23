<?php
session_start();
require 'db.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { header("Location: index.php"); exit(); }

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = htmlspecialchars($_POST['titre']);
    $desc = htmlspecialchars($_POST['description']);
    $type = $_POST['type'];
    
    // Upload Fichier
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
        $upload_dir = '../protected_uploads/'; // Dossier sécurisé hors de www
        $ext = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
        $server_name = uniqid('cours_') . '.' . $ext;
        
        $allowed = ['pdf', 'mp4', 'mov', 'avi'];
        if (in_array(strtolower($ext), $allowed)) {
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_dir . $server_name)) {
                $sql = "INSERT INTO cours (titre, description, nom_fichier_serveur, nom_fichier_original, type_contenu, auteur_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $desc, $server_name, $_FILES['fichier']['name'], $type, $_SESSION['user_id']]);
                $msg = "<div style='color:#2ecc71; margin-bottom:15px;'>Cours ajouté avec succès !</div>";
            } else {
                $msg = "<div style='color:#e74c3c; margin-bottom:15px;'>Erreur d'écriture fichier (Permissions).</div>";
            }
        } else {
            $msg = "<div style='color:#e74c3c; margin-bottom:15px;'>Format non autorisé.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout Cours - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="admin_dashboard.php" class="logo"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme"><i class="fas fa-sun"></i></button>
        </div>
    </header>

    <div class="container">
        <div class="auth-box" style="max-width:600px; margin-top:20px;">
            <h2>Ajouter un contenu</h2>
            <?= $msg ?>
            <form method="POST" enctype="multipart/form-data">
                <label>Titre</label>
                <input type="text" name="titre" required>

                <label>Description</label>
                <textarea name="description" rows="4"></textarea>

                <label>Type</label>
                <select name="type">
                    <option value="pdf">Document PDF</option>
                    <option value="video">Vidéo (MP4)</option>
                </select>

                <label>Fichier (Max 500Mo)</label>
                <input type="file" name="fichier" required>

                <button type="submit" class="btn-main">Mettre en ligne</button>
            </form>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>