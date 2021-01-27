<?php
    namespace Enobrev;

    /**
     * @param string $sUnit first letter, b, k, m, g, t
     * @param int    $iPrecision
     * @param bool   $bIncludeUnit
     *
     * @return array
     */
    function system_memory(string $sUnit = 'k', int $iPrecision = 2, bool $bIncludeUnit = false): array {
        $sInfo  = file_get_contents("/proc/meminfo");
        $aData  = explode("\n", trim($sInfo));
        $aInfo  = [];
        $aUnits = ['b', 'k', 'm', 'g', 't'];
        $aSuffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $iBaseUnit  = array_search($sUnit, $aUnits);
        $sSuffix    = $aSuffixes[$iBaseUnit];

        foreach ($aData as $sLine) {
            [$sKey, $sValue] = explode(':', $sLine);
            $sTrimmed        = trim($sValue);
            $bKb             = strpos($sTrimmed, 'kB') !== false;
            $aInfo[$sKey]    = (int) $sTrimmed;


            if ($bKb && $aInfo[$sKey] !== 0) {
                $iBytes         = $aInfo[$sKey] * 1024;
                $iBase          = log($iBytes, 1024);
                $iAdjusted      = round(pow(1024, $iBase - $iBaseUnit), $iPrecision);
                $aInfo[$sKey]   = $bIncludeUnit ? "$iAdjusted $sSuffix" : $iAdjusted;
            }
        }

        return $aInfo;
    }

    /**
     * @param string $sUnit first letter, b, k, m, g, t
     * @param int    $iPrecision
     * @param bool   $bIncludeUnit
     *
     * @return int|null
     */
    function system_total_memory(string $sUnit = 'g', int $iPrecision = 2, bool $bIncludeUnit = false): ?float {
        $aMemory = system_memory($sUnit, $iPrecision, $bIncludeUnit);
        return $aMemory['MemTotal'] ?? null;
    }