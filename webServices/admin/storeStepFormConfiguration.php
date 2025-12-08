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

global $igrtSqli;

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);

$exptId = $jSonArray['exptId'];
$formType = $jSonArray['formType'];
// = $jSonArray['formName'];
//$introAccordionClosed = $jSonArray['introAccordionClosed'];
$finalAccordionClosed = $jSonArray['finalAccordionClosed'];
$introAccordionClosed = $jSonArray['introAccordionClosed'];
$pagesAccordionClosed = $jSonArray['pagesAccordionClosed'];
$recruitmentAccordionClosed = $jSonArray['recruitmentAccordionClosed'];
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
$eligibilityQType = $jSonArray['eligibilityQ']['qType'];
$eligibilityQLabel = $igrtSqli->real_escape_string($jSonArray['eligibilityQ']['qLabel']);
$eligibilityQValidationMsg = $igrtSqli->real_escape_string($jSonArray['eligibilityQ']['qValidationMsg']);
$eligibilityQContinuousSliderMax = $jSonArray['eligibilityQ']['qContinuousSliderMax'];
$eqoExclusive = $jSonArray['eligibilityQ']['qOptionsAreExclusive'];
$eqUseJTypeSelector = $jSonArray['eligibilityQ']['qUseJTypeSelector'];
$qNonEligibleMsg = $igrtSqli->real_escape_string($jSonArray['eligibilityQ']['qNonEligibleMsg']);
$eligibilityQAccordionClosed = $jSonArray['eligibilityQ']['qAccordionClosed'];
$eqOptionsAccordionClosed = $jSonArray['eligibilityQ']['qOptionsAccordionClosed'];
$eqOptions = $jSonArray['eligibilityQ']['options'];
$useRecruitmentCode = $jSonArray['useRecruitmentCode'];
$useFinalPage = $jSonArray['useFinalPage'];
$allowNullRecruitmentCode = $igrtSqli->real_escape_string($jSonArray['allowNullRecruitmentCode']);
$recruitmentCodeLabel = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeLabel']);
$recruitmentCodeMessage = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeMessage']);
$recruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeYesLabel']);
$nullRecruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['recruitmentCodeNoLabel']);

//$eqOptions = $jSonArray['eqOptions'];
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
	$cleanQuestions = "DELETE FROM fdStepFormsPageFilterQuestions WHERE exptId=$exptId AND formType=$formType";
	$igrtSqli->query($cleanQuestions);
	$cleanOptions = "DELETE FROM fdStepFormsPageFilterQuestionsOptions WHERE exptId=$exptId AND formType=$formType";
	$igrtSqli->query($cleanOptions);
  $cleanOptions = "DELETE FROM fdStepFormsGridValues WHERE exptId=$exptId AND formType=$formType";
  $igrtSqli->query($cleanOptions);
  // insert new definition
  $makeForm=sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel,"
      . "introAccordionClosed, finalAccordionClosed, pagesAccordionClosed, recruitmentAccordionClosed,"
      . "useIntroPage, introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ, currentFocusControlId, definitionComplete, "
      . "useRecruitmentCode, allowNullRecruitmentCode, recruitmentCodeLabel, recruitmentCodeMessage, recruitmentCodeOptionLabel, nullRecruitmentCodeOptionLabel, useFinalPage) "
      . "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
    $exptId, $formType, $formTitle, $formInst, $finalMsg, $finalButtonLabel,
	  $introAccordionClosed, $finalAccordionClosed, $pagesAccordionClosed, $recruitmentAccordionClosed,
	  $useIntroPage, $introPageTitle, $introPageMessage, $introPageButtonLabel, $useEligibilityQ, $currentFocusControlId, $definitionComplete,
	  $useRecruitmentCode, $allowNullRecruitmentCode, $recruitmentCodeLabel, $recruitmentCodeMessage, $recruitmentCodeOptionLabel, $nullRecruitmentCodeOptionLabel, $useFinalPage);
  $igrtSqli->query($makeForm);
  // store eligibilty question
  $makeEligibility = sprintf("INSERT INTO fdStepFormsEligibilityQuestions "
      . "(exptId, formType, qAccordionClosed, qValidationMsg, qType, qLabel, qContinuousSliderMax, qOptionsAreExclusive, qNonEligibleMsg, qUseJTypeSelector, qOptionsAccordionClosed) "
      . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $exptId, $formType, $eligibilityQAccordionClosed, $eligibilityQValidationMsg, $eligibilityQType,
      $eligibilityQLabel, $eligibilityQContinuousSliderMax, $eqoExclusive, $qNonEligibleMsg, $eqUseJTypeSelector, $eqOptionsAccordionClosed);
  $igrtSqli->query($makeEligibility);
  //echo $makeEligibility;
  for ($oPtr=0; $oPtr<count($eqOptions); $oPtr++) {
    $makeEligibilityOptions = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, jType) "
        . "VALUES('%s', '%s', '%s', '%s', '%s')",
        $exptId, $formType, $igrtSqli->real_escape_string($eqOptions[$oPtr]['label']), $oPtr, $eqOptions[$oPtr]['jType']);
    $igrtSqli->query($makeEligibilityOptions);
    //echo $makeEligibilityOptions;
  }
  // build up queries with multiple VALUES statements as more efficient - avoid locking problems
  $makePage = "INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, "
    . "contingentPage, useFilter, pageAccordionClosed, contingentValue, contingentText, ignorePage, jType) VALUES";
	$makeQuestion = "INSERT INTO fdStepFormsQuestions "
		. "(exptId, formType, pNo, qNo, qType, qLabel, qContingentValue, qAccordionClosed, qValidationMsg, qContinuousSliderMax, qMandatory, qGridInstruction, qGridTarget, optionsAccordionClosed) VALUES ";
	$makeOption = "INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder) VALUES ";
//	$makeFilterQuestion = "INSERT INTO fdStepFormsPageFilterQuestions "
//		. "(exptId, formType, pNo, fqType, fqLabel, fqAccordionClosed, fqOptionsAccordionClosed, filterMapping) VALUES ";
//	$makeFilterOption = "INSERT INTO fdStepFormsPageFilterQuestionsOptions (exptId, formType, pNo, fqoLabel, responseMapping) VALUES ";
	$makeGridColumns = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, colValue) VALUES ";
  $makeGridRows = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, rowNo) VALUES ";
  for ($i=0; $i<count($pages); $i++) {
    $pageNo = $i;
    if ($i > 0) {
    	$makePage.= ',';
	    //$makeFilterQuestion.= ',';
    }
    $makePage.= sprintf("('%s', '%s', '%s', '%s', '%s','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $exptId, $formType, $pageNo,
      $igrtSqli->real_escape_string($pages[$i]['pageTitle']), 
      $igrtSqli->real_escape_string($pages[$i]['pageInst']), 
      $igrtSqli->real_escape_string($pages[$i]['pageButtonLabel']), 
      $pages[$i]['contingentPage'],
      $pages[$i]['useFilter'],
      $pages[$i]['pageAccordionClosed'],
      $pages[$i]['contingentValue'],
      $pages[$i]['contingentText'],
      $pages[$i]['ignorePage'],
      $pages[$i]['jType']);
     for ($j=0; $j<count($pages[$i]['questions']); $j++) {
      if (($i>0) || ($j>0)) {
      	$makeQuestion.= ',';
      }
      $makeQuestion.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
        $exptId, $formType, $pageNo, $j, 
        $pages[$i]['questions'][$j]['qType'],
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qLabel']),
        $pages[$i]['questions'][$j]['qContingentValue'],
        $pages[$i]['questions'][$j]['qAccordionClosed'],
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qValidationMsg']),
        $pages[$i]['questions'][$j]['qContinuousSliderMax'],
        $pages[$i]['questions'][$j]['qMandatory'] === "1" ? 1 : 0,
        $igrtSqli->real_escape_string($pages[$i]['questions'][$j]['qGridInstruction']),
	      $pages[$i]['questions'][$j]['qGridTarget'],
	      $pages[$i]['questions'][$j]['optionsAccordionClosed']
      );
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
//	$igrtSqli->query($makeFilterQuestion);
//	$igrtSqli->query($makeFilterOption);
  $igrtSqli->query($makeGridColumns);
  $igrtSqli->query($makeGridRows);

}

// store the one filter question for this page
//	  $makeFilterQuestion.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
//		  $exptId, $formType, $pageNo,
//		  $pages[$i]['filterQuestion']['fqType'],
//		  $igrtSqli->real_escape_string($pages[$i]['filterQuestion']['fqLabel']),
//		  $pages[$i]['filterQuestion']['fqAccordionClosed'],
//		  $pages[$i]['filterQuestion']['fqOptionsAccordionClosed'],
//		  0 // $pages[$i]['filterQuestion']['filterMapping'] - don't think filterMapping is used
//	  );
// store filter question options
//	  for ($k=0; $k<count($pages[$i]['filterQuestion']['fqOptions']); $k++) {
//		  if (($i>0) || ($k>0)) { $makeFilterOption.= ','; }
//		  $makeFilterOption.= sprintf("('%s', '%s', '%s', '%s', '%s')", $exptId, $formType, $pageNo,
//			  $igrtSqli->real_escape_string($pages[$i]['filterQuestion']['fqOptions'][$k]['fqoLabel']),
//			  $pages[$i]['filterQuestion']['fqOptions'][$k]['responseMapping']);
//	  }
// store questions per page
