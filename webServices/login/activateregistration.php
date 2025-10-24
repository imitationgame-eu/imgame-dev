<?php
global $staticPageMappings;
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/staticPageDefinitions.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/classes/membership/uActivate.php');
//echo "hello";
$pagePtr=-1;   // out of range
$activationCode=$_GET['act'];
$activationObject=new userActivation($activationCode);
//echo $activationCode.'<br />';
$activationExists=$activationObject->isActivationValid();
if ($activationExists) {
  if ($activationObject->processActivation()) {
    // all okay
    $pagePtr=5;
  }
  else {
    // activation failure - contact system administrator
    $pagePtr=7;
  }
}
else {
  // post to retry-attempt page
  $pagePtr=6;
}
$filecontents = file_get_contents(sprintf("%s/%s",$_SERVER['DOCUMENT_ROOT'], $staticPageMappings[$pagePtr]));
echo $filecontents;

