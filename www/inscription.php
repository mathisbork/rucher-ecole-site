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
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $msg = "<div class='alert alert-error'>Cet email est déjà utilisé.</div>";
        } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Rucher École</title>
    <link rel="stylesheet" href="style.css?v=AUTH_FINAL">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <div style="font-size: 3rem; color: var(--success); margin-bottom: 15px;">
            <i class="fas fa-user-plus"></i>
        </div>

        <h2>Inscription</h2>
        <p class="auth-subtitle">Rejoignez la communauté du Rucher</p>

        <?= $msg ?>

        <form method="POST">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="prenom" placeholder="Prénom" required>
                <input type="text" name="nom" placeholder="Nom" required>
            </div>

            <input type="email" name="email" placeholder="Email professionnel ou personnel" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="password" name="confirm" placeholder="Confirmer mot de passe" required>

            <button type="submit" class="btn-main" style="background-color: var(--success);">
                Créer mon compte
            </button>
        </form>

        <a href="connexion.php" class="auth-link">Déjà inscrit ? <strong>Se connecter</strong></a>
    </div>

</body>
</html>