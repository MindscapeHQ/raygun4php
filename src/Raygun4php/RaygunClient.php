<?php
namespace Raygun4php {

    class RaygunClient
    {
        /**
         * @var RaygunMessageBuilder
         */
        protected $messageBuilder;

        /**
         * @var Senders\RaygunMessageSender
         */
        protected $messageSender;

        public function __construct($key, $useAsyncSending = true)
        {
            $this->messageBuilder = new RaygunMessageBuilder();

            if ($useAsyncSending) {
                $this->messageSender = new Senders\RaygunForkCurlSender(
                    $key,
                    'api.raygun.io',
                    '/entries',
                    realpath(__DIR__ . '/cacert.crt')
                );
            }
            else {
                $this->messageSender = new Senders\RaygunBlockingSocketSender(
                    $key,
                    'api.raygun.io',
                    '/entries',
                    realpath(__DIR__ . '/cacert.crt')
                );
            }
        }

        /*
        * Transmits an error to the Raygun.io API
        * @param int $errorno The error number
        * @param string $errstr The error string
        * @param string $errfile The file the error occurred in
        * @param int $errline The line the error occurred on
        * @param array $tags An optional array of string tags used to provide metadata for the message
        * @param array $userCustomData An optional associative array that can be used to place custom key-value
        * data in the message payload
        * @return The HTTP status code of the result when transmitting the message to Raygun.io
        */
        public function SendError($errno, $errstr, $errfile, $errline, $tags = null, $userCustomData = null, $timestamp = null)
        {
            $message = $this->messageBuilder->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline), $timestamp);

            if ($tags != null) {
                $this->messageBuilder->AddTagsToMessage($message, $tags);
            }
            if ($userCustomData != null) {
                $this->messageBuilder->AddUserCustomDataToMessage($message, $userCustomData);
            }

            return $this->messageSender->Send($message);
        }

        /*
        * Transmits an exception to the Raygun.io API
        * @param \Exception $exception An exception object to transmit
        * @param array $tags An optional array of string tags used to provide metadata for the message
        * @param array $userCustomData An optional associative array that can be used to place custom key-value
        * data in the message payload
        * @return The HTTP status code of the result when transmitting the message to Raygun.io
        */
        public function SendException($exception, $tags = null, $userCustomData = null, $timestamp = null)
        {
            $message = $this->messageBuilder->BuildMessage($exception, $timestamp);

            if ($tags != null) {
                $this->messageBuilder->AddTagsToMessage($message, $tags);
            }
            if ($userCustomData != null) {
                $this->messageBuilder->AddUserCustomDataToMessage($message, $userCustomData);
            }

            return $this->messageSender->Send($message);
        }

        /*
         * Sets the version number of your project that will be transmitted
         * to Raygun.io.
         * @param string $version The version number in the form of x.x.x.x,
         * where x is a positive integer.
         *
         */
        public function SetVersion($version)
        {
            $this->messageBuilder->SetVersion($version);
        }

        /*
         *  Stores the current user of the calling application. This will be added to any messages sent
         *  by this provider. It is used in the dashboard to provide unique user tracking.
         *  If it is an email address, the user's Gravatar can be displayed. This method is optional,
         *  if it is not used a random identifier will be assigned to the current user.
         *  @param string $user A username, email address or other identifier for the current user
         *  of the calling application.
         *
         */
        public function SetUser($user = null)
        {
            $this->messageBuilder->SetUser($user);
        }
    }
}