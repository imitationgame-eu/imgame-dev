<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$controlName = $_GET['controlName'];
$status = $_GET['status'];
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];

  function processMessage($exptId, $formType, $controlName, $status) {
    global $igrtSqli;
    $controlDetails = explode('_', $controlName);
    switch ($controlDetails[0]) {
      case 'spAccordion' : {
        $sql = sprintf("UPDATE fdStepForms SET startPageAccordionClosed='%s' WHERE exptId='%s' AND formType='%s'", $status, $exptId, $formType);
        break;
      }
      case 'epAccordion' : {
        $sql = sprintf("UPDATE fdStepForms SET finalAccordion='%s' WHERE exptId='%s' AND formType='%s'", $status, $exptId, $formType);
        break;
      }
      case 'eqAccordion' : {
        $sql = sprintf("UPDATE fdStepFormsEligibilityQuestions SET qAccordion='%s' WHERE exptId='%s' AND formType='%s'", $status, $exptId, $formType);
        break;
      }
      case 'eqOptions' : {
        $sql = sprintf("UPDATE fdStepForms SET eligibilityQOptionsAccordionClosed='%s' WHERE exptId='%s' AND formType='%s'", $status, $exptId, $formType);
        break;
      }
      case 'recAccordion' : {
        $sql = sprintf("UPDATE fdStepForms SET recruitmentAccordionClosed='%s' WHERE exptId='%s' AND formType='%s'", $status, $exptId, $formType);
        break;
      }
      case 'pageAccordion' : {
        $sql = sprintf("UPDATE fdStepFormsPages SET pageAccordion='%s' WHERE exptId='%s' AND formType='%s' AND pNo='%s'", $status, $exptId, $formType, $controlDetails[1]);
        break;
      }
      case 'qAccordion' : {
        $sql = sprintf("UPDATE fdStepFormsQuestions SET qAccordion='%s' WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s'", $status, $exptId, $formType, $controlDetails[1], $controlDetails[2]);
        break;
      }
      case 'optionsAccordion' : {
        $sql = sprintf("UPDATE fdStepFormsQuestions SET optionsAccordionClosed='%s' WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s'", $status, $exptId, $formType, $controlDetails[1], $controlDetails[2]);
        break;
      }
    }
    $igrtSqli->query($sql);
  }

//ensure admin
if ($permissions >= 128) {
  processMessage($exptId, $formType, $controlName, $status);
}
