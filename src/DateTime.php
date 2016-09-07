<?php
    namespace Enobrev;

    use DateTime;

    /**
     * Returns DateTime object with proper microtime
     * @return DateTime
     */
    function notNowByRightNow() {
        $aMicroTime = explode(' ', microtime());
        return new DateTime(date('Y-m-d H:i:s.' . $aMicroTime[0] * 1000000, $aMicroTime[1]));
    }