<?php
    namespace Enobrev;

    use DateTime;
    use Adbar\Dot;

    class SpanMeta {
        private const TIMESTAMP_FORMAT = DATE_RFC3339_EXTENDED;

//      private const VERSION = 1: included tags, which were not used
//      private const VERSION = 2; included metrics and a memory-leaking timer
        private const VERSION = 3;

        private string $sName = '';

        private DateTime $oStart;

        private bool $bError;

        public Dot $Context;

        public TimeKeeper $Timer;

        public function __construct(DateTime $oStart) {
            $this->oStart   = $oStart;
            $this->bError   = false;
            $this->Context  = new Dot();
            $this->Timer    = new TimeKeeper('_REQUEST');
            $this->Timer->start();
        }

        public function getName():string {
            return $this->sName;
        }

        public function setName(string $sName):void {
            $this->sName = $sName;
        }

        public function setError(bool $bError):void {
            $this->bError = $bError;
        }

        public function hasName():bool {
            return !empty($this->sName);
        }

        public function getMessage(string $sService): array {
            return [
                '_format'         => 'SSFSpan.DashedTrace',
                'version'         => self::VERSION,
                'service'         => $sService,
                'name'            => $this->sName,
                'start_timestamp' => $this->oStart->format(self::TIMESTAMP_FORMAT),
                'end_timestamp'   => notNowButRightNow()->format(self::TIMESTAMP_FORMAT),
                'error'           => $this->bError,
                'context'         => $this->Context->all()
            ];
        }
    }