<?php

namespace Raygun4php;

class RaygunExceptionMessage
{
    public $Message;
    public $ClassName;
    public $StackTrace = array();
    public $FileName;
    public $Data;
    public $InnerError;

    /**
     * @param \Throwable $exception
     */
    public function __construct($exception)
    {
        $exceptionClass = get_class($exception);

        if ($exceptionClass !== 'ErrorException') {
            $this->Message = $exceptionClass . ': ' . $exception->getMessage();
            $this->BuildStackTrace($exception);
            $this->ClassName = $exceptionClass;
        } else {
            $this->Message = 'Error: ' . $exception->getMessage();
            $this->BuildErrorTrace($exception);
        }

        $this->FileName = baseName($exception->getFile());

        if ($prev = $exception->getPrevious()) {
            $this->InnerError = new self($prev);
        }
    }

    private function getExceptionLine( $exceptionOrErrorException )
    {
        $line = new RaygunExceptionTraceLineMessage();
        $line->FileName = $exceptionOrErrorException->getFile();
        $line->LineNumber = $exceptionOrErrorException->getLine();
        return $line;
    }

    private function BuildErrorTrace($error)
    {
        $traces = $error->getTrace();
        $lines = array();

        $lines[] = $this->getExceptionLine( $error );

        foreach ($traces as $trace) {
            $line = new RaygunExceptionTraceLineMessage();

            $fromManualSendError = false;
            if (
                array_key_exists('function', $trace)
                && array_key_exists('class', $trace)
            ) {
                if ($trace['function'] === 'SendError' && $trace['class'] === RaygunClient::class) {
                    $fromManualSendError = true;
                }
            }

            if (array_key_exists('args', $trace) && $fromManualSendError == true) {
                $errorData = $trace['args'];

                if (count($errorData) >= 2) {
                    $line->ClassName = $errorData[1];
                }
                if (count($errorData) >= 3) {
                    $line->FileName = $errorData[2];
                }
                if (count($errorData) >= 4) {
                    $line->LineNumber = $errorData[3];
                }
            } else {
                $line = $this->BuildLine($trace);
            }

            $lines[] = $line;
        }

        $this->StackTrace = $lines;
    }

    private function BuildStackTrace($exception)
    {
        $traces = $exception->getTrace();
        $lines = array();

        $lines[] = $this->getExceptionLine( $exception );

        foreach ($traces as $trace) {
            $lines[] = $this->BuildLine($trace);
        }

        $this->StackTrace = $lines;
    }

    private function BuildLine($trace)
    {
        $line = new RaygunExceptionTraceLineMessage();

        if (array_key_exists('file', $trace)) {
            $line->FileName = $trace['file'];
        }
        if (array_key_exists('class', $trace)) {
            $line->ClassName = $trace['class'];
        }
        if (array_key_exists('function', $trace)) {
            $line->MethodName = $trace['function'];
        }
        if (array_key_exists('line', $trace)) {
            $line->LineNumber = $trace['line'];
        }

        return $line;
    }
}
