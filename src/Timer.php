<?php
    namespace Enobrev;

    class Timer {
        private $aTimers;
        
        public function __construct() {
            $this->aTimers = array();
        }

        /**
         * @param $sLabel
         */
        public function init($sLabel) {
            $this->aTimers[$sLabel] = array(
                'start' => 0,
                'stop'  => 0
            );
        }

        /**
         * @param string $sLabel
         * @return array
         */
        public function get($sLabel) {
            if (isset($this->aTimers[$sLabel])
            &&  isset($this->aTimers[$sLabel]['start'])) {
                $this->aTimers[$sLabel]['range'] = $this->aTimers[$sLabel]['stop'] - $this->aTimers[$sLabel]['start'];
                $this->aTimers[$sLabel]['range_human'] = sprintf("%01.2f", $this->aTimers[$sLabel]['range']);

                return $this->aTimers[$sLabel];
            }
        }

        /**
         * @return array
         */
        public function getAll() {
            $aReturn = array();
            $aReturn['__total__'] = array(
                'range'         => 0,
                'range_human'   => "0.00",
                'average'       => 0
            );
            foreach (array_keys($this->aTimers) as $sLabel) {
                $aReturn[$sLabel] = $this->get($sLabel);
                $aReturn['__total__']['range'] += $aReturn[$sLabel]['range'];
            }
            
            $aReturn['__total__']['range_human'] = sprintf("%01.2f", $aReturn['__total__']['range']);
            $aReturn['__total__']['average'] = sprintf("%01.2f", $aReturn['__total__']['range'] / count($this->aTimers));
            
            return $aReturn;
        }

        /**
         * @param string $sLabel
         */
        public function start($sLabel) {
            $this->init($sLabel);
            $this->aTimers[$sLabel]['start'] = $this->getTime();
            $this->aTimers[$sLabel]['stop']  = 0;
        }

        /**
         * @param string $sLabel
         */
        public function stop($sLabel) {
            $this->aTimers[$sLabel]['stop'] = $this->getTime();
        }

        /**
         * @return mixed
         */
        private function getTime() {
            return microtime(true) * 1000;
        }
    }
?>