<?php

require __DIR__ . '/../vendor/autoload.php'; // Adjust the path based on your project structure

// Path to the credentials JSON file obtained from the Google Cloud Console
$credentialsPath = __DIR__ . '/../credentials.json';

$client = new Google_Client();
$client->setApplicationName('Google Sheets API PHP Quickstart');
$client->setScopes(Google_Service_Sheets::SPREADSHEETS);
$client->setAuthConfig($credentialsPath);
$client->setAccessType('offline');

$service = new Google_Service_Sheets($client);

// Function to read data from Google Sheets
function getData($spreadsheetId,$range) {
    global $service;
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    return $response->getValues();

    if (!empty($values)) {
        $headers = array_shift($values); // Extract headers from the first row
        $result = [];
        foreach ($values as $row) {
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? ''; // Add default value if the column is not present
            }
            $result[] = $rowData;
        }
        return $result;
    } else {
        return [];
    }
}

// Function to write data to Google Sheets
function writeData($spreadsheetId, $range, $data) {
    global $service;
    $body = new Google_Service_Sheets_ValueRange(['values' => $data]);
    $params = ['valueInputOption' => 'RAW'];
    $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
}
