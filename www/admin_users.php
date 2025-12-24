<?php
session_start();
require 'db.php';

// Sécurité Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";
$edit_user = null; // Pour stocker l'utilisateur en cours d'édition

// --- 1. TRAITEMENT : SUPPRESSION ---
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    // On empêche de se supprimer soi-même
    if ($id_del != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id_del]);
        header("Location: admin_users.php?msg=deleted"); exit();
    }
}

// --- 2. TRAITEMENT : MISE A JOUR ---
if (isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id = intval($_POST['user_id']);
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_pass = $_POST['new_password'];

    // Mise à jour infos de base
    $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?";
    $params = [$nom, $prenom, $email, $id];
    
    // Si un mot de passe est rempli, on le change aussi
    if (!empty($new_pass)) {
        $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, password = ? WHERE id = ?";
        $params = [$nom, $prenom, $email, password_hash($new_pass, PASSWORD_DEFAULT), $id];
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $msg = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
    } else {
        $msg = "<div class='alert alert-error'>Erreur lors de la mise à jour.</div>";
    }
}

// --- 3. MODE ÉDITION (Récupérer les infos) ---
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_user = $stmt->fetch();
}

// --- 4. RECHERCHE & LISTE ---
$search = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
$sql_list = "SELECT * FROM utilisateurs WHERE is_admin = 0 AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?) ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql_list);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$users = $stmt->fetchAll();

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<div class='alert alert-success'>Utilisateur supprimé définitivement.</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Élèves - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { margin-bottom: 0; }
        .action-btn { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; margin-right: 5px; font-size: 0.9rem; }
        .btn-edit { background: var(--accent); }
        .btn-del { background: var(--danger); }
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
            <div class="auth-box" style="margin-bottom: 30px; border-left: 5px solid var(--accent);">
                <h3><i class="fas fa-user-edit"></i> Modifier : <?= htmlspecialchars($edit_user['prenom']) ?></h3>
                <form method="POST" action="admin_users.php">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">

                    <div style="display:flex; gap:10px;">
                        <div style="flex:1"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit_user['prenom']) ?>" required></div>
                        <div style="flex:1"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit_user['nom']) ?>" required></div>
                    </div>

                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>

                    <label>Nouveau mot de passe (Laisser vide pour ne pas changer)</label>
                    <input type="text" name="new_password" placeholder="Ex: Rucher2025!" style="border-color: var(--accent);">

                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-main">Enregistrer</button>
                        <a href="admin_users.php" class="btn-main" style="background:var(--text-muted);">Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <form method="GET" class="search-bar">
            <input type="text" name="q" placeholder="Rechercher un élève (Nom, Email...)" value="<?= $search ?>">
            <button type="submit" class="btn-admin"><i class="fas fa-search"></i></button>
            <?php if($search): ?>
                <a href="admin_users.php" class="btn-admin" style="background:var(--danger); display:flex; align-items:center;">X</a>
            <?php endif; ?>
        </form>

        <div class="card" style="padding:0;">
            <div class="table-responsive">
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Inscrit le</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user-graduate" style="color:var(--text-muted); margin-right:5px;"></i>
                                <strong><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <a href="admin_users.php?edit=<?= $u['id'] ?>" class="action-btn btn-edit" title="Modifier / Reset Password">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="admin_users.php?delete=<?= $u['id'] ?>" class="action-btn btn-del" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet élève ? Cette action est irréversible.');"
                                   title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if(empty($users)): ?>
                <div style="padding:20px; text-align:center; color:var(--text-muted);">Aucun élève trouvé.</div>
            <?php endif; ?>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>