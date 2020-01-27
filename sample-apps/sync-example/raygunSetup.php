<?php

namespace {
    require_once 'vendor/autoload.php';
    require_once 'config.php';

    use GuzzleHttp\Client;
    use Monolog\Handler\FirePHPHandler;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Raygun4php\RaygunClient;
    use Raygun4php\Transports\GuzzleSync;

    const RAYGUN_BASE_URI = 'https://api.raygun.com';
    const HTTP_CLIENT_TIMEOUT = 2.0;
    const LOGGER_NAME = 'sync_logger';
    const LOG_FILE_PATH = __DIR__ . '/debug.log';

    $logger = new Logger(LOGGER_NAME);
    $logger->pushHandler(new StreamHandler(LOG_FILE_PATH));
    $logger->pushHandler(new FirePHPHandler());

    $httpClient = new Client([
        'base_uri' => RAYGUN_BASE_URI,
        'timeout' => HTTP_CLIENT_TIMEOUT,
        'headers' => [
            'X-ApiKey' => API_KEY
        ]
    ]);

    $transport = new GuzzleSync($httpClient);
    $transport->setLogger($logger);

    $raygunClient = new RaygunClient($transport, false, $logger);
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

