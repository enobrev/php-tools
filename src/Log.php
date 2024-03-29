<?php
    namespace Enobrev;

    use Monolog\Handler\StreamHandler;
    use Throwable;

    use Adbar\Dot;
    use Laminas\Diactoros\ServerRequestFactory;
    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\SyslogHandler;
    use Monolog\Logger;
    use Psr\Http\Message\ServerRequestInterface;

    class Log {

        public const EMERGENCY  = 0;
        public const ALERT      = 1;
        public const CRITICAL   = 2;
        public const ERROR      = 3;
        public const WARNING    = 4;
        public const NOTICE     = 5;
        public const INFO       = 6;
        public const DEBUG      = 7;

        protected static $aLevels = [
            Logger::EMERGENCY => self::EMERGENCY,
            Logger::ALERT     => self::ALERT,
            Logger::CRITICAL  => self::CRITICAL,
            Logger::ERROR     => self::ERROR,
            Logger::WARNING   => self::WARNING,
            Logger::NOTICE    => self::NOTICE,
            Logger::INFO      => self::INFO,
            Logger::DEBUG     => self::DEBUG,
        ];

        protected static $aLevelNames = [
            self::EMERGENCY => 'emergency',
            self::ALERT     => 'alert',
            self::CRITICAL  => 'critical',
            self::ERROR     => 'error',
            self::WARNING   => 'warning',
            self::NOTICE    => 'notice',
            self::INFO      => 'info',
            self::DEBUG     => 'debug',
        ];

        protected static $aLookupLevels = [
            self::EMERGENCY => Logger::EMERGENCY,
            self::ALERT     => Logger::ALERT,
            self::CRITICAL  => Logger::CRITICAL,
            self::ERROR     => Logger::ERROR,
            self::WARNING   => Logger::WARNING,
            self::NOTICE    => Logger::NOTICE,
            self::INFO      => Logger::INFO,
            self::DEBUG     => Logger::DEBUG,
        ];

        private static ?Logger $oLog = null;

        private static ServerRequestInterface $oServerRequest;

        private static string $sService = 'Enobrev_Logger_Replace_Me';

        private static int $iStackLimit = 5;

        private static bool $bMetrics = false;

        private static bool $bJSONLogs = false;

        private static bool $bCEELogs = false;

        private static bool $bContained = false;

        private static ?string $sThreadHash = null;

        private static array $aSpans = [];

        /** @var SpanMeta[] */
        private static array $aSpanMetas = [];

        private static int $iGlobalIndex = 0;

        private static array $aDisabled = [
            'd'  => false,
            'dt' => false
        ];

        /**
         * @return Logger
         */
        public static function initLogger(): Logger {
            if (self::$oLog === null) {
                register_shutdown_function([self::class, 'summary']);

                self::$oLog = new Logger(self::$sService);

                if (self::$bContained) {
                    if (self::$bJSONLogs) {
                        //$sFormat = "%datetime% %level_name% @cee: %context%\n"; // Requires mmnormalize in rsyslog sidecar

                        $sFormat = "%context%\n";
                        if (self::$bCEELogs) {
                            $sFormat = "@cee: $sFormat";
                        }
                    } else {
                        $sFormat = "%datetime% %level_name% %extra% %context%\n";
                    }

                    $oFormatter = new LineFormatter($sFormat, DATE_ATOM);
                    $oHandler   = new StreamHandler(fopen('php://stdout', 'wb'), Logger::DEBUG);
                    $oHandler->setFormatter($oFormatter);

                    self::$oLog->pushHandler($oHandler);
                } else {
                    $sFormat = '%context%';
                    if (self::$bJSONLogs && self::$bCEELogs) {
                        $sFormat = "@cee: $sFormat";
                    }

                    $oFormatter = new LineFormatter($sFormat);
                    $oSyslog    = new SyslogHandler('API');
                    $oSyslog->setFormatter($oFormatter);
                    self::$oLog->pushHandler($oSyslog);
                }

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
                self::prepareContext($sMessage, $aContext, $iLevel),
                self::getCurrentSpan()
            );

            return self::initLogger()->addRecord($iLevel, $sMessage, $aLog);
        }

        private static function getLevelName(int $iLevel): string {
            return self::$aLevelNames[self::getLevel($iLevel)];
        }

        private static function getLevel(int $iLevel): int {
            return self::$aLevels[$iLevel];
        }

        /**
         * @param string $sMessage
         * @param array  $aContext
         * @return array
         */
        private static function prepareContext(string $sMessage, array $aContext = [], ?int $iLevel = null): array {
            self::$iGlobalIndex++;

            $oLog = new Dot([
                '--action' => $sMessage,
                '--i'      => self::$iGlobalIndex
            ]);

            if ($iLevel) {
                $oLog->set('--s', self::getLevel($iLevel));
                $oLog->set('--sn', self::getLevelName($iLevel));
            }

            $sRequestHash  = self::getCurrentRequestHash();

            if ($aContext && is_array($aContext) && count($aContext)) {
                foreach ($aContext as $sKey => $mValue) {
                    if (strncmp($sKey, '--', 2) === 0) {
                        if (!is_scalar($mValue)) {
                            $oLog->mergeRecursiveDistinct($sKey, $mValue);
                        } else {
                            $oLog->set($sKey, $mValue);
                        }
                        unset($aContext[$sKey]);
                    }

                    if (strncmp($sKey, '#', 1) === 0) {
                        $oLog->mergeRecursiveDistinct($sKey, $mValue);
                        $sStrippedKey = str_replace('#', '', $sKey);
                        unset($aContext[$sKey]);

                        self::$aSpanMetas[$sRequestHash]->Context->mergeRecursiveDistinct($sStrippedKey, $mValue);
                    }
                }

                /** @noinspection NotOptimalIfConditionsInspection */
                if (count($aContext)) {
                    $oLog->mergeRecursiveDistinct($sMessage, $aContext);
                }
            }

            return $oLog->all();
        }

        /**
         * @param string $sService
         */
        public static function setService(string $sService): void {
            self::$sService = $sService;
        }

        /**
         * @param bool $bMetrics
         */
        public static function enableMetrics(bool $bMetrics): void {
            self::$bMetrics = $bMetrics;
        }

        /**
         * @param array $aContext
         */
        public static function justAddContext(array $aContext): void {
            self::prepareContext('', $aContext);
        }

        /**
         * @param bool $bIsError
         */
        public static function setProcessIsError(bool $bIsError): void {
            self::$aSpanMetas[self::getCurrentRequestHash()]->setError($bIsError);
        }

        public static function getPurpose(): string {
            return self::$aSpanMetas[self::getCurrentRequestHash()]->getName();
        }

        /**
         * @param string $sPurpose
         */
        public static function setPurpose(string $sPurpose): void {
            self::$aSpanMetas[self::getCurrentRequestHash()]->setName($sPurpose);
        }

        /**
         * @return bool
         */
        public static function hasPurpose(): bool {
            return self::$aSpanMetas[self::getCurrentRequestHash()]->hasName();
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

        public static function enableCEE(): void {
            self::$bCEELogs = true;
        }

        public static function enableJSON(): void {
            self::$bJSONLogs = true;
        }

        public static function contained(bool $bContained = true): void {
            self::$bContained = $bContained;
        }

        public static function setStackLimit(int $iStackLimit): void {
            self::$iStackLimit = $iStackLimit;
        }

        public static function disableD(bool $bDisabled = true): void {
            self::$aDisabled['d'] = $bDisabled;
        }

        public static function disableDT(bool $bDisabled = true): void {
            self::$aDisabled['dt'] = $bDisabled;
        }

        public static function method(string $sMethod, int $iLevels = 1): string {
            $sMethod = str_replace('::', '.', $sMethod);
            $aMethod = explode('\\', $sMethod);
            $aSliced = array_slice($aMethod, -$iLevels);
            return implode('.', $aSliced);
        }

        public static function chunked(int $iSeverity, string $sMessage, array $aContext, $iChunkSize = 500) {
            $iLevel  = self::$aLookupLevels[$iSeverity];
            $aChunks = array_chunk($aContext, $iChunkSize);
            foreach($aChunks as $aChunk) {
                self::addRecord($iLevel, $sMessage, ['chunked' => $aChunk]);
            }
        }

        /**
         * Adds a log record at the DEBUG level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function d(string $sMessage, array $aContext = array()): bool {
            if (self::$aDisabled['d']) {
                self::justAddContext($aContext);
                return false;
            }

            return self::addRecord(Logger::DEBUG, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the INFO level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function i(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::INFO, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the NOTICE level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function n(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::NOTICE, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the WARNING level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function w(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::WARNING, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ERROR level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function e(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::ERROR, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ERROR level.
         *
         * @param  string    $sMessage   The log message
         * @param  Throwable $oThrowable The exception
         * @param  array     $aContext   The log context
         *
         * @return boolean Whether the record has been processed
         */
        public static function ex(string $sMessage, Throwable $oThrowable, array $aContext = array()): bool {
            $oWhoops = new \Whoops\Run;
            $oWhoops->allowQuit(false);
            $oWhoops->writeToOutput(false);
            $oWhoopsHandler = new \Whoops\Handler\JsonResponseHandler();
            $oWhoopsHandler->addTraceToOutput(true);
            $oWhoops->pushHandler($oWhoopsHandler);

            $aContext['--exception'] = [
                'type'    => get_class($oThrowable),
                'code'    => $oThrowable->getCode(),
                'message' => $oThrowable->getMessage(),
                'file'    => $oThrowable->getFile(),
                'line'    => $oThrowable->getLine(),
                'stack'   => $oWhoops->handleException($oThrowable)
            ];

            $aContext['--span'] = [
                'context' => self::getContextForOutput()
            ];

            return self::addRecord(Logger::ERROR, $sMessage, $aContext);
        }

        private static function replaceObjects(array $aArgs): array {
            $aOutput = [];
            foreach($aArgs as $sKey => $aArg) {  // Do NOT use a reference here as getTrace returns references to the actual args in the call stack which can then modify the real vars in the stack
                if (is_object($aArg)) {
                    $aOutput[$sKey] = 'Object: '. get_class($aArg);
                } else if (is_array($aArg)) {
                    if (count($aArg) === 0) {
                        $aOutput[$sKey] = 'Empty Array';
                    } else {
                        $aOutput[$sKey] = self::replaceObjects($aArg);
                    }
                }
            }

            return $aOutput;
        }

        /**
         * Adds a log record at the CRITICAL level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function c(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::CRITICAL, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the ALERT level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function a(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::ALERT, $sMessage, $aContext);
        }

        /**
         * Adds a log record at the EMERGENCY level.
         *
         * @param  string  $sMessage The log message
         * @param  array   $aContext The log context
         * @return boolean Whether the record has been processed
         */
        public static function em(string $sMessage, array $aContext = array()): bool {
            return self::addRecord(Logger::EMERGENCY, $sMessage, $aContext);
        }

        /**
         * @param TimeKeeper $oTimer
         * @param array      $aContext
         */
        public static function dt(TimeKeeper $oTimer, array $aContext = []): void {
            $aContext['--ms'] = $oTimer->stop();

            if (self::$aDisabled['dt']) {
                self::justAddContext($aContext);
                return;
            }

            self::d($oTimer->label(), $aContext);
        }

        /**
         * @param TimeKeeper $oTimer
         * @param array      $aContext
         */
        public static function et(TimeKeeper $oTimer, array $aContext = []): void {
            $aContext['--ms'] = $oTimer->stop();

            self::e($oTimer->label(), $aContext);
        }

        /**
         * @param string $sLabel
         * @return TimeKeeper
         */
        public static function startTimer(string $sLabel): TimeKeeper {
            $oTimer = new TimeKeeper($sLabel);
            $oTimer->start();
            return $oTimer;
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
        public static function startChildRequest(?string $sThreadHash = null, ?string $sParentHash = null): void {
            $aUser  = [];
            if (self::$aSpanMetas[self::getCurrentRequestHash()]->Context->has('user')) {
                $aUser = self::$aSpanMetas[self::getCurrentRequestHash()]->Context->get('user');
            }

            self::initSpan(self::$oServerRequest, $aUser, $sThreadHash, $sParentHash);
        }

        /**
         * Retrieves the previous request hash
         */
        public static function endChildRequest(): void {
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
                if ($aGetParams && is_array($aGetParams) && isset($aGetParams['--t'])) {
                    self::$sThreadHash = $aGetParams['--t'];
                    return self::$sThreadHash;
                }

                $aPostParams = self::$oServerRequest->getParsedBody();
                if ($aPostParams && is_array($aPostParams) && isset($aPostParams['--t'])) {
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
                    // We prefer to use any thread hash sent in from another process
                    self::$sThreadHash = $aServerParams['NGINX_REQUEST_ID'];
                    return self::$sThreadHash;
                }
            }

            if (isset($_SERVER['NGINX_REQUEST_ID'])) {
                // NGINX Request ID should be last because that should always be set, but
                // We prefer to use any thread hash sent in from another process
                self::$sThreadHash = $_SERVER['NGINX_REQUEST_ID'];
                return self::$sThreadHash;
            }

            self::$sThreadHash = substr(hash('sha1', notNowButRightNow()->format('Y-m-d G:i:s.u')), 0, 6);
            return self::$sThreadHash;
        }

        private static array $aIndices    = [];

        private static bool $bJSONParsed = false;

        private static function parseJSONBodyForIndices(): void {
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
                if ($aPostParams && is_array($aPostParams) && isset($aPostParams['--p'])) {
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

            return $_REQUEST['--p'] ?? null;
        }

        /**
         * @param ServerRequestInterface $oRequest
         * @param array                  $aUser
         * @param string|null            $sIncomingThreadHash
         * @param string|null            $sIncomingParentHash
         */
        public static function initSpan(ServerRequestInterface $oRequest, array $aUser = [], ?string $sIncomingThreadHash = null, ?string $sIncomingParentHash = null): void {
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
            if ($sIP !== 'unknown')                        { $aRequestDetails['ip']         = $sIP;                               }

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

            self::$aSpanMetas[$sRequestHash] = new SpanMeta($oStartTime);

            if ($sThreadHash = $sIncomingThreadHash ?? self::getThreadHash()) {
                $aSpan['--t'] = $sThreadHash;
            }

            if ($sIncomingParentHash) {
                $aSpan['--p'] = $sIncomingParentHash;
            } else if (count(self::$aSpans) > 0) {
                $aSpan['--p'] = self::$aSpans[count(self::$aSpans) - 1]['--r'];
            } else if ($sParentHash = self::getParentHash()) {
                $aSpan['--p'] = $sParentHash;
            }

            if ($sIP) {
                $aUser['ip'] = $sIP;
            }

            if (isset($aRequestDetails['agent'])) {
                $aUser['agent'] = $aRequestDetails['agent'];
            }

            if (count($aUser)) {
                self::$aSpanMetas[$sRequestHash]->Context->mergeRecursiveDistinct(['user' => $aUser]);
            }

            self::$aSpans[] = $aSpan;
        }

        /**
         * @param string $sOverrideName
         * @psalm-suppress UndefinedClass
         */
        public static function summary(string $sOverrideName = 'Summary'): void {
            self::incrementCurrentIndex();
            $aMessage = array_merge(
                self::prepareContext(
                    self::$sService . '.' . $sOverrideName,
                    [
                        '--ms'      => self::$aSpanMetas[self::getCurrentRequestHash()]->Timer->stop(),
                        '--summary' => true,
                        '--span'    => self::$aSpanMetas[self::getCurrentRequestHash()]->getMessage(self::$sService),
                    ],
                    Logger::INFO
                ),
                self::getCurrentSpan()
            );

            self::initLogger()->addRecord(Logger::INFO, $aMessage['--action'], $aMessage);
        }

        public static function getContextForOutput(): array {
            return self::$aSpanMetas[self::getCurrentRequestHash()]->Context->all();
        }
    }

    Log::initSpan(ServerRequestFactory::fromGlobals());