<?php
    namespace Enobrev;

    use DateTime;

    /**
     * https://stackoverflow.com/a/29598719/14651
     *
     * Returns DateTime object with proper microtime
     * @return DateTime
     */
    function notNowButRightNow() {
        return DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
    }

    function minutes(DateTime $oFrom, DateTime $oTo):int {
        $oDiff  = $oFrom->diff($oTo);
        $iDiff  = $oDiff->d * 24 * 60;
        $iDiff += $oDiff->h * 60;
        $iDiff += $oDiff->i;

        if ($oDiff->invert) {
            return -$iDiff;
        }

        return $iDiff;
    }

    function minutes_ago(DateTime $oFrom, DateTime $oTo) {
        $oDiff  = $oFrom->diff($oTo);
        $iDiff  = $oDiff->d * 24 * 60;
        $iDiff += $oDiff->h * 60;
        $iDiff += $oDiff->i;

        $sMinutes = abs($iDiff) === 1 ? 'minute' : 'minutes';

        if ($iDiff === 0) {
            return 'now';
        }

        if ($oDiff->invert) {
            return "$iDiff $sMinutes ago";
        }

        return "in $iDiff $sMinutes";
    }