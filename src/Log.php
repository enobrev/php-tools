<?php
    namespace Enobrev;

    use Adbar\Dot;
    use Monolog;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;
    use Psr\Http\Message\ServerRequestInterface;
    use Zend\Diactoros\ServerRequestFactory;

    class Log {
        /** @var Monolog\Logger */
        private static $oLog  = null;

        /** @var ServerRequestInterface */
        private static $oServerRequest = null;

        /** @var string */
        private static $sService = 'Enobrev_Logger_Replace_Me';

        /** @var bool */
        private static $bJSONLogs = false;

        /** @var string */
        private static $sThreadHash = null;

        /** @var array  */
        private static $aSpans = [];

        /** @var array  */
        private static $aSettings = [];

        /** @var  int */
        private static $iGlobalIndex = 0;

        const TIMESTAMP_FORMAT = DATE_RFC3339_EXTENDED;

        /**
         * @return Monolog\Logger
         */
        private static function initLogger() {
            if (self::$oLog === null) {
                register_shutdown_function([self::class, 'summary']);

                self::$oLog = new Monolog\Logger(self::$sService);

                if (self::$bJSONLogs) {
                    $oFormatter = new LineFormatter("@cee: %context%");
                } else {
                    $oFormatter = new LineFormatter("%context%");
                }

                $oSyslog = new SyslogHandler('API');
                $oSyslog->setFormatter($oFormatter);
                self::$oLog->pushHandler($oSyslog);
            }

            return self::$oLog;
        }

        /**
         * Adds a log record at the designated level
         *
         * @psalm-suppress InvalidReturnType
         * @psalm-suppress InvalidReturnStatement
         * @psalm-suppress UndefinedClass
         * @param  int     $iLevel   The logging level
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        private static function addRecord(int $iLevel, string $sMessage, array $aContext = array()): bool {
            self::incrementCurrentIndex();

            $aLog    = array_merge(
                self::prepareContext($sMessage, $aContext),
                self::getCurrentSpan()
            );

            return self::initLogger()->addRecord($iLevel, $sMessage, $aLog);
        }

        /**
         * @param string $sMessage
         * @param array  $aContext
         * @return array
         */
        private static function prepareContext(string $sMessage, array $aContext = []): array {
            self::$iGlobalIndex++;

            $oLog = new Dot([
                '--action' => $sMessage,
                '--i'      => self::$iGlobalIndex
            ]);

            $sRequestHash  = self::getCurrentRequestHash();
            $oLocalContext = new Dot(self::$aSettings[$sRequestHash]['context']);

            if ($aContext && is_array($aContext) && count($aContext)) {
                foreach ($aContext as $sKey => $mValue) {
                    if (strncmp($sKey, "--", 2) === 0) {
                        if (!is_scalar($mValue)) {
                            $oLog->mergeRecursiveDistinct($sKey, $mValue);
                        } else {
                            $oLog->set($sKey, $mValue);
                        }
                        unset($aContext[$sKey]);
                    }

                    if (strncmp($sKey, "#", 1) === 0) {
                        $sStrippedKey = str_replace('#', '', $sKey);
                        $aContext[$sStrippedKey] = $mValue;
                        unset($aContext[$sKey]);

                        $oLocalContext->mergeRecursiveDistinct($sStrippedKey, $mValue);
                    }
                }

                if (count($aContext)) {
                    $oLog->mergeRecursiveDistinct($sMessage, $aContext);
                }
            }

            self::$aSettings[$sRequestHash]['context'] = $oLocalContext->all();
            return $oLog->all();
        }

        /**
         * @param string $sService
         */
        public static function setService(string $sService): void {
            self::$sService = $sService;
        }

        /**
         * @param array $aContext
         */
        public static function justAddContext(array $aContext): void {
            self::prepareContext('', $aContext);
        }

        /**
         * @param string $sTag
         * @param mixed  $mValue
         */
        public static function addTag(string $sTag, $mValue): void {
            self::$aSettings[self::getCurrentRequestHash()]['tags'][$sTag] = $mValue;
        }

        /**
         * @param bool $bIsError
         */
        public static function setProcessIsError(bool $bIsError): void {
            self::$aSettings[self::getCurrentRequestHash()]['error'] = $bIsError;
        }

        /**
         * @param string $sPurpose
         */
        public static function setPurpose(string $sPurpose): void {
            self::$aSettings[self::getCurrentRequestHash()]['name'] = $sPurpose;
        }

        private static function incrementCurrentIndex(): void {
            $iSpans = count(self::$aSpans);
            if ($iSpans <= 0) {
                return;
            }

            self::$aSpans[$iSpans - 1]['--ri']++;
        }

        private static function getCurrentRequestHash(): string {
            return self::$aSpans[count(self::$aSpans) - 1]['--r'];
        }

        private static function getCurrentSpan(): array {
            return self::$aSpans[count(self::$aSpans) - 1];
        }

        private static function getCurrentSettings(): array {
            return self::$aSettings[self::getCurrentRequestHash()];
        }

        public static function enableJSON(): void {
            self::$bJSONLogs = true;
        }

        /**
         * Adds a log record at the DEBUG level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function d($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::DEBUG, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the INFO level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function i($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::INFO, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the NOTICE level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function n($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::NOTICE, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the WARNING level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function w($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::WARNING, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ERROR level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function e($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::ERROR, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the CRITICAL level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function c($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ALERT level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function a($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::ALERT, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the EMERGENCY level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function em($sMessage, array $aContext = array()) {
            return self::addRecord(Monolog\Logger::EMERGENCY, $sMessage, $aContext);
        }

        /**
         * @param TimeKeeper $oTimer
         * @param array      $aContext
         */
        public static function dt(TimeKeeper $oTimer, array $aContext = []): void {
            $aContext['--ms'] = $oTimer->stop();
            self::d($oTimer->label(), $aContext);
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public static function startTimer(string $sLabel): TimeKeeper {
            return self::$aSettings[self::getCurrentRequestHash()]['metrics']->start($sLabel);
        }

        /**
         * @param string $sLabel
         *
         * @return float
         */
        public static function stopTimer(string $sLabel): float {
            return self::$aSettings[self::getCurrentRequestHash()]['metrics']->stop($sLabel);
        }

        /**
         * @return string
         */
        public static function getRequestHashForOutput(): string {
            return self::getCurrentRequestHash();
        }

        /**
         * @return string
         */
        public static function getThreadHashForOutput(): string {
            return self::getThreadHash();
        }

        /**
         * Sets the Parent Hash to the current Hash, and then resets the Request Hash
         */
        public static function startChildRequest(): void {
            $aContext  = [];
            $aSettings = self::getCurrentSettings();
            if (isset($aSettings['context']['user'])) {
                $aContext['user'] = $aSettings['context']['user'];
            }

            self::initSpan(self::$oServerRequest, $aContext);
        }

        /**
         * Retrieves the previous request hash
         */
        public static function endChildRequest(): void {
            self::stopTimer('_REQUEST');
            self::summary();
            array_pop(self::$aSpans);
        }

        /**
         * @return string
         */
        private static function getThreadHash(): string {
            if (self::$sThreadHash !== NULL) {
                return self::$sThreadHash;
            }

            if (self::$oServerRequest) {
                $aGetParams = self::$oServerRequest->getQueryParams();
                if ($aGetParams && isset($aGetParams['--t'])) {
                    self::$sThreadHash = $aGetParams['--t'];
                    return self::$sThreadHash;
                }

                $aPostParams = self::$oServerRequest->getParsedBody();
                if ($aPostParams && isset($aPostParams['--t'])) {
                    self::$sThreadHash = $aPostParams['--t'];
                    return self::$sThreadHash;
                }

                $aHeader = self::$oServerRequest->getHeader('--t');
                if (is_array($aHeader) && count($aHeader)) {
                    self::$sThreadHash = $aHeader[0];
                    return self::$sThreadHash;
                }

                self::parseJSONBodyForIndices();
                if (isset(self::$aIndices['--t'])) {
                    self::$sThreadHash = self::$aIndices['--t'];
                    return self::$sThreadHash;
                }
            }

            if (isset($_REQUEST['--t'])) {
                self::$sThreadHash = $_REQUEST['--t'];
                return self::$sThreadHash;
            }

            if (self::$oServerRequest) {
                $aServerParams = self::$oServerRequest->getServerParams();
                if ($aServerParams && isset($aServerParams['NGINX_REQUEST_ID'])) {
                    // NGINX Request ID should be last because that should always be set, but
                    // We prefer to use any thread hash sent in from another proces
                    self::$sThreadHash = $aServerParams['NGINX_REQUEST_ID'];
                    return self::$sThreadHash;
                }
            }

            if (isset($_SERVER['NGINX_REQUEST_ID'])) {
                // NGINX Request ID should be last because that should always be set, but
                // We prefer to use any thread hash sent in from another proces
                self::$sThreadHash = $_SERVER['NGINX_REQUEST_ID'];
                return self::$sThreadHash;
            }

            self::$sThreadHash = substr(hash('sha1', notNowButRightNow()->format('Y-m-d G:i:s.u')), 0, 6);
            return self::$sThreadHash;
        }

        private static $aIndices    = [];
        private static $bJSONParsed = false;

        private static function parseJSONBodyForIndices() {
            if (self::$bJSONParsed) {
                return;
            }

            self::$bJSONParsed = true;

            if (self::$oServerRequest) {
                $aContentType = self::$oServerRequest->getHeader('Content-Type');

                if ($aContentType) {
                    $aParts = explode(';', $aContentType[0]);
                    $sMime = trim(array_shift($aParts));

                    if (preg_match('~[/+]json$~', $sMime)) {
                        $sBody = (string)self::$oServerRequest->getBody();

                        if (!empty($sBody)) {
                            $aParsedBody = json_decode($sBody, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                if (isset($aParsedBody['--t'])) {
                                    self::$aIndices['--t'] = $aParsedBody['--t'];
                                }

                                if (isset($aParsedBody['--p'])) {
                                    self::$aIndices['--p'] = $aParsedBody['--p'];
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * @return string
         */
        private static function getParentHash(): ?string {
            if (self::$oServerRequest) {
                $aGetParams = self::$oServerRequest->getQueryParams();
                if ($aGetParams && isset($aGetParams['--p'])) {
                    return $aGetParams['--p'];
                }

                $aPostParams = self::$oServerRequest->getParsedBody();
                if ($aPostParams && isset($aPostParams['--p'])) {
                    return $aPostParams['--p'];
                }

                $aHeader = self::$oServerRequest->getHeader('--p');
                if (is_array($aHeader) && count($aHeader)) {
                    return $aHeader[0];
                }

                self::parseJSONBodyForIndices();
                if (isset(self::$aIndices['--p'])) {
                    self::$sThreadHash = self::$aIndices['--p'];
                    return self::$sThreadHash;
                }
            }

            if (isset($_REQUEST['--p'])) {
                return $_REQUEST['--p'];
            }

            return null;
        }

        /**
         * @param ServerRequestInterface $oRequest
         * @param array $aContext
         */
        public static function initSpan(ServerRequestInterface $oRequest, array $aContext = []): void {
            self::$oServerRequest = $oRequest;

            $oStartTime          = notNowButRightNow();
            $sIP                 = get_ip();
            $aRequestDetails     = [
                'date' => $oStartTime->format('Y-m-d H:i:s.u')
            ];

            $aServerParams       = self::$oServerRequest->getServerParams();

            if (isset($aServerParams['HTTP_REFERER']))    { $aRequestDetails['referrer']   = $aServerParams['HTTP_REFERER'];     }
            if (isset($aServerParams['REQUEST_URI']))     { $aRequestDetails['uri']        = $aServerParams['REQUEST_URI'];      }
            if (isset($aServerParams['HTTP_HOST']))       { $aRequestDetails['host']       = $aServerParams['HTTP_HOST'];        }
            if (isset($aServerParams['HTTP_USER_AGENT'])) { $aRequestDetails['agent']      = $aServerParams['HTTP_USER_AGENT'];  }
            if ($sIP != 'unknown')                        { $aRequestDetails['ip']         = $sIP;                               }

            /*
                $aPath        = array_column(self::$aSpans, '--r');
                $sPath        = count($aPath) > 0 ? implode('.', $aPath) . '.' : '';
                $sRequestHash = $sPath . substr(hash('sha1', json_encode($aRequestDetails)), 0, 6);
            */
            $sRequestHash = substr(hash('sha1', (string) json_encode($aRequestDetails)), 0, 8);

            $aSpan = [
                '--ri'     => 0,
                '--r'      => $sRequestHash
            ];

            self::$aSettings[$sRequestHash] = [
                'name'            => '',
                'start_timestamp' => $oStartTime->format(self::TIMESTAMP_FORMAT),
                'error'           => false,
                'tags'            => [],
                'context'         => $aContext,
                'metrics'         => new Timer()
            ];

            if ($sThreadHash = Log::getThreadHash()) {
                $aSpan['--t'] = $sThreadHash;
            }

            if (count(self::$aSpans) > 0) {
                $aSpan['--p'] = self::$aSpans[count(self::$aSpans) - 1]['--r'];
            } else if ($sParentHash = self::getParentHash()) {
                $aSpan['--p'] = $sParentHash;
            }

            $aUser = [];

            if ($sIP) {
                $aUser['ip'] = $sIP;
            }

            if (isset($aRequestDetails['agent'])) {
                $aUser['agent'] = $aRequestDetails['agent'];
            }

            if (count($aUser)) {
                self::$aSettings[$sRequestHash]['context']['user'] = isset(self::$aSettings[$sRequestHash]['context']['user']) ? array_merge(self::$aSettings[$sRequestHash]['context']['user'], $aUser) : $aUser;
            }

            self::$aSpans[] = $aSpan;

            self::startTimer('_REQUEST');
        }

        /**
         * @param string $sOverrideName
         * @psalm-suppress UndefinedClass
         */
        public static function summary(string $sOverrideName = 'Summary'): void {
            self::incrementCurrentIndex();
            $iTimer    = self::stopTimer('_REQUEST');
            $aSettings = self::getCurrentSettings();
            $aSettings['metrics'] = json_encode($aSettings['metrics']->stats());
            
            $aMessage = array_merge(
                self::prepareContext(
                    self::$sService . '.' . $sOverrideName,
                    [
                        '--ms'            => $iTimer,
                        '--summary'       => true,
                        '--span' => array_merge(
                            [
                                '_format'         => 'SSFSpan.DashedTrace',
                                'version'         => 1,
                                'end_timestamp'   => notNowButRightNow()->format(self::TIMESTAMP_FORMAT),
                                'service'         => self::$sService
                            ],
                            $aSettings
                        )
                    ]
                ),
                self::getCurrentSpan()
            );

            self::initLogger()->addRecord(Monolog\Logger::INFO, $aMessage['--action'], $aMessage);
        }
    }

    Log::initSpan(ServerRequestFactory::fromGlobals());