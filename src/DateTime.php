<?php
    namespace Enobrev;

    use DateTime;

    /**
     * Returns DateTime object with proper microtime
     * @return DateTime
     */
    function notNowByRightNow() {
        $aMicroTime    = explode(' ', microtime());
        $iMicroSeconds = (int) $aMicroTime[0] * 1000000;
        return new DateTime(date('Y-m-d H:i:s.' . $iMicroSeconds, (int) $aMicroTime[1]));
    }