<?php
session_start();
require 'db.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) { header("Location: index.php"); exit(); }
$msg = "";

// 1. Création Promo
if (isset($_POST['action']) && $_POST['action'] == 'create_promo') {
    $nom = htmlspecialchars($_POST['nom']);
    $annee = intval($_POST['annee']);
    $date_fin = $_POST['date_suppression'];
    if (!empty($nom)) {
        $pdo->prepare("INSERT INTO promotions (nom, annee, date_suppression) VALUES (?, ?, ?)")->execute([$nom, $annee, $date_fin]);
        $msg = "<div class='alert alert-success'>Promo créée !</div>";
    }
}
// 2. Inscription Élève
if (isset($_POST['action']) && $_POST['action'] == 'add_student') {
    $promo_id = intval($_POST['promotion_id']);
    $user_id = intval($_POST['user_id']);
    $check = $pdo->prepare("SELECT * FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?");
    $check->execute([$user_id, $promo_id]);
    if ($check->rowCount() == 0) {
        $pdo->prepare("INSERT INTO inscriptions (utilisateur_id, promotion_id) VALUES (?, ?)")->execute([$user_id, $promo_id]);
        $msg = "<div class='alert alert-success'>Élève inscrit !</div>";
    } else {
        $msg = "<div class='alert alert-error'>Déjà inscrit.</div>";
    }
}
// 3. Suppression
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM promotions WHERE id = ?")->execute([intval($_GET['delete'])]);
    header("Location: admin_promos.php"); exit();
}

$promos = $pdo->query("SELECT p.*, COUNT(i.utilisateur_id) as nb_eleves FROM promotions p LEFT JOIN inscriptions i ON p.id = i.promotion_id GROUP BY p.id ORDER BY p.annee DESC")->fetchAll();
$users = $pdo->query("SELECT id, nom, prenom, email FROM utilisateurs WHERE is_admin = 0 ORDER BY nom ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Promos - Admin</title>
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
        <h1>Promotions</h1>
        <?= $msg ?>

        <div class="responsive-wrapper">
            <div class="auth-box responsive-col">
                <h3><i class="fas fa-plus-circle"></i> Créer Promo</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_promo">
                    <label>Nom</label> <input type="text" name="nom" placeholder="Ex: Promo 2025" required>
                    <label>Année</label> <input type="number" name="annee" value="<?= date('Y') ?>" required>
                    <label>Fin d'accès</label> <input type="date" name="date_suppression" required>
                    <button type="submit" class="btn-main">Créer</button>
                </form>
            </div>

            <div class="auth-box responsive-col">
                <h3><i class="fas fa-user-plus"></i> Inscrire Élève</h3>
                <?php if(empty($promos)): ?>
                    <p>Créez une promo d'abord.</p>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_student">
                        <label>Promo</label>
                        <select name="promotion_id">
                            <?php foreach($promos as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option><?php endforeach; ?>
                        </select>
                        <label>Élève</label>
                        <select name="user_id">
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-main" style="background:var(--success);">Inscrire</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <h2>Liste Promos</h2>
        <div class="card" style="padding:0;">
            <div class="table-responsive">
                <table class="table-admin">
                    <thead><tr><th>Nom</th><th>Fin</th><th>Élèves</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($promos as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($p['date_suppression'])) ?></td>
                            <td><span class="badge pdf"><?= $p['nb_eleves'] ?></span></td>
                            <td>
                                <a href="admin_promos.php?delete=<?= $p['id'] ?>" style="color:var(--danger);" onclick="return confirm('Supprimer ?');"><i class="fas fa-trash"></i></a>
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