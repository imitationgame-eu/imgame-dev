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

  function delEqOption($exptId, $formType, $optionNo) {
    global $igrtSqli;
    $delQry = sprintf("DELETE FROM fdStepFormsEligibilityQuestionsOptions "
          . "WHERE exptId='%s' AND formType='%s' AND displayOrder='%s'",
        $exptId, $formType, $optionNo);
    $igrtSqli->query($delQry);
  }

  function addEqOption($exptId, $formType, $optionNo) {
    global $igrtSqli;
    $qry = sprintf("SELECT * FROM fdStepFormsEligibilityQuestionsOptions "
        . "WHERE exptId='%s' AND formType='%s' ORDER BY displayOrder ASC",
        $exptId, $formType);
    $result = $igrtSqli->query($qry);
    $optionList = [];
    $newOptionList = [];
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($optionList, ['label'=>$row->label, 'isEligibleResponse'=>$row->isEligibleResponse, 'jType'=>$row->jType]);
      }
      $startArray = array_slice($optionList, 0, $optionNo+1);
      $endArray = array_slice($optionList, $optionNo+1);
      for ($i=0; $i<count($startArray); $i++) {
        array_push($newOptionList, $startArray[$i]);
      }
      array_push($newOptionList, ['label'=>'new option', 'isEligibleResponse'=>0, 'jType'=>2]);
      for ($i=0; $i<count($endArray); $i++) {
        array_push($newOptionList, $endArray[$i]);
      }
      $clear = sprintf("DELETE FROM fdStepFormsEligibilityQuestionsOptions "
          . "WHERE exptId='%s' AND formType='%s'",
          $exptId, $formType);
      $igrtSqli->query($clear);
      for ($i=0; $i<count($newOptionList); $i++) {
        $insert = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions "
          . "SET exptId='%s', formType='%s', label='%s', displayOrder='%s', isEligibleResponse='%s', jType='%s'",
          $exptId, $formType, $igrtSqli->real_escape_string($newOptionList[$i]['label']), $i, $newOptionList[$i]['isEligibleResponse'], $newOptionList[$i]['jType'] );
        $igrtSqli->query($insert);
      }
    }
    else {
      $insert = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions "
          . "SET exptId='%s', formType='%s', label='new option', displayOrder=0, isEligibleResponse=0, jType=2",
          $exptId, $formType);
      $igrtSqli->query($insert);
    }
  }
  
  function addQuestionOption($exptId, $formType, $pNo, $qNo, $oNo) {
    global $igrtSqli;
    $qry = sprintf("SELECT * FROM fdStepFormsQuestionsOptions "
        . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s' ORDER BY displayOrder ASC",
        $exptId, $formType, $pNo, $qNo);
    $result = $igrtSqli->query($qry);
    $optionList = [];
    $newOptionList = [];
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($optionList, ['label'=>$row->label]);
      }
      $startArray = array_slice($optionList, 0, $oNo+1);
      $endArray = array_slice($optionList, $oNo+1);
      for ($i=0; $i<count($startArray); $i++) {
        array_push($newOptionList, $startArray[$i]);
      }
      array_push($newOptionList, ['label'=>'new option']);
      for ($i=0; $i<count($endArray); $i++) {
        array_push($newOptionList, $endArray[$i]);
      }
      $clear = sprintf("DELETE FROM fdStepFormsQuestionsOptions "
          . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s' ",
          $exptId, $formType, $pNo, $qNo);
      $igrtSqli->query($clear);
      for ($i=0; $i<count($newOptionList); $i++) {
        $insert = sprintf("INSERT INTO fdStepFormsQuestionsOptions "
          . "SET exptId='%s', formType='%s', label='%s', pNo='%s', qNo='%s', displayOrder='%s'",
          $exptId, $formType, $igrtSqli->real_escape_string($newOptionList[$i]['label']), $pNo, $qNo, $i);
        $igrtSqli->query($insert);
        echo $insert;
      }
    }
    else {
      $insert = sprintf("INSERT INTO fdStepFormsQuestionsOptions "
          . "SET exptId='%s', formType='%s',  label='new option', pNo='%s', qNo='%s', displayOrder=0 ",
          $exptId, $formType, $pNo, $qNo);
      $igrtSqli->query($insert);
    }    
  }
    
  function processOption($exptId, $formType, $pNo, $qNo, $op, $oNo) {
    global $igrtSqli;
    if ($op == "del") {
      $del = sprintf("DELETE FROM fdStepFormsQuestionsOptions "
          . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s' AND displayOrder='%s'",
          $exptId, $formType, $pNo, $qNo, $oNo);
      $igrtSqli->query($del);
    }
    else {
      addQuestionOption($exptId, $formType, $pNo, $qNo, $oNo);
    }
  }

  function addQuestion($exptId, $formType, $pNo, $qNo) {
    global $igrtSqli;
    $qry = sprintf("SELECT * FROM fdStepFormsQuestions "
        . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' ORDER BY qNo ASC",
        $exptId, $formType, $pNo);
    $result = $igrtSqli->query($qry);
    $questionList = [];
    $newQuestionList = [];
    if ($result) {
      while ($row = $result->fetch_object()) {
        array_push($questionList, ['label'=>$row->qLabel, 'qMandatory'=>$row->qMandatory, 'qAccordion'=>$row->qAccordion, 'qType'=>$row->qType]);
      }
      $startArray = array_slice($questionList, 0, $qNo+1);
      $endArray = array_slice($questionList, $qNo+1);
      for ($i=0; $i<count($startArray); $i++) {
        array_push($newQuestionList, $startArray[$i]);
      }
      array_push($newQuestionList, ['label'=>'new question', 'qMandatory'=>0, 'qAccordion'=>1, 'qType'=>1]);
      for ($i=0; $i<count($endArray); $i++) {
        array_push($newQuestionList, $endArray[$i]);
      }
      $clear = sprintf("DELETE FROM fdStepFormsQuestions "
          . "WHERE exptId='%s' AND formType='%s' AND pNo='%s'",
          $exptId, $formType, $pNo);
      $igrtSqli->query($clear);
      for ($i=0; $i<count($newQuestionList); $i++) {
        $insert = sprintf("INSERT INTO fdStepFormsQuestions "
          . "SET exptId='%s', formType='%s', qLabel='%s', qMandatory='%s', qAccordion='%s', qType='%s', pNo='%s', qNo='%s'",
          $exptId, $formType, $igrtSqli->real_escape_string($newQuestionList[$i]['label']), $newQuestionList[$i]['qMandatory'], $newQuestionList[$i]['qAccordion'], $newQuestionList[$i]['qType'], $pNo, $i);
        $igrtSqli->query($insert);
      }
    }
    else {
      $insert = sprintf("INSERT INTO fdStepFormsQuestions "
          . "SET exptId='%s', formType='%s', qLabel='new question', qType='1', pNo='%s', qNo=0",
          $exptId, $formType, $pNo);
      $igrtSqli->query($insert);
    }    
  }
    
  function processQuestion($exptId, $formType, $pNo, $op, $qNo) {
    global $igrtSqli;
    if ($op == "del") {
      $del = sprintf("DELETE FROM fdStepFormsQuestions "
          . "WHERE exptId='%s' AND formType='%s' AND pNo='%s' AND qNo='%s'",
          $exptId, $formType, $pNo, $qNo);
      $igrtSqli->query($del);
    }
    else {
      addQuestion($exptId, $formType, $pNo, $qNo);
    }
  }

  function processMessage($exptId, $formType, $controlName) {
    $idDetails = explode('_', $controlName);
    switch ($idDetails[0]) {
      case 'eqOptions' : {
        if ($idDetails[1] == 'add') {
          addEqOption($exptId, $formType, $idDetails[2]);
        }
        else {
          delEqOption($exptId, $formType, $idDetails[2]);
        }
        break;
      }
      case 'oB' : {
        processOption($exptId, $formType, $idDetails[1], $idDetails[2], $idDetails[3], $idDetails[4]);  // pNo, qNo, op text, oNo
        break;
      }
      case 'qB' : {
        processQuestion($exptId, $formType, $idDetails[1], $idDetails[2], $idDetails[3]);  // pNo, op text, qNo,
        break;
      }
    }
  }

if ($permissions >= 128) {
  processMessage($exptId, $formType, $controlName);
}
