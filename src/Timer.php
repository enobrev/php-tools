<?php
    namespace Enobrev;

    class Timer {
        /**
         * @var TimeKeeper[][]
         */
        private static $aTimers = [];

        /**
         * @param bool $bReturnTimers
         * @return array
         */
        public static function stats($bReturnTimers = false) {
            $aReturn = [];
            $aReturn['__total__'] = [
                'count'         => 0,
                'range'       => 0,
                'average'     => 0
            ];

            if (count(self::$aTimers)) {
                foreach (self::$aTimers as $sLabel => $aTimers) {
                    $iTotal = 0;
                    $iCount = 0;
                    $aStats = [];

                    foreach ($aTimers as &$oTimer) {
                        if ($oTimer->started()) {
                            $oTimer->stop();
                            $aTimerStats = $oTimer->stats();

                            $iTotal += $aTimerStats['range'];
                            $iCount++;

                            $aStats[] = $aTimerStats;
                        }
                    }

                    $aReturn[$sLabel] = [
                        'range'   => $iTotal,
                        'count'   => $iCount,
                        'average' => $iTotal / $iCount
                    ];

                    if ($bReturnTimers) {
                        $aReturn[$sLabel]['timers'] = $aStats;
                    }

                    $aReturn['__total__']['range'] += $aReturn[$sLabel]['range'];
                    $aReturn['__total__']['count'] += $aReturn[$sLabel]['count'];
                }

                $aReturn['__total__']['average'] = (float) sprintf("%01.2f", $aReturn['__total__']['range'] / count(self::$aTimers));
            }

            return $aReturn;
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public static function &get(string $sLabel) {
            if (isset(self::$aTimers[$sLabel])) {
                $iTimers = count(self::$aTimers[$sLabel]);
                if ($iTimers > 0) {
                    $oTimer = &self::$aTimers[$sLabel][$iTimers - 1];
                    return $oTimer;
                }
            }
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public static function &start(string $sLabel) {
            if (!isset(self::$aTimers[$sLabel])) {
                self::$aTimers[$sLabel] = [];
            }

            $oTimer = new TimeKeeper($sLabel);
            $oTimer->start();

            self::$aTimers[$sLabel][] = &$oTimer;

            return $oTimer;
        }

        /**
         * @param string $sLabel
         * @return float
         */
        public static function stop(string $sLabel) {
            $oTimer = &self::get($sLabel);
            if ($oTimer) {
                return $oTimer->stop();
            }
        }
    }

