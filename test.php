<?php

function makeRequest($url, $postFields = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    
    curl_setopt($ch, CURLOPT_USERPWD, "ajamuser:t3sl@admin");
    
    if ($postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    }
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    
    curl_close($ch);
    
    return $response;
}

// Login to Asterisk
$loginUrl = "http://127.0.0.1:8088/asterisk/rawman?action=Login&Username=ajamuser&Secret=t3sl@admin";
$loginResponse = makeRequest($loginUrl);
echo "Login Response: " . $loginResponse . "\n";

if (strpos($loginResponse, 'Success') !== false) {
    // Login was successful, make the QueueStatus request
    $queueStatusUrl = "http://127.0.0.1:8088/asterisk/rawman?action=QueueStatus&ActionId=bb904e18&Queue=";
    $queueStatusResponse = makeRequest($queueStatusUrl);
    echo "QueueStatus Response: " . $queueStatusResponse . "\n";
} else {
    echo "Login failed: " . $loginResponse;
}
?>
