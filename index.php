<?php

function getRequestHeaders() {
    $headers = array();
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_' && $key != 'HTTP_HOST' && $key != 'HTTP_COOKIE') {
            $headers[] = str_replace('_', '-', substr($key, 5)) . ': ' . $value;
        }
    }
    return $headers;
}

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => 'https://fonts.googleapis.com/css?' . $_SERVER['QUERY_STRING'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HTTPHEADER => getRequestHeaders(),
    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
    CURLOPT_HEADER => false,
    CURLOPT_BUFFERSIZE => 8192,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADERFUNCTION => function ($h, $header) {
        if (substr($header, 0, 23) != 'Strict-Transport-Security' && substr($header, 0, 4) != 'Vary') {
            header($header);
        }

        return strlen($header);
    },
    CURLOPT_WRITEFUNCTION => function ($h, $body) {
        echo str_replace('}', "  font-display: swap;\n}", $body);
        return strlen($body);
    }
));

curl_exec($ch);
curl_close($ch);
