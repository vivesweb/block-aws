<?php

$jsonFile = 'https://ip-ranges.amazonaws.com/ip-ranges.json';


$cacheFile = __DIR__ . '/cache/amazon_ips.cache.json';
$cacheFileCsv = $cacheFile.'.csv';
$updateInterval = 7 * 24 * 60 * 60; // 7 días en segundos

// Comprobar si el archivo de caché existe y si ha pasado el tiempo de actualización
if ( file_exists($cacheFileCsv) && time() - filemtime($cacheFileCsv) < $updateInterval) {
    // El archivo de caché es válido, cargar datos desde el caché
    $csvContent = file_get_contents($cacheFileCsv); // Set CSV Content
} else {
    // Descargar el nuevo archivo JSON
    $jsonContent = file_get_contents($jsonFile);

    if( $jsonContent === false || strlen($jsonContent) < 500000 ){
        // SOME ERROR. LOAD PREVIOUS CACHE FILE
        $jsonContent = file_get_contents($cacheFile);
    } else {
        // Guardar el nuevo contenido en el archivo de caché
        $jsonContent = str_replace( ["\r\n", "\r", "\n", ' '], '', $jsonContent); // Compress file
        file_put_contents($cacheFile, $jsonContent); // Original file
    }

    // Prepare same file in .csv format (fastest)
    $ipData = json_decode($jsonContent, true);
    $arrIps = [];
    foreach( $ipData['prefixes'] as $prefix ){
        $arrIps[] = $prefix['ip_prefix'];
    }
    
    $csvContent = implode(',', $arrIps); // Set CSV Content

    file_put_contents($cacheFileCsv, $csvContent); // .csvfile
}

// Decodificar el JSON para obtener un array de direcciones IP
//$ipData = json_decode($jsonContent, true);
$ipData = explode(',', $csvContent);

$myIp = $_SERVER['REMOTE_ADDR'];

foreach( $ipData as $ip ){
    if(ip_in_range($myIp, $ip)  ){
        http_response_code(404);
        die();    
    }
}

// Función para verificar si la dirección IP del cliente está en la gama especificada
function ip_in_range($ip, $range) {
    list($subnet, $mask) = explode('/', $range);
    $subnet = ip2long($subnet);
    $ip = ip2long($ip);
    $mask = ~((1 << (32 - $mask)) - 1);

    return ($ip & $mask) == ($subnet & $mask);
}
?>
