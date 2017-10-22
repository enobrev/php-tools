<?php
    namespace Enobrev;

    use DateTime;
    use Monolog;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;

    class Log {
        /** @var Monolog\Logger */
        private static $oLog  = null;

        /** @var string */
        private static $sService = null;

        /** @var bool */
        private static $bJSONLogs = false;

        /** @var string */
        private static $sThreadHash = null;

        /** @var Timer */
        private static $oTimer = null;

        /** @var array  */
        private static $aSpans = [];

        /** @var array  */
        private static $aSettings = [];

        /** @var array */
        private static $aGlobalContext = [];


        private static function init() {
            if (self::$oLog === null) {
                if (self::$sService === null) {
                    throw new \Exception("Please set a Service Name for the Logger using Enobrev\\Log::setService()");
                }

                register_shutdown_function(array(self::class, 'shutdown'));

                self::$oLog = new Monolog\Logger(self::$sService);

                if (self::$bJSONLogs) {
                    $oFormatter = new LineFormatter("@cee: %context%");
                } else {
                    $oFormatter = new LineFormatter("%context%");
                }

                $oSyslog    = new SyslogHandler('API');
                $oSyslog->setFormatter($oFormatter);
                self::$oLog->pushHandler($oSyslog);
            }

            self::initSpan();

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
            self::incrementCurrentIndex();

            $aLog  = array_merge(
                self::prepareContext($sMessage, $aContext),
                self::getCurrentSpan()
            );

            return self::init()->addRecord($iLevel, $sMessage, $aLog);
        }

        /**
         * @param       $sMessage
         * @param array $aContext
         * @return array
         */
        private static function prepareContext($sMessage, array $aContext = []) {
            $aLog = ['--action' => $sMessage];

            if ($aContext && is_array($aContext) && count($aContext)) {
                foreach ($aContext as $sKey => $mValue) {
                    if (strncmp($sKey, "--", 2) === 0) {
                        $aLog[$sKey] = $mValue;
                        unset($aContext[$sKey]);
                    }

                    if (strncmp($sKey, "#", 1) === 0) {
                        $sStrippedKey = str_replace('#', '', $sKey);

                        if (is_array($mValue) && isset(self::$aGlobalContext[$sStrippedKey]) && is_array(self::$aGlobalContext[$sStrippedKey])) {
                            self::$aGlobalContext[$sStrippedKey] = array_merge(self::$aGlobalContext[$sStrippedKey], $mValue);
                        } else {
                            self::$aGlobalContext[$sStrippedKey] = $mValue;
                        }

                        $aLog[$sStrippedKey] = $mValue;
                        unset($aContext[$sKey]);
                    }
                }

                self::assignArrayByPath($aLog, $sMessage, $aContext);
            }

            return $aLog;
        }

        /**
         * @param string $sService
         */
        public static function setService(string $sService) {
            self::$sService = $sService;
        }

        /**
         * @param string $sTag
         * @param        $mValue
         */
        public static function addTag(string $sTag, $mValue) {
            self::$aSettings[self::getCurrentRequestHash()]['tags'][$sTag] = $mValue;
        }

        /**
         * @param bool $bIsError
         */
        public static function setProcessIsError(bool $bIsError) {
            self::$aSettings[self::getCurrentRequestHash()]['error'] = $bIsError;
        }

        /**
         * @param string $sPurpose
         */
        public static function setPurpose(string $sPurpose) {
            self::$aSettings[self::getCurrentRequestHash()]['name'] = $sPurpose;
        }

        private static function incrementCurrentIndex() {
            self::$aSpans[count(self::$aSpans) - 1]['--i']++;
        }

        private static function getCurrentRequestHash() {
            return self::$aSpans[count(self::$aSpans) - 1]['--r'];
        }

        private static function getCurrentSpan() {
            return self::$aSpans[count(self::$aSpans) - 1];
        }

        private static function getCurrentSettings() {
            return self::$aSettings[self::getCurrentRequestHash()];
        }

        public static function enableJSON() {
            self::$bJSONLogs = true;
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
         * @param TimeKeeper $oTimer
         */
        public static function dt(TimeKeeper $oTimer) {
            self::d($oTimer->label(), ['--ms' => $oTimer->stop()]);
        }

        /**
         * @param $sLabel
         * @return TimeKeeper
         */
        public static function startTimer(string $sLabel) {
            if (self::$oTimer instanceof Timer === false) {
                self::$oTimer = new Timer();
            }

            return self::$oTimer->start($sLabel);
        }

        /**
         * @param $sLabel
         *
         * @return float
         */
        public static function stopTimer(string $sLabel) {
            if (self::$oTimer instanceof Timer) {
                return self::$oTimer->stop($sLabel);
            }
        }

        /**
         * @return string
         */
        public static function getRequestHashForOutput() {
            return self::getCurrentRequestHash();
        }

        /**
         * Sets the Parent Hash to the current Hash, and then resets the Request Hash
         */
        public static function startChildRequest() {
            self::initSpan();
        }

        /**
         * Retrieves the previous request hash
         */
        public static function endChildRequest() {
            self::stopTimer(self::getCurrentRequestHash());
            array_pop(self::$aSpans);;
        }

        /**
         * @return string
         */
        private static function getThreadHash() {
            if (self::$sThreadHash !== NULL) {
                // Fall Through
            } else if (isset($_REQUEST['--t'])) {
                self::$sThreadHash = $_REQUEST['--t'];
            } else if (isset($_SERVER['NGINX_REQUEST_ID'])) {
                self::$sThreadHash = $_SERVER['NGINX_REQUEST_ID'];
            } else {
                self::$sThreadHash = substr(hash('sha1', notNowByRightNow()->format('Y-m-d G:i:s.u')), 0, 6);
            }

            return self::$sThreadHash;
        }

        private static function initSpan() {
            $oStartTime      = notNowByRightNow();
            $sIP             = get_ip();
            $aRequestDetails = [
                'date' => $oStartTime->format('Y-m-d H:i:s.u')
            ];

            if (isset($_SERVER['HTTP_REFERER']))    { $aRequestDetails['referrer']   = $_SERVER['HTTP_REFERER'];     }
            if (isset($_SERVER['REQUEST_URI']))     { $aRequestDetails['uri']        = $_SERVER['REQUEST_URI'];      }
            if (isset($_SERVER['HTTP_HOST']))       { $aRequestDetails['host']       = $_SERVER['HTTP_HOST'];        }
            if (isset($_SERVER['HTTP_USER_AGENT'])) { $aRequestDetails['agent']      = $_SERVER['HTTP_USER_AGENT'];  }
            if ($sIP != 'unknown')                  { $aRequestDetails['ip']         = $sIP;                         }

            $aPath        = array_column(self::$aSpans, '--r');
            $sPath        = count($aPath) > 0 ? implode('.', $aPath) . '.' : '';
            $sRequestHash = $sPath . substr(hash('sha1', json_encode($aRequestDetails)), 0, 6);

            $aSpan = [
                '--i'      => 1,
                '--r'      => $sRequestHash
            ];

            self::$aSettings[$sRequestHash] = [
                'name'            => '',
                'start_timestamp' => $oStartTime->format(DateTime::ATOM),
                'error'           => false,
                'tags'            => []
            ];

            if ($sThreadHash = Log::getThreadHash()) {
                $aSpan['--t'] = $sThreadHash;
            }

            if (count(self::$aSpans) > 0) {
                $aSpan['--p'] = self::$aSpans[count(self::$aSpans) - 1]['--r'];
            } else if (isset($_REQUEST['--p'])) {
                $aSpan['--p'] = $_REQUEST['--p'];
            }

            $aInit = [
                'meta' => $aRequestDetails
            ];

            $aUser = [];

            if ($sIP) {
                $aUser['ip']    = $sIP;
            }

            if (isset($aRequestDetails['agent'])) {
                $aUser['agent'] = $aRequestDetails['agent'];
            }

            if (count($aUser)) {
                $aInit['#user'] = $aUser;
            }

            $aMessage = array_merge(
                self::prepareContext(self::$sService . '.Init', $aInit),
                $aSpan
            );

            self::startTimer($sRequestHash);
            self::init()->addRecord(Monolog\Logger::INFO, $aMessage['--action'], $aMessage);

            self::$aSpans[] = $aSpan;
        }

        public static function shutdown() {
            self::incrementCurrentIndex();
            self::stopTimer(self::getCurrentRequestHash());

            $aTimers  = self::$oTimer->stats();
            $aMessage = array_merge(
                self::prepareContext(self::$sService . '.Summary', [
                    '--format'        => 'SSFSpan.DashedTrace',
                    'version'         => 1,
                    'end_timestamp'   => notNowByRightNow()->format(DateTime::ATOM),
                    'service'         => self::$sService,
                    'metrics'         => json_encode($aTimers),
                    'indicator'       => false,
                    'context'         => self::$aGlobalContext
                ]),
                self::getCurrentSettings(),
                self::getCurrentSpan()
            );

            self::init()->addRecord(Monolog\Logger::INFO, $aMessage['--action'], $aMessage);
        }
    }