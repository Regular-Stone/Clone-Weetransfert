<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Utilisez getallheaders pour lire les en-têtes
    $headers = getallheaders();

    // Journalisez tous les en-têtes reçus pour vérifier leur présence
    error_log(print_r($headers, true));

    $uploadId = isset($headers['Upload-ID']) ? $headers['Upload-ID'] : null;
    $totalChunks = isset($headers['Total-Chunks']) ? $headers['Total-Chunks'] : null;
    $fileMd5 = isset($headers['File-MD5']) ? $headers['File-MD5'] : null;
    $uploadComplete = isset($headers['Upload-Complete']) ? $headers['Upload-Complete'] : false;

    // Vérifiez que les en-têtes sont bien définis
    if (!$uploadId || !$totalChunks || !$fileMd5) {
        error_log("Headers manquants : " . print_r($headers, true));
        echo "Headers manquants";
        exit;
    }

    // Assurez-vous que les répertoires nécessaires existent
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Assemble les chunks
    if (!$uploadComplete) {
        $fileName = isset($headers['Content-Disposition']) ? $headers['Content-Disposition'] : null;
        if ($fileName) {
            preg_match('/filename="(.+)"/', $fileName, $matches);
            $fileName = $matches[1];
        }

        $fileContent = file_get_contents('php://input');
        file_put_contents($uploadDir . $uploadId, $fileContent, FILE_APPEND);
    } else {
        $filePath = $uploadDir . $uploadId;

        // Vérifier le hash MD5
        if (md5_file($filePath) === $fileMd5) {
            echo "Le fichier n'a pas été corrompu.";
        } else {
            echo "Le fichier a été corrompu.";
        }

        // Renommer le fichier final avec son nom original
        rename($filePath, $uploadDir . $fileName);
    }
}
?>
