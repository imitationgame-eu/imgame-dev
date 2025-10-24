<?php
// -----------------------------------------------------------------------------
//  web service to clone a form definition onto other selected forms, 
//  either within the same experiment, or across experiments.
//  The list of forms to be overwritten/created 
// 
// -----------------------------------------------------------------------------
  function getTypeFromName($targetFormName) {
    global $formNames;
    $i = 0;
    while ($i<count($formNames)) {
      if ($formNames[$i] === $targetFormName) { return $i; }
      ++$i;
    }
  }
  function getNames() {
    global $igrtSqli;
    $getTypeSql = "SELECT * FROM fdStepFormsNames ORDER BY formType ASC";
    $getTypeResult = $igrtSqli->query($getTypeSql);
    $retArray = array();
    while ($getTypeRow = $getTypeResult->fetch_object()) {
      array_push($retArray, $getTypeRow->formName);
    }
    return $retArray;
  }

  
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php';
$formNames = getNames();
$json = file_get_contents('php://input');
$jsonData = json_decode($json, true);
$sourceExptId = $jsonData['sourceExptId'];
$sourceFormType = $jsonData['sourceFormType'];

foreach ($jsonData['flipSwitchStates'] as $flipSwitchState) {
	if ($flipSwitchState['cloneHere'] == 1) {
		$details = explode('_',$flipSwitchState['id']);
		$targetExptId = $details[1];
		$targetFormType = $details[2];
		$qry = "CALL igrt.cloneForm($targetExptId, $targetFormType, $sourceExptId, $sourceFormType)";
		$igrtSqli->query($qry);
	}
}

