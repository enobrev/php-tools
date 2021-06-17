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

    /**
     * @param array    $aArray
     * @param callable $fFind
     *
     * @return mixed|null
     */
    function array_find(iterable $aArray, callable $fFind) {
        foreach ($aArray as $mItem) {
            if ($fFind($mItem) === true) {
                return $mItem;
            }
        }

        return null;
    }

    /**
     * @param array    $aArray
     * @param callable $fFind
     *
     * @return mixed|null
     */
    function array_find_index(iterable $aArray, callable $fFind) {
        foreach ($aArray as $iIndex => $mItem) {
            if ($fFind($mItem) === true) {
                return $iIndex;
            }
        }

        return null;
    }

    /**
     * Similar to array_find but instead of returning the array item, it returns the callable value
     * @param iterable $aArray
     * @param callable $fFind
     *
     * @return null
     */
    function array_find_value(iterable $aArray, callable $fFind) {
        foreach ($aArray as $mItem) {
            $mValue = $fFind($mItem);
            if ($mValue !== null) {
                return $mValue;
            }
        }

        return null;
    }