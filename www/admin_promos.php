<?php
session_start();
require 'db.php';

// Sécurité : Admin seulement
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";

// --- TRAITEMENT DES FORMULAIRES ---

// 1. Création d'une nouvelle promotion
if (isset($_POST['action']) && $_POST['action'] == 'create_promo') {
    $nom = htmlspecialchars($_POST['nom']);
    $annee = intval($_POST['annee']);
    $date_fin = $_POST['date_suppression'];

    if (!empty($nom) && !empty($annee) && !empty($date_fin)) {
        $sql = "INSERT INTO promotions (nom, annee, date_suppression) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$nom, $annee, $date_fin])) {
            $msg = "<div class='alert alert-success'>Promotion <strong>$nom</strong> créée !</div>";
        } else {
            $msg = "<div class='alert alert-error'>Erreur lors de la création.</div>";
        }
    }
}

// 2. Inscription d'un élève dans une promo
if (isset($_POST['action']) && $_POST['action'] == 'add_student') {
    $promo_id = intval($_POST['promotion_id']);
    $user_id = intval($_POST['user_id']);

    // Vérifier si déjà inscrit pour éviter les doublons
    $check = $pdo->prepare("SELECT * FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?");
    $check->execute([$user_id, $promo_id]);

    if ($check->rowCount() == 0) {
        $sql = "INSERT INTO inscriptions (utilisateur_id, promotion_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$user_id, $promo_id])) {
            $msg = "<div class='alert alert-success'>Élève inscrit avec succès !</div>";
        }
    } else {
        $msg = "<div class='alert alert-error'>Cet élève est déjà dans cette promotion.</div>";
    }
}

// 3. Suppression d'une promo (Optionnel mais utile)
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([$id_del]);
    header("Location: admin_promos.php"); // On recharge pour nettoyer l'URL
    exit();
}

// --- RÉCUPÉRATION DES DONNÉES ---

// Liste des promos existantes (avec le nombre d'élèves inscrits)
$sql_promos = "
    SELECT p.*, COUNT(i.utilisateur_id) as nb_eleves 
    FROM promotions p 
    LEFT JOIN inscriptions i ON p.id = i.promotion_id 
    GROUP BY p.id 
    ORDER BY p.annee DESC, p.created_at DESC";
$promos = $pdo->query($sql_promos)->fetchAll();

// Liste des élèves (pour le menu déroulant) - On exclut les admins pour ne pas polluer
$users = $pdo->query("SELECT id, nom, prenom, email FROM utilisateurs WHERE is_admin = 0 ORDER BY nom ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Promotions - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Petit ajustement pour aligner les formulaires côte à côte sur grand écran */
        .forms-container { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-box { flex: 1; min-width: 300px; }
        
        /* Style table simple pour la liste */
        .table-promos { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table-promos th, .table-promos td { 
            padding: 12px; text-align: left; border-bottom: 1px solid var(--border); 
            color: var(--text);
        }
        .table-promos th { background-color: var(--card-bg); font-weight: bold; }
        .btn-small-danger { 
            color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; 
            border-radius: 4px; text-decoration: none; font-size: 0.8rem; 
        }
        .btn-small-danger:hover { background: #e74c3c; color: white; }
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
        <h1>Gestion des Promotions</h1>
        <?= $msg ?>

        <div class="forms-container">
            <div class="auth-box form-box" style="margin-top:0;">
                <h3><i class="fas fa-plus-circle"></i> Nouvelle Promotion</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_promo">
                    
                    <label>Nom de la promotion</label>
                    <input type="text" name="nom" placeholder="Ex: Promo Débutant 2025" required>

                    <label>Année</label>
                    <input type="number" name="annee" value="<?= date('Y') ?>" required>

                    <label>Date de fin d'accès (Suppression auto)</label>
                    <input type="date" name="date_suppression" required>

                    <button type="submit" class="btn-main">Créer la promo</button>
                </form>
            </div>

            <div class="auth-box form-box" style="margin-top:0;">
                <h3><i class="fas fa-user-plus"></i> Inscrire un élève</h3>
                
                <?php if(empty($promos)): ?>
                    <p style="color:var(--text-muted)">Créez d'abord une promotion.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_student">
                        
                        <label>Choisir la promotion</label>
                        <select name="promotion_id" required>
                            <?php foreach($promos as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label>Choisir l'élève</label>
                        <select name="user_id" required>
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?> (<?= $u['email'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn-main" style="background:#27ae60;">Inscrire l'élève</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <h2 style="margin-top:40px;">Promotions actives</h2>
        <div class="card" style="padding:0; overflow-x:auto;">
            <table class="table-promos">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Année</th>
                        <th>Fin d'accès</th>
                        <th>Élèves inscrits</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promos as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                        <td><?= $p['annee'] ?></td>
                        <td><?= date('d/m/Y', strtotime($p['date_suppression'])) ?></td>
                        <td><span class="badge pdf"><?= $p['nb_eleves'] ?> élèves</span></td>
                        <td>
                            <a href="admin_promos.php?delete=<?= $p['id'] ?>" 
                               class="btn-small-danger"
                               onclick="return confirm('Attention : Supprimer la promo supprimera aussi tous les accès aux cours pour ces élèves. Continuer ?');">
                               <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($promos)): ?>
                <div style="padding:20px; text-align:center; color:var(--text-muted);">Aucune promotion pour l'instant.</div>
            <?php endif; ?>
        </div>

    </div>
    <script src="script.js"></script>
</body>
</html>