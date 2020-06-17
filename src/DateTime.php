<?php
    /** @noinspection SummerTimeUnsafeTimeManipulationInspection */

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

    /**
     * @param DateTime $oFrom
     * @param DateTime $oTo
     *
     * @return int
     */
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

    /**
     * @param DateTime $oFrom
     * @param DateTime $oTo
     *
     * @return string
     */
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

    // https://stackoverflow.com/a/4763921/14651
    function formatSeconds( float $nSeconds , int $iMSPlaces = 0): string {
        $iHours = 0;

        $nMS = $nSeconds - floor( $nSeconds );
        $nMSRounded = $iMSPlaces ? round($nMS, $iMSPlaces) : $nMS;
        $sMS = str_replace( "0.", '', $nMSRounded );

        if ( $nSeconds > 3600 )
        {
            $iHours = floor( $nSeconds / 3600 );
        }
        $nSeconds %= 3600;

        return str_pad( $iHours, 2, '0', STR_PAD_LEFT )
               . gmdate( ':i:s', $nSeconds )
               . ($sMS ? ".$sMS" : '')
            ;
    }

    function formatMilliseconds( float $nMS , int $iMSPlaces = 0 ): string {
        return formatSeconds($nMS / 1000, $iMSPlaces);
    }