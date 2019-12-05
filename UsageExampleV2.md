## Usage

```php

require_once './vendor/autoload.php';

$apiUrl = 'https://api.raygun.io';
$proxy = 'some://proxy';
$apiKey = 'your-api-key';

$httpClient = new GuzzleHttp\Client([
    'base_uri' => $apiUrl,
    'headers' => ['X-ApiKey' => $apiKey],
    'proxy' => $proxy
]);

// If synchronous message delivery is wanted use Raygun4php\Transports\GuzzleSync
$transport = new Raygun4php\Transports\GuzzleAsync($httpClient);

$client = new Raygun4php\RaygunClient($transport);


// Create and register error handlers
function error_handler($errno, $errstr, $errfile, $errline )
{
	global $client;
	$client->SendError($errno, $errstr, $errfile, $errline);
}

function exception_handler($exception)
{
    global $client;
    $client->SendException($exception);
}

function fatal_error()
{
    global $client;
    $last_error = error_get_last();

    if (!is_null($last_error)) {
        $errno = $last_error['type'];
        $errstr = $last_error['message'];
        $errfile = $last_error['file'];
        $errline = $last_error['line'];
        $client->SendError($errno, $errstr, $errfile, $errline);
    }
}

    set_exception_handler('exception_handler');
    set_error_handler("error_handler");
    register_shutdown_function("fatal_error");


```
