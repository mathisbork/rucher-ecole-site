<?php
session_start();
require 'db.php';

// Sécurité Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$msg = "";
$edit_user = null;

// =========================================================
// 1. TRAITEMENT DES ACTIONS
// =========================================================

// --- SUPPRESSION ---
if (isset($_GET['delete'])) {
    $id_del = intval($_GET['delete']);
    // Sécurité : Impossible de se supprimer soi-même
    if ($id_del == $_SESSION['user_id']) {
        $msg = "<div class='alert alert-error'>Vous ne pouvez pas supprimer votre propre compte !</div>";
    } else {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id_del]);
        header("Location: admin_users.php?msg=deleted"); exit();
    }
}

// --- MISE A JOUR PROFIL ---
if (isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id = intval($_POST['user_id']);
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_pass = $_POST['new_password'];
    
    // On récupère le statut admin (case à cocher) - Optionnel si vous voulez gérer ça ici
    // Pour l'instant on ne touche pas au statut admin via le formulaire simple

    $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ? WHERE id = ?";
    $params = [$nom, $prenom, $email, $id];
    
    if (!empty($new_pass)) {
        $sql = "UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, password = ? WHERE id = ?";
        $params = [$nom, $prenom, $email, password_hash($new_pass, PASSWORD_DEFAULT), $id];
    }

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        $msg = "<div class='alert alert-success'>Profil mis à jour !</div>";
        $_GET['edit'] = $id; 
    } else {
        $msg = "<div class='alert alert-error'>Erreur lors de la mise à jour.</div>";
    }
}

// --- AJOUTER À UNE PROMO (Fonctionne aussi pour les Admins maintenant) ---
if (isset($_POST['action']) && $_POST['action'] == 'add_promo_link') {
    $u_id = intval($_POST['user_id']);
    $p_id = intval($_POST['promotion_id']);

    $check = $pdo->prepare("SELECT * FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?");
    $check->execute([$u_id, $p_id]);

    if ($check->rowCount() == 0) {
        $pdo->prepare("INSERT INTO inscriptions (utilisateur_id, promotion_id) VALUES (?, ?)")->execute([$u_id, $p_id]);
        $msg = "<div class='alert alert-success'>Utilisateur ajouté à la promotion !</div>";
    } else {
        $msg = "<div class='alert alert-error'>Déjà inscrit dans cette promotion.</div>";
    }
    $_GET['edit'] = $u_id;
}

// --- RETIRER D'UNE PROMO ---
if (isset($_GET['remove_promo']) && isset($_GET['user'])) {
    $p_id = intval($_GET['remove_promo']);
    $u_id = intval($_GET['user']);
    $pdo->prepare("DELETE FROM inscriptions WHERE utilisateur_id = ? AND promotion_id = ?")->execute([$u_id, $p_id]);
    $msg = "<div class='alert alert-success'>Inscription retirée.</div>";
    $_GET['edit'] = $u_id;
}

// =========================================================
// 2. MODE ÉDITION
// =========================================================
$user_promos = [];
$all_promos = [];

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();

    if ($edit_user) {
        // Promos de l'utilisateur (Admin ou Élève)
        $sql_u_promos = "SELECT p.* FROM promotions p JOIN inscriptions i ON p.id = i.promotion_id WHERE i.utilisateur_id = ?";
        $stmt = $pdo->prepare($sql_u_promos);
        $stmt->execute([$edit_id]);
        $user_promos = $stmt->fetchAll();

        // Toutes les promos pour le choix
        $all_promos = $pdo->query("SELECT * FROM promotions ORDER BY annee DESC")->fetchAll();
    }
}

// =========================================================
// 3. LISTE GLOBALE (MODIFIÉ : On inclut les Admins)
// =========================================================
$search = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';

// ICI LA MODIFICATION : On a retiré "WHERE is_admin = 0"
$sql_list = "SELECT * FROM utilisateurs WHERE (nom LIKE ? OR prenom LIKE ? OR email LIKE ?) ORDER BY is_admin DESC, created_at DESC";

$stmt = $pdo->prepare($sql_list);
$stmt->execute(["%$search%", "%$search%", "%$search%"]);
$users = $stmt->fetchAll();

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') $msg = "<div class='alert alert-success'>Utilisateur supprimé.</div>";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs - Admin</title>
    <link rel="stylesheet" href="style.css?v=ADMIN_USER_V2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { margin-bottom: 0; }
        .action-btn { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; margin-right: 5px; font-size: 0.9rem; }
        .btn-edit { background: var(--accent); }
        .btn-del { background: var(--danger); }
        
        .promo-tag {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--bg); border: 1px solid var(--border);
            padding: 5px 10px; border-radius: 20px; margin: 5px 5px 5px 0; font-size: 0.9rem;
        }
        .promo-tag a { color: var(--danger); text-decoration: none; font-weight: bold; }
        
        /* Badge Admin */
        .badge-admin { 
            background: #f1c40f; color: #000; padding: 2px 6px; 
            border-radius: 4px; font-size: 0.7rem; font-weight: bold; margin-left: 5px; text-transform: uppercase;
        }
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
        <h1>