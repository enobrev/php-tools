<?php
    namespace Enobrev;

    class Timer {
        /** @var array  */
        private $aTimers;

        /** @var int[] */
        private $aIndices;

        /** @var bool  */
        private $bReturnTimers = false;

        /**
         * Timer constructor.
         * @param bool $bReturnTimers
         */
        public function __construct(bool $bReturnTimers = false) {
            $this->aTimers          = [];
            $this->aIndices         = [];
            $this->bReturnTimers    = $bReturnTimers;
        }

        /**
         * @param bool $bReturnTimers
         */
        public function shouldReturnTimers(bool $bReturnTimers) {
            $this->bReturnTimers = $bReturnTimers;
        }

        /**
         * @param $sLabel
         * @return int
         */
        private function init($sLabel) {
            if (!isset($this->aTimers[$sLabel])) {
                $this->aTimers[$sLabel] = [];
            }

            $this->aTimers[$sLabel][] = [
                'start' => 0,
                'stop'  => 0
            ];

            $this->aIndices[$sLabel] = count($this->aTimers[$sLabel]) - 1;

            return $this->aIndices[$sLabel];
        }

        /**
         * @param string $sLabel
         * @return array
         */
        public function get($sLabel) {
            if (isset($this->aTimers[$sLabel])
            &&  count($this->aTimers[$sLabel])) {
                $aTimers = $this->aTimers[$sLabel];
                $iTotal  = 0;
                $iCount  = 0;
                foreach($aTimers as &$aTimer) {
                    if (isset($aTimer['start'])) {
                        $aTimer['range']       = $aTimer['stop'] - $aTimer['start'];
                        $aTimer['range_human'] = sprintf("%01.2f", $aTimer['range']);

                        $iTotal += $aTimer['range'];
                        $iCount++;
                    }
                }

                $aReturn = [
                    'label'         => $sLabel,
                    'total'         => $iTotal,
                    'total_human'   => sprintf("%01.2f", $iTotal),
                    'average'       => sprintf("%01.2f", $iTotal / $iCount),
                    'count'         => $iCount
                ];

                if ($this->bReturnTimers) {
                    $aReturn['timers'] = $aTimers;
                }

                return $aReturn;
            }
        }

        /**
         * @return array
         */
        public function getAll() {
            $aReturn = array();
            $aReturn['__total__'] = array(
                'count'         => 0,
                'range'         => 0,
                'range_human'   => "0.00",
                'average'       => 0
            );

            foreach (array_keys($this->aTimers) as $sLabel) {
                $aReturn[$sLabel] = $this->get($sLabel);
                if ($aReturn[$sLabel]) {
                    $aReturn['__total__']['range'] += $aReturn[$sLabel]['total'];
                    $aReturn['__total__']['count'] += $aReturn[$sLabel]['count'];
                }
            }

            $aReturn['__total__']['range_human'] = sprintf("%01.2f", $aReturn['__total__']['range']);
            $aReturn['__total__']['average']     = sprintf("%01.2f", $aReturn['__total__']['range'] / $aReturn['__total__']['count']);

            return $aReturn;
        }

        /**
         * @param string $sLabel
         */
        public function start($sLabel) {
            $iIndex = $this->init($sLabel);
            $this->aTimers[$sLabel][$iIndex]['start'] = $this->getTime();
            $this->aTimers[$sLabel][$iIndex]['stop']  = 0;
        }

        /**
         * @param string $sLabel
         */
        public function stop($sLabel) {
            $this->aTimers[$sLabel][$this->aIndices[$sLabel]]['stop'] = $this->getTime();
        }

        /**
         * @return mixed
         */
        private function getTime() {
            return microtime(true) * 1000;
        }
    }