<?php
session_start();
require 'db.php';

// Sécurité Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";
$edit_user = null; // Utilisateur en cours d'édition

// =========================================================
// 1. TRAITEMENT DES ACTIONS
// =========================================================

// --- SUPPRESSION ÉLÈVE ---
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    if ($id_del != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id_del]);
        header("Location: admin_users.php?msg=deleted"); exit();
    }
}

// --- MISE A JOUR PROFIL (Nom, Email, Pass) ---
if (isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id = intval($_POST['user_id']);
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_pass = $_POST['new_password'];

    $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?";
    $params = [$nom, $prenom, $email, $id];
    
    if (!empty($new_pass)) {
        $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, password = ? WHERE id = ?";
        $params = [$nom, $prenom, $email, password_hash($new_pass, PASSWORD_DEFAULT), $id];
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $msg = "<div class='alert alert-success'>Profil mis à jour !</div>";
        // On reste sur la page d'édition pour voir les changements
        $_GET['edit'] = $id; 
    } else {
        $msg = "<div class='alert alert-error'>Erreur mise à jour.</div>";
    }
}

// --- AJOUTER À UNE PROMO ---
if (isset($_POST['action']) && $_POST['action'] == 'add_promo_link') {
    $u_id = intval($_POST['user_id']);
    $p_id = intval($_POST['promotion_id']);

    // Vérifier si déjà inscrit
    $check = $pdo->prepare("SELECT * FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?");
    $check->execute([$u_id, $p_id]);

    if ($check->rowCount() == 0) {
        $pdo->prepare("INSERT INTO inscriptions (utilisateur_id, promotion_id) VALUES (?, ?)")->execute([$u_id, $p_id]);
        $msg = "<div class='alert alert-success'>Élève ajouté à la promotion !</div>";
    } else {
        $msg = "<div class='alert alert-error'>Déjà inscrit dans cette promotion.</div>";
    }
    $_GET['edit'] = $u_id; // On reste en mode édition
}

// --- RETIRER D'UNE PROMO ---
if (isset($_GET['remove_promo']) && isset($_GET['user'])) {
    $p_id = intval($_GET['remove_promo']);
    $u_id = intval($_GET['user']);
    
    $pdo->prepare("DELETE FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?")->execute([$u_id, $p_id]);
    
    $msg = "<div class='alert alert-success'>Inscription retirée.</div>";
    $_GET['edit'] = $u_id; // On reste en mode édition
}

// =========================================================
// 2. MODE ÉDITION (Récupération des données)
// =========================================================
$user_promos = [];
$all_promos = [];

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    // 1. Infos de l'élève
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();

    if ($edit_user) {
        // 2. Promos actuelles de cet élève
        $sql_u_promos = "SELECT p.* FROM promotions p JOIN inscriptions i ON p.id = i.promotion_id WHERE i.utilisateur_id = ?";
        $stmt = $pdo->prepare($sql_u_promos);
        $stmt->execute([$edit_id]);
        $user_promos = $stmt->fetchAll();

        // 3. Toutes les promos (pour le menu déroulant)
        $all_promos = $pdo->query("SELECT * FROM promotions ORDER BY annee DESC")->fetchAll();
    }
}

// =========================================================
// 3. RECHERCHE & LISTE GLOBALE
// =========================================================
$search = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
$sql_list = "SELECT * FROM utilisateurs WHERE is_admin = 0 AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?) ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql_list);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$users = $stmt->fetchAll();

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') $msg = "<div class='alert alert-success'>Élève supprimé.</div>";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Élèves - Admin</title>
    <link rel="stylesheet" href="style.css?v=ADMIN_USER">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { margin-bottom: 0; }
        .action-btn { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; margin-right: 5px; font-size: 0.9rem; }
        .btn-edit { background: var(--accent); }
        .btn-del { background: var(--danger); }
        
        /* Style spécial pour la liste des promos dans l'édition */
        .promo-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--bg); border: 1px solid var(--border);
            padding: 5px 10px; border-radius: 20px; margin: 5px 5px 5px 0; font-size: 0.9rem;
        }
        .promo-tag a { color: var(--danger); text-decoration: none; font-weight: bold; }
        .promo-tag a:hover { color: #c0392b; }
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
        <h1>Gestion des Élèves</h1>
        <?= $msg ?>

        <?php if ($edit_user): ?>
            <div class="auth-box" style="margin-bottom: 30px; border-left: 5px solid var(--accent); max-width: 800px;">
                
                <div class="responsive-wrapper" style="margin-bottom:0; gap:30px;">
                    
                    <div class="responsive-col">
                        <h3><i class="fas fa-user-edit"></i> Profil : <?= htmlspecialchars($edit_user['prenom']) ?></h3>
                        <form method="POST" action="admin_users.php">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">

                            <div style="display:flex; gap:10px;">
                                <div style="flex:1"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit_user['prenom']) ?>" required></div>
                                <div style="flex:1"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit_user['nom']) ?>" required></div>
                            </div>

                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>

                            <label>Reset Mot de passe (Optionnel)</label>
                            <input type="text" name="new_password" placeholder="Nouveau mot de passe...">

                            <button type="submit" class="btn-main">Enregistrer Profil</button>
                        </form>
                    </div>

                    <div class="responsive-col" style="border-left: 1px solid var(--border); padding-left: 20px;">
                        <h3><i class="fas fa-graduation-cap"></i> Promotions</h3>
                        
                        <div style="margin-bottom: 20px;">
                            <?php if (empty($user_promos)): ?>
                                <p style="color:var(--text-muted); font-style:italic;">Aucune promotion pour l'instant.</p>
                            <?php else: ?>
                                <?php foreach($user_promos as $up): ?>
                                    <div class="promo-tag">
                                        <?= htmlspecialchars($up['nom']) ?>
                                        <a href="admin_users.php?edit=<?= $edit_user['id'] ?>&remove_promo=<?= $up['id'] ?>&user=<?= $edit_user['id'] ?>" 
                                           onclick="return confirm('Retirer l\'élève de cette promo ?');" title="Retirer">
                                           <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="admin_users.php" style="background:var(--bg); padding:15px; border-radius:8px;">
                            <input type="hidden" name="action" value="add_promo_link">
                            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
                            
                            <label style="font-size:0.9rem;">Ajouter à une promo :</label>
                            <div style="display:flex; gap:10px;">
                                <select name="promotion_id" style="margin-bottom:0;">
                                    <?php foreach($all_promos as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-main" style="width:auto; background:var(--success); padding: 10px 15px;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </form>

                        <div style="margin-top:20px; text-align:right;">
                            <a href="admin_users.php" style="color:var(--text-muted); text-decoration:none;">Fermer l'édition</a>
                        </div>
                    </div>

                </div>
            </div>
        <?php endif; ?>

        <form method="GET" class="search-bar">
            <input type="text" name="q" placeholder="Rechercher un élève..." value="<?= $search ?>">
            <button type="submit" class="btn-admin"><i class="fas fa-search"></i></button>
            <?php if($search): ?><a href="admin_users.php" class="btn-admin" style="background:var(--danger);">X</a><?php endif; ?>
        </form>

        <div class="card" style="padding:0;">
            <div class="table-responsive">
                <table class="table-admin">
                    <thead><tr><th>Élève</th><th>Email</th><th>Inscrit le</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <a href="admin_users.php?edit=<?= $u['id'] ?>" class="action-btn btn-edit"><i class="fas fa-pen"></i></a>
                                <a href="admin_users.php?delete=<?= $u['id'] ?>" class="action-btn btn-del" onclick="return confirm('Supprimer ?');"><i class="fas fa-trash"></i></a>
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