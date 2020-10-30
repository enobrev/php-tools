<?php
    /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
    /** @noinspection PhpIllegalPsrClassPathInspection */
    /** @noinspection PhpUnhandledExceptionInspection */

    namespace Enobrev;

    use ErrorException;

    /**
     * http://us3.php.net/manual/en/function.set-error-handler.php#112881
     * throw exceptions based on E_* error types
     */
    set_error_handler(static function (int $err_severity, string $err_msg, string $err_file, int $err_line): void
    {
        // error was suppressed with the @-operator
        if (0 === error_reporting()) { return; }
        switch($err_severity)
        {
            case E_ERROR:               throw new ErrorException            ($err_msg, E_ERROR,             $err_severity, $err_file, $err_line);
            case E_WARNING:             throw new WarningException          ($err_msg, E_WARNING,           $err_severity, $err_file, $err_line);
            case E_PARSE:               throw new ParseException            ($err_msg, E_PARSE,             $err_severity, $err_file, $err_line);
            case E_NOTICE:              throw new NoticeException           ($err_msg, E_NOTICE,            $err_severity, $err_file, $err_line);
            case E_CORE_ERROR:          throw new CoreErrorException        ($err_msg, E_CORE_ERROR,        $err_severity, $err_file, $err_line);
            case E_CORE_WARNING:        throw new CoreWarningException      ($err_msg, E_CORE_WARNING,      $err_severity, $err_file, $err_line);
            case E_COMPILE_ERROR:       throw new CompileErrorException     ($err_msg, E_COMPILE_ERROR,     $err_severity, $err_file, $err_line);
            case E_COMPILE_WARNING:     throw new CoreWarningException      ($err_msg, E_COMPILE_WARNING,   $err_severity, $err_file, $err_line);
            case E_USER_ERROR:          throw new UserErrorException        ($err_msg, E_USER_ERROR,        $err_severity, $err_file, $err_line);
            case E_USER_WARNING:        throw new UserWarningException      ($err_msg, E_USER_WARNING,      $err_severity, $err_file, $err_line);
            case E_USER_NOTICE:         throw new UserNoticeException       ($err_msg, E_USER_NOTICE,       $err_severity, $err_file, $err_line);
            case E_STRICT:              throw new StrictException           ($err_msg, E_STRICT,            $err_severity, $err_file, $err_line);
            case E_RECOVERABLE_ERROR:   throw new RecoverableErrorException ($err_msg, E_RECOVERABLE_ERROR, $err_severity, $err_file, $err_line);
            case E_DEPRECATED:          throw new DeprecatedException       ($err_msg, E_DEPRECATED,        $err_severity, $err_file, $err_line);
            case E_USER_DEPRECATED:     throw new UserDeprecatedException   ($err_msg, E_USER_DEPRECATED,   $err_severity, $err_file, $err_line);
        }
    });

    class WarningException              extends ErrorException {}
    class ParseException                extends ErrorException {}
    class NoticeException               extends ErrorException {}
    class CoreErrorException            extends ErrorException {}
    class CoreWarningException          extends ErrorException {}
    class CompileErrorException         extends ErrorException {}
    class CompileWarningException       extends ErrorException {}
    class UserErrorException            extends ErrorException {}
    class UserWarningException          extends ErrorException {}
    class UserNoticeException           extends ErrorException {}
    class StrictException               extends ErrorException {}
    class RecoverableErrorException     extends ErrorException {}
    class DeprecatedException           extends ErrorException {}
    class UserDeprecatedException       extends ErrorException {}