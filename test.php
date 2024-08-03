<?php
function securize($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}
    // Ici c'est le fichier de test qui traitera les données envoyées par le formulaire
    // On récupère les données envoyées par le formulaire
    // les données sont : les mails des destinataires, le mail de l'expéditeur et les fichiers à envoyer contenu dans un formDATA
    // On va d'abord uploader les fichiers dans le dossier uploads
    // Ensuite on va envoyer les mails aux destinataires
    // On va utiliser la librairie PHPMailer pour envoyer les mails
    $headers = apache_request_headers();
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    // Début de l'upload des fichiers
    $datas = $_POST;
    $files = $_FILES;
    $uploadId = $headers['Upload-ID'];
    $totalChunks = $headers['Total-Chunks'];
    $uploadComplete = isset($headers['Upload-Complete']);
    $emetteur = securize($_POST['expediteurEmail']);
    $destinataire = securize($_POST['destEmail']);
    $date = date("Y-m-d H:i:s");
    $repositoryName = md5($emetteur . $destinataire . $date, false);
    // Créer un dossier unique pour cet ensemble d'uploads s'il n'existe pas
    $uploadDir = "./uploads/" . $repositoryName;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
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