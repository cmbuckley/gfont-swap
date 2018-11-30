<?php

class Fonts {
    protected $server;
    protected $body = '';
    protected $encoding = '';

    public function __construct($server) {
        $this->server = $server;
    }

    public function getRequestHeaders() {
        $headers = array();
        foreach ($this->server as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_' && $key != 'HTTP_HOST' && $key != 'HTTP_COOKIE') {
                // don't request brotli encoding if server can't handle it
                if ($key == 'HTTP_ACCEPT_ENCODING' && !extension_loaded('brotli')) {
                    $value = str_replace(', br', '', $value);
                }

                $headers[] = str_replace('_', '-', substr($key, 5)) . ': ' . $value;
            }
        }
        return $headers;
    }

    public function decode($string) {
        return $this->transcode($string, array(
            'gzip' => 'gzdecode',
            'br'   => 'brotli_uncompress',
        ));
    }

    public function encode($string) {
        return $this->transcode($string, array(
            'gzip' => 'gzencode',
            'br'   => 'brotli_compress',
        ));
    }

    protected function transcode($string, array $funcs) {
        if (isset($funcs[$this->encoding])) {
            $func = $funcs[$this->encoding];
            return $func($string);
        }

        return $string;
    }

    protected function writeCurlHeader($ch, $header) {
        // store the encoding for later
        if (substr($header, 0, 16) == 'Content-Encoding') {
            $this->encoding = trim(substr($header, 17));
        }

        header($header);
        return strlen($header);
    }

    protected function writeCurlBody($ch, $chunk) {
        $this->body .= $chunk;
        return strlen($chunk);
    }

    public function request() {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL            => 'https://fonts.googleapis.com/css?' . $this->server['QUERY_STRING'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => $this->getRequestHeaders(),
            CURLOPT_HEADER         => false,
            CURLOPT_BUFFERSIZE     => 8192,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADERFUNCTION => array($this, 'writeCurlHeader'),
            CURLOPT_WRITEFUNCTION  => array($this, 'writeCurlBody'),
        ));

        curl_exec($ch);
        curl_close($ch);
        return $this->output();
    }

    protected function output() {
        return $this->encode(str_replace('}', "  font-display: swap;\n}", $this->decode($this->body)));
    }

    public function __toString() {
        return $this->request();
    }
}

echo new Fonts($_SERVER);
