<?php
    namespace Enobrev;

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
         * @return TimeKeeper|null
         */
        public function &get(string $sLabel): ?TimeKeeper {
            if (!isset($this->aTimers[$sLabel])) {
                return $this->start($sLabel);
            }

            $oTimer  = null;
            $iTimers = count($this->aTimers[$sLabel]);
            if ($iTimers > 0) {
                $oTimer = &$this->aTimers[$sLabel][$iTimers - 1];
            }

            return $oTimer;
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public function &start(string $sLabel): TimeKeeper {
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
         * @return float|null
         */
        public function stop(string $sLabel): ?float {
            $oTimeKeeper = &$this->get($sLabel);
            if ($oTimeKeeper) {
                return $oTimeKeeper->stop();
            }

            return null;
        }
    }