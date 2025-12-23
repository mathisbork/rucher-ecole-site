<?php
session_start();
require 'db.php';

// Si pas connecté, direction login
if (!isset($_SESSION['user_id'])) { header("Location: connexion.php"); exit(); }

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// Récupération des cours accessibles
$sql = "
    SELECT DISTINCT c.* FROM cours c
    JOIN cours_promotions cp ON c.id = cp.cours_id
    JOIN inscriptions i ON cp.promotion_id = i.promotion_id
    WHERE i.utilisateur_id = ? 
    AND cp.date_ouverture <= NOW()
    ORDER BY c.date_creation DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cours = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - Rucher École</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-bee"></i> Rucher École</a>
        
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme" title="Thème"><i class="fas fa-sun"></i></button>

            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php" class="btn-admin"><i class="fas fa-cogs"></i> Admin</a>
            <?php endif; ?>

            <span style="font-size:0.9rem;">Bonjour, <strong><?= htmlspecialchars($_SESSION['prenom']) ?></strong></span>
            <a href="logout.php" class="btn-logout" title="Déconnexion"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <div class="container">
        <h1>Mes modules</h1>
        
        <?php if (empty($cours)): ?>
            <div class="auth-box" style="text-align:center; padding:40px;">
                <i class="fas fa-inbox" style="font-size:3rem; color:var(--text-muted); margin-bottom:20px;"></i>
                <p>Aucun cours accessible pour le moment.</p>
                <small style="color:var(--text-muted)">Votre inscription est peut-être en attente.</small>
            </div>
        <?php else: ?>
            <div class="grid-cours">
                <?php foreach ($cours as $c): ?>
                    <div class="card">
                        <div class="card-img">
                            <?php if($c['type_contenu'] == 'video'): ?>
                                <i class="fas fa-play-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-file-pdf"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <span class="badge <?= $c['type_contenu'] ?>"><?= strtoupper($c['type_contenu']) ?></span>
                            <h3 class="card-title"><?= htmlspecialchars($c['titre']) ?></h3>
                            <p class="card-desc"><?= substr(htmlspecialchars($c['description']), 0, 90) ?>...</p>
                            <a href="lecture.php?id=<?= $c['id'] ?>" class="btn-main">Ouvrir</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>