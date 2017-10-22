<?php
    namespace Enobrev;

    class Timer {
        /**
         * @var TimeKeeper[][]
         */
        private $aTimers = [];

        /**
         * @param bool $bReturnTimers
         * @return array
         */
        public function stats($bReturnTimers = false) {
            $aReturn = [];
            $aReturn['__total__'] = [
                'count'         => 0,
                'range'       => 0,
                'average'     => 0
            ];

            if (count($this->aTimers)) {
                foreach ($this->aTimers as $sLabel => $aTimers) {
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

                $aReturn['__total__']['average'] = (float) sprintf("%01.2f", $aReturn['__total__']['range'] / count($this->aTimers));
            }

            return $aReturn;
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public function &get(string $sLabel) {
            if (isset($this->aTimers[$sLabel])) {
                $iTimers = count($this->aTimers[$sLabel]);
                if ($iTimers > 0) {
                    $oTimer = &$this->aTimers[$sLabel][$iTimers - 1];
                    return $oTimer;
                }
            }
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public function &start(string $sLabel) {
            if (!isset($this->aTimers[$sLabel])) {
                $this->aTimers[$sLabel] = [];
            }

            $oTimer = new TimeKeeper($sLabel);
            $oTimer->start();

            $this->aTimers[$sLabel][] = &$oTimer;

            return $oTimer;
        }

        /**
         * @param string $sLabel
         * @return float
         */
        public function stop(string $sLabel) {
            $oTimeKeeperr = &$this->get($sLabel);
            if ($oTimeKeeperr) {
                return $oTimeKeeperr->stop();
            }
        }
    }