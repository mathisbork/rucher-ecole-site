<?php
session_start();
require 'db.php';

// 1. Sécurité de base
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    die("Interdit");
}

$cours_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Vérification des droits (Copie de la logique de lecture.php)
// On revérifie ici car un petit malin pourrait appeler stream.php directement
$sql = "
    SELECT c.nom_fichier_serveur, c.type_contenu FROM cours c
    JOIN cours_promotions cp ON c.id = cp.cours_id
    JOIN inscriptions i ON cp.promotion_id = i.promotion_id
    WHERE c.id = ? AND i.utilisateur_id = ? AND cp.date_ouverture <= NOW()
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$cours_id, $user_id]);
$cours = $stmt->fetch();

if (!$cours) {
    http_response_code(403);
    die("Accès refusé");
}

// 3. Récupération du fichier dans le dossier sécurisé
// '../protected_uploads/' remonte d'un cran pour sortir du dossier www public
$file_path = '../protected_uploads/' . $cours['nom_fichier_serveur'];

if (!file_exists($file_path)) {
    http_response_code(404);
    die("Fichier introuvable sur le serveur.");
}

// 4. Envoi des bons en-têtes (Headers) au navigateur
$mime_type = ($cours['type_contenu'] == 'video') ? 'video/mp4' : 'application/pdf';
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));

// Pour empêcher le téléchargement direct et forcer l'affichage dans le navigateur
header('Content-Disposition: inline; filename="cours_protege"');

// 5. Lecture et envoi du fichier
readfile($file_path);
exit;
?>
