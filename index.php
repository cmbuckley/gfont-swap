<?php

class Fonts {
    // server variables
    protected $server;

    // font-display mode
    protected $mode = '';

    // response data
    protected $body = '';
    protected $encoding = '';

    public function __construct($server, $mode = 'swap') {
        $this->server = $server;
        $this->setMode($mode);
    }

    public function setMode($mode) {
        if (!in_array($mode, array('auto', 'block', 'swap', 'fallback', 'optional'))) {
            $mode = '';
        }

        $this->mode = $mode;
    }

    public function getRequestHeaders() {
        $headers = array();
        foreach ($this->server as $key => $value) {
            // find all request headers in server variables
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

    // decode a string according to specified encoding
    protected function decode($string) {
        return $this->transcode($string, array(
            'gzip' => 'gzdecode',
            'br'   => 'brotli_uncompress',
        ));
    }

    // encode a string according to specified encoding
    protected function encode($string) {
        return $this->transcode($string, array(
            'gzip' => 'gzencode',
            'br'   => 'brotli_compress',
        ));
    }

    // perform a string function according to encoding
    protected function transcode($string, array $funcs) {
        if (isset($funcs[$this->encoding])) {
            $func = $funcs[$this->encoding];
            return $func($string);
        }

        return $string;
    }

    // add font-display declaration if required
    protected function transform($string) {
        if (!$this->mode) {
            return $string;
        }

        // Google announced support; do nothing
        if (strpos($string, 'font-display:')) {
            return $string;
        }

        return str_replace('}', "  font-display: $this->mode;\n}", $string);
    }

    // capture response headers from curl request (one call per header field)
    protected function writeCurlHeader($ch, $header) {
        // store the encoding for later
        if (substr($header, 0, 16) == 'Content-Encoding') {
            $this->encoding = trim(substr($header, 17));
        }

        header($header);
        return strlen($header);
    }

    // capture response body from curl request (can arrive in multiple chunks)
    protected function writeCurlBody($ch, $chunk) {
        $this->body .= $chunk;
        return strlen($chunk);
    }

    // make the font request
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

    // output the transformed content
    protected function output() {
        if (!$this->mode) {
            return $this->body; // save transcoding for nothing
        }

        return $this->encode($this->transform($this->decode($this->body)));
    }

    public function __toString() {
        return $this->request();
    }
}

echo new Fonts($_SERVER, isset($_GET['display']) ? $_GET['display'] : '');
