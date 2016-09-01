<?php
    namespace Enobrev;

    /**
     * @param string $sPath
     * @param mixed $mValue
     * @param string $sDelimiter
     * @return array
     */
    function array_from_path($sPath, $mValue, $sDelimiter = '.') {
        $aPath     = explode($sDelimiter, $sPath);
        $aResponse = $mValue;
        while(count($aPath) > 1) {
            $aResponse = array(array_pop($aPath) => $aResponse);
        }

        return $aResponse;
    }