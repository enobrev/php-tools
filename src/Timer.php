<?php
    namespace Enobrev;

    /**
     * @deprecated for causing memory leaks
     */
    class Timer {
        /**
         * @var TimeKeeper[][]
         */
        private array $aTimers = [];

        /**
         * @param bool $bReturnTimers
         * @return array
         */
        public function stats($bReturnTimers = false): array {
            $aReturn = [];

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
                    /** @noinspection DisconnectedForeachInstructionInspection */
                    unset($oTimer);

                    $aReturn[$sLabel] = [
                        'range'   => $iTotal,
                        'count'   => $iCount,
                        'average' => $iTotal / $iCount
                    ];

                    if ($bReturnTimers) {
                        $aReturn[$sLabel]['timers'] = $aStats;
                    }
                }
            }

            return $aReturn;
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public function &start(string $sLabel): TimeKeeper {
            $this->aTimers[$sLabel] = new TimeKeeper($sLabel);
            $this->aTimers[$sLabel]->start();

            return $this->aTimers[$sLabel];
        }

        /**
         * @param string $sLabel
         * @return float|null
         */
        public function stop(string $sLabel): ?float {
            if (!isset($this->aTimers[$sLabel])) {
                return null;
            }

            return $this->aTimers[$sLabel]->stop();
        }
    }