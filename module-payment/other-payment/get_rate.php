<?php
header('Content-Type: application/json');

function getExchangeRate($from = 'USD', $to = 'MYR') {
    $apiKey = '478addc78ae50bb630c29c99'; // Get your API key from https://www.exchangerate-api.com/
    $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['conversion_rate'] ?? 4.51; // Fallback to current rate if API fails
}

$rate = getExchangeRate('USD', 'MYR');
echo json_encode(['rate' => $rate]); 