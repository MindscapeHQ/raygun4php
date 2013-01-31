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
                $start = max($this->LineNumber - 3, 1);
                $end = min($this->LineNumber + 3, $this->GetLineCount());

                $iterator = new \LimitIterator($file, $start, $end);
                foreach ($iterator as $line)
                {
                    $this->Code[] = $line.PHP_EOL;
                }
            }
        }

        private function GetLineCount()
        {
            $file = $this->FileName;
            $lineCount = 0;
            $handle = fopen($file, "r");
            while(!feof($handle)){
                $line = fgets($handle, 4096);
                $lineCount = $lineCount + substr_count($line, PHP_EOL);
            }

            fclose($handle);
            return $lineCount;
        }
    }
}
