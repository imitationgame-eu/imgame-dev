<?php
// -----------------------------------------------------------------------------
// 
// web service to return step form definition as JSON object for rendering 
// by KO-JS
// 
// uses class.stepFormsHandler.php which is used at this configuration stage
// and also at runtime where it builds the form
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/admin/class.stepFormsConfigurator.php');
$permissions = $_GET['permissions'];
$exptId = isset($_GET['exptId']) ? $_GET['exptId'] : -1;
$jType = isset($_GET['jType']) ? $_GET['jType'] : -1;
$formType = isset($_GET['formType']) ? $_GET['formType'] : -1;
$formName = isset($_GET['formName']) ? $_GET['formName'] : "";

//echo $exptId.' '.$jType.' '.$formType;

//ensure admin
if ($permissions >= 128) {
  $formHandler = new stepFormsConfigurator();
  echo $formHandler->getStepFormJSON();
}
