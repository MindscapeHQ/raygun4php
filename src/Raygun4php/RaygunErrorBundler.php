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
      $json = json_encode($this->getBundle());

      if($this->settings["gzipBundle"]) {
        $json = gzcompress($json, $this->settings["gzipLevel"]);
      }

      return $json;
    }

    public function reset() {
      $this->startTime = time();
      $this->setBundle(array());
      $_SESSION["raygun_error_bundle"] = array();
    }

    public function isReadyToSend() {
      return count($this->getBundle()) >= $this->settings["maxBundleSize"] || $this->isBundleExpired();
    }

    private function isBundleExpired() {
      return (time() - $this->startTime) > $this->settings["expiryInSeconds"];
    }

  }
}