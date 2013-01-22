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
    require_once realpath(__DIR__.'/RaygunMessageDetails.php');
    require_once realpath(__DIR__.'/RaygunExceptionMessage.php');
    require_once realpath(__DIR__.'/RaygunRequestMessage.php');
    require_once realpath(__DIR__.'/RaygunClientMessage.php');

    class RaygunMessage
    {
        public $OccurredOn;
        public $Details;

        public function __construct()
        {
            $this->OccurredOn = gmdate("Y-m-d\TH:i");
            $this->Details = new RaygunMessageDetails();
        }

        public function Build($exception)
        {
            $this->Details->MachineName = gethostname();
            $this->Details->Error = new RaygunExceptionMessage($exception);
            $this->Details->Request = new RaygunRequestMessage();
            $this->Details->Client = new RaygunClientMessage();
        }
    }
}