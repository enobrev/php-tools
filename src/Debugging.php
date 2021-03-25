<?php
    namespace Enobrev;

    /**
     * @param string $sMessage
     */
    function _output(string $sMessage): void {
        if (isCli() || contentTypeIsNotHtml()) {
            $sMessage = str_replace(['<br />', '<pre>', '</pre>'], ["\n", '', ''], $sMessage);
        }

        echo $sMessage;
    }

    function dbg(...$aArgs): void {
        $sTitle = '';

        if (count($aArgs) > 1) {
            $sTitle = array_shift($aArgs) . ': ';
        }

        if ($sTitle !== '') {
            if (is_object($aArgs[0])
            ||  is_array($aArgs[0])) {
                _output($sTitle . '<br />');
            } else {
                _output($sTitle);
            }
        }

        foreach ($aArgs as $mArg) {
            if (is_object($mArg)
            ||  is_array($mArg)) {
                _output('<pre>' . print_r($mArg, true) . '</pre>');
            } else {
                _output($mArg . '<br />');
            }
        }
    }

    function dbg_nl(...$aArgs): void {
        $sTitle = '';

        if (count($aArgs) > 1) {
            $sTitle = array_shift($aArgs) . ': ';
        }

        if ($sTitle !== '') {
            if (is_object($aArgs[0])
            ||  is_array($aArgs[0])) {
                echo "$sTitle\n";
            } else {
                echo $sTitle;
            }
        }

        foreach ($aArgs as $mArg) {
            if (is_object($mArg)
            ||  is_array($mArg)) {
                echo print_r($mArg, true);
            } else {
                echo $mArg;
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
                    $sResponse .= ': ' . str_replace(dirname(__FILE__, 3), '', $oTrace['file']);
                }

                $sResponse .=  ': ' . $oTrace['function'];
                $aResponse[] = $sResponse;
            }

            if ($bReturn) {
                return $aResponse;
            }

            dbg($aResponse);
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
                    unset($mArg);
                }
            }
            unset($oTrace);

            if ($bReturn) {
                return $oBacktrace;
            }

            dbg($oBacktrace);
        }
    }