<?php

namespace {
    require_once 'vendor/autoload.php';
    require_once '../../vendor/autoload.php';
    require_once 'config.php';

    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    use Monolog\Handler\FirePHPHandler;
    use Psr\Log\LoggerInterface;
    use Raygun4php\Factories\RaygunClientFactory;
    use Raygun4php\RaygunClient;

    /**
     * Class RaygunSetup
     */
    class RaygunSetup
    {
        const LOGGER_NAME = 'raygun_logger';
        const LOG_FILE_PATH = __DIR__ . '/logs/debug.log';

        /**
         * @var Logger
         */
        private $logger;

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
            $this->logger = $this->createLogger();

            $raygunClientFactory = new RaygunClientFactory(API_KEY, false, $useAsync);
            $raygunClient = $raygunClientFactory->setLogger($this->logger)->build();

            // Get the logged-in user info to track affected user
            $raygunClient->SetUser("test@example.com", "Test", "Test User", "test@example.com");

            $this->tags = $tags;
            $this->raygunClient = $raygunClient;
        }

        public function sendError($errno, $errstr, $errfile, $errline): void
        {
            $this->logger->info('Send error', [$errno, $errstr]);
            $this->raygunClient->SendError($errno, $errstr, $errfile, $errline, $this->tags);
        }

        public function sendException($exception): void
        {
            $this->logger->info('Send exception', [$exception]);
            $this->raygunClient->SendException($exception, $this->tags);
        }

        public function handleFatalError(): void
        {
            $this->logger->info('Shutdown occurred');
            $lastError = error_get_last();

            if (!is_null($lastError)) {
                [$type, $message, $file, $line] = $lastError;
                $this->logger->error('Fatal error', [$type, $message]);

                $tags = array_merge($this->tags, ['fatal-error']);
                $this->raygunClient->SendError($type, $message, $file, $line, $tags);
            }
        }

        private function createLogger(): LoggerInterface
        {
            $logger = new Logger(self::LOGGER_NAME);
            $logger->pushHandler(new StreamHandler(self::LOG_FILE_PATH));
            $logger->pushHandler(new FirePHPHandler());

            return $logger;
        }
    }

    $raygunSetup = new RaygunSetup(['local']);

    set_error_handler([$raygunSetup, 'sendError']);
    set_exception_handler([$raygunSetup, 'sendException']);
    register_shutdown_function([$raygunSetup, 'handleFatalError']);
}
