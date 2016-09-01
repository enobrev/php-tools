<?php
    namespace Enobrev;

    use Monolog;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;

    class Log {
        /** @var Monolog\Logger */
        private static $oLog  = null;
        private static $sName = null;

        private static function init() {
            if (self::$oLog === null) {
                if (self::$sName === null) {
                    throw new \Exception("Please set a Name for the Logger using Enobrev\\Log::setName()");
                }

                self::$oLog = new Monolog\Logger(self::$sName);
                // $oFormatter = new LineFormatter("@cee: %context%");  // TODO: Activate @cee logger - will need to use config from build/ideas and add a mapper cron to ensure elasticsearch doesn't use resources trying to index all the response variables
                $oFormatter = new LineFormatter("%context%");
                $oSyslog    = new SyslogHandler('API');
                $oSyslog->setFormatter($oFormatter);
                self::$oLog->pushHandler($oSyslog);
            }

            return self::$oLog;
        }

        /**
         * Adds a log record at the designated level
         *
         * @param  int     $iLevel   The logging level
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        private static function addRecord($iLevel, $sMessage, array $aContext = array()) {
            return self::init()->addRecord($iLevel, $sMessage, array_merge(['action' => $sMessage], $aContext));
        }

        /**
         * @param string $sName
         */
        public static function setName(string $sName) {
            self::$sName = $sName;
        }

        /**
         * Adds a log record at the DEBUG level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function d($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::DEBUG, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the INFO level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function i($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::INFO, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the NOTICE level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function n($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::NOTICE, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the WARNING level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function w($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::WARNING, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ERROR level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function e($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::ERROR, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the CRITICAL level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function c($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ALERT level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function a($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the EMERGENCY level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function em($sMessage, array $aContext = array()) {
            self::addRecord(Monolog\Logger::EMERGENCY, $sMessage, $aContext);
        }
    }