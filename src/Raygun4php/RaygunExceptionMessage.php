<?php
namespace Raygun4php
{
    require_once realpath(__DIR__.'/RaygunExceptionTraceLineMessage.php');

    class RaygunExceptionMessage
    {
        public $Message;
        public $ClassName;
        public $StackTrace = array();
        public $FileName;
        public $Data;
        //public $CatchingMethod;

        public function __construct($exception)
        {
            $exceptionClass = get_class($exception);
            if ($exceptionClass != "ErrorException")
            {
                $this->Message = $exceptionClass.": ".$exception->getMessage();
            }
            else
            {
                $this->Message = "Error: ".$exception->getMessage();
            }
            $this->FileName = baseName($exception->getFile());
            $this->BuildStackTrace($exception);
            //$this->ClassName = $this->GetClassName();
        }

        private function BuildStackTrace($exception)
        {
            $traces = $exception->getTrace();
            $lines = array();
            foreach ($traces as $trace)
            {
                $line = new RaygunExceptionTraceLineMessage();
                if (array_key_exists("file", $trace))
                {
                $line->FileName = $trace["file"];
                }
                if (array_key_exists("class", $trace))
                {
                $line->ClassName = $trace["class"];
                }
                if (array_key_exists("function", $trace))
                {
                $line->MethodName = $trace["function"];
                }
                if (array_key_exists("line", $trace))
                {
                $line->LineNumber = $trace["line"];
                }
                $lines[] = $line;
             }
            $this->StackTrace = $lines;
        }

        private function GetClassName()
        {
            $fp = fopen($this->fileName, 'r');
            $class = $namespace = $buffer = '';
            $i = 0;
            while (!$class) {
                if (feof($fp)) break;

                $buffer .= fread($fp, 512);
                $tokens = token_get_all($buffer);

                if (strpos($buffer, '{') === false) continue;

                for (;$i<count($tokens);$i++) {
                    if ($tokens[$i][0] === T_NAMESPACE) {
                        for ($j=$i+1;$j<count($tokens); $j++) {
                            if ($tokens[$j][0] === T_STRING) {
                                $namespace .= '\\'.$tokens[$j][1];
                            } else if ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                break;
                            }
                        }
                    }

                    if ($tokens[$i][0] === T_CLASS) {
                        for ($j=$i+1;$j<count($tokens);$j++) {
                            if ($tokens[$j] === '{') {
                                $class = $tokens[$i+2][1];
                            }
                        }
                    }
                }
            }
            if ($class != "")
            {
                return $class;
            }
            else
            {
                return null;
            }
        }
    }
}
