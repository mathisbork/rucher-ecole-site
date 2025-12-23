<?php
session_start();
require 'db.php';

// Si pas connecté ou pas d'ID, on renvoie à l'accueil
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$cours_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// VÉRIFICATION STRICTE DES DROITS
// L'élève a-t-il le droit de voir ce cours via une de ses promos ?
$sql = "
    SELECT c.*, u.nom as auteur_nom, u.prenom as auteur_prenom 
    FROM cours c
    JOIN cours_promotions cp ON c.id = cp.cours_id
    JOIN inscriptions i ON cp.promotion_id = i.promotion_id
    LEFT JOIN utilisateurs u ON c.auteur_id = u.id
    WHERE c.id = ? AND i.utilisateur_id = ? AND cp.date_ouverture <= NOW()
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cours_id, $user_id]);
$cours = $stmt->fetch();

if (!$cours) {
    die("Accès refusé ou cours indisponible.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?= htmlspecialchars($cours['titre']) ?> - Lecture</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-arrow-left"></i> Retour aux cours</a>
        <div class="nav-links">
            <button id="theme-toggle" class="btn-toggle-theme"><i class="fas fa-sun"></i></button>
            <span style="font-weight:bold; display:none; @media(min-width:600px){display:inline;}">
                Module de formation
            </span>
        </div>
    </header>

    <div class="container">
        
        <div class="lecture-container">
            
            <div class="player-wrapper">
                <?php if ($cours['type_contenu'] == 'video'): ?>
                    
                    <video controls controlsList="nodownload" oncontextmenu="return false;">
                        <source src="stream.php?id=<?= $cours['id'] ?>" type="video/mp4">
                        Votre navigateur ne supporte pas la vidéo.
                    </video>
                
                <?php elseif ($cours['type_contenu'] == 'pdf'): ?>
                    
                    <iframe src="stream.php?id=<?= $cours['id'] ?>#toolbar=0&view=FitH" class="pdf-viewer"></iframe>
                    
                <?php endif; ?>
            </div>

            <div class="lecture-info">
                <span class="badge <?= $cours['type_contenu'] ?>"><?= strtoupper($cours['type_contenu']) ?></span>
                
                <h1 class="lecture-title"><?= htmlspecialchars($cours['titre']) ?></h1>
                
                <div class="lecture-meta">
                    <i class="fas fa-calendar-alt"></i> Mis en ligne le <?= date('d/m/Y', strtotime($cours['date_creation'])) ?>
                    <?php if($cours['auteur_nom']): ?>
                        &bull; <i class="fas fa-user-tie"></i> Par <?= htmlspecialchars($cours['auteur_prenom'] . ' ' . $cours['auteur_nom']) ?>
                    <?php endif; ?>
                </div>

                <div class="card-desc" style="color:var(--text); font-size:1rem; line-height:1.6;">
                    <?= nl2br(htmlspecialchars($cours['description'])) ?>
                </div>
            </div>

        </div>

    </div>
    <script src="script.js"></script>
</body>
</html>