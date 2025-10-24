<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';       
include_once $root_path.'/ws/models/class.step1AllocationModel.php';

$exptId = 277;  
$dayNo = 1;           
$sessionNo = 1;       
$fn = "BerlinFuss.csv";
$lines = file($fn, FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}
for ($i=0; $i<count($datalines); $i++) {
  $components = explode(',', $datalines[$i]['data']);
  $jType = $components[0];
  $jNo = $components[1];
  $qNo = $components[2];
  $confidence = $components[4];
  $reason = urldecode($components[5]);
  $reason = $igrtSqli->real_escape_string($reason);
  $update = sprintf("UPDATE dataSTEP1 SET rating='interval%s', reason='%s' WHERE "
      . "exptId=277 AND jType='%s' AND jNo='%s' AND qNo='%s'",
      $confidence, $reason, $jType, $jNo, $qNo);
  echo $update.';<br />';
}