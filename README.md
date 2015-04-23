Raygun4PHP
==========

[Raygun.io](http://raygun.io) provider for PHP 5.3+

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

Clone this repository and copy src/Raygun4php into an appropriate subdirectory in your project, such as /vendor/Raygun4php. Add `requires` definitions for RaygunClient.php where you want to make a call to Send().

```php
require (dirname(dirname(__FILE__)).'/vendor/Raygun4php/RaygunClient.php');
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

	set_exception_handler('exception_handler');
	set_error_handler("error_handler");
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

## Troubleshooting

As above, enable debug mode by instantiating the client with

```php
$client = new \Raygun4php\RaygunClient("apiKey", FALSE, TRUE);
```

This will echo the HTTP response code. Check the list above, and create an issue or contact us if you continue to have problems.

### 400 from command-line Posix environments

If, when running a PHP script from the command line on *nix operating systems, you receive a '400 Bad Request' error (when debug mode is enabled), check to see if you have any LESS_TERMCAP environment variables set. These are not compatible with the current version of Raygun4PHP. As a workaround, unset these variables before your script runs, then reset them afterwards.

## Changelog

-	1.6.1: Assign ClassName as exceptionClass
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
