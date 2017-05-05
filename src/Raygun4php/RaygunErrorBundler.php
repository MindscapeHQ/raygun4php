<?php 

namespace Raygun4php
{
  class RaygunErrorBundler {
    private $bundle = array();
    private $startTime;
    private $maxBundleSize;
    private $expiryInSeconds;

    public function __construct($options = array()) {
      $this->maxBundleSize = isset($options["maxBundleSize"]) ? $options["maxBundleSize"] : 100;
      $this->expiryInSeconds = isset($options["expiryInSeconds"]) ? $options["expiryInSeconds"] : 60;
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
      return json_encode($this->bundle);
    }

    public function reset() {
      $this->startTime = time();
      $this->setBundle(array());
      $_SESSION["raygun_error_bundle"] = array();
    }

    public function isReadyToSend() {
      return count($this->bundle) >= $this->maxBundleSize || $this->isBundleExpired();
    }

    private function isBundleExpired() {
      $currentTimestamp = time();

      return ($currentTimestamp - $this->startTime) > $this->expiryInSeconds;
    }

  }
}