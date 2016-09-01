<?php
    namespace Enobrev;

    /**
     * @return bool
     */
    function isCli() {
        return php_sapi_name() == 'cli' 
            && empty($_SERVER['REMOTE_ADDR']);
    }

    function _contentTypeIsNotHtml() {
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

    /**
     * @param $sMessage
     */
    function _output($sMessage) {
        if (isCli() || _contentTypeIsNotHtml()) {
            $sMessage = str_replace('<br />', "\n", $sMessage);
            $sMessage = str_replace('<pre>',  '', $sMessage);
            $sMessage = str_replace('</pre>', '', $sMessage);
        }

        echo $sMessage;
    }

    function dbg(...$aArgs) {
        $sTitle = '';

        if (count($aArgs) > 1) {
            $sTitle = array_shift($aArgs) . ': ';
        }

        if (strlen($sTitle)) {
            if (is_object($aArgs[0])
                ||  is_array($aArgs[0])) {
                _output($sTitle . "<br />");
            } else {
                _output($sTitle);
            }
        }

        foreach ($aArgs as $mArg) {
            if (is_object($mArg)
            ||  is_array($mArg)) {
                _output('<pre>' . print_r($mArg, 1) . '</pre>');
            } else {
                _output($mArg . "<br />");
            }
        }
    }

    /**
     * @param bool $bShort
     * @param bool $bReturn
     * @return array|void
     */
    function trace($bShort = true, $bReturn = false) {
        $oBacktrace = debug_backtrace();

        if ($bShort) {
            $aResponse = array();

            foreach ($oBacktrace as $oTrace) {
                $sResponse = '';

                if (isset($oTrace['line'])) {
                    $sResponse .= $oTrace['line'];
                }

                if (isset($oTrace['file'])) {
                    $sResponse .= ': ' . str_replace(dirname(dirname(dirname(__FILE__))), '', $oTrace['file']);
                }

                $sResponse .=  ': ' . $oTrace['function'];
                $aResponse[] = $sResponse;
            }

            if ($bReturn) {
                return $aResponse;
            } else {
                dbg($aResponse);
            }
        } else {
            foreach ($oBacktrace as &$oTrace) {
                if (isset($oTrace['object'])) {
                    $oTrace['object'] = '[OBJECT]';
                }
                if (isset($oTrace['args'])) {
                    foreach ($oTrace['args'] as &$mArg) {
                        if (is_object($mArg)) {
                            $mArg = '[OBJECT]';
                        } else if (is_array($mArg)) {
                            $mArg = '[ARRAY]';
                        }
                    }
                }
            }

            if ($bReturn) {
                return $oBacktrace;
            } else {
                dbg($oBacktrace);
            }
        }
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
     * @param string $word
     * @return string
     */
    function depluralize($word){
        $rules = array(
            'ss'  => false,
            'os'  => 'o',
            'ies' => 'y',
            'xes' => 'x',
            'oes' => 'o',
            'ves' => 'f',
            's'   => ''
        );

        foreach(array_keys($rules) as $key){
            if(substr($word, (strlen($key) * -1)) != $key)
                continue;
            if($key === false)
                return $word;
            return substr($word, 0, strlen($word) - strlen($key)) . $rules[$key];
        }

        return $word;
    }