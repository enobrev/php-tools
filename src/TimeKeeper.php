<?php
    namespace Enobrev;

    class TimeKeeper {
        private string $sLabel;

        private float $nStart;

        private float $nStop;

        /**
         * @param string $sLabel
         */
        public function __construct(string $sLabel) {
            $this->sLabel = $sLabel;
            $this->nStart = 0;
            $this->nStop  = 0;
        }

        /**
         * @return string
         */
        public function label(): string {
            return $this->sLabel;
        }

        public function start(): void {
            $this->nStart = $this->getTime();
            $this->nStop = 0;
        }

        /**
         * @return float
         */
        public function stop():float {
            if (!$this->stopped()) {
                $this->nStop = $this->getTime();
            }

            return $this->range();
        }

        /**
         * @return bool
         */
        public function started(): bool {
            return $this->nStart > 0;
        }

        /**
         * @return bool
         */
        public function stopped(): bool {
            return $this->nStop > 0;
        }

        /**
         * @return float
         */
        private function range(): float {
            return $this->nStop - $this->nStart;
        }

        /**
         * @return array
         */
        public function stats(): array {
            return [
                'start'       => $this->nStart,
                'stop'        => $this->nStop,
                'range'       => $this->range()
            ];
        }

        /**
         * @return float
         */
        private function getTime(): float {
            return microtime(true) * 1000;
        }
    }