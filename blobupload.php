<?php
$headers = apache_request_headers();
header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");

// Récupérer le nom du fichier depuis les en-têtes
$filename = $headers['Content-Disposition'];
preg_match('/filename="(.+)"/', $filename, $matches);
$originalFilename = $matches[1];

// Récupérer l'ID de l'upload et les informations de morceau
$uploadId = $headers['Upload-ID'];
$totalChunks = $headers['Total-Chunks'];
$uploadComplete = isset($headers['Upload-Complete']);

// Créer un dossier unique pour cet ensemble d'uploads s'il n'existe pas
$uploadDir = 'uploads/' . $uploadId;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// On récupére l'extension du fichier
$extension = pathinfo($originalFilename, PATHINFO_EXTENSION);

// Calculer le hash MD5 du nom de fichier
$hashedFilename = md5($originalFilename) . '.' . $extension;

// Chemin vers le fichier temporaire où les chunks seront stockés
$tempFilePath = $uploadDir . '/' . $hashedFilename . '.part';

// Lire le contenu du chunk
$result = file_get_contents('php://input');

// Écrire le chunk dans le fichier temporaire
file_put_contents($tempFilePath, $result, FILE_APPEND);

if ($uploadComplete) {
    // Renommer le fichier temporaire pour indiquer qu'il est complet
    $finalFilePath = $uploadDir . '/' . $hashedFilename;
    rename($tempFilePath, $finalFilePath);
} else {
    
}
