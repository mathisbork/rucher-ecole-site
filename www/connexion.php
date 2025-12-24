<?php
session_start();
require 'db.php';
$msg = "";

if (isset($_GET['new'])) $msg = "<div class='alert alert-success'>Compte créé ! Connectez-vous.</div>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header("Location: index.php");
        exit();
    } else {
        $msg = "<div class='alert alert-error'>Identifiants incorrects.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Rucher École</title>
    <link rel="stylesheet" href="style.css?v=AUTH_FINAL">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page"> <div class="auth-container">
        <div style="font-size: 3.5rem; color: var(--accent); margin-bottom: 20px;">
            <i class="fas fa-bee"></i>
        </div>

        <h2>Bienvenue</h2>
        <p class="auth-subtitle">Connectez-vous à votre espace formation</p>

        <?= $msg ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Adresse Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            
            <button type="submit" class="btn-main">Se connecter</button>
        </form>

        <a href="inscription.php" class="auth-link">Nouveau ici ? <strong>Créer un compte</strong></a>
    </div>

</body>
</html>