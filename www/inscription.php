<?php
session_start();
require 'db.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($pass != $confirm) {
        $msg = "<div class='alert alert-error'>Les mots de passe ne correspondent pas.</div>";
    } else {
        // Vérif si email existe
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $msg = "<div class='alert alert-error'>Cet email est déjà utilisé.</div>";
        } else {
            // Création compte
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO utilisateurs (nom, prenom, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nom, $prenom, $email, $hash])) {
                header("Location: connexion.php?new=1");
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Rucher École</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-box">
        <h2 style="text-align:center">Inscription</h2>
        <?= $msg ?>
        <form method="POST">
            <div class="form-group"><input type="text" name="nom" placeholder="Nom" required></div>
            <div class="form-group"><input type="text" name="prenom" placeholder="Prénom" required></div>
            <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
            <div class="form-group"><input type="password" name="password" placeholder="Mot de passe" required></div>
            <div class="form-group"><input type="password" name="confirm" placeholder="Confirmer mot de passe" required></div>
            <button type="submit" class="btn-main">Créer mon compte</button>
        </form>
        <p style="text-align:center; margin-top:15px;">
            <a href="connexion.php">J'ai déjà un compte</a>
        </p>
    </div>
</body>
</html>
