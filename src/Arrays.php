<?php
    namespace Enobrev;

    /**
     * @param array $aArray
     * @param array ...$aKeys
     * @return array
     */
    function array_without_keys(array $aArray, ...$aKeys) {
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
    function array_is_multi(array $aArray) {
        foreach ($aArray as $mValue) {
            if (is_array($mValue)) return true;
        }
        return false;
    }