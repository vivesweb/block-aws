<?php

/**
 * BLOCK AMAZON WS IP'S
 * 
 * By Rafael Martín Soto: @rafainatica 2024
 * 
 */

$jsonFile = 'https://ip-ranges.amazonaws.com/ip-ranges.json';


$cacheFile = __DIR__ . '/../cache/amazon_ips.cache.json';
$cacheFileCsv = $cacheFile.'.csv';
$updateInterval = 7 * 24 * 60 * 60; // 7 days in seconds

// Check if the cache file exists and if the update time has passed
if ( file_exists($cacheFileCsv) && time() - filemtime($cacheFileCsv) < $updateInterval) {
    // Cache file is valid, load data from cache
    $csvContent = file_get_contents($cacheFileCsv); // Set CSV Content
} else {
    // Download the new JSON file
    $jsonContent = file_get_contents($jsonFile);

    if( $jsonContent === false || strlen($jsonContent) < 500000 ){
        // SOME ERROR. LOAD PREVIOUS CACHE FILE
        $jsonContent = file_get_contents($cacheFile);
    } else {
        // Save the new content to the cache file
        $jsonContent = str_replace( ["\r\n", "\r", "\n", ' '], '', $jsonContent); // Compress file
        file_put_contents($cacheFile, $jsonContent); // Original file
    }

    // Prepare same file in .csv format (faster)
    $ipData = json_decode($jsonContent, true);
    $arrIps = [];
    foreach( $ipData['prefixes'] as $prefix ){
        $arrIps[] = $prefix['ip_prefix'];
    }
    
    $csvContent = implode(',', $arrIps); // Set CSV Content

    file_put_contents($cacheFileCsv, $csvContent); // .csvfile
}

// Decode the JSON to obtain an array of IP addresses
$ipData = explode(',', $csvContent);

$myIp = $_SERVER['REMOTE_ADDR'];

foreach( $ipData as $ip ){
    if(ip_in_range($myIp, $ip)  ){
        http_response_code(404);
        die();    
    }
}

// Function to check if the client's IP address is in the specified range
function ip_in_range($ip, $range) {
    list($subnet, $mask) = explode('/', $range);
    $subnet = ip2long($subnet);
    $ip = ip2long($ip);
    $mask = ~((1 << (32 - $mask)) - 1);

    return ($ip & $mask) == ($subnet & $mask);
}
?>
