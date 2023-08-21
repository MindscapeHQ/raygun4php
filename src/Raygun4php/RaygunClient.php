<?php

namespace Raygun4php;

use Raygun4php\Interfaces\TransportInterface;
use Raygun4php\Rhumsaa\Uuid\Uuid;

class RaygunClient
{
    protected $version;
    protected $tags;
    protected $userIdentifier;
    protected $user;
    protected $firstName;
    protected $fullName;
    protected $email;
    protected $isAnonymous;
    protected $uuid;
    protected $disableUserTracking;
    protected $transport;

    protected $groupingKeyCallback;

    protected $cookieOptions = array(
        'use'      => true,
        'expire'   => 2592000, // 30 * 24 * 60 * 60
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => false
    );

    /**
     * @var array Parameter names to filter out of logged form data. Case insensitive.
     *   Accepts regular expressions when the name starts with a forward slash.
     *   Maps either to TRUE, or to a callable with $key and $value arguments.
     */
    protected $filterParams = array();

    /**
     * @var boolean If true, all values from the $_POST array will be filtered out.
     */
    protected $filterAllFormValues = false;

    /**
     * Creates a new RaygunClient instance.
     *
     * @param TransportInterface $transport
     * @param bool $disableUserTracking
     */
    public function __construct(TransportInterface $transport, $disableUserTracking = false)
    {
        $this->transport = $transport;

        if (!$disableUserTracking) {
            $this->SetUser();
        }

        $this->disableUserTracking = $disableUserTracking;
    }

    /**
     * @param bool $disableUserTracking True to disable user tracking
     * @return $this
     */
    public function setDisableUserTracking($disableUserTracking)
    {
        $this->disableUserTracking = $disableUserTracking;
        return $this;
    }

    /**
     * @return bool Returns true if user tracking is disabled
     */
    public function getDisableUserTracking()
    {
        return $this->disableUserTracking;
    }

    /**
     * Transmits an error to the Raygun API
     *
     * @param int    $errno          The error number
     * @param string $errstr         The error string (Used for error grouping. So don't include identifiers in $errstr. Use $userCustomData for, per instance, unique values)
     * @param string $errfile        The file the error occurred in
     * @param int    $errline        The line the error occurred on
     * @param array  $tags           An optional array of string tags used to provide metadata for the message
     * @param array  $userCustomData An optional associative array that can be used to place custom key-value
     * @param int    $timestamp      Current Unix timestamp in the local timezone, used to set when an error occurred.
     *                               data in the message payload
     * @return int The HTTP status code of the result when transmitting the message to Raygun
     */
    public function SendError(
        $errno,
        $errstr,
        $errfile,
        $errline,
        $tags = null,
        $userCustomData = null,
        $timestamp = null
    ) {
        $message = $this->BuildMessage(new \ErrorException($errstr, $errno, 0, $errfile, $errline), $timestamp);

        if ($tags != null) {
            $this->AddTags($message, $tags);
        }

        if ($userCustomData != null) {
            $this->AddUserCustomData($message, $userCustomData);
        }

        $this->AddGroupingKey($message);

        return $this->Send($message);
    }

    /**
     * Transmits an exception to the Raygun API
     *
     * @param \Throwable $throwable      An exception object to transmit
     * @param array      $tags           An optional array of string tags used to provide metadata for the message
     * @param array      $userCustomData An optional associative array that can be used to place custom key-value
     *                                   data in the message payload
     * @param int        $timestamp      Current Unix timestamp in the local timezone, used to set when an exception
     *                                   occurred.
     * @return int The HTTP status code of the result when transmitting the message to Raygun
     */
    public function SendException($throwable, $tags = null, $userCustomData = null, $timestamp = null)
    {
        $message = $this->BuildMessage($throwable, $timestamp);

        if ($tags != null) {
            $this->AddTags($message, $tags);
        }

        if ($userCustomData != null) {
            $this->AddUserCustomData($message, $userCustomData);
        }

        $this->AddGroupingKey($message);

        return $this->Send($message);
    }

    /**
     * Sets the version number of your project that will be transmitted to Raygun.com.
     *
     * @param string $version The version number in the form of x.x.x.x, where x is a positive integer.
     */
    public function SetVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Sets an identifier for the current user of the application into the context
     *
     * When using this method, the user identifier is not persisted internally by this library. It's up to
     * the caller to:
     *   - use a long-lived identifier (like an immutable database ID for the user, not just a session ID)
     *   - persist the values in some sort of cross-request storage (like a user database)
     *   - construct a RaygunIdentifier and pass it in
     *
     * If you'd like this library to manage the lifetime of the user token, use $this->setUser() instead, which emits
     * cookies, but doesn't require a identifier value object.
     *
     * @param RaygunIdentifier $identifier
     * @return $this
     */
    public function setUserIdentifier(RaygunIdentifier $identifier)
    {
        $this->userIdentifier = $identifier;
        return $this;
    }

    /**
     * Stores the current user of the calling application. This will be added to any messages sent
     * by this provider. It is used in the dashboard to provide unique user tracking.
     *
     * @param string|int $user        String or numeric type, identifier for the current user, a username,
     *                                email address or other unique identifier, if not supplied user is set to anonymous
     *                                and a unique identifier is generated
     * @param string     $firstName
     * @param string     $fullName
     * @param string     $email
     * @param boolean    $isAnonymous Indicates whether the user is anonymous or not
     * @param string     $uuid        Optional device identifier
     */
    public function SetUser(
        $user = null,
        $firstName = null,
        $fullName = null,
        $email = null,
        $isAnonymous = null,
        $uuid = null
    ) {
        $this->firstName = $this->StoreOrRetrieveUserCookie('rgfirstname', $firstName);
        $this->fullName = $this->StoreOrRetrieveUserCookie('rgfullname', $fullName);
        $this->email = $this->StoreOrRetrieveUserCookie('rgemail', $email);
        $this->uuid = $this->StoreOrRetrieveUserCookie('rguuidvalue', $uuid);

        $isAnonymousCookie = $this->StoreOrRetrieveUserCookie('rgisanonymous', $isAnonymous ? 'true' : 'false');
        $this->isAnonymous = ($isAnonymousCookie === 'true');

        if ((is_string($user) || is_numeric($user)) && !$isAnonymous) {
            $this->user = $user;

            if (php_sapi_name() != 'cli' && !headers_sent()) {
                $this->setCookie('rguserid', $user);
                $this->setCookie('rguuid', 'false');
            }
        } else {
            if (!array_key_exists('rguuid', $_COOKIE)) {
                $this->user = (string)Uuid::uuid4();

                if (php_sapi_name() != 'cli' && !headers_sent()) {
                    $this->setCookie('rguserid', $this->user);
                    $this->setCookie('rguuid', 'true');
                }
            } elseif (array_key_exists('rguserid', $_COOKIE)) {
                $this->user = $_COOKIE['rguserid'];
            }

            $this->isAnonymous = $this->StoreOrRetrieveUserCookie('rgisanonymous', 'true') == 'true';
        }
    }

    /**
     * Sets a callback to control how error instances are grouped together. The callback
     * is provided with the payload and stack trace of the error upon execution. If the
     * callback returns a string then error instances with a matching key will grouped together.
     * If the callback doesn't return a value, or the value is not a string, then automatic
     * grouping will be used.
     * @param callable $callback
     *
     */
    public function SetGroupingKey($callback)
    {
        $this->groupingKeyCallback = $callback;
    }

    private function StoreOrRetrieveUserCookie($key, $value)
    {
        if (is_string($value)) {
            if (php_sapi_name() != 'cli' && !headers_sent()) {
                $this->setCookie($key, $value ?? '');
            }

            return $value;
        } else {
            if (array_key_exists($key, $_COOKIE)) {
                if ($_COOKIE[$key] != $value && php_sapi_name() != 'cli' && !headers_sent()) {
                    $this->setCookie($key, $value ?? '');
                }
                return $_COOKIE[$key];
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function setCookie($name, $value)
    {
        $options = $this->cookieOptions;

        if ($options['use'] === true) {
            setcookie(
                $name,
                $value,
                time() + $options['expire'],
                $options['path'],
                $options['domain'],
                $options['secure'],
                $options['httponly']
            );
        }
    }

    /**
     * @param \Throwable $errorException
     * @param int $timestamp
     * @return RaygunMessage
     */
    private function BuildMessage($errorException, $timestamp = null)
    {
        $message = new RaygunMessage($timestamp);
        $message->build($errorException);
        $message->Details->Version = $this->version;
        $message->Details->Context = new RaygunIdentifier(session_id());

        if (!empty($this->userIdentifier)) {
            $message->Details->User = $this->userIdentifier;
        } elseif ($this->user != null) {
            $message->Details->User = new RaygunIdentifier(
                $this->user,
                $this->firstName,
                $this->fullName,
                $this->email,
                $this->isAnonymous,
                $this->uuid
            );
        } elseif (!$this->disableUserTracking && array_key_exists('rguserid', $_COOKIE)) {
            $message->Details->User = new RaygunIdentifier($_COOKIE['rguserid']);
        }

        return $message;
    }

    /**
     * Sets a string array of tags relating to the message, used for identification. These will be transmitted along
     * with messages that are sent.
     *
     * @param RaygunMessage $message
     * @param array         $tags The tags relating to your project's version
     * @throws Raygun4PhpException
     */
    private function AddTags(&$message, $tags)
    {
        if (!is_array($tags)) {
            throw new Raygun4PhpException("Tags must be an array");
        }
        $message->Details->Tags = $tags;
    }

    /**
     * @param RaygunMessage $message
     * @param array $userCustomData
     * @throws Raygun4PhpException
     */
    private function AddUserCustomData(&$message, $userCustomData)
    {
        if ($this->is_assoc($userCustomData)) {
            $message->Details->UserCustomData = $userCustomData;
        } else {
            throw new Raygun4PhpException("UserCustomData must be an associative array");
        }
    }

    /**
     * @param RaygunMessage $message
     */
    private function AddGroupingKey(&$message)
    {
        if (is_callable($this->groupingKeyCallback)) {
            $groupingKey = call_user_func($this->groupingKeyCallback, $message, $message->Details->Error->StackTrace);

            if (is_string($groupingKey)) {
                $message->Details->GroupingKey = $groupingKey;
            }
        }
    }

    /**
     * @param array $array
     * @return bool
     */
    private function is_assoc($array)
    {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Transmits a RaygunMessage to the Raygun API.
     * This is a lower level function used by SendException and SendError and one of those should be used preferably.
     *
     * @param RaygunMessage $message A populated message to be posted to the Raygun API
     * @return bool Returns true if the transmission attempt is successful.
     *              However, this does not guarantee that the message is delivered.
     */
    public function Send($message)
    {
        $message = $this->filterParamsFromMessage($message);
        return $this->transport->transmit($message);
    }

    /**
     * Optionally applies a value transformation to every matching key, as defined by {@link FilterParams}.
     * Replaces the value by default, but also supports custom transformations through
     * anonymous functions. Applies to form data, environment data, HTTP headers.
     * Does not apply to GET parameters in the request URI.
     * Filters out raw HTTP data in case any filters are defined, since we can't accurately filter it.
     *
     * @param RaygunMessage $message
     * @param  string $replace Value to be inserted by default (unless specified otherwise by custom transformations).
     * @return RaygunMessage
     */
    public function filterParamsFromMessage($message, $replace = '[filtered]')
    {
        $filterParams = $this->getFilterParams();

        // Skip checks if none are defined
        if (!$filterParams && !$this->getFilterAllFormValues()) {
            return $message;
        }

        // Ensure all filters are callable
        $defaultFn = function ($key, $val) use ($replace) {
            return $replace;
        };
        foreach ($filterParams as $filterKey => $filterFn) {
            if (!is_callable($filterFn)) {
                $filterParams[$filterKey] = $defaultFn;
            }
        }

        $walkFn = function (&$val, $key) use ($filterParams) {
            foreach ($filterParams as $filterKey => $filterFn) {
                if (
                    (strpos($filterKey, '/') === 0 && preg_match($filterKey, $key))
                    || (strpos($filterKey, '/') === false && strtolower($filterKey) == strtolower($key))
                ) {
                    $val = $filterFn($key, $val);
                }
            }
        };

        // Filter form values
        if ($message->Details->Request->Form) {
            if ($this->getFilterAllFormValues()) {
                // Filter out ALL form values.
                $filterAllDataFn = function (&$val, $key) use ($defaultFn) {
                    $val = $defaultFn($key, $val);
                };
                array_walk_recursive($message->Details->Request->Form, $filterAllDataFn);
            } else {
                // Filter only form values that match a filter param.
                array_walk_recursive($message->Details->Request->Form, $walkFn);
            }
        }

        if ($message->Details->Request->Headers) {
            array_walk_recursive($message->Details->Request->Headers, $walkFn);
        }

        if ($message->Details->Request->Data) {
            array_walk_recursive($message->Details->Request->Data, $walkFn);
        }

        if ($message->Details->Request->IpAddress) {
            $walkFn($message->Details->Request->IpAddress, 'IpAddress');
        }

        if ($message->Details->UserCustomData) {
            array_walk_recursive($message->Details->UserCustomData, $walkFn);
        }

        // Unset raw HTTP data since we can't accurately filter it
        if ($message->Details->Request->RawData) {
            $message->Details->Request->RawData = null;
        }

        return $message;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setFilterParams($params)
    {
        $this->filterParams = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilterParams()
    {
        return $this->filterParams;
    }

    /**
     * @param boolean $filterAll
     * @return $this
     */
    public function setFilterAllFormValues(bool $filterAll)
    {
        $this->filterAllFormValues = $filterAll;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getFilterAllFormValues()
    {
        return $this->filterAllFormValues;
    }

    /**
     * Sets the given cookie options
     *
     * Existing values will be overridden. Values that are missing from the array being set will keep their current
     * values.
     *
     * The key names match the argument names on setcookie() (e.g. 'expire' or 'path'). Pass the default value according
     * to PHP's setcookie() function to ignore that parameter.
     *
     * @param array<string,mixed> $options
     */
    public function SetCookieOptions($options)
    {
        $this->cookieOptions = array_merge($this->cookieOptions, $options);
    }
}
