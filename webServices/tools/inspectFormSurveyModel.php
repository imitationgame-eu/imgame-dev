<?php
// -----------------------------------------------------------------------------
// 
// debug a form/survey definition
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once $root_path.'/domainSpecific/mySqlObject.php';
require_once $root_path.'/helpers/forms/class.stepFormsHandler.php';
require_once $root_path.'/kint/Kint.class.php';
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];
$jType = "";
$formHandler = new stepFormsHandler(null, $exptId, $formType);
$formDef = $formHandler->getForm();
kint::dump($formDef);
