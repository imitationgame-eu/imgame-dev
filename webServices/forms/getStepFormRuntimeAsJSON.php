<?php
// -----------------------------------------------------------------------------
// 
// web service to return runtime step form definition as JSON object for 
// rendering by KO-JS
// 
// uses class.stepFormsHandler.php which is used at this configuration stage
// and also at runtime where it builds the form
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);

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

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/admin/class.stepFormsConfigurator.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';     
$permissions = $_GET['permissions'];
$formType = $_GET['formType'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];
$restartUID = isset($_GET['restartUID']) ? $_GET['restartUID'] : -1;
$respId = isset($_GET['respId']) ? $_GET['respId'] : -1;
$formNames = getNames();
$formName = $formNames[$formType];

//ensure admin
if ($permissions >= 128) {
  // need to seed stepFormsConfigurator with formName even though
  // not used in runtime
  $formConfigurator = new stepFormsConfigurator();
  echo $formConfigurator->getStepFormRuntimeJSON($formType, $restartUID, $respId);
}
