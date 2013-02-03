<?php
namespace Raygun4php
{
    class RaygunExceptionTraceLineMessage
    {
        public $LineNumber;
        public $ClassName;
        public $FileName;
        public $MethodName;
        public $Code;

        public function __construct()
        {
            $this->FileName = "";
            $this->ClassName = "";
            $this->LineNumber = "0";
            $this->MethodName = "";
        }

        public function SetCode()
        {
            if (!empty($this->FileName) && $this->LineNumber != 0)
            {
                $file = new \SplFileObject($this->FileName);
                $start = max($this->LineNumber - 3, 0);

                $iterator = new \LimitIterator($file, $start, 5);
                foreach ($iterator as $line)
                {
                    $this->Code[] = $line.PHP_EOL;
                }
            }
        }
    }
}
