<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/domainSpecific/mySqlObject.php');
$permissions = $_GET['permissions'];
$uid = $_GET['uid'];
$controlName = $_GET['controlName'];
$textValue = $_GET['textValue'];
$exptId = $_GET['exptId'];
$formType = $_GET['formType'];

  function processMessage($exptId, $formType, $controlName, $textValue) {
    global $igrtSqli;
    $idDetails = explode('_', $controlName);
    switch ($idDetails[0]) {
      case 'ftTA' : {
        $sql = sprintf("UPDATE fdStepForms SET formTitle='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'fiTA' : {
        $sql = sprintf("UPDATE fdStepForms SET formInst='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'fmTA' : {
        $sql = sprintf("UPDATE fdStepForms SET finalMsg='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'recTA' : {
        $sql = sprintf("UPDATE fdStepForms SET recruitmentCodeMessage='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'recCodeTA' : {
        $sql = sprintf("UPDATE fdStepForms SET recruitmentCodeLabel='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'recNoTA' : {
        $sql = sprintf("UPDATE fdStepForms SET nullRecruitmentCodeOptionLabel='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'recYesTA' : {
        $sql = sprintf("UPDATE fdStepForms SET recruitmentCodeOptionLabel='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'eqTA' : {
        $sql = sprintf("UPDATE fdStepFormsEligibilityQuestions SET qLabel='%s' "
            . "WHERE exptId='%s' AND formType='%s'", 
            $textValue, $exptId, $formType);
        break;
      }
      case 'eqOptions' : {
        $optionNo = $idDetails[2];
        $sql = sprintf("UPDATE fdStepFormsEligibilityQuestionsOptions SET label='%s' "
            . "WHERE exptId='%s' AND formType='%s' AND displayOrder='%s'", 
            $textValue, $exptId, $formType, $optionNo);
        break;
      }
      case 'pageTitleTA' : {
        $pNo = $idDetails[1];
        $sql = sprintf("UPDATE fdStepFormsPages SET pageTitle='%s' "
            . "WHERE exptId='%s' AND formType='%s' AND pNo='%s'", 
            $textValue, $exptId, $formType, $pNo);
        break;
      }
      case 'pageInstTA' : {
        $pNo = $idDetails[1];
        $sql = sprintf("UPDATE fdStepFormsPages SET pageInst='%s' "
            . "WHERE exptId='%s' AND formType='%s' AND pNo='%s'", 
            $textValue, $exptId, $formType, $pNo);
        break;
      }
      case 'qTA' : {
        $pNo = $idDetails[1];
        $qNo= $idDetails[2];
        $sql = sprintf("UPDATE fdStepFormsQuestions SET qLabel='%s' "
            . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s'", 
            $textValue, $exptId, $formType, $pNo, $qNo);
        break;
      }
      case 'oTA' : {
        $pNo = $idDetails[1];
        $qNo = $idDetails[2];
        $oNo = $idDetails[3];
        $sql = sprintf("UPDATE fdStepFormsQuestionsOptions SET label='%s' "
            . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s' AND displayOrder='%s'", 
            $textValue, $exptId, $formType, $pNo, $qNo, $oNo);
        break;
      }
    }
    echo $sql;
    $igrtSqli->query($sql);
  }

if ($permissions >= 128) {
  $decodedValue = urldecode($textValue);
  $dbValue = $igrtSqli->real_escape_string($decodedValue);
  processMessage($exptId, $formType, $controlName, $dbValue);
}
