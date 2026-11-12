<?php
// Fichier inclus au début de chaque page : session + connexion MySQL + petites fonctions
date_default_timezone_set('Africa/Casablanca');
session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=edsc;charset=utf8mb4', 'root', '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Connexion à la base impossible : ' . $e->getMessage());
}

// Affiche un texte en toute sécurité (protège contre l'injection de HTML/JS)
function e($texte) { return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8'); }

function connecte() { return isset($_SESSION['membre']); }

function estAdmin() {
    return connecte() && ($_SESSION['membre']['role'] ?? '') === 'admin';
}

function estStaff() {
    return connecte() && in_array($_SESSION['membre']['role'] ?? '', ['employe', 'admin'], true);
}
