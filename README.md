Raygun4PHP
==========

[Raygun.com](http://raygun.com) provider for PHP 5.3+

[![Build
Status](https://secure.travis-ci.org/MindscapeHQ/raygun4php.png?branch=master)](http://travis-ci.org/MindscapeHQ/raygun4php)

## Installation

Firstly, ensure that **curl** is installed and enabled in your server's php.ini file.

### With Composer

Composer is a package management tool for PHP which automatically fetches dependencies and also supports autoloading - this is a low-impact way to get Raygun4PHP into your site.

1. If you use a *nix environment, [follow the instructions](http://getcomposer.org/doc/01-basic-usage.md#installation) to install Composer. Windows users can run [this installer](https://github.com/johnstevenson/composer-setup) to automatically add it to the Path.

2. Inside your project's root directory create a composer.json file, containing:
```json
{
        "require": {
            "mindscape/raygun4php": "1.*"
        }
}
```
3. From your shell run `php composer.phar install` (*nix) or `composer install` (Windows). This will download Raygun4Php and create the appropriate autoload data.

4. Then in a PHP file just add:
```php
require_once 'vendor/autoload.php';
```
and the library will be imported ready for use.

### Manually with Git

Clone this repository and copy src/Raygun4php into an appropriate subdirectory in your project, such as /vendor/Raygun4php. Add a `requires` definition that references the location of RaygunClient.php where you want to make a call to Send().

```php
require __DIR__ . '/vendor/raygun4php/src/Raygun4php/RaygunClient.php';
```
## Usage

You can send both PHP errors and object-oriented exceptions to Raygun. An easy way to accomplish this is to create a file containing exception and error handlers which make calls to the appropriate Raygun4PHP functions. As above, import Raygun4PHP - if you're using Composer, just add `require_once 'vendor/autoload.php'`, or if not manually import RaygunClient.php.

Then, create handlers that look something like this:

```php
namespace
{
	// paste your 'requires' statement

	$client = new \Raygun4php\RaygunClient("apikey for your application");

	function error_handler($errno, $errstr, $errfile, $errline ) {
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
}
```

Note that if you are placing in inside a file with a namespace of your choosing, the above code should be declared to be within the global namespace (thus the `namespace { }` is required). You will also need whichever `requires` statement as above (autoload or manual) before the `$client` instantiation.

Copy your application's API key from the Raygun.io dashboard, and place it in the constructor call as above (do not include the curly brackets).

If the handlers reside in their own file, just import it in every file where you'd like exceptions and errors to be sent, and they will be delivered to Raygun.io.

## Configuration

### Sending method - async/sync

Raygun4PHP has two algorithms which it can use to send your errors:

* **Asynchronous**: POSTs the message and returns to your script immediately without waiting for the response from the Raygun API.

* **Synchronous**: POSTs the message, blocks and receives the HTTP response from the Raygun API. This uses a socket connection which is still reasonably fast. This also allows the use of the debug mode to receive the HTTP response code; see below.


This can be set by passing in a boolean as the 2nd parameter to the constructor:

```php
$client = new \Raygun4php\RaygunClient("apiKey", $useAsyncSending);
```
#### $useAsyncSending options

Type: *boolean*

Linux/OS X default: *true*

Windows default: *false*

* If **$useAsyncSending** is *true*, and the script is running on a *nix platform, the message will be delivered asynchronously. SendError() and SendException() will return 0 if all went well.

* If **$useAsyncSending** is *false*, the script will block and receive the HTTP response.

*false* is the only effective option on Windows due to platform and library limitations within the supported versions.

### Proxies

A HTTP proxy can be set if your environment can't connect out through PHP or the `curl` binrary natively:

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->setProxy('http://someproxy:8080');
```

### Debug mode

The client offers a debug mode in which the HTTP response code can be returned after a POST attempt. This can be useful when adding Raygun to your site. This is accessed by passing in *true* as the third parameter in the client constructor:

```php
$client = new \Raygun4php\RaygunClient("apiKey", $useAsyncSending, $debugMode);
```

#### $debugMode options

*Default: false*

If true is passed in, and **$useAsyncSending** is set to *false*, client->SendException() or SendError() will return the HTTP status code of the POST attempt.

**Note:** If $useAsyncSending is *true*, $debugMode is not available.

#### Response codes

* **202**: Message received by Raygun API correctly
* **403**: Invalid API key. Copy it from your Raygun Application Settings, it should be of the form `new RaygunClient("A+nUc2dLh27vbh8abls7==")`

### Version numbers

You can transmit the version number of your PHP project along with the message by calling `SetVersion()` on your RaygunClient after it is instantiated - this is optional but recommended as the version number is considered to be first-class data for a message.

### Adding Tags

Tags can be added to error data to provide extra information and to help filtering errors within Raygun.
They are provided as an array of strings or numbers passed as the `5th argument to the SendError function` and as the `2nd argument to the SendException function`.

The declaration of the exception and error handlers using tags could look something like this:

```php
$tags = array("testing-enviroment", "machine-4");

function error_handler($errno, $errstr, $errfile, $errline) {
	global $client, $tags;
  	$client->SendError($errno, $errstr, $errfile, $errline, $tags);
}

function exception_handler($exception) {
	global $client, $tags;
	$client->SendException($exception, $tags);
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
    $client->SendError($errno, $errstr, $errfile, $errline, $tags);
  }
}
```

### Affected user tracking

**New in 1.5: additional data support**

You can call $client->SetUser, passing in some or all of the following data, which will be used to provide an affected user count and reports:

```php
SetUser($user = null, $firstName = null, $fullName = null, $email = null, $isAnonymous = null, $uuid = null)
```

`$user` should be a unique identifier which is used to identify your users. If you set this to their email address, be sure to also set the $email parameter too.

This feature and values are optional if you wish to disable it for privacy concerns. To do so, pass `true` in as the third parameter to the RaygunClient constructor.

Note that this data is stored as cookies. If you do not call SetUser the default is to store a random UUID to represent the user.

This feature can be used in CLI mode by calling SetUser() at the start of your session.

### Custom error grouping

Control of how error instances are grouped together can achieved by passing a callback to the `SetGroupingKey` method on the client. If the callback returns a string, ideally 100 characters or less, errors matching that key will grouped together. Overriding the default automatic grouping. If the callback returns a non-string value then that error will be grouped automatically.  

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->SetGroupingKey(function($payload, $stackTrace) {
  // Inspect the above parameters and return a hash from the properties

  return $payload->Details->Error->Message; // Naive message-based grouping only
});
```

### Filtering Sensitive Data

Some error data will be too sensitive to transmit to an external service, such as credit card details or passwords. Since this data is very application specific, Raygun doesn't filter out anything by default. You can configure to either replace or otherwise transform specific values based on their keys. These transformations apply to form data (`$_POST`), custom user data, HTTP headers, and environment data (`$_SERVER`). It does not filter the URL or its `$_GET` parameters, or custom message strings. Since Raygun doesn't log method arguments in stack traces, those don't need filtering. All key comparisons are case insensitive.

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->setFilterParams(array(
	'password' => true,
	'creditcardnumber' => true,
	'ccv' => true,
	'php_auth_pw' => true, // filters basic auth from $_SERVER
));
// Example input: array('Username' => 'myuser','Password' => 'secret')
// Example output: array('Username' => 'myuser','Password' => '[filtered]')
```

You can also define keys as regular expressions:

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->setFilterParams(array(
	'/^credit/i' => true,
));
// Example input: array('CreditCardNumber' => '4111111111111111','CreditCardCcv' => '123')
// Example output: array('CreditCardNumber' => '[filtered]','CreditCardCcv' => '[filtered]')
```

In case you want to retain some hints on the data rather than removing it completely, you can also apply custom transformations through PHP's anonymous functions. The following example truncates all keys starting with "address".

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->setFilterParams(array(
	'Email' => function($key, $val) {return substr($val, 0, 5) . '...';}
));
// Example input: array('Email' => 'test@test.com')
// Example output: array('Email' => 'test@...')
```

Note that when any filters are defined, the Raygun error will no longer contain the raw HTTP data, since there's no effective way to filter it.

### Updating Cookie options

Cookies are used for the user tracking functionality of the Raygun4Php provider. In version 1.8 of the provider, the options passed to the `setcookie` method can now be customized to your needs.

```php
$client = new \Raygun4php\RaygunClient("apiKey");
$client->SetCookieOptions(array(
    'expire'   => 2592000, // 30 * 24 * 60 * 60
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => false
));
```

## Troubleshooting

As above, enable debug mode by instantiating the client with

```php
$client = new \Raygun4php\RaygunClient("apiKey", FALSE, TRUE);
```

This will echo the HTTP response code. Check the list above, and create an issue or contact us if you continue to have problems.

### 400 from command-line Posix environments

If, when running a PHP script from the command line on *nix operating systems, you receive a '400 Bad Request' error (when debug mode is enabled), check to see if you have any LESS_TERMCAP environment variables set. These are not compatible with the current version of Raygun4PHP. As a workaround, unset these variables before your script runs, then reset them afterwards.


### Error Control Operators (@)

If you are using the setup as described above errors will be send to Raygun regardless of any lines prepended with an error control operator (the @ symbol). To stop these errors from being sent to Raygun you can call PHP's [error_reporting](http://php.net/manual/en/function.error-reporting.php) method which return 0 if the triggered error was preceded by an @ symbol.

_Error handler example:_
```php
function error_handler($errno, $errstr, $errfile, $errline ) {
    global $client;
    if(error_reporting() !== 0) {
        $client->SendError($errno, $errstr, $errfile, $errline);
    }
}
```

See the [Error Control Operators section on PHP.net](http://php.net/manual/en/language.operators.errorcontrol.php) for more information  

## Changelog
- 1.8.3: Remove the `--dev` option for composer installations as it's now deprecated
- 1.8.2: No longer output warning when a socket connection fails
- 1.8.1: Fix issue with error being raised with null bytes send with escapeshellarg method
- 1.8.0: Bugfix with multiple cookies being set. Cookie options can be set via the setCookieOptions method
- 1.7.1: Fixed illegal string offset
- 1.7.0: Added custom error grouping
- 1.6.1: Assign ClassName as exceptionClass
- 1.6.0: Added HTTP proxy support, support X-Forwarded-For, null server var guards
- 1.5.3: Unify property casing (internal change)
- 1.5.2: Prevent error when query_string isn't present in $_SERVER
- 1.5.1: Guard against intermittent user id cookie being null; overload for disabling user tracking
- 1.5.0: Add enhanced user data support; fix null backtrace frames that could occur in 1.4
- 1.4.0: Added Sensitive Data Filtering; improved Error backtraces; Travis CI enabled
- 1.3.6: Move included Rhumsaa\Uuid lib into this namespace to prevent collisions if already included
- 1.3.5: Fixed possible bug in async curl logic
- 1.3.4: Bugfix in request message for testing
- 1.3.3: Hotfix for script error in v1.3.2
- 1.3.2: UTF-8 encoding routine from previous version updated to remove PHP 5.5 deprecated function
- 1.3.1: Request data, specifically $_SERVER variables, are now correctly encoded in UTF-8
- 1.3: Added debug mode to output HTTP response code when in socket mode
- 1.2.6: Fixed a bug in previous release rendering the UTC offset fix ineffective (thanks @mrardon for spotting this)
- 1.2.5: Request rawData (php://input) limited to 4096 bytes in line with other providers; clamp UTC offset to sane values as API was seeing some entries with max int offsets
- 1.2.4: Merged in unit tests
- 1.2.3: Fixed a bug where OccurredOn wasn't in correct ISO 8601 representation
- 1.2.2: Minor formatting refactor
- 1.2.1: Several bugfixes for user tracking and request processing
- 1.2: Added new async sending function; removed cURL dependency
- 1.1: Added user tracking support; improved experience in CLI mode; add user-specified timestamp support; fixed user data encoding error
- 1.0: Initial commit
