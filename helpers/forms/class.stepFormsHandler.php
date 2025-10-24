<?php
/**
 * Step forms controller
 * top-level controller for step forms
 * @author MartinHall
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';

class stepFormsHandler {
  private $controlItems = array();
  private $judgeTypes = [];
  private $igrtSqli;
  private $tabIndex = 1;
  private $formName;
  public $formDef;  // public during debug
  private $formType;
  private $exptId;
  private $jType;
  
// <editor-fold defaultstate="collapsed" desc=" get, save & create definition functions">

  function getEligibilityQ() {
    $qSql = sprintf("SELECT * FROM fdStepFormsEligibilityQuestions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $qResult = $this->igrtSqli->query($qSql);
    $qRow = $qResult->fetch_object();
    $qDef = [];
    if (isset($qRow)) {
	    $qDef=array(
		    'qType' => $qRow->qType,
		    'qLabel' => $qRow->qLabel,
		    'qAccordionClosed' => $qRow->qAccordionClosed,
		    'qOptionsAccordionClosed' => $qRow->qOptionsAccordionClosed,
		    'qValidationMsg' => $qRow->qValidationMsg,
		    'qContinuousSliderMax' => $qRow->qContinuousSliderMax,
		    'qOptionsAreExclusive' => $qRow->qOptionsAreExclusive,
		    'qNonEligibleMsg' => $qRow->qNonEligibleMsg,
		    'qUseJTypeSelector' => $qRow->qUseJTypeSelector,
		    'options' => array()
	    );
    }
    else {
    	$this->createDefaultEligibilityQuestion();
    	$qDef = $this->getEligibilityQ();
    }
    $qDef['options'] = $this->getEligibilityQOptions();
    return $qDef;
  }

  function getEligibilityQOptions() {
	  // get associated options (always at least one option for eligibility Q )
	  $eqOptions=[];
	  $eqoSql=sprintf("SELECT * FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId='%s' AND formType='%s' ORDER BY displayOrder ASC", $this->exptId, $this->formType);
	  $eqoResult = $this->igrtSqli->query($eqoSql);
	  while ($eqoRow = $eqoResult->fetch_object()) {
		  $eqoDef=array(
			  'id'=>$eqoRow->displayOrder,
			  'label'=>$eqoRow->label,
//			  'isEligibleResponse'=>$eqoRow->isEligibleResponse, // this is implicit in jType 0 ,1 being S1 interrogator types and 2 being ineligible
			  'jType' => $eqoRow->jType
		  );
		  array_push($eqOptions, $eqoDef);
	  }
	  if (count($eqOptions) == 0) {
	  	$this->createDefaultEligibilityQuestionOptions();
	  	$eqOptions = $this->getEligibilityQOptions();
	  }
		return $eqOptions;
  }

  function getFormPageQuestions($pNo) {
    $qList = [];
    $qSql = sprintf("SELECT * FROM fdStepFormsQuestions WHERE exptId='%s' AND formType='%s' "
                  . "AND pNo='%s' ORDER BY qNo ASC", $this->exptId, $this->formType, $pNo);
    $qResult = $this->igrtSqli->query($qSql);
    while ($qRow = $qResult->fetch_object()) {
      $qDef=array(
        'pNo' => $pNo,
        'qNo' => $qRow->qNo,
        'qType' => $qRow->qType,
        'qLabel' => $qRow->qLabel,
        'qAccordionClosed' => $qRow->qAccordionClosed,
        'optionsAccordionClosed' => $qRow->optionsAccordionClosed,
        'qContingentValue' => $qRow->qContingentValue,
        'qContingentText' => $this->formDef['eligibilityQ']['options'][$qRow->qContingentValue]['label'],
        'qValidationMsg' => $qRow->qValidationMsg,
        'qContinuousSliderMax' => $qRow->qContinuousSliderMax,
        'qMandatory' => $qRow->qMandatory,
        'qGridTarget' => $qRow->qGridTarget,
        'qGridInstruction' => $qRow->qGridInstruction,
        'gridColumns' => array(),
        'gridRows' => array(),
        'options' => array()
      );
      // get associated options (always at least one option even for no-option qTypes )
      $cbPairs=array();
      $sqlCb=sprintf("SELECT * FROM fdStepFormsQuestionsOptions WHERE exptId='%s' AND formType='%s' AND qNo='%s' "
                . "AND pNo='%s' ORDER BY displayOrder ASC", $this->exptId, $this->formType, $qDef['qNo'], $pNo);
      $cbResult = $this->igrtSqli->query($sqlCb);
      while ($cbRow = $cbResult->fetch_object()) {
        $cbPairDef=array('id'=>$cbRow->displayOrder, 'label'=>$cbRow->label);
        array_push($cbPairs, $cbPairDef);
      }
      $qDef['options'] = $cbPairs;
      // get grid options (or default 5 col and 3 row in case grid selected later)
      $gridColItems = array();
      $gridSql = sprintf("SELECT * FROM fdStepFormsGridValues WHERE exptId='%s' AND formType='%s' AND qNo='%s' "
                . "AND pNo='%s' AND isRowLabel=0 ORDER BY colValue ASC", $this->exptId, $this->formType, $qDef['qNo'], $pNo);
      $gridResult = $this->igrtSqli->query($gridSql);
      if ($this->igrtSqli->affected_rows > 0) {
        while ($gridRow = $gridResult->fetch_object()) {
          $gridItems = array('colValue' => $gridRow->colValue, 'label' => $gridRow->label );
          array_push($gridColItems, $gridItems);
        }        
      }
      else {
        for ($i=0; $i<6; $i++) {
          $gridItems = array('colValue' => $i, 'label' => "$i" );
          array_push($gridColItems, $gridItems);          
        }
      }
      $qDef['gridColumns'] = $gridColItems;
      
      $gridRowItems = array();
      $gridSql = sprintf("SELECT * FROM fdStepFormsGridValues WHERE exptId='%s' AND formType='%s' AND qNo='%s' "
                . "AND pNo='%s' AND isRowLabel=1 ORDER BY rowNo ASC", $this->exptId, $this->formType, $qDef['qNo'], $pNo);
      $gridResult = $this->igrtSqli->query($gridSql);
      if ($this->igrtSqli->affected_rows > 0) {
        while ($gridRow = $gridResult->fetch_object()) {
          $gridItems = array('rowNo' => $gridRow->rowNo, 'label' => $gridRow->label );
          array_push($gridRowItems, $gridItems);
        }        
      }
      else {
        for ($i=0; $i<3; $i++) {
          $gridItems = array('rowNo' => $i, 'label' => "$i" );
          array_push($gridRowItems, $gridItems);          
        }
      }
      $qDef['gridRows'] = $gridRowItems;
      array_push($qList, $qDef);
    }
    if (count($qList) == 0) {
    	$this->createDefaultPageQuestion();
    	$qList = $this->getFormPageQuestions($pNo);
    }
    return $qList;
  }

  function getFormPageFilterQuestionOptions($pNo) {
  	$fqOptions = [];
  	$fqoSql = sprintf("SELECT * FROM fdStepFormsPageFilterQuestionsOptions WHERE exptId='%s' AND formType='%s' AND pNo='%s'", $this->exptId, $this->formType, $pNo);
  	$fqoResult = $this->igrtSqli->query($fqoSql);
  	while ($fqoRow = $fqoResult->fetch_object()) {
  		$fqOptionDef = array('responseMapping'=>$fqoRow->responseMapping, 'fqoLabel'=>$fqoRow->fqoLabel);
  		array_push($fqOptions, $fqOptionDef);
	  }
	  if (count($fqOptions) == 0) {
	  	$this->createDefaultFilterQuestionOptions($pNo);
	  	$fqOptions = $this->getFormPageFilterQuestionOptions($pNo);
	  }
  	return $fqOptions;
  }

  function getFormPageFilterQuestion($pNo) {
    $fqSql = sprintf("SELECT * FROM fdStepFormsPageFilterQuestions WHERE exptId='%s' AND formType='%s' AND pNo='%s'", $this->exptId, $this->formType, $pNo);
    $fqResult = $this->igrtSqli->query($fqSql);
    $fqRow = $fqResult->fetch_object();
    if (is_null($fqRow)) {
    	$this->createDefaultFilterQuestion($pNo);
	    $fqDef = $this->getFormPageFilterQuestion($pNo);
    }
    else {
    	$fqDef = [
    		'fqType' => $fqRow->fqType,
		    'fqLabel' => $fqRow->fqLabel,
		    'fqOptionsAccordionClosed' => $fqRow->fqOptionsAccordionClosed,
		    'fqAccordionClosed' => $fqRow->fqAccordionClosed,
		    'fqOptions' => $this->getFormPageFilterQuestionOptions($pNo)
	    ];
    }
    return $fqDef;
  }
    
  function getFormPageDefinitions() {
    $pageDefList = array();
    $pageSql = sprintf("SELECT * FROM fdStepFormsPages WHERE exptId='%s' AND formType='%s' ORDER BY pNo ASC", $this->exptId, $this->formType);
    $pageResult = $this->igrtSqli->query($pageSql);
    while ($pageRow = $pageResult->fetch_object()) {
      $pNo = $pageRow->pNo;
      $pageDef = array(
        'pNo' => $pNo,
        'pageTitle' => $pageRow->pageTitle,
        'pageInst' => $pageRow->pageInst,
        'pageButtonLabel' => $pageRow->pageButtonLabel,
        'contingentPage' => $pageRow->contingentPage,
        'useFilter' => $pageRow->useFilter,
        'pageAccordionClosed' => $pageRow->pageAccordionClosed,
        'contingentValue' => $pageRow->contingentValue,
        'contingentText' => $pageRow->contingentText,
        'ignorePage' => $pageRow->ignorePage,
        //'q0isFilter' => $pageRow->useFilter,
        'jType' => $pageRow->jType, // jType is mainly used if form does not have eligibilityQ (post forms especially, as jType is selected from pre-form which does have )
	      //'filterQuestion' => $this->getFormPageFilterQuestion($pNo),
        'questions' => $this->getFormPageQuestions($pNo)
      );
      array_push($pageDefList, $pageDef);
    }
    if (count($pageDefList) == 0) {
			$this->createDefaultPage();
			$pageDefList = $this->getFormPageDefinitions();
    }
    return $pageDefList;
  }
  
  function getActivePageCount($jType, $pages) {
  	$cnt = 0;
  	foreach ($pages as $page) {
  		if ($page['contingentPage'] == 0) {
  			++$cnt;
		  }
  		else {
  			if ($page['contingentValue'] == $jType) {
  				++$cnt;
  			}
		  }
	  }
  	return $cnt;
  }
  
  function getForm() {
    $form = array();
    // check a definition is in  for this expt and type, create if not
    $sql=sprintf("SELECT * FROM fdStepForms WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $result=$this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      $row=$result->fetch_object();
      $form['judgeTypeOptions'] = $this->judgeTypes;
      $form['exptId'] = $this->exptId;
      $form['formType'] = $this->formType;
      $form['useEligibilityQ'] = $row->useEligibilityQ;
      $form['currentFocusControlId'] = $row->currentFocusControlId;
      $form['formTitle'] = $row->formTitle;
      $form['formInst'] = $row->formInst;  
      $form['useIntroPage'] = $row->useIntroPage;
      $form['introPageTitle'] = $row->introPageTitle;
      $form['introPageMessage'] = $row->introPageMessage;
      $form['introPageButtonLabel'] = $row->introPageButtonLabel;
      $form['finalMsg'] = $row->finalMsg;
      $form['finalButtonLabel'] = $row->finalButtonLabel;
      //$form['introAccordionClosed'] = $row->introAccordionClosed;
	    $form['finalAccordionClosed'] = $row->finalAccordionClosed;
	    $form['recruitmentAccordionClosed'] = $row->recruitmentAccordionClosed;
      $form['startPageAccordionClosed'] = $row->startPageAccordionClosed;
	    $form['recruitmentAccordionClosed'] = $row->recruitmentAccordionClosed;
	    $form['startPageAccordionClosed'] = $row->startPageAccordionClosed;
	    $form['pagesAccordionClosed'] = $row->pagesAccordionClosed;
      $form['definitionComplete'] = $row->definitionComplete;
      $form['useRecruitmentCode'] = $row->useRecruitmentCode;
      $form['allowNullRecruitmentCode'] = $row->allowNullRecruitmentCode;
      $form['recruitmentCodeLabel'] = $row->recruitmentCodeLabel;
      $form['recruitmentCodeMessage'] = $row->recruitmentCodeMessage;
      $form['recruitmentCodeYesLabel'] = $row->recruitmentCodeOptionLabel;
      $form['recruitmentCodeNoLabel'] = $row->nullRecruitmentCodeOptionLabel;
      $form['eligibilityQ'] = $this->getEligibilityQ();       
      $form['registrationViews'] = $this->getFormPageDefinitions();
      $form['cntActivePages'] = [];
	    array_push($form['cntActivePages'], $this->getActivePageCount("0", $form['registrationViews']));
	    array_push($form['cntActivePages'], $this->getActivePageCount("1", $form['registrationViews']));
 	    $form['useFinalPage'] = $row->useFinalPage;
      return $form;
    }
    else {
      return $this->makeDefaultForm();
    }
  } 
       
  function saveForm() {
    // wipe out existing form 
    $cleanForm = "DELETE FROM fdStepForms WHERE exptId=$this->exptId AND formType=$this->formType";      
    $this->igrtSqli->query($cleanForm);
    $cleanPages = "DELETE FROM fdStepFormsPages WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanPages);
    $cleanQuestions = "DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanQuestions);
    $cleanOptions = "DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    $cleanQuestions = "DELETE FROM fdStepFormsQuestions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanQuestions);
    $cleanOptions = "DELETE FROM fdStepFormsQuestionsOptions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    $cleanOptions = "DELETE FROM fdStepFormsGridValues WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    
    // insert
    $makeForm = sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, introAccordionClosed, "
      . "finalAccordionClosed, useIntroPage, introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ, currentFocusControlId, "
        . "useRecruitmentCode, allowNullRecruitmentCode, recruitmentCodeLabel, recruitmentCodeMessage, recruitmentCodeOptionLabel, nullRecruitmentCodeOptionLabel ) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", 
      $this->exptId, $this->formType, $this->formDef['formTitle'], $this->formDef['formInst'], $this->formDef['finalMsg'], 
      $this->formDef['finalButtonLabel'], $this->formDef['introAccordionClosed'], $this->formDef['finalAccordionClosed'],
      $this->formDef['useIntroPage'], $this->formDef['introPageTitle'], $this->formDef['introPageMessage'], 
      $this->formDef['introPageButtonLabel'], $this->formDef['useEligibilityQ'], $this->formDef['currentFocusControlId'],   
      $this->formDef['useRecruitmentCode'], $this->formDef['allowNullRecruitmentCode'], $this->formDef['recruitmentCodeLabel'],   
      $this->formDef['recruitmentCodeMessage'], $this->formDef['recruitmentCodeOptionLabel'], $this->formDef['nullRecruitmentCodeOptionLabel']);   
    $this->igrtSqli->query($makeForm);
    // store eligibility question 
    $makeEligibilityQuestion = sprintf("INSERT INTO fdStepFormsEligibilityQuestions (exptId, formType, qType, qLabel, qAccordionClosed, "
        . "qValidationMsg, qContinuousSliderMax, qOptionsAreExclusive, qNonEligibleMsg, qUseJTypeSelector) "
      . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $this->exptId, $this->formType, $this->formDef['eligibilityQ']['qType'], $this->formDef['eligibilityQ']['qLabel'], 
      $this->formDef['eligibilityQ']['qAccordionClosed'], $this->formDef['eligibilityQ']['qValidationMsg'], $this->formDef['eligibilityQ']['qContinuousSliderMax'],
      $this->formDef['eligibilityQ']['qOptionsAreExclusive'], $this->formDef['eligibilityQ']['qNonEligibleMsg'], $this->formDef['eligibilityQ']['qUseJTypeSelector']);
    $this->igrtSqli->query($makeEligibilityQuestion);
    // store options for eligibility
    foreach ($this->formDef['eligibilityQ']['options'] as $qOption) {
      $makeEligibilityOption = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType)"
        . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
        $this->exptId, $this->formType, $qOption['label'], $qOption['id'], $qOption['isEligibleResponse'], $qOption['jType']);
      $this->igrtSqli->query($makeEligibilityOption);
    }
    // now do each page
    for ($pageNo=0; $pageNo<count($this->formDef['registrationViews']); $pageNo++) {
      $pageDef = $this->formDef['registrationViews'][$pageNo];
      $makePage = sprintf("INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, "
        . "contingentPage, pageAccordionClosed, contingentValue, contingentText, ignorePage, q0isFilter, jType) "
        . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ,'%s', '%s', '%s', '%s', '%s')",
        $this->exptId, $this->formType, $pageNo, $pageDef['pageTitle'], $pageDef['pageInst'], $pageDef['pageButtonLabel'], 
        $pageDef['contingentPage'], $pageDef['pageAccordionClosed'], $pageDef['contingentValue'],
        $pageDef['contingentText'], $pageDef['ignorePage'], $pageDef['q0isFilter'], $pageDef['jType']);
      $this->igrtSqli->query($makePage);     
      // do each question
      for ($qNo=0; $qNo<count($pageDef['questions']); $qNo++) {
        $qDef = $pageDef['questions'][$qNo];
        $makeQuestion = sprintf("INSERT INTO fdStepFormsQuestions (exptId, formType, pNo, qNo, qType, qLabel, "
            . "qContingentValue, qAccordionClosed, qValidationMsg, qContinuousSliderMax, qMandatory, qGridTarget, qGridInstruction) "
          . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $this->exptId, $this->formType, $pageNo, $qNo, $qDef['qType'], $qDef['qLabel'], 
          $qDef['qContingentValue'], $qDef['qAccordionClosed'], $qDef['qValidationMsg'], $qDef['qContinuousSliderMax'],
          $qDef['qMandatory'], $qDef['qGridTarget'], $qDef['qGridInstruction']);
        $this->igrtSqli->query($makeQuestion);
        //do each option for this question
        for ($optionNo=0; $optionNo<count($qDef['options']); $optionNo++) {
          $qOption = $qDef['options'][$optionNo];
          $makeOption = sprintf("INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
            $this->exptId, $this->formType, $pageNo, $qNo, $qOption['label'], $qOption['id']);
          $this->igrtSqli->query($makeOption);          
        }
        for ($i = 0; $i<count($qDef['gridColumns']); $i++) {
          $gridSet = $qDef['gridColumns'][$i];
          $makeGridSet = sprintf("INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, colValue, isRowLabel)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '0')",
            $this->exptId, $this->formType, $pageNo, $qNo, $gridSet['label'], $gridSet['colValue']);
          $this->igrtSqli->query($makeGridSet);          
        }
        for ($i = 0; $i<count($qDef['gridRows']); $i++) {
          $gridSet = $qDef['gridRows'][$i];
          $makeGridSet = sprintf("INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, rowNo, isRowLabel)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '1')",
            $this->exptId, $this->formType, $pageNo, $qNo, $gridSet['label'], $i);
          $this->igrtSqli->query($makeGridSet);          
        }
      }  
    }
  }

  function makeDefaultForm() {
    $cleanPages = sprintf("DELETE FROM fdStepFormsPages WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanPages);
    //echo $cleanPages;
    $cleanEligibilityQuestions = sprintf("DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanEligibilityQuestions);
    $cleanQuestions = sprintf("DELETE FROM fdStepFormsQuestions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanQuestions);
    $cleanOptions = sprintf("DELETE FROM fdStepFormsQuestionsOptions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanOptions);
    $cleanEligibilityOptions = sprintf("DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanEligibilityOptions);
    // make basic form with minimum registrationViews, questions and options
    $sqlMake = sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, introAccordionClosed, finalAccordionClosed, "
      . "useIntroPage, introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", 
      $this->exptId, $this->formType, "insert title here", "insert instructions here", "insert final message here", "done", "0", "0",
      "0", "insert intro page title here", "insert intro page message here", "next", "0");
    $this->igrtSqli->query($sqlMake);
    // create a default initial page 
//    $createInitialPage = sprintf("INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, contingentPage, pageAccordionClosed, contingentValue, contingentText, q0isFilter, ignorePage)
//      VALUES('%s', '%s', '0', 'new page title', 'new page instruction', 'next', '0', '0', '-1', '', '1', '0')", $this->exptId, $this->formType);
//      $this->igrtSqli->query($createInitialPage);
    //echo $createInitialPage;
	  $this->createDefaultPage();
    $this->createDefaultFilterQuestion(0);
    $this->createDefaultEligibilityQuestion();
    return $this->getForm();    
  }

  function createDefaultPageQuestion() {
	  $insertQuestion = sprintf("INSERT INTO fdStepFormsQuestions (exptId, formType, pNo, qNo, qType, qLabel, qAccordionClosed, qValidationMsg, qContingentValue, qContinuousSliderMax, qMandatory, qGridTarget, qGridInstruction, optionsAccordionClosed) 
			VALUES ('%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
		  $this->exptId, $this->formType, 0, 0, 3, 'question label', 1, 'this question must be answered', 0, 100, 0, 0, 'grid instruction', 1);
	  $this->igrtSqli->query($insertQuestion);
	  $insertOption = sprintf("INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder) VALUES ('%s','%s','%s','%s','%s','%s')",
		  $this->exptId, $this->formType, 0, 0, 'first option', 0);
	  $this->igrtSqli->query($insertOption);
  }

  function createDefaultPage() {
		$insertPage = sprintf("INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, contingentPage, useFilter, pageAccordionClosed, contingentValue, contingentText, ignorePage, jType) 
			VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$this->exptId, $this->formType, 0, 'page title', 'page instruction', 'button label', 0, 0, 1, 0, '', 0, 0);
		$this->igrtSqli->query($insertPage);
  }

  function createDefaultEligibilityQuestion() {
    $insertSql = sprintf("INSERT INTO fdStepFormsEligibilityQuestions (exptId, formType, qType, qLabel, qValidationMsg, qOptionsAreExclusive, qNonEligibleMsg) "
      . "VALUES ('%s', '%s', '5', 'eligibility question instruction', 'eligibility validation message', '0', 'non-eligible message')",
      $this->exptId, $this->formType);
    $this->igrtSqli->query($insertSql);  
  }

  function createDefaultEligibilityQuestionOptions() {
	  $makeOption = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType)"
		  . "VALUES ('%s', '%s', '%s', '%s', '%s', '0')",
		  $this->exptId, $this->formType, 'first eligibility option', 0, 0);
	  $this->igrtSqli->query($makeOption);
  }
 
  function createDefaultFilterQuestion($pNo) {
    $insertSql = sprintf("INSERT INTO fdStepFormsPageFilterQuestions (exptId, formType, pNo, fqType, fqLabel, fqOptionsAccordionClosed, filterMapping) "
      . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $this->exptId, $this->formType, $pNo, 5, 'question instruction',  1, 0);
    $this->igrtSqli->query($insertSql);
  }

  function createDefaultFilterQuestionOptions($pNo) {
	  $makeOption = sprintf("INSERT INTO fdStepFormsPageFilterQuestionsOptions (exptId, formType, pNo, fqoLabel, responseMapping) "
		  . "VALUES ('%s', '%s', '%s', '%s', '%s')",
		  $this->exptId, $this->formType, $pNo, 'first option', 0);
	  $this->igrtSqli->query($makeOption);
  }

// </editor-fold>
  
// <editor-fold defaultstate="collapsed" desc=" helpers and debug">

  function getFormType() {
    $getTypeSql = sprintf("SELECT * FROM fdStepFormsNames WHERE formName='%s'", $this->formName);
    $getTypeResult = $this->igrtSqli->query($getTypeSql);
    if ($this->igrtSqli->affected_rows > 0) {
      $getTypeRow = $getTypeResult->fetch_object();
      return $getTypeRow->formType;      
    }
    else {
      return -1;
    }      
  }
  
  function getFormName() {
    $getTypeSql = sprintf("SELECT * FROM fdStepFormsNames WHERE formType='%s'", $this->formType);
//    return $getTypeSql;
    $getTypeResult = $this->igrtSqli->query($getTypeSql);
    if ($this->igrtSqli->affected_rows > 0) {
      $getTypeRow = $getTypeResult->fetch_object();
      return $getTypeRow->formName;      
    }
    else {
      return 'unset';
    }          
  }
  
  function getControlItems() {
    // get all control values  
    $controlQry = "SELECT * FROM igControlTypes";
    $controlResults = $this->igrtSqli->query($controlQry);
    if ($this->igrtSqli->affected_rows > 0) {
      while ($row = $controlResults->fetch_object()) {
        $controlDetail = array(
          'id' => $row->cValue,
          'label' => $row->cLabel
        );
        array_push($this->controlItems, $controlDetail);
      }
    }
  }

  function getJudgeTypes() {
		$eModel = new experimentModel($this->exptId);
	  array_push($this->judgeTypes, ['id' => -1, 'label' => 'no contingency required']);
	  array_push($this->judgeTypes, ['id' => 0, 'label' => $eModel->evenS1Label]);
	  array_push($this->judgeTypes, ['id' => 1, 'label' => $eModel->oddS1Label]);
  }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" constructor">
  
  public function setJType($jType) {
  	$this->jType = $jType;
  }

  function __construct($exptId = null, $formType = null) {
    if (isset($GLOBALS['exptId'])) { $this->exptId = $GLOBALS['exptId']; }
    if (isset($GLOBALS['formName'])) { $this->formName = $GLOBALS['formName']; }
    if (isset($GLOBALS['igrtSqli'])) { $this->igrtSqli = $GLOBALS['igrtSqli']; }

	  $this->exptId = isset($GLOBALS['exptId']) ? $GLOBALS['exptId'] : $exptId;
	  $this->formType = isset($GLOBALS['formType']) ? $GLOBALS['formType'] : $formType;
	  $this->formName = isset($GLOBALS['formName']) ? $GLOBALS['formName'] : $this->getFormName();
	  $this->igrtSqli = $GLOBALS['igrtSqli'];
    $this->tabIndex = 1;   //
    //$this->formType = $this->getFormType();
    $this->getControlItems();
    $this->getJudgeTypes();
  }


// </editor-fold>

}

