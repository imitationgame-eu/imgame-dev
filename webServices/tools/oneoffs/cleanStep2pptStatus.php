<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';      
  $exptId = $_GET['exptId'];
  $distinctAJNSql = sprintf("SELECT DISTINCT(actualJNo) as actualJNo FROM wt_Step2Balancer WHERE exptId='%s' AND jType=0", $exptId);
  $distinctAJNResult = $igrtSqli->query($distinctAJNSql);
  while ($distinctAJNRow = $distinctAJNResult->fetch_object()) {
    $actualJNo = $distinctAJNRow->actualJNo;
    $maxResp = $distinctAJNRow->respCount;
    $cleanUpSql = sprintf("DELETE FROM wt_Step2pptStatus WHERE exptId='%s' AND jType=0 AND actualJNo='%s' AND respNo>'%s'", $exptId, $actualJNo, $maxResp);
    $igrtSqli->query($cleanUpSql);
  }
  