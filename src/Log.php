<?php
    namespace Enobrev;

    use Monolog;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;
    use DateTime;

    class Log {
        /** @var Monolog\Logger */
        private static $oLog  = null;

        /** @var string */
        private static $sName = null;

        /** @var string */
        private static $sRequestHash = null;

        /** @var string */
        private static $sThreadHash = null;

        /** @var array  */
        private static $aHashHistory = [];

        /** @var Timer */
        private static $oTimer = null;

        /** @var  Timer[] */
        private static $aTimers = [];

        /** @var array[]  */
        private static $aRequests = [];

        const FULL_TIMER = 'FULL_TIMER';

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
            $aContext        = array_merge(['action' => $sMessage], $aContext);
            $aContext['__r'] = self::getRequestHash();
            $aContext['__t'] = self::getThreadHash();
            $aContext['__p'] = self::getParentHash();

            return self::init()->addRecord($iLevel, $sMessage, $aContext);
        }

        /**
         * @param string $sName
         */
        public static function setName(string $sName) {
            self::$sName = $sName;
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
                self::$oTimer->stop($sLabel);
                $aTimer = self::$oTimer->get($sLabel);

                if (!isset(self::$aTimers[$sLabel])) {
                    self::$aTimers[$sLabel] = 0;
                }

                self::$aTimers[$sLabel] += $aTimer['range'];
                return $aTimer['range'];
            }
        }

        /**
         * @return string
         */
        private static function getThreadHash() {
            if (self::$sThreadHash !== NULL) {
                // Fall Through
            } else if (isset($_REQUEST['__t'])) {
                self::$sThreadHash = $_REQUEST['__t'];
            } else {
                self::$sThreadHash = substr(hash('sha1', (new DateTime())->format('Y-m-d G:i:s u')), 0, 6);
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
         * @internal param bool $bForceReset
         * @return string
         */
        private static function getRequestHash() {
            if (self::$sRequestHash == NULL) {
                $oNow   = new DateTime();
                $sIP    = get_ip();
                $sAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

                $aRequest = array(
                    'date' => $oNow->format('Y-m-d G:i:s u')
                );

                if (isset($_SERVER['HTTP_REFERER']))    { $aRequest['referrer']   = $_SERVER['HTTP_REFERER'];     }
                if (isset($_SERVER['REQUEST_URI']))     { $aRequest['uri']        = $_SERVER['REQUEST_URI'];      }
                if (isset($_SERVER['HTTP_HOST']))       { $aRequest['host']       = $_SERVER['HTTP_HOST'];        }
                if (strlen($sAgent))                    { $aRequest['agent']      = $sAgent;                      }
                if ($sIP != 'unknown')                  { $aRequest['ip']         = $sIP;                         }

                self::$sRequestHash = substr(hash('sha1', json_encode($aRequest)), 0, 8);

                $aMessage = array(
                    'action'    => 'Log.Start',
                    'meta'      => $aRequest,
                    '__r'       => self::$sRequestHash
                );

                self::$aRequests[self::$sRequestHash] = $aRequest;

                $sThreadHash = Log::getThreadHash();
                $sParentHash = Log::getParentHash();

                if ($sThreadHash) {
                    $aMessage['__t'] = $sThreadHash;
                }

                if ($sParentHash) {
                    $aMessage['__p'] = $sParentHash;
                }

                self::startTimer(self::$sRequestHash);
                return self::init()->addRecord(Monolog\Logger::INFO, $aMessage['action'], $aMessage);
            }

            return self::$sRequestHash;
        }

        public function __destruct() {
            try {
                $sRequestHash = Log::getRequestHash();
                $sThreadHash  = Log::getThreadHash();
                $sParentHash  = Log::getParentHash();

                $aMessage = array(
                    'action'    => 'Log.End',
                    'meta'      => self::$aRequests[$sRequestHash],
                    '__ms'      => self::stopTimer($sRequestHash),
                    '__timers'  => self::$aTimers
                );

                if ($sThreadHash) {
                    $aMessage['__t'] = $sThreadHash;
                }

                if ($sParentHash) {
                    $aMessage['__p'] = $sParentHash;
                }

                return self::init()->addRecord(Monolog\Logger::INFO, $aMessage['action'], $aMessage);
            } catch (\Exception $e) {
                // do nothing
            }
        }
    }