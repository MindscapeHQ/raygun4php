<?php 

namespace Raygun4php
{
  class RaygunErrorBundler {
    private $bundle = array();
    private $settings = array();
    private $startTime;

    public function __construct($options = array()) {
      $defaults = array(
        "maxBundleSize" => 100,
        "expiryInSeconds" => 60,
        "gzipBundle" => false,
        "gzipLevel" => 6
      );

      $this->settings = array_merge($defaults, $options);

      $this->startTime = time();
    }

    public function getBundle() {
      return $this->bundle;
    }

    public function setBundle($bundle = array()) {
      $this->bundle = $bundle;
    }

    public function addMessage($message) {
      $this->bundle[] = $message;
      $_SESSION["raygun_error_bundle"] = $this->bundle;
    }

    public function getJson() {
      $bundle = $this->getBundle();

      // Manually convert to JSON to prevent double-encoding
      $bundleString = implode(",", $bundle);
      $bundleString = "[{$bundleString}]";

      if($this->settings["gzipBundle"]) {
        $bundleString = gzencode($bundleString, $this->settings["gzipLevel"]);
      }

      return base64_encode($bundleString);
    }

    public function reset() {
      $this->startTime = time();
      $this->setBundle(array());
      $_SESSION["raygun_error_bundle"] = array();
    }

    public function isReadyToSend() {
      return count($this->getBundle()) >= $this->settings["maxBundleSize"] || $this->isBundleExpired();
    }

    public function getFromStorage() {
      if(isset($_SESSION) && isset($_SESSION["raygun_error_bundle"])) {
        $sessionBundle = $_SESSION["raygun_error_bundle"];

        if(is_array($sessionBundle) && !empty($sessionBundle)) {
          return $sessionBundle;
        }
      }

      return false;
    }
    private function isBundleExpired() {
      return (time() - $this->startTime) > $this->settings["expiryInSeconds"];
    }

  }
}