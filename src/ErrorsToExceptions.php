<?php
    namespace Enobrev;

    use ErrorException;
    use Enobrev\Exceptions;

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
            case E_ERROR:               throw new ErrorException                       ($err_msg, E_ERROR,             $err_severity, $err_file, $err_line);
            case E_WARNING:             throw new Exceptions\WarningException          ($err_msg, E_WARNING,           $err_severity, $err_file, $err_line);
            case E_PARSE:               throw new Exceptions\ParseException            ($err_msg, E_PARSE,             $err_severity, $err_file, $err_line);
            case E_NOTICE:              throw new Exceptions\NoticeException           ($err_msg, E_NOTICE,            $err_severity, $err_file, $err_line);
            case E_CORE_ERROR:          throw new Exceptions\CoreErrorException        ($err_msg, E_CORE_ERROR,        $err_severity, $err_file, $err_line);
            case E_CORE_WARNING:        throw new Exceptions\CoreWarningException      ($err_msg, E_CORE_WARNING,      $err_severity, $err_file, $err_line);
            case E_COMPILE_ERROR:       throw new Exceptions\CompileErrorException     ($err_msg, E_COMPILE_ERROR,     $err_severity, $err_file, $err_line);
            case E_COMPILE_WARNING:     throw new Exceptions\CompileWarningException   ($err_msg, E_COMPILE_WARNING,   $err_severity, $err_file, $err_line);
            case E_USER_ERROR:          throw new Exceptions\UserErrorException        ($err_msg, E_USER_ERROR,        $err_severity, $err_file, $err_line);
            case E_USER_WARNING:        throw new Exceptions\UserWarningException      ($err_msg, E_USER_WARNING,      $err_severity, $err_file, $err_line);
            case E_USER_NOTICE:         throw new Exceptions\UserNoticeException       ($err_msg, E_USER_NOTICE,       $err_severity, $err_file, $err_line);
            case E_STRICT:              throw new Exceptions\StrictException           ($err_msg, E_STRICT,            $err_severity, $err_file, $err_line);
            case E_RECOVERABLE_ERROR:   throw new Exceptions\RecoverableErrorException ($err_msg, E_RECOVERABLE_ERROR, $err_severity, $err_file, $err_line);
            case E_DEPRECATED:          throw new Exceptions\DeprecatedException       ($err_msg, E_DEPRECATED,        $err_severity, $err_file, $err_line);
            case E_USER_DEPRECATED:     throw new Exceptions\UserDeprecatedException   ($err_msg, E_USER_DEPRECATED,   $err_severity, $err_file, $err_line);
        }
    });