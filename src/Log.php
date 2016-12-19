<?php
    namespace Enobrev;

    use Monolog;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;

    class Log {
        /** @var Monolog\Logger */
        private static $oLog  = null;

        /** @var string */
        private static $sName = null;

        /** @var bool */
        private static $bJSONLogs = false;

        /** @var int */
        private static $iLogIndex = null;

        /** @var string */
        private static $sRequestHash = null;

        /** @var string */
        private static $sThreadHash = null;

        /** @var array  */
        private static $aHashHistory = [];

        /** @var Timer */
        private static $oTimer = null;

        /** @var array[]  */
        private static $aRequests = [];


        private static function init() {
            if (self::$oLog === null) {
                if (self::$sName === null) {
                    throw new \Exception("Please set a Name for the Logger using Enobrev\\Log::setName()");
                }

                register_shutdown_function(array(self::class, 'shutdown'));

                self::$oLog = new Monolog\Logger(self::$sName);

                if (self::$bJSONLogs) {
                    $oFormatter = new LineFormatter("@cee: %context%");
                } else {
                    $oFormatter = new LineFormatter("%context%");
                }

                $oSyslog    = new SyslogHandler('API');
                $oSyslog->setFormatter($oFormatter);
                self::$oLog->pushHandler($oSyslog);
            }

            return self::$oLog;
        }

        private static function assignArrayByPath(&$arr, $path, $value, $separator = '.') {
            $keys = explode($separator, $path);

            foreach ($keys as $key) {
                $arr = &$arr[$key];
            }

            $arr = $value;
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
            $aLog = self::prepareContext($sMessage, $aContext);

            if ($sRequestHash = self::getRequestHash()) {
                $aLog['--r'] = $sRequestHash;
            }

            if ($sThreadHash = self::getThreadHash()) {
                $aLog['--t'] = $sThreadHash;
            }

            if ($sParentHash = self::getParentHash()) {
                $aLog['--p'] = $sParentHash;
            }

            $aLog['--i'] = self::getLogIndex();

            return self::init()->addRecord($iLevel, $sMessage, $aLog);
        }

        /**
         * @param       $sMessage
         * @param array $aContext
         * @return array
         */
        private static function prepareContext($sMessage, array $aContext = []) {
            $aLog = ['action' => $sMessage];

            if ($aContext && is_array($aContext) && count($aContext)) {
                foreach ($aContext as $sKey => $mValue) {
                    if (strncmp($sKey, "--", 2) === 0) {
                        $aLog[$sKey] = $mValue;
                        unset($aContext[$sKey]);
                    }
                }

                self::assignArrayByPath($aLog, $sMessage, $aContext);
            }

            return $aLog;
        }

        /**
         * @param string $sName
         */
        public static function setName(string $sName) {
            self::$sName = $sName;
        }

        public static function enableJSON() {
            self::$bJSONLogs = true;
        }

        /**
         * Sets the Parent Hash to the current Hash, and then resets the Request Hash
         */
        public static function startChildRequest() {
            self::$aHashHistory[] = self::getRequestHash();
            self::$sRequestHash = null;
        }

        /**
         * Retrieves the previous request hash
         */
        public static function endChildRequest() {
            self::stopTimer(self::$sRequestHash);
            self::$sRequestHash = array_pop(self::$aHashHistory);
        }

        /**
         * Adds a log record at the DEBUG level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function d($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::DEBUG, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the INFO level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function i($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::INFO, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the NOTICE level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function n($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::NOTICE, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the WARNING level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function w($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::WARNING, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ERROR level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function e($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::ERROR, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the CRITICAL level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function c($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ALERT level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function a($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the EMERGENCY level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return Boolean Whether the record has been processed
         */
        public static function em($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::EMERGENCY, $sMessage, $aContext);
        }

        /**
         * @param $sLabel
         */
        public static function startTimer($sLabel) {
            if (self::$oTimer instanceof Timer === false) {
                self::$oTimer = new Timer();
            }

            self::$oTimer->start($sLabel);
        }

        /**
         * @param $sLabel
         *
         * @return float
         */
        public static function stopTimer($sLabel) {
            if (self::$oTimer instanceof Timer) {
                return self::$oTimer->stop($sLabel);
            }
        }

        /**
         * @return string
         */
        private static function getThreadHash() {
            if (self::$sThreadHash !== NULL) {
                // Fall Through
            } else if (isset($_REQUEST['--t'])) {
                self::$sThreadHash = $_REQUEST['--t'];
            } else {
                self::$sThreadHash = substr(hash('sha1', notNowByRightNow()->format('Y-m-d G:i:s.u')), 0, 6);
            }

            return self::$sThreadHash;
        }

        /**
         * @return string
         */
        private static function getParentHash() {
            $iHashHistory = count(self::$aHashHistory);
            if ($iHashHistory > 0) {
                return self::$aHashHistory[$iHashHistory - 1];
            }
        }

        /**
         * @return string
         */
        private static function getLogIndex() {
            if (self::$iLogIndex === null) {
                self::$iLogIndex = 0;
            }

            self::$iLogIndex++;
            return self::$iLogIndex;
        }

        /**
         * @return string
         */
        private static function getParentPath() {
            if (count(self::$aHashHistory)) {
                return implode('.', self::$aHashHistory) . '.';
            }

            return '';
        }

        /**
         * @internal param bool $bForceReset
         * @return string
         */
        private static function getRequestHash() {
            if (self::$sRequestHash == NULL) {
                $sIP    = get_ip();
                $sAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

                $aRequest   = array(
                    'date' => notNowByRightNow()->format('Y-m-d H:i:s.u')
                );

                if (isset($_SERVER['HTTP_REFERER']))    { $aRequest['referrer']   = $_SERVER['HTTP_REFERER'];     }
                if (isset($_SERVER['REQUEST_URI']))     { $aRequest['uri']        = $_SERVER['REQUEST_URI'];      }
                if (isset($_SERVER['HTTP_HOST']))       { $aRequest['host']       = $_SERVER['HTTP_HOST'];        }
                if (strlen($sAgent))                    { $aRequest['agent']      = $sAgent;                      }
                if ($sIP != 'unknown')                  { $aRequest['ip']         = $sIP;                         }

                self::$sRequestHash = self::getParentPath() . substr(hash('sha1', json_encode($aRequest)), 0, 6);

                $aMessage = self::prepareContext('Log.Start', [
                    'meta' => $aRequest,
                    '--r'  => self::$sRequestHash
                ]);

                self::$aRequests[self::$sRequestHash] = $aRequest;

                if ($sThreadHash = Log::getThreadHash()) {
                    $aMessage['--t'] = $sThreadHash;
                }

                if ($sParentHash = Log::getParentHash()) {
                    $aMessage['--p'] = $sParentHash;
                }

                if (!self::$iLogIndex) {
                    self::$iLogIndex = 1;
                }

                $aMessage['--i'] = self::getLogIndex();

                self::startTimer(self::$sRequestHash);
                self::init()->addRecord(Monolog\Logger::INFO, $aMessage['action'], $aMessage);
            }

            return self::$sRequestHash;
        }

        public static function shutdown() {
            $sRequestHash = Log::getRequestHash();

            self::stopTimer($sRequestHash);
            $aTimers = self::$oTimer->getAll();

            $aMessage = self::prepareContext('Log.End', [
                'meta'     => self::$aRequests[$sRequestHash],
                '--r'      => $sRequestHash,
                '--ms'     => $aTimers['__total__']['range'],
                '--timer'  => $aTimers['__total__'],
                '--timers' => json_encode($aTimers)
            ]);

            if ($sThreadHash  = Log::getThreadHash()) {
                $aMessage['--t'] = $sThreadHash;
            }

            if ($sParentHash  = Log::getParentHash()) {
                $aMessage['--p'] = $sParentHash;
            }

            $aMessage['--i'] = self::getLogIndex();

            self::init()->addRecord(Monolog\Logger::INFO, $aMessage['action'], $aMessage);
        }
    }