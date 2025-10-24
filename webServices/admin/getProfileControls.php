<?php
// -----------------------------------------------------------------------------
// 
// web service to create page controls for configuring the registration profile
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
require_once($root_path.'/helpers/forms/class.formBuilder.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';       
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];

//ensure admin
if ($permissions >= 128) {
  $htmlBuilder = new htmlBuilder();
  $formBuilder = new formBuilder($uid);
  echo $formBuilder->getUserProfileDefinition();
}
