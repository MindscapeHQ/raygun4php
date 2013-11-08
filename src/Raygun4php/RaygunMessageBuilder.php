<?php

namespace Raygun4php;

use Rhumsaa\Uuid\Uuid;

class RaygunMessageBuilder {

    private $user;
    private $version;

    function __construct()
    {
        $this->SetUser();
    }


    public function SetUser($user = null)
    {
        if (is_string($user)) {
            $this->user = $user;
            if (php_sapi_name() != 'cli' && !headers_sent()) {
                setcookie('rguserid', $user, time() + 60 * 60 * 24 * 30);
                setcookie('rguuid', 'false', time() + 60 * 60 * 24 * 30);
            }
        } else {
            if (!array_key_exists('rguuid', $_COOKIE)) {
                $this->user = (string)Uuid::uuid4();
                if (php_sapi_name() != 'cli' && !headers_sent()) {
                    setcookie('rguserid', $this->user, time() + 60 * 60 * 24 * 30);
                    setcookie('rguuid', 'true', time() + 60 * 60 * 24 * 30);
                }
            } else {
                $this->user = $_COOKIE['rguserid'];
            }
        }
    }

    /**
     * Sets the version number of your project that will be transmitted
     * to Raygun.io.
     * @param string $version The version number in the form of x.x.x.x,
     * where x is a positive integer.
     *
     */
    public function SetVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Sets a string array of tags relating to the message,
     * used for identification. These will be transmitted along with messages that
     * are sent.
     * @param $errorException
     * @param mixed $timestamp
     * @internal param array $tags The tags relating to your project's version
     * @return \Raygun4php\RaygunMessage
     */
    public  function BuildMessage($errorException, $timestamp = null)
    {
        $message = new RaygunMessage($timestamp);
        $message->Build($errorException);
        $message->Details->Version = $this->version;
        $message->Details->Context = new RaygunIdentifier(session_id());

        if ($this->user != null) {
            $message->Details->User = new RaygunIdentifier($this->user);
        } else {
            $message->Details->User = new RaygunIdentifier($_COOKIE['rguserid']);
        }
        return $message;
    }

    public function AddTagsToMessage(&$message, $tags)
    {
        if (is_array($tags)) {
            $message->Details->Tags = $tags;
        } else {
            throw new Raygun4PhpException("Tags must be an array");
        }
    }
} 