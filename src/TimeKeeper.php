<?php
    namespace Enobrev;

    class TimeKeeper {
        /**
         * @var string
         */
        private $sLabel;

        /**
         * @var float
         */
        private $nStart;

        /**
         * @var float
         */
        private $nStop;

        /**
         * @param string $sLabel
         */
        public function __construct(string $sLabel) {
            $this->sLabel = $sLabel;
            $this->nStart = 0;
            $this->nStop = 0;
        }

        /**
         * @return string
         */
        public function label() {
            return $this->sLabel;
        }

        public function start() {
            $this->nStart = $this->getTime();
            $this->nStop = 0;
        }

        /**
         * @return float
         */
        public function stop() {
            if (!$this->stopped()) {
                $this->nStop = $this->getTime();
            }

            return $this->range();
        }

        /**
         * @return bool
         */
        public function started() {
            return $this->nStart > 0;
        }

        /**
         * @return bool
         */
        public function stopped() {
            return $this->nStop > 0;
        }

        /**
         * @return float
         */
        private function range() {
            return $this->nStop - $this->nStart;
        }

        /**
         * @return array
         */
        public function stats() {
            return [
                'start'       => $this->nStart,
                'stop'        => $this->nStop,
                'range'       => $this->range()
            ];
        }

        /**
         * @return float
         */
        private function getTime() {
            return microtime(true) * 1000;
        }
    }