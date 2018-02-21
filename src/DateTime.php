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