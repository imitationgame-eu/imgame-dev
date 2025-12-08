<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$userId = $_GET['uid'];
$fieldName = $_GET['fieldName'];
$status = $_GET['status'];
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];


function processMessage($userId, $exptId, $formType, $fieldName, $status) {
  global $igrtSqli;
  $fnVars = explode('_',$fieldName);
  switch(count($fnVars)) {
    case 1:
      // top level accordions
      $checkQry = sprintf("select * from ui_formDefControlUserStatus where userId='%s' and exptId='%s' and formType='%s'", $userId, $exptId, $formType);
      $checkResult = $igrtSqli->query($checkQry);
      if ($checkResult->num_rows == 0) {
        $createQry = sprintf("insert into ui_formDefControlUserStatus (userId, exptId, formType) VALUES ('%s','%s','%s')", $userId, $exptId, $formType);
        $igrtSqli->query($createQry);
      }
      $updateQry=sprintf("update ui_formDefControlUserStatus set %s='%s' where userId='%s' and exptId='%s' and formType='%s'", $fieldName, $status, $userId, $exptId, $formType);
      $igrtSqli->query($updateQry);
      break;
    case 2:
      // page accordions
      $pNo = $fnVars[1];
      $checkQry = sprintf("select * from ui_formDefPageControlUserStatus where userId='%s' and exptId='%s' and formType='%s' and pNo='%s'", $userId, $exptId, $formType, $pNo);
      $checkResult = $igrtSqli->query($checkQry);
      if ($checkResult->num_rows == 0) {
        $createQry = sprintf("insert into ui_formDefPageControlUserStatus (userId, exptId, formType, pNo, status) VALUES ('%s','%s','%s','%s', 1)", $userId, $exptId, $formType, $pNo);
        $igrtSqli->query($createQry);
      }
      $updateQry=sprintf("update ui_formDefPageControlUserStatus set status='%s' where userId='%s' and exptId='%s' and formType='%s' and pNo='%s'", $status, $userId, $exptId, $formType, $pNo);
      $igrtSqli->query($updateQry);
      break;
    case 3:
      // question accordions
      $pNo = $fnVars[1];
      $qNo = $fnVars[2];
      $checkQry = sprintf("select * from ui_formDefPageQuestionControlStatus where userId='%s' and exptId='%s' and formType='%s' and pNo='%s' and qNo='%s'", $userId, $exptId, $formType, $pNo, $qNo);
      $checkResult = $igrtSqli->query($checkQry);
      if ($checkResult->num_rows == 0) {
        $createQry = sprintf("insert into ui_formDefPageQuestionControlStatus (userId, exptId, formType, pNo, qNo, status) VALUES ('%s','%s','%s','%s','%s', 1)", $userId, $exptId, $formType, $pNo, $qNo);
        $igrtSqli->query($createQry);
      }
      $updateQry=sprintf("update ui_formDefPageQuestionControlStatus set status='%s' where userId='%s' and exptId='%s' and formType='%s' and pNo='%s' and qNo='%s'", $status, $userId, $exptId, $formType, $pNo, $qNo);
      $igrtSqli->query($updateQry);
      break;
  }
  
  
  
  $result['status'] = 'ok';
  echo json_encode($result);
}

//ensure admin
if ($permissions >= 128) {
  processMessage($userId, $exptId, $formType, $fieldName, $status);
}
