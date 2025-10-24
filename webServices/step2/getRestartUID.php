<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php'; 

$exptId = $_GET['exptId'];
$formType = $_GET['formType'];
$userCode = $_GET['userCode'];

$getUID = sprintf("INSERT INTO wt_Step2FormUIDs (exptId,formType,recruitmentCode) VALUES('%s','%s','%s')", $exptId, $formType, $userCode);
$igrtSqli->query($getUID);
//echo $getUID;
$uid = $igrtSqli->insert_id;
echo $uid;

