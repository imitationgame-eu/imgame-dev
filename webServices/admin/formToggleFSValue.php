<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$controlName = $_GET['controlName'];
$selection = $_GET['selection'];
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];

  function processMessage($exptId, $formType, $controlName, $selection) {
    global $igrtSqli;
    $idDetails = explode('_', $controlName);
    switch ($idDetails[0]) {
      case 'dcFS' : {
        $sql = sprintf("UPDATE fdStepForms SET definitionComplete='%s' WHERE exptId='%s' AND formType='%s'", $selection, $exptId, $formType);
        break;
      }
      case 'urFS' : {
        $sql = sprintf("UPDATE fdStepForms SET useRecruitmentCode='%s' WHERE exptId='%s' AND formType='%s'", $selection, $exptId, $formType);
        break;
      }
      case 'ueFS' : {
        $sql = sprintf("UPDATE fdStepForms SET useEligibilityQ='%s' WHERE exptId='%s' AND formType='%s'", $selection, $exptId, $formType);
        break;
      }
      case 'jTypeSelectorFS' : {
        $sql = sprintf("UPDATE fdStepFormsEligibilityQuestions SET qUseJTypeSelector='%s' WHERE exptId='%s' AND formType='%s'", $selection, $exptId, $formType);
        break;
      }
    }
    $igrtSqli->query($sql);
  }

if ($permissions >= 128) {
  processMessage($exptId, $formType, $controlName, $selection);
}
