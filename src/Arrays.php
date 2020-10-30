<?php
    namespace Enobrev;

    /**
     * @param array $aArray
     * @param string[] $aKeys
     * @return array
     */
    function array_without_keys(array $aArray, ...$aKeys): array {
        $aDiff = [];
        foreach($aKeys as $sKey) {
            $aDiff[$sKey] = 1;
        }

        return array_diff_key($aArray, $aDiff);
    }

    /**
     * @param array $aArray
     * @return bool
     */
    function array_is_multi(array $aArray):bool {
        foreach ($aArray as $mValue) {
            if (is_array($mValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $sPath
     * @param mixed $mValue
     * @param string $sDelimiter
     * @return array
     */
    function array_from_path(string $sPath, $mValue, $sDelimiter = '.'): array {
        $aPath     = explode($sDelimiter, $sPath);
        $aResponse = $mValue;
        while(count($aPath) > 1) {
            $aResponse = array(array_pop($aPath) => $aResponse);
        }

        return $aResponse;
    }

    /**
     * @param array $aArray
     * @return bool
     */
    function array_not_associative(array $aArray):bool {
        if (count($aArray) === 0) {
            return true;
        }

        return array_keys($aArray) === range(0, count($aArray) - 1);
    }