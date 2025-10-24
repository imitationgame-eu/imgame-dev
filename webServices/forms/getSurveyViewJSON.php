<?php
// -----------------------------------------------------------------------------
// 
// build JSON of all survey responses for viewing in KO-JS page
// but can be used to output RTF from the JSON with an extra parameter
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/webServices/forms/buildSurveyDefinition.php';

//include_once $root_path.'/kint/Kint.class.php';
//require_once $root_path.'/helpers/rtf/PHPRtfLite.php';


$permissions = $_GET['permissions'];
$formType = $_GET['formType'];
$exptId = $_GET['exptId'];

//ensure admin
if ($permissions >= 128) {

	$surveyBuilder = new buildSurveyDefinition($exptId, $formType);
	$jsonOut = $surveyBuilder->GetFormJSON();

	$jsonStr = json_encode($jsonOut);
	echo $jsonStr;
}

