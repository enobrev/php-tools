<?php
    namespace Enobrev;

    /**
     * @return array
     */
    function getHeaders() {
        $aHeaders = [];
        foreach ($_SERVER as $sName => $sValue) {
            if (substr($sName, 0, 5) == 'HTTP_') {
                $aHeaders[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($sName, 5)))))] = $sValue;
            }
        }
        return $aHeaders;
    }

    /**
     * @return bool
     */
    function isCli() {
        return php_sapi_name() == 'cli'
            && empty($_SERVER['REMOTE_ADDR']);
    }

    /**
     * @return string
     */
    function get_ip() {
        if (isset($_SERVER["HTTP_X_REAL_IP"]) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $sIP = $_SERVER["HTTP_X_REAL_IP"]; // explicitly set in nginx load balancer
        } else if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $sIP = getenv("HTTP_CLIENT_IP");
        } else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $sIP = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $sIP = getenv("REMOTE_ADDR");
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $sIP = $_SERVER['REMOTE_ADDR'];
        } else {
            $sIP = "unknown";
        }

        return $sIP;
    }

    /**
     * @return bool
     */
    function contentTypeIsNotHtml() {
        $aHeaders = headers_list();
        if (count($aHeaders)) {
            foreach($aHeaders as $sHeader) {
                $aHeader = explode(':', $sHeader);
                if (strtolower($aHeader[0]) == 'content-type') {
                    return $aHeader[1] != 'text/html';
                }
            }
        }

        // text/html is default
        return false;
    }