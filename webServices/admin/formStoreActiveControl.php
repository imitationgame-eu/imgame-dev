<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$controlName = $_GET['controlName'];
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];


if ($permissions >= 128) {
  $sql = sprintf("UPDATE fdStepForms SET currentFocusControlId='%s' WHERE exptId='%s' AND formType='%s'", $controlName, $exptId, $formType);
  $igrtSqli->query($sql);
}
