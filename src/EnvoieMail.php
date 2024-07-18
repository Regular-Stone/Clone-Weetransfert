<?php

use App\Service\PHPMailService;

include_once 'dotEnv.php';
dotEnv("../");
//Load Composer's autoloader
require '../vendor/autoload.php';


function emailSetting(){
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailService;
    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    //To load the French version
    $mail->setLanguage('fr', 'vendor/phpmailer/phpmailer/language/phpmailer.lang-fr.php');
    return $mail;
}

function sendToDestinataire($mail, $sendTo, $sendFrom, $downloadFile){
    $delais = 7;
    $downloadLink = $_ENV['WEB_URL'] . '/src/downloadPage.php?file=' . $downloadFile;
    $mail->addAddress($sendTo, '');     //Add a recipient
    $mail->Subject = 'EasyUpload: Réception de Fichiers';
    $mailTemplate = destiMailTemplate($sendTo, $sendFrom, $downloadLink, $delais);
    $mail->Body    = $mailTemplate;

    if (!$mail->send()) {
        return $mail->ErrorInfo;
    } else {
        return 'noerror';
    }
}

function envoieMail($sendTo, $sendFrom, $downloadFile)
{
    $sendToD = explode(',', $sendTo);
    $mail = eMailSetting();
    $error = 'noerror';
    $countFail = 0;

    foreach ($sendToD as $value) {
        $error = sendToDestinataire($mail, $value, $sendFrom, $downloadFile);
        $mail->clearAllRecipients();
        // concat message
        if ($error == 'noerror') {
            // code msg 'le lien de téléchargement de vos fichiers à bien été envoyé à ...'
        } else {
            $countFail += 1;
            // code msg 'suite à une erreur, le lien de téléchargement de vos fichiers n\'à pas pu être envoyé à ...'
        }
    }
    $case = false;
    if ($countFail === 0) {
        $case = true;
        $messageSubject = 'Vos fichiers ont été correctement transférés!';
        
    } else if ($countFail ===  count($sendTo)) {
        $case = false;
        $messageSubject = 'Vos fichiers n\'ont pus être transférés!';
    } else {
        $case = false;
        $messageSubject = 'Vos fichiers n\'ont été partiellement transférés!';
    }
    $mailTemplate = expeMailTemplate($sendTo, $sendFrom, $case);
    //Envoie du second mail
    $mail->clearAllRecipients();
    $mail = eMailSetting();
    $mail->addAddress($sendFrom, '');
    $mail->Subject = $messageSubject;
    $mail->Body    = $mailTemplate;

    if (!$mail->send()) {
        echo 'Erreur lors de l\'envoi du deuxième e-mail : ' . $mail->ErrorInfo;
    }
}

function destiMailTemplate($sendTo, $sendFrom, $downloadLink, $delais) {   
    $commonStyles = getCommonEmailStyles();
    $link = $_ENV['WEB_URL'];
    $template = <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <style>
            {$commonStyles}
            .downloadButton {
                display: inline-block;
                text-align: center;
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                color: #292929 !important;
                font-weight: bold;
                background-color: antiquewhite;
            }
        </style>
    </head>
    <body>
        <div class="container">
        <div class="box">
        <table>
            <tr>
                <!-- 
                Il pourrait être pertinant d'avoir le lien vers le logo dans une variable d'environnement 
                mais si le site est déployer on pourrait utiliser l'image stockée dans le serveur plutôt 
                que de faire une requête à un serveur externe
                -->
                <td align="right"><img src="https://i.goopics.net/kn2ydb.png" class="logo" alt="logo de EasyUpload"></td>
                <td valign="bottom" align="left"><h2>Easy Upload</h2></td>
            </tr>
            <tr>
                <td colspan="2"><h2>Bonjour {$sendTo},</h2></td>
            </tr>
            <tr>
                <td colspan="2" ><p>{$sendFrom} souhaite vous transmettre des documents. Pour les télécharger, veuillez cliquer sur le lien suivant:</p></td>
            </tr>
            <tr>
                <td colspan="2"><p style="text-align:center; margin: 20px 0;"><a href="{$downloadLink}" class="downloadButton">Télécharger les documents</a></p></td>
            </tr>
            <tr>
                <td colspan="2"><p>Veuillez noter que ce lien sera valide pendant $delais jours. Passé ce délai, vos documents ne seront plus disponibles. Merci.</p></td>
            </tr>
            <tr>
                <td colspan="2"><p>L'équipe EasyUpload.</p></td>
            </tr>
            </div>
            <tr>
                <td colspan="2" align="center"><a href="{$link}">Lien vers EasyUpload</a></td>
            </tr>
        </table>
        </div>
    </body>
    </html>
    HTML;
    return $template;
    }
    
    function expeMailTemplate($sendTo, $sendFrom, $case) {
    $emails = explode(',', $sendTo); // Sépare les emails dans un tableau
    if (count($emails) >= 2) {
        // Réassemble les emails avec <br> et un espace si plus d'un email
        $formattedEmails = implode('<br>', $emails);
    } else {
        // Utilise la chaîne telle quelle si un seul email
        $formattedEmails = $sendTo;
    }
    $case ? $content = 
    "Vos fichiers ont été correctement transférés! Le lien de téléchargement de vos fichiers à bien été envoyé à : $formattedEmails." 
    : $content = "Suite à une erreur, le lien de téléchargement de vos fichiers n'a pas pu être envoyé à $formattedEmails. Merci de bien vouloir réessayer ou de contacter notre service technique.";
    $commonStyles = getCommonEmailStyles();
    $wordWrap = 'style="word-wrap: break-word; white-space: normal;"';
    $link = $_ENV['WEB_URL'];
    $template = <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <style>
            {$commonStyles}
            .downloadButton {
                display: inline-block;
                padding: 10px 20px;
                border-radius: 5px;
                text-decoration: none;
                color: #292929 !important;
                font-weight: bold;
                background-color: antiquewhite;
            }
        </style>
    </head>
    <body>
        <div {$wordWrap} class="container">
        <div {$wordWrap} class="box">
        <table  >
            <tr>
                <!-- 
                Il pourrait être pertinant d'avoir le lien vers le logo dans une variable d'environnement 
                mais si le site est déployer on pourrait utiliser l'image stockée dans le serveur plutôt 
                que de faire une requête à un serveur externe
                -->
                <td align="right"><img src="https://i.goopics.net/kn2ydb.png" class="logo" alt="logo de EasyUpload"></td>
                <td valign="bottom" align="left"><h2>Easy Upload</h2></td>
            </tr>
            <tr>
                <td colspan="2"><h2>Bonjour {$sendFrom},</h2></td>
            </tr>
            <tr {$wordWrap}>
                <td {$wordWrap} colspan="2" ><p {$wordWrap}>{$content}</p></td>
            </tr>
            <tr>
                <td colspan="2"><p>Merci d'avoir utilisé EasyUpload.</p></td>
            </tr>
            <tr>
                <td colspan="2"><p>L'équipe EasyUpload.</p></td>
            </tr>
            </div>
            <tr>
                <td colspan="2" align="center"><a href="{$link}">Lien vers EasyUpload</a></td>
            </tr>
        </table>
        </div>
    </body>
    </html>
    HTML;
    return $template;
    }
    
    
    function getCommonEmailStyles() {
        return "
        .container {
            background-color: #292929;
            padding: 50px;
        }
        .box {
            max-width: 600px;
            border: 1px solid antiquewhite;
            border-radius: 5px;
            margin: 0 auto 20px auto;
            padding: 20px;
            overflow: hidden;
    
        }
        .logo {
            width: 100px;
            height: auto;
        }
        h2, p {
            color: antiquewhite;
        }
        a {
            color: #83b4f3 !important;
        }
        tr, table, tbody {
            width: 100%;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 40px 20px;
            }
            .box {
                padding: 20px 10px;
            }
            .logo {
                width: 80px;
            }
            h2 {
                font-size: 1.2em;
            }
        }
        
        ";
        
    }
