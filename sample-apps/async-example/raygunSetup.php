<?php

namespace {
    require_once 'vendor/autoload.php';
    require_once 'config.php';

    use GuzzleHttp\Client;
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    use Monolog\Handler\FirePHPHandler;
    use Raygun4php\RaygunClient;
    use Raygun4php\Transports\GuzzleAsync;
    use Raygun4php\Transports\GuzzleSync;

    /**
     * Class RaygunSetup
     */
    class RaygunSetup
    {
        const RAYGUN_BASE_URI = 'https://api.raygun.com';
        const HTTP_CLIENT_TIMEOUT = 2.0;
        const LOGGER_NAME = 'example_logger';
        const LOG_FILE_PATH = __DIR__ . '/debug.log';

        /**
         * @var GuzzleAsync|GuzzleSync
         */
        private $transport;

        /**
         * @var bool
         */
        private $useAsync;

        /**
         * @var Logger
         */
        private $logger;

        /**
         * @var Client
         */
        private $httpClient;

        /**
         * @var RaygunClient
         */
        private $raygunClient;

        /**
         * @var array
         */
        private $tags;

        /**
         * RaygunSetup constructor.
         * @param array $tags
         * @param bool $useAsync
         */
        public function __construct($tags = [], $useAsync = true)
        {
            $this->useAsync = $useAsync;
            $this->logger = new Logger(self::LOGGER_NAME);
            $this->logger->pushHandler(new StreamHandler(self::LOG_FILE_PATH));
            $this->logger->pushHandler(new FirePHPHandler());

            $this->httpClient = new Client([
                'base_uri' => self::RAYGUN_BASE_URI,
                'timeout' => self::HTTP_CLIENT_TIMEOUT,
                'headers' => [
                    'X-ApiKey' => API_KEY
                ]
            ]);

            if ($this->useAsync) {
                $this->transport = new GuzzleAsync($this->httpClient);
            } else {
                $this->transport = new GuzzleSync($this->httpClient);
            }

            $this->transport->setLogger($this->logger);

            $raygunClient = new RaygunClient($this->transport);

            // Get the logged-in user info to track Customers
            $raygunClient->SetUser("test@example.com", "Test", "Test User", "test@example.com");

            $this->tags = $tags;

            $this->raygunClient = $raygunClient;
        }

        public function getRaygunClient(): RaygunClient
        {
            return $this->raygunClient;
        }

        public function sendError($errno, $errstr, $errfile, $errline): void {
            $this->raygunClient->SendError($errno, $errstr, $errfile, $errline, $this->tags);
        }

        public function sendException($exception): void {
            $this->raygunClient->SendException($exception, $this->tags);
        }

        public function handleFatalError(): void
        {
            $lastError = error_get_last();

            if (!is_null($lastError)) {
                [$type, $message, $file, $line] = $lastError;

                $tags = array_merge($this->tags, ['fatal-error']);
                $this->raygunClient->SendError($type, $message, $file, $line, $tags);
            }
        }

        public function flushAsyncPromises(): void
        {
            if ($this->useAsync) {
                $this->transport->wait();
            }
        }
    }

    $raygunSetup = new RaygunSetup(['local']);

    set_error_handler([$raygunSetup, 'sendError']);
    set_exception_handler([$raygunSetup, 'sendException']);
    register_shutdown_function([$raygunSetup, 'handleFatalError']);
    register_shutdown_function([$raygunSetup, 'flushAsyncPromises']);
}
