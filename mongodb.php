<?php
// Connexion à MongoDB
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Sélection de la base de données
    $mongoDB = $client->selectDatabase('vite_gourmand_logs');

} catch (Exception $e) {
    die("Erreur de connexion MongoDB : " . $e->getMessage());
}