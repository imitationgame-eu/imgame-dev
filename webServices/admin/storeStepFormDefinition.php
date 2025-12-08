<?php
  // ---- functions
    function getTypeFromText($text) {
      switch ($text) {
        case 'checkbox' : { return 0; break; }
        case 'single-line edit' : { return 1; break; }
        case 'multi-line edit' : { return 2; break; }
        case 'email' : { return 3; break; }
        case 'datetime' : { return 4; break; }
        case 'radiobutton' : { return 5; break; }
        case 'selector' : { return 6; break; }
        case 'slider' : { return 7; break; }
        case 'continuous slider' : { return 8; break; }
        case 'radiobuttonGrid' : { return 9; break; }
      }
    }

    function getContingentValueFromText($question0, $text) {
      for ($i = 0; $i<count($question0['options']); $i++) {
        if ($question0['options'][$i]['label'] === $text) { return $i; }        
      }          
      return -1; // possible houston, but not if no filter question on this page
    }
  // ----

ini_set('display_errors', 'On');
error_reporting(E_ALL);

$full_ws_path=realpath(dirname(__FILE__));
$root_path=substr($full_ws_path, 0, strlen($full_ws_path)-18);
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php';              

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);

$exptId = $jSonArray['exptId'];
$formType = $jSonArray['formType'];
$formName = $jSonArray['formName'];
$introAccordion = $jSonArray['introAccordion'];
$finalAccordion = $jSonArray['finalAccordion'];
$formTitle = $igrtSqli->real_escape_string($jSonArray['formTitle']);
$formInst = $igrtSqli->real_escape_string($jSonArray['formInst']);
$finalMsg = $igrtSqli->real_escape_string($jSonArray['finalMsg']);
$finalButtonLabel = $igrtSqli->real_escape_string($jSonArray['finalButtonLabel']);
$useIntroPage = $jSonArray['useIntroPage'];
$introPageMessage = $igrtSqli->real_escape_string($jSonArray['introPageMessage']);
$introPageTitle = $igrtSqli->real_escape_string($jSonArray['introPageTitle']);
$introPageButtonLabel = $igrtSqli->real_escape_string($jSonArray['introPageButtonLabel']);
$useEligibilityQ = $jSonArray['useEligibilityQ'];
$definitionComplete = $jSonArray['definitionComplete'];
$eligibilityQType = getTypeFromText($jSonArray['eligibilityQType']);
$eligibilityQAccordion = $jSonArray['eligibilityQAccordion'];
$eligibilityQLabel = $igrtSqli->real_escape_string($jSonArray['eligibilityQLabel']);
$eligibilityQValidationMsg = $igrtSqli->real_escape_string($jSonArray['eligibilityQValidationMsg']);
$eligibilityQContinuousSliderMax = $jSonArray['eligibilityQContinuousSliderMax'];
$eqoExclusive = $jSonArray['eligibilityQOptionsAreExclusive'];
$eqUseJTypeSelector = $jSonArray['eligibilityUseJTypeSelector'];
$qNonEligibleMsg = $igrtSqli->real_escape_string($jSonArray['qNonEligibleMsg']);


$useRecruitmentCode = $jSonArray['useRecruitmentCode'];
$allowNullRecruitmentCode = $igrtSqli->real_escape_string($jSonArray['allowNullRecruitmentCode']);
$recruitmentCodeLabel = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeLabel']);
$recruitmentCodeMessage = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeMessage']);
$recruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeOptionLabel']);
$nullRecruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['nullRecruitmentCodeOptionLabel']);

$eqOptions = $jSonArray['eqOptions'];
$pages = $jSonArray['pages'];
$currentFocusControlId = $jSonArray['currentFocusControlId'];

$storeForm = true;
if ($storeForm) {
  // delete all current info about the form
  $cleanForm = "DELETE FROM fdStepForms WHERE exptId=$exptId AND formType=$formType";      
  $igrtSqli->query($cleanForm);
  $cleanPages = "DELETE FROM fdStepFormsPages WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanPages);
  $cleanQuestions = "DELETE FROM fdStepFormsQuestions WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanQuestions);
  $cleanOptions = "DELETE FROM fdStepFormsQuestionsOptions WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanOptions);
  $cleanQuestions = "DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanQuestions);
  $cleanOptions = "DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanOptions);
  $cleanOptions = "DELETE FROM fdStepFormsGridValues WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanOptions);
  // insert new definition
  $uipBool = $useIntroPage === true ? 1 : 0;
  $ueqBool = $useEligibilityQ === true ? 1 : 0;
  $dcBool = $definitionComplete === true ? 1 : 0;
  $eqoExclusiveBool = $eqoExclusive === true ? 1 : 0;
  $eqUseJTypeSelectorBool = $eqUseJTypeSelector === true ? 1 : 0;
  $useRCBool = $useRecruitmentCode === true ? 1 : 0;
  $makeForm=sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, introAccordion, finalAccordion, useIntroPage, "
      . "introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ, currentFocusControlId, definitionComplete, "
      . "useRecruitmentCode, allowNullRecruitmentCode, recruitmentCodeLabel, recruitmentCodeMessage, recruitmentCodeOptionLabel, nullRecruitmentCodeOptionLabel) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", 
    $exptId, $formType, $formTitle, $formInst, $finalMsg, $finalButtonLabel, $introAccordion, $finalAccordion, 
    $uipBool, $introPageTitle, $introPageMessage, $introPageButtonLabel, $ueqBool, $currentFocusControlId, $dcBool,
    $useRCBool, $allowNullRecruitmentCode, $recruitmentCodeLabel, $recruitmentCodeMessage, $recruitmentCodeOptionLabel, $nullRecruitmentCodeOptionLabel);
  $igrtSqli->query($makeForm);
  // echo $makeForm;
  // store eligibilty question
  $makeEligibility = sprintf("INSERT INTO fdStepFormsEligibilityQuestions "
      . "(exptId, formType, qAccordion, qValidationMsg, qType, qLabel, qContinuousSliderMax, qOptionsAreExclusive, qNonEligibleMsg, qUseJTypeSelector) "
      . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $exptId, $formType, $eligibilityQAccordion, $eligibilityQValidationMsg, $eligibilityQType, 
      $eligibilityQLabel, $eligibilityQContinuousSliderMax, $eqoExclusiveBool, $qNonEligibleMsg, $eqUseJTypeSelectorBool);
  $igrtSqli->query($makeEligibility);
  //echo $makeEligibility;
  for ($oPtr=0; $oPtr<count($eqOptions); $oPtr++) {
    $ierBool = $eqOptions[$oPtr]['isEligibleResponse'] === true ? 1 : 0;
    $makeEligibilityOptions = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType) "
        . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')",
        $exptId, $formType, $igrtSqli->real_escape_string($eqOptions[$oPtr]['label']), $oPtr, $ierBool, $eqOptions[$oPtr]['jType'] == 'even' ? 0 : 1);
    $igrtSqli->query($makeEligibilityOptions);
    //echo $makeEligibilityOptions;
  }
  // build up queries with multiple VALUES statements as more efficient - avoid locking problems
  $makePage = "INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, "
    . "contingentPage, q0isFilter, pageAccordion, contingentValue, contingentText, ignorePage, jType) VALUES";
  $makeQuestion = "INSERT INTO fdStepFormsQuestions "
    . "(exptId, formType, pNo, qNo, qType, qLabel, qContingentValue, qAccordion, qValidationMsg, qContinuousSliderMax, qMandatory, qGridInstruction, qGridTarget) VALUES ";
  $makeOption = "INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder) VALUES ";
  $makeGridColumns = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, colValue) VALUES ";
  $makeGridRows = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, rowNo) VALUES ";
  for ($i=0; $i<count($pages); $i++) {
    $pageNo = $i;
    if ($i > 0) { $makePage.= ','; }
    $makePage.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $exptId, $formType, $pageNo,
      $igrtSqli->real_escape_string($pages[$i]['pageTitle']), 
      $igrtSqli->real_escape_string($pages[$i]['pageInst']), 
      $igrtSqli->real_escape_string($pages[$i]['pageButtonLabel']), 
      $pages[$i]['contingentPage'] === true ? 1 : 0, 
      $pages[$i]['q0isFilter'] === true ? 1 : 0,
      $pages[$i]['pageAccordion'],
      $pages[$i]['contingentPageValue'],
      $pages[$i]['contingentPageText'],
      $pages[$i]['ignorePage'] === true ? 1 : 0,
      $pages[$i]['jType'] === 'even' ? 0 : 1);
    //echo $makePage;
    // store questions per page
    for ($j=0; $j<count($pages[$i]['questions']); $j++) {
      if (($i>0) || ($j>0)) { $makeQuestion.= ','; }
      $makeQuestion.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
        $exptId, $formType, $pageNo, $j, 
        getTypeFromText($pages[$i]['questions'][$j]['qType']),
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qLabel']),
        isset($pages[$i]['questions'][$j]['qContingentText']) ? getContingentValueFromText($pages[$i]['questions'][0], $pages[$i]['questions'][$j]['qContingentText']) : -1,
        $pages[$i]['questions'][$j]['qAccordion'],
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qValidationMsg']),
        $pages[$i]['questions'][$j]['qContinuousSliderMax'],
        $pages[$i]['questions'][$j]['qMandatory'] === true ? 1 : 0,
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qGridInstruction']),
        $pages[$i]['questions'][$j]['qGridTarget']         
      );
      //echo $makeQuestion;
      // store options per question
      for ($k=0; $k<count($pages[$i]['questions'][$j]['options']); $k++) {
        if (($i>0) || ($j>0) || ($k>0)) { $makeOption.= ','; }
        $makeOption.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId, $formType, $pageNo, $j,
          $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['options'][$k]['label']),
          $pages[$i]['questions'][$j]['options'][$k]['id']);
      }
      for ($k=0; $k<count($pages[$i]['questions'][$j]['gridColumns']); $k++) {
        if (($i>0) || ($j>0) || ($k>0)) { $makeGridColumns.= ','; }
        $makeGridColumns.= sprintf("('%s', '%s', '%s', '%s', '%s', '0', '%s')",
          $exptId, $formType, $pageNo, $j,
          $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['gridColumns'][$k]['label']),
          $pages[$i]['questions'][$j]['gridColumns'][$k]['colValue']);        
      }
      for ($k=0; $k<count($pages[$i]['questions'][$j]['gridRows']); $k++) {
        if (($i>0) || ($j>0) || ($k>0)) { $makeGridRows.= ','; }
        $makeGridRows.= sprintf("('%s', '%s', '%s', '%s', '%s', '1', '%s')",
          $exptId, $formType, $pageNo, $j,
          $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['gridRows'][$k]['label']),
          $k);        
      }
    }
  }
  $igrtSqli->query($makePage);
  $igrtSqli->query($makeQuestion);
  $igrtSqli->query($makeOption);
  $igrtSqli->query($makeGridColumns);
  $igrtSqli->query($makeGridRows);
  echo $makePage;
  echo $makeQuestion;
  echo $makeOption;
  echo $makeGridColumns;
  echo $makeGridRows;
}