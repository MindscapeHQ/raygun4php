<?php

namespace {
    require_once 'vendor/autoload.php';
    require_once 'config.php';

    // Needed during development
    require_once '../../src/Raygun4php/Factories/Interfaces/TransportFactoryInterface.php';
    require_once '../../src/Raygun4php/Factories/Interfaces/HttpClientFactoryInterface.php';
    require_once '../../src/Raygun4php/Factories/Interfaces/RaygunClientFactoryInterface.php';
    require_once '../../src/Raygun4php/Factories/TransportFactory.php';
    require_once '../../src/Raygun4php/Factories/HttpClientFactory.php';
    require_once '../../src/Raygun4php/Factories/RaygunClientFactory.php';

    use Monolog\Handler\FirePHPHandler;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Raygun4php\Factories\RaygunClientFactory;

    const LOGGER_NAME = 'raygun_logger';
    const LOG_FILE_PATH = __DIR__ . '/logs/debug.log';

    $logger = new Logger(LOGGER_NAME);
    $logger->pushHandler(new StreamHandler(LOG_FILE_PATH));
    $logger->pushHandler(new FirePHPHandler());

    $raygunClientFactory = new RaygunClientFactory(API_KEY, false, false);
    $raygunClient = $raygunClientFactory->setLogger($logger)->build();

    $tags = ['local-environment', 'machine-4'];

    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($raygunClient, $tags) {
        $raygunClient->SendError($errno, $errstr, $errfile, $errline, $tags);
    });

    set_exception_handler(function ($exception) use ($raygunClient, $tags) {
        $raygunClient->SendException($exception, $tags);
    });

    register_shutdown_function(function () use ($raygunClient, $tags) {
        $lastError = error_get_last();

        if (!is_null($lastError)) {
            [$type, $message, $file, $line] = $lastError;
            $_tags = array_merge($tags, ['fatal-error']);
            $raygunClient->SendError($type, $message, $file, $line, $_tags);
        }
    });
}

