<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     


//clear down
$d = "DELETE FROM dataSTEP2 WHERE exptId=276";
$igrtSqli->query($d);
$d = "DELETE FROM wt_Step2Balancer WHERE exptId=276";
$igrtSqli->query($d);
$d = "DELETE FROM wt_Step2pptStatus WHERE exptId=276";
$igrtSqli->query($d);
$d = "DELETE FROM wt_Step2FormUIDs WHERE exptId=276";
$igrtSqli->query($d);

$ins ="INSERT INTO `wt_Step2Balancer` SELECT * FROM `276Step2Balancer` WHERE exptId=276";
$igrtSqli->query($ins);
$ins ="INSERT INTO `wt_Step2pptStatus` SELECT * FROM `276Step2pptStatus` WHERE exptId=276";
$igrtSqli->query($ins);

$getId = "SELECT * FROM `276Step2FormUIDs` ORDER BY id ASC";
$idResult = $igrtSqli->query($getId);
while ($row = $idResult->fetch_object()) {
  $oldId = $row->id;
  $formType = $row->formType;
  $finishedPre = $row->finishedPre;
  $finishedPost = $row->finishedPost;
  $recruitmentCode = $row->recruitmentCode;
  
  // de-reference pptStatus id to insert into 
  
  
  $newIns = sprintf("INSERT INTO wt_Step2FormUIDs (exptId, formType, finishedPre, finishedPost, recruitmentCode) "
      . "VALUES ('276','%s','%s','%s','%s')",
      $formType, $finishedPre, $finishedPost, $recruitmentCode);
  $igrtSqli->query($newIns);
  $newId = $igrtSqli->insert_id;
  $update = sprintf("UPDATE `276Step2pptStatus` SET restartUID='%s' WHERE restartUID='%s'", $newId, $oldId);
  $igrtSqli->query($update);
  
  echo "$newIns updated $oldId with $newId<br />";
  /// THEN check in tables before INSERT-SELECT 
}
echo 'done';

