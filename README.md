Raygun4php
==========

Raygun.io client for PHP 5.3
Beta

##Installation & Usage

Place the Mindscape.Raygun4php folder into your site, in an appropriate subdirectory such as /vendor. You can send both errors and exceptions to Raygun; an easy way to accomplish this is to place calls to the sending functions in the error/exception handlers.

Begin by including RaygunClient.php, then implementing the above:

'''php
require_once realpath(__DIR__.'/vendor/Mindscape.Raygun4php/RaygunClient.php');

function error_handler($errno, $errstr, $errfile, $errline ) {
    $client = new \Raygun4php\RaygunClient("{{apikey for your application}}");
    $client->SendError($errno, $errstr, $errfile, $errline);
}

set_error_handler("error_handler");
'''

Copy your application's API key from the Raygun.io dashboard, and place it in the constructor call as above (do not include the curly brackets).

The above code will send PHP errors to Raygun.io. To also transmit exceptions, use an exception handler like the following:

'''php
function exception_handler($exception)
{
	$client = new \Raygun4php\RaygunClient("{{apikey for your application}}");
    $this->client->SendException($exception);
}

set_exception_handler('exception_handler');
'''