<?php
    namespace Enobrev;

    require __DIR__ . '/../vendor/autoload.php';

    Log::setPurpose('Testing Log!');
    $oTimer = Log::startTimer('test');
    Log::justAddContext(['#persist' => 'persisted']);
    Log::d('Debug!', ['context' => 'whatever']);
    Log::i('Info!', ['context' => 'whatever']);
    Log::n('Notice!', ['context' => 'whatever']);
    Log::w('Warning!', ['context' => 'whatever']);
    Log::setProcessIsError(true);
    Log::e('Error!', ['context' => 'whatever']);
    Log::ex('Error!', new \Exception('Error!'), ['context' => 'whatever']);
    Log::c('Critical!', ['context' => 'whatever']);
    Log::dt($oTimer);
    Log::summary();
