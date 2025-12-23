<?php
session_start();
require 'db.php';

// Sécurité Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";

// --- 1. TRAITEMENT : UPLOAD NOUVEAU COURS ---
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
                $sql = "INSERT INTO cours (titre, description, nom_fichier_serveur, nom_fichier_original, type_contenu, auteur_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $desc, $server_name, $_FILES['fichier']['name'], $type, $_SESSION['user_id']]);
                $msg = "<div class='alert alert-success'>Nouveau cours ajouté à la bibliothèque !</div>";
            } else {
                $msg = "<div class='alert alert-error'>Erreur de permission sur le dossier d'upload.</div>";
            }
        } else {
            $msg = "<div class='alert alert-error'>Format non autorisé.</div>";
        }
    }
}

// --- 2. TRAITEMENT : ATTRIBUER COURS -> PROMO ---
if (isset($_POST['action']) && $_POST['action'] == 'assign_course') {
    $cours_id = intval($_POST['cours_id']);
    $promo_id = intval($_POST['promo_id']);
    $date_ouverture = $_POST['date_ouverture'];

    // Vérifier si le lien existe déjà
    $check = $pdo->prepare("SELECT * FROM cours_promotions WHERE cours_id = ? AND promotion_id = ?");
    $check->execute([$cours_id, $promo_id]);

    if ($check->rowCount() == 0) {
        $sql = "INSERT INTO cours_promotions (cours_id, promotion_id, date_ouverture) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$cours_id, $promo_id, $date_ouverture])) {
            $msg = "<div class='alert alert-success'>Cours attribué à la promotion !</div>";
        }
    } else {
        $msg = "<div class='alert alert-error'>Ce cours est déjà attribué à cette promotion.</div>";
    }
}

// --- 3. TRAITEMENT : DETACHER COURS (POUBELLE) ---
if (isset($_GET['detach_c']) && isset($_GET['detach_p'])) {
    $c_id = intval($_GET['detach_c']);
    $p_id = intval($_GET['detach_p']);
    
    $sql = "DELETE FROM cours_promotions WHERE cours_id = ? AND promotion_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$c_id, $p_id]);
    
    header("Location: admin_cours.php"); // Nettoyage URL
    exit();
}

// --- 4. RÉCUPÉRATION DES DONNÉES ---

// Liste des cours pour le menu déroulant
$courses_list = $pdo->query("SELECT id, titre FROM cours ORDER BY date_creation DESC")->fetchAll();

// Liste des promos pour le menu déroulant
$promos_list = $pdo->query("SELECT id, nom FROM promotions ORDER BY annee DESC")->fetchAll();

// Liste des attributions actuelles (Tableau du bas)
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
    <title>Gestion des Cours - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .forms-wrapper { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 40px; }
        .form-column { flex: 1; min-width: 300px; }
        
        /* Style Table */
        .table-admin { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-admin th, .table-admin td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); color: var(--text); }
        .table-admin th { background: var(--card-bg); font-weight: bold; }
        
        .btn-trash { 
            color: #e74c3c; cursor: pointer; text-decoration: none; padding: 5px; border-radius: 4px; border: 1px solid transparent; 
        }
        .btn-trash:hover { background: rgba(231, 76, 60, 0.1); border-color: #e74c3c; }
    </style>
</head>
<body>
    <header>
        <a href="admin_dashboard.php" class="logo"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme"><i class="fas fa-sun"></i></button>
        </div>
    </header>

    <div class="container">
        <h1>Gestion du contenu pédagogique</h1>
        <?= $msg ?>
        
        <div class="forms-wrapper">
            
            <div class="auth-box form-column" style="margin-top:0;">
                <h3><i class="fas fa-cloud-upload-alt"></i> 1. Uploader un nouveau cours</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_course">
                    
                    <label>Titre</label>
                    <input type="text" name="titre" required>

                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>

                    <label>Type & Fichier</label>
                    <div style="display:flex; gap:10px;">
                        <select name="type" style="width:100px;">
                            <option value="pdf">PDF</option>
                            <option value="video">Vidéo</option>
                        </select>
                        <input type="file" name="fichier" required>
                    </div>

                    <button type="submit" class="btn-main">Mettre en ligne</button>
                </form>
            </div>

            <div class="auth-box form-column" style="margin-top:0; border-top: 4px solid var(--accent);">
                <h3><i class="fas fa-link"></i> 2. Attribuer à une promotion</h3>
                
                <?php if(empty($courses_list) || empty($promos_list)): ?>
                    <p style="color:var(--text-muted)">Il faut au moins un cours et une promotion.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_course">
                        
                        <label>Choisir le cours</label>
                        <select name="cours_id" required>
                            <?php foreach($courses_list as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['titre']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Choisir la promotion</label>
                        <select name="promo_id" required>
                            <?php foreach($promos_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Disponible à partir du :</label>
                        <input type="datetime-local" name="date_ouverture" value="<?= date('Y-m-d\TH:i') ?>" required>

                        <button type="submit" class="btn-main" style="background:#27ae60;">Valider l'attribution</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="padding:0; overflow-x:auto;">
            <div style="padding:15px; background:var(--primary); color:var(--bg); font-weight:bold;">
                <i class="fas fa-list"></i> Disponibilité des cours par promotion
            </div>
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Promotion</th>
                        <th>Cours</th>
                        <th>Date d'ouverture</th>
                        <th style="text-align:center;">Retirer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['promo_nom']) ?></strong></td>
                        <td><?= htmlspecialchars($a['cours_titre']) ?></td>
                        <td>
                            <?php 
                                $date = new DateTime($a['date_ouverture']);
                                echo $date->format('d/m/Y H:i');
                                if ($date > new DateTime()) echo " <small style='color:orange'>(Futur)</small>";
                            ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="admin_cours.php?detach_c=<?= $a['cours_id'] ?>&detach_p=<?= $a['promotion_id'] ?>" 
                               class="btn-trash" 
                               onclick="return confirm('Voulez-vous retirer ce cours de cette promotion ?');"
                               title="Retirer l'accès">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($assignments)): ?>
                <div style="padding:20px; text-align:center; color:var(--text-muted)">Aucune attribution pour le moment.</div>
            <?php endif; ?>
        </div>

    </div>
    <script src="script.js"></script>
</body>
</html>