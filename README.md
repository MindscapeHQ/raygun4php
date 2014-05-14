Raygun4php
==========

[Raygun.io](http://raygun.io) provider for PHP 5.3+

## Installation

Firstly, ensure that **curl** is installed and enabled in your server's php.ini file.

### With Composer

Composer is a package management tool for PHP which automatically fetches dependencies and also supports autoloading - this is a low-impact way to get Raygun4php into your site.

1. If you use a *nix environment, [follow the instructions](http://getcomposer.org/doc/01-basic-usage.md#installation) to install Composer. Windows users can run [this installer](https://github.com/johnstevenson/composer-setup) to automatically add it to the path etc.

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

You can send both PHP errors and object-oriented exceptions to Raygun. An easy way to accomplish this is to create a file containing exception and error handlers which make calls to the appropriate Raygun4php functions. As above, import Raygun4php - if you're using Composer, just add `require_once 'vendor/autoload.php'`, or if not manually import RaygunClient.php.

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

### User tracking

You can call $client->SetUser($user), passing in a string representing the username or email address of the current user of the calling application. This will be attached to the message and visible in the dashboard. This method is optional - if it is not called, a random identifier will be used. If you use this, and the user changes (log in/out), be sure to call it again passing in the new user (or just call $client->SetUser() to assign a new random identifier).

Note that this data is stored as a cookie. If you do not call SetUser the default is to store a random UUID to represent the user.

This feature can be used in CLI mode by calling SetUser(string) at the start of your session.

## Troubleshooting

As above, enable debug mode by instantiating the client with

```php
$client = new \Raygun4php\RaygunClient("apiKey", FALSE, TRUE);
```

This will echo the HTTP response code. Check the list above, and create an issue or contact us if you continue to have problems.

### 400 from command-line Posix environments

If, when running a PHP script from the command line on *nix operating systems, you receive a '400 Bad Request' error (when debug mode is enabled), check to see if you have any LESS_TERMCAP environment variables set. These are not compatible with the current version of Raygun4PHP. As a workaround, unset these variables before your script runs, then reset them afterwards.

## Changelog

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
