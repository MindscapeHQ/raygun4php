<?php
namespace Raygun4php
{
/*
    Copyright (C) 2013 Mindscape

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
    documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
    rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
    permit persons to whom the Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions
    of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
    THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
    TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
*/
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
