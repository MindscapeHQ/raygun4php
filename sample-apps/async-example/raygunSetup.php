<?php

namespace
{
  require_once 'vendor/autoload.php';

  $client = new \Raygun4php\RaygunClient(API_KEY, true, false, false);

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