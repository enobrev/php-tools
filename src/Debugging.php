<?php
    namespace Enobrev;

    /**
     * @param $sMessage
     */
    function _output($sMessage) {
        if (isCli() || contentTypeIsNotHtml()) {
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
    function trace(bool $bShort = true, bool $bReturn = false) {
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