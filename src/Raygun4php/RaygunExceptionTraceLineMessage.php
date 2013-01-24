<?php
namespace Raygun4php
{
    class RaygunExceptionTraceLineMessage
    {
        public $LineNumber;
        public $ClassName;
        public $FileName;
        public $MethodName;

        public function __construct()
        {
            $this->FileName = "";
            $this->ClassName = "";
            $this->LineNumber = "0";
            $this->MethodName = "";
        }
    }
}
