<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$cours_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Vérification stricte : L'utilisateur a-t-il le droit de voir CE cours ?
$sql = "
    SELECT c.* FROM cours c
    JOIN cours_promotions cp ON c.id = cp.cours_id
    JOIN inscriptions i ON cp.promotion_id = i.promotion_id
    WHERE c.id = ? AND i.utilisateur_id = ? AND cp.date_ouverture <= NOW()
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cours_id, $user_id]);
$cours = $stmt->fetch();

if (!$cours) {
    die("Accès refusé ou cours inexistant.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($cours['titre']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .player-container { text-align: center; background: black; padding: 20px; border-radius: 8px; }
        video, iframe { width: 100%; max-width: 900px; height: 500px; border: none; }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-arrow-left"></i> Retour aux cours</a>
        <div class="nav-links">
            <span><?= htmlspecialchars($cours['titre']) ?></span>
        </div>
    </header>

    <div class="container">
        <div class="player-container">
            <?php if ($cours['type_contenu'] == 'video'): ?>
                <video controls controlsList="nodownload" oncontextmenu="return false;">
                    <source src="stream.php?id=<?= $cours['id'] ?>" type="video/mp4">
                    Votre navigateur ne supporte pas la vidéo.
                </video>
            
            <?php elseif ($cours['type_contenu'] == 'pdf'): ?>
                <iframe src="stream.php?id=<?= $cours['id'] ?>#toolbar=0"></iframe>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px; background: white; padding: 20px; border-radius: 8px;">
            <h2>Description</h2>
            <p><?= nl2br(htmlspecialchars($cours['description'])) ?></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
