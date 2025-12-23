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
    <title>Connexion - Rucher École</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-box">
        <h2 style="text-align:center">Espace Membre</h2>
        <?= $msg ?>
        <form method="POST">
            <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
            <div class="form-group"><input type="password" name="password" placeholder="Mot de passe" required></div>
            <button type="submit" class="btn-main">Se connecter</button>
        </form>
        <p style="text-align:center; margin-top:15px;">
            <a href="inscription.php">S'inscrire à l'école</a>
        </p>
    </div>
</body>
</html>
