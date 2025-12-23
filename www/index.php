<?php
session_start();
require 'db.php';

// Protection : Si pas connecté, on vire
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupération des cours accessibles via les promos de l'élève
// Et seulement si la date_ouverture est passée
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
    <title>Mes Cours - Rucher École</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-bee"></i> Rucher École</a>
        <nav class="nav-links">
            <span>Bonjour, <strong><?= htmlspecialchars($_SESSION['prenom']) ?></strong></span>
            <?php if($_SESSION['is_admin']): ?>
                <a href="#" style="color:var(--accent);">[Admin]</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        </nav>
    </header>

    <div class="container">
        <h1>Mes modules de formation</h1>
        
        <?php if (empty($cours)): ?>
            <div class="alert alert-error">
                Vous n'avez accès à aucun cours pour le moment.<br>
                Votre inscription à une promotion est peut-être en attente de validation.
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
                            <p class="card-desc"><?= substr(htmlspecialchars($c['description']), 0, 100) ?>...</p>
                            <a href="lecture.php?id=<?= $c['id'] ?>" class="btn-main">Accéder au cours</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>
</html>
