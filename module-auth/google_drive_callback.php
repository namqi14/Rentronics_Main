<?php

require_once 'vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId('384432871044-32eo7a23dglgse5luhvt2hkctuv9egcj.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('https://rentronics-ez.com/google_drive_callback.php');
$client->addScope(Google_Service_Drive::DRIVE);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $token;
    header('Location: ' . filter_var($client->getRedirectUri(), FILTER_SANITIZE_URL));
}

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
} else {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
}

$drive = new Google_Service_Drive($client);

function listFiles($drive) {
    $files = $drive->files->listFiles(array())->getFiles();
    foreach ($files as $file) {
        echo $file->getName() . "<br>";
    }
}

function uploadFile($drive, $filePath, $fileName) {
    $file = new Google_Service_Drive_DriveFile();
    $file->setName($fileName);
    $file->setParents(array("YOUR_FOLDER_ID"));

    $content = file_get_contents($filePath);
    $drive->files->create($file, array(
        'data' => $content,
        'mimeType' => mime_content_type($filePath),
        'uploadType' => 'multipart'
    ));
}

// Example usage
listFiles($drive);
// uploadFile($drive, 'path/to/your/file', 'uploaded_file_name');

?>
