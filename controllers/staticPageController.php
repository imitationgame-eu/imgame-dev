<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
if (!isset($domainName)) { $do = $_SERVER['DOCUMENT_ROOT']; }
include_once($root_path . "/config/staticPageDefinitions.php");
global $staticPageMappings;

class StaticPageController {
  
  private $_statusPagePtr;
  
  public $responseData;
  
  public function __construct($statusPagePtr) {
    $this->_statusPagePtr = $statusPagePtr;
  }
  
  public function invoke() {
    global $staticPageMappings;
    $this->responseData = file_get_contents($staticPageMappings[$this->_statusPagePtr]);
  }
  
}
