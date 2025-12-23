<?php
session_start();
require 'db.php';

// Sécurité Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";

// --- 1. TRAITEMENT : UPLOAD ---
if (isset($_POST['action']) && $_POST['action'] == 'upload_course') {
    $titre = htmlspecialchars($_POST['titre']);
    $desc = htmlspecialchars($_POST['description']);
    $type = $_POST['type'];
    
    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] == 0) {
        $upload_dir = '../protected_uploads/';
        $ext = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
        $server_name = uniqid('cours_') . '.' . $ext;
        $allowed = ['pdf', 'mp4', 'mov', 'avi'];

        if (in_array(strtolower($ext), $allowed)) {
            if (move_uploaded_file($_FILES['fichier']['tmp_name'], $upload_dir . $server_name)) {
                // CORRECTION SQL ICI :
                $sql = "INSERT INTO cours (titre, description, nom_fichier_serveur, nom_fichier_original, type_contenu, auteur_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $desc, $server_name, $_FILES['fichier']['name'], $type, $_SESSION['user_id']]);
                $msg = "<div class='alert alert-success'>Cours ajouté !</div>";
            } else {
                $msg = "<div class='alert alert-error'>Erreur permission dossier.</div>";
            }
        } else {
            $msg = "<div class='alert alert-error'>Format non autorisé.</div>";
        }
    }
}

// --- 2. TRAITEMENT : ATTRIBUTION ---
if (isset($_POST['action']) && $_POST['action'] == 'assign_course') {
    $cours_id = intval($_POST['cours_id']);
    $promo_id = intval($_POST['promo_id']);
    $date_ouverture = $_POST['date_ouverture'];

    $check = $pdo->prepare("SELECT * FROM cours_promotions WHERE cours_id = ? AND promotion_id = ?");
    $check->execute([$cours_id, $promo_id]);

    if ($check->rowCount() == 0) {
        $sql = "INSERT INTO cours_promotions (cours_id, promotion_id, date_ouverture) VALUES (?, ?, ?)";
        $pdo->prepare($sql)->execute([$cours_id, $promo_id, $date_ouverture]);
        $msg = "<div class='alert alert-success'>Cours attribué !</div>";
    } else {
        $msg = "<div class='alert alert-error'>Déjà attribué.</div>";
    }
}

// --- 3. TRAITEMENT : POUBELLE ---
if (isset($_GET['detach_c']) && isset($_GET['detach_p'])) {
    $c_id = intval($_GET['detach_c']);
    $p_id = intval($_GET['detach_p']);
    $pdo->prepare("DELETE FROM cours_promotions WHERE cours_id = ? AND promotion_id = ?")->execute([$c_id, $p_id]);
    header("Location: admin_cours.php"); exit();
}

// --- DONNÉES (CORRECTION SQL created_at -> date_creation) ---
$courses_list = $pdo->query("SELECT id, titre FROM cours ORDER BY date_creation DESC")->fetchAll();
$promos_list = $pdo->query("SELECT id, nom FROM promotions ORDER BY annee DESC")->fetchAll();

$sql_assignments = "
    SELECT cp.*, c.titre as cours_titre, p.nom as promo_nom 
    FROM cours_promotions cp
    JOIN cours c ON cp.cours_id = c.id
    JOIN promotions p ON cp.promotion_id = p.id
    ORDER BY cp.date_ouverture DESC
";
$assignments = $pdo->query($sql_assignments)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Gestion Cours - Admin</title>
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
        <h1>Gestion Contenu</h1>
        <?= $msg ?>
        
        <div class="responsive-wrapper">
            
            <div class="auth-box responsive-col">
                <h3><i class="fas fa-cloud-upload-alt"></i> 1. Nouveau Cours</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_course">
                    <label>Titre</label> <input type="text" name="titre" required>
                    <label>Description</label> <textarea name="description" rows="2"></textarea>
                    
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1;"><label>Type</label>
                        <select name="type"><option value="pdf">PDF</option><option value="video">Vidéo</option></select></div>
                        <div style="flex:2;"><label>Fichier</label>
                        <input type="file" name="fichier" required></div>
                    </div>
                    <button type="submit" class="btn-main">Uploader</button>
                </form>
            </div>

            <div class="auth-box responsive-col" style="border-top: 4px solid var(--accent);">
                <h3><i class="fas fa-link"></i> 2. Attribuer à Promo</h3>
                <?php if(empty($courses_list) || empty($promos_list)): ?>
                    <p style="color:var(--text-muted)">Il faut 1 cours et 1 promo.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_course">
                        <label>Cours</label>
                        <select name="cours_id">
                            <?php foreach($courses_list as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['titre']) ?></option><?php endforeach; ?>
                        </select>
                        <label>Promo</label>
                        <select name="promo_id">
                            <?php foreach($promos_list as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
                        </select>
                        <label>Date ouverture</label>
                        <input type="datetime-local" name="date_ouverture" value="<?= date('Y-m-d\TH:i') ?>">
                        <button type="submit" class="btn-main" style="background:var(--success);">Valider</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:0;">
            <div style="padding:15px; background:var(--primary); color:var(--bg); font-weight:bold;">
                <i class="fas fa-list"></i> Accès Actifs
            </div>
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr><th>Promo</th><th>Cours</th><th>Date</th><th style="text-align:center;">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['promo_nom']) ?></strong></td>
                            <td><?= htmlspecialchars($a['cours_titre']) ?></td>
                            <td><?= (new DateTime($a['date_ouverture']))->format('d/m/Y H:i') ?></td>
                            <td style="text-align:center;">
                                <a href="admin_cours.php?detach_c=<?= $a['cours_id'] ?>&detach_p=<?= $a['promotion_id'] ?>" 
                                   style="color:var(--danger);" onclick="return confirm('Retirer accès ?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>