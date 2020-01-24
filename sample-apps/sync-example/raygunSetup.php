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

    const RAYGUN_BASE_URI = 'http://api.raygun.com';
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

    class HandlerUtilities {
        private $raygunClient;
        private $tags;

        public function __construct($raygunClient, $tags)
        {
            $this->raygunClient = $raygunClient;
            $this->tags = $tags;
        }

        public function errorHandler($errno, $errstr, $errfile, $errline) {
            $this->raygunClient->SendError($errno, $errstr, $errfile, $errline, $this->tags);
        }

        public function exceptionHandler($exception) {
            $this->raygunClient->SendException($exception, $this->tags);
        }

        public function fatalErrorHandler() {
            $lastError = error_get_last();

            if (!is_null($lastError)) {
                $this->raygunClient->SendError($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
            }
        }
    }

    $tags = ['staging-environment', 'machine-4'];
    $handlers = new HandlerUtilities($raygunClient, $tags);

    set_error_handler([$handlers, 'errorHandler']);
    set_exception_handler([$handlers, 'exceptionHandler']);
    register_shutdown_function([$handlers, 'fatalErrorHandler']);
}

