<?php

function getRequestHeaders() {
    $headers = array();
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) == 'HTTP_' && $key != 'HTTP_HOST' && $key != 'HTTP_COOKIE') {
            if ($key == 'HTTP_ACCEPT_ENCODING' && !extension_loaded('brotli')) { $value = str_replace(', br', '', $value); }
            $headers[] = str_replace('_', '-', substr($key, 5)) . ': ' . $value;
        }
    }
    return $headers;
}

function decode($string, $encoding) {
    return transcode($string, $encoding, array(
        'gzip' => 'gzdecode',
        'br'   => 'brotli_uncompress',
    ));
}

function encode($string, $encoding) {
    return transcode($string, $encoding, array(
        'gzip' => 'gzencode',
        'br'   => 'brotli_compress',
    ));
}

function transcode($string, $encoding, array $funcs) {
    if (isset($funcs[$encoding])) {
        $func = $funcs[$encoding];
        return $func($string);
    }

    return $string;
}

$body = $encoding = '';
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
    CURLOPT_HEADERFUNCTION => function ($h, $header) use (&$encoding) {
        if (substr($header, 0, 23) != 'Strict-Transport-Security' && substr($header, 0, 4) != 'Vary') {
            header($header);
        }

        if (substr($header, 0, 16) == 'Content-Encoding') {
            $encoding = trim(substr($header, 17));
        }

        return strlen($header);
    },
    CURLOPT_WRITEFUNCTION => function ($h, $chunk) use (&$body) {
        $body .= $chunk;
        return strlen($chunk);
    }
));

curl_exec($ch);
curl_close($ch);

echo encode(str_replace('}', "  font-display: swap;\n}", decode($body, $encoding)), $encoding);
