<?php
    namespace Enobrev;

    function getHeaders(): array {
        $aHeaders = [];
        foreach ($_SERVER as $sName => $sValue) {
            if (strpos($sName, 'HTTP_') === 0) {
                $aHeaders[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($sName, 5)))))] = $sValue;
            }
        }
        return $aHeaders;
    }

    function isCli(): bool {
        return PHP_SAPI === 'cli'
            && empty($_SERVER['REMOTE_ADDR']);
    }

    /**
     * @return string
     */
    function get_ip() {
        $bIsCLI = isCli();
        if ($bIsCLI && isset($_SERVER['SERVER_ADDR'])) {
            $sIP = $_SERVER['SERVER_ADDR'];
        } else if ($bIsCLI && isset($_SERVER['LOCAL_ADDR'])) {
            $sIP = $_SERVER['LOCAL_ADDR'];
        } else if ($bIsCLI) {
            $sIP = gethostbyname(gethostname());
        } else if (isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP'] && strcasecmp($_SERVER['HTTP_X_REAL_IP'], 'unknown')) {
            $sIP = $_SERVER['HTTP_X_REAL_IP']; // explicitly set in nginx load balancer
        } else if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $sIP = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $sIP = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $sIP = getenv('REMOTE_ADDR');
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $sIP = $_SERVER['REMOTE_ADDR'];
        } else {
            $sIP = 'unknown';
        }

        return $sIP;
    }

    function contentTypeIsNotHtml(): bool {
        $aHeaders = headers_list();
        if (count($aHeaders)) {
            foreach($aHeaders as $sHeader) {
                $aHeader = explode(':', $sHeader);
                if (strtolower($aHeader[0]) === 'content-type') {
                    return $aHeader[1] !== 'text/html';
                }
            }
        }

        // text/html is default
        return false;
    }