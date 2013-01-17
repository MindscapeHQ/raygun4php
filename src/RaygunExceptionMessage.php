<?php
namespace Raygun4php
{
    class RaygunExceptionMessage
    {
        protected $message;
        protected $file;
        protected $line;
        protected $code;
        protected $trace;

        public function __construct($exception)
        {
            $this->message = $exception->getMessage();
            $this->file = $exception->getFile();
            $this->line = $exception->getLine();
            $this->code = $exception->getCode();
            $this->trace = $exception->getTrace();
        }
    }
}
