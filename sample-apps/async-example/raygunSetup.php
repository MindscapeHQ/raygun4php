<?php

namespace {
    require_once 'vendor/autoload.php';

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
        const RAYGUN_BASE_URI = 'https://api.raygun.io';
        const HTTP_CLIENT_TIMEOUT = 2.0;
        const API_KEY = 'sgbs3RocyWEnR2ar5q6W6Q';
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
         * RaygunSetup constructor.
         * @param bool $useAsync
         */
        public function __construct($useAsync = true)
        {
            $this->useAsync = $useAsync;
            $this->logger = new Logger(self::LOGGER_NAME);
            $this->logger->pushHandler(new StreamHandler(self::LOG_FILE_PATH));
            $this->logger->pushHandler(new FirePHPHandler());

            $this->httpClient = new Client([
                'base_uri' => self::RAYGUN_BASE_URI,
                'timeout' => self::HTTP_CLIENT_TIMEOUT,
                'headers' => [
                    'X-ApiKey' => self::API_KEY
                ]
            ]);

            if ($this->useAsync) {
                $this->transport = new GuzzleAsync($this->httpClient);
            } else {
                $this->transport = new GuzzleSync($this->httpClient);
            }

            $this->transport->setLogger($this->logger);

            $raygunClient = new RaygunClient($this->transport);

            // Get the logged-in user info to track affected user
            $raygunClient->SetUser("test@example.com", "Test", "Test User", "test@example.com");

            $this->raygunClient = $raygunClient;
        }

        public function getRaygunClient(): RaygunClient {
            return $this->raygunClient;
        }

        public function handleLastError(): void
        {
            $last_error = error_get_last();

            if (!is_null($last_error)) {
                $this->raygunClient->SendError($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
            }
        }

        public function flushAsyncPromises(): void
        {
            if ($this->useAsync) {
                $this->transport->wait();
            }
        }
    }

    $raygunSetup = new RaygunSetup();
    $raygunClient = $raygunSetup->getRaygunClient();

    set_error_handler([$raygunClient, 'SendError']);
    set_exception_handler([$raygunClient, 'SendException']);
    register_shutdown_function([$raygunSetup, 'handleLastError']);
    register_shutdown_function([$raygunSetup, 'flushAsyncPromises']);
}
