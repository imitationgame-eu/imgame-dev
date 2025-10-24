<?php
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/parseJSON.php';
include_once $root_path.'/helpers/forms/class.stepFormsHandler.php';


class buildSurveyDefinition
{
	private $exptId;
	private $formType;
	private $formName;
	private $fieldsWithQuotes = [];

	public function __construct($exptId, $formType) {
		$this->exptId = $exptId;
		$this->formType = $formType;
		$this->formName = $this->getFormName($formType);
	}


	//<editor-fold defaultstate="collapsed" desc=" JSON and form definition functions">

	private function getFormName($formType) {
		global $igrtSqli;
		$getTypeSql = sprintf("SELECT * FROM fdStepFormsNames WHERE formType = '%s'", $formType);
		$getTypeResult = $igrtSqli->query($getTypeSql);
		$getTypeRow = $getTypeResult->fetch_object();
		return $getTypeRow->formName;
	}

	private function markFieldsWithQuotes($formDef) {
		// do single fields first
		$this->doesFieldHaveQuotes($formDef['formTitle']);
		$this->doesFieldHaveQuotes($formDef['introPageTitle']);
		$this->doesFieldHaveQuotes($formDef['introPageMessage']);
		$this->doesFieldHaveQuotes($formDef['introPageButtonLabel']);
		$this->doesFieldHaveQuotes($formDef['finalButtonLabel']);
		$this->doesFieldHaveQuotes($formDef['recruitmentCodeLabel']);
		$this->doesFieldHaveQuotes($formDef['recruitmentCodeMessage']);
		$this->doesFieldHaveQuotes($formDef['recruitmentCodeYesLabel']);
		$this->doesFieldHaveQuotes($formDef['recruitmentCodeYesLabel']);
		$this->doesFieldHaveQuotes($formDef['eligibilityQ']['qLabel']);
		$this->doesFieldHaveQuotes($formDef['eligibilityQ']['qValidationMsg']);
		$this->doesFieldHaveQuotes($formDef['eligibilityQ']['qNonEligibleMsg']);
		$this->doesFieldHaveQuotes($formDef['formInst']);
		$this->doesFieldHaveQuotes($formDef['finalMsg']);
		// then do arrays
		$this->doesArrayOptionsHaveQuotes($formDef['eligibilityQ']['options']);
		// now do dynamic list of registrationViews
		for ($i=0; $i<count($formDef['registrationViews']); $i++) {
			$this->doesFieldHaveQuotes($formDef['registrationViews'][$i]['pageTitle']);
			$this->doesFieldHaveQuotes($formDef['registrationViews'][$i]['pageButtonLabel']);
			$this->doesFieldHaveQuotes($formDef['registrationViews'][$i]['contingentText']);
			for ($j=0; $j<count($formDef['registrationViews'][$i]['questions']); $j++) {
				$this->doesFieldHaveQuotes($formDef['registrationViews'][$i]['questions'][$j]['qLabel']);
				$this->doesFieldHaveQuotes($formDef['registrationViews'][$i]['questions'][$j]['qValidationMsg']);
//				$this->doesArrayOptionsHaveQuotes($formDef['registrationViews'][$i]['questions'][$j]['gridColumns']);
//				$this->doesArrayOptionsHaveQuotes($formDef['registrationViews'][$i]['questions'][$j]['gridRows']);
				$this->doesArrayOptionsHaveQuotes($formDef['registrationViews'][$i]['questions'][$j]['options']);
			}
		}
	}

	private function removeQuotes($srcJson) {
		for ($i=0; $i<count($this->fieldsWithQuotes); $i++) {
			if ($this->fieldsWithQuotes[$i]['isSingle']) {
				$replacement = str_replace("'", ';', $this->fieldsWithQuotes[$i]['text']);
			}
			else {
				$replacement = str_replace('"', ';', $this->fieldsWithQuotes[$i]['text']);
			}
			$srcJson = str_replace($this->fieldsWithQuotes[$i]['text'], $replacement, $srcJson);
		}
		return $srcJson;
	}

	private function unescape($src) {
		$src = str_replace('\\', '', $src);
		$src = str_replace('""', '"', $src);
		return $src;
	}

	private function doesfieldHaveQuotes($target) {
		$flag = false;
		$isSingle = false;
		if (strpos("'", $target) > 0) {
			$flag = true;
			$isSingle = true;
		}
		if (strpos('"', $target) > 0) {
			$flag = true;
			$isSingle = false;
		}
		if ($flag) {
			array_push($this->fieldsWithQuotes, ['text' => $target, 'isSingle'=> $isSingle]);
		}
	}

	private function doesArrayHaveQuotes($targetArray) {
		for ($i=0; $i<count($targetArray); $i++) {
			$this->doesfieldHaveQuotes($targetArray[$i]);
		}
	}

	private function doesArrayOptionsHaveQuotes($targetArray) {
		for ($i=0; $i<count($targetArray); $i++) {
			$this->doesfieldHaveQuotes($targetArray[$i]['label']);
		}
	}


	//</editor-fold>

	//<editor-fold defaultstate="collapsed" desc=" non-JQM forms functions">

	function getChosenOption($optionList) {
		$foundOption = -1;
		$i = 0;
		while (($foundOption < 0) && ($i < count($optionList))) {
			if (!is_null($optionList[$i]['optionSelected'])) {
				$foundOption = $i;
			}
			++$i;
		}
		return $foundOption;
	}

	function getQuestionResponse($question) {
		//Kint::dump($question);
		$qLabel = '';
		for ($i=0; $i<count($question['qLabel']); $i++) {
			if ($i > 0) { $qLabel.= "\\n"; }
			$qLabel.= $question['qLabel'][$i]['para'];
		}
		$responseDef = ['qNo'=> $question['qNo'], 'qType'=>$question['qType'], 'mandatory'=>$question['qMandatory'], 'questionText'=>$qLabel, 'responses'=>[]];
		switch ($question['qType']) {
			case "radiobutton" :
			case "selector" : {
				$response = ['optionNo'=>-1, 'optionText'=>''];
				for ($i=0; $i<count($question['options']); $i++) {
					if (!is_null($question['options'][$i]['optionSelected'])) {
						$response['optionNo'] = $i;
						$response['optionText'] = $question['options'][$i]['label'];
					}
				}
				$responseDef['responses'] = $response;
				break;
			}
			case "checkbox" : {
				break;
			}
			case "radiobuttonGrid" : {
				break;
			}
			case "datetime" : {
				break;
			}
			case "email" : {
				break;
			}
			case "multi-line edit" :
			case "single-line edit" : {
				$responseDef['responses'] = ['edit' => $question['QAnswerText']];
				break;
			}
		}
		return $responseDef;
	}

	function getStep2Params($restartUID) {
		global $igrtSqli, $exptId, $jType;
		$params = [
			'canLink' => false,
			'formType' => 'step2',
			'actualJNo' => -1,
			'respNo' => -1
		];
		$paramQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='%s' AND restartUID='%s'", $exptId, $jType, $restartUID);
		$paramResult = $igrtSqli->query($paramQry);
		if ($paramResult->num_rows > 0) {
			$paramRow = $paramResult->fetch_object();
			$params['canLink'] = true;
			$params['actualJNo'] = $paramRow->actualJNo;
			$params['respNo'] = $paramRow->respNo;
		}
		return $params;
	}

	function getStep4Params($restartUID) {
		global $igrtSqli, $exptId, $jType;
		$params = [
			'canLink' => false,
			'formType' => 'step4',
			's4jno' => -1,
			'respNo' => -1
		];
		$paramQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' AND s4jNo='%s'", $exptId, $jType, $restartUID);
		$paramResult = $igrtSqli->query($paramQry);
		if ($paramResult->num_rows > 0) {
			$paramRow = $paramResult->fetch_object();
			$params['canLink'] = true;
			$params['s4jNo'] = $restartUID;
		}
		return $params;
	}

	function getInvertedStep2Params($restartUID) {
		global $igrtSqli, $exptId, $jType;
		$params = [
			'canLink' => false,
			'formType' => 'invertedStep2',
			'actualJNo' => -1,
			'respNo' => -1
		];
		$paramQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND restartUID='%s'", $exptId, $jType, $restartUID);
		$paramResult = $igrtSqli->query($paramQry);
		if ($paramResult->num_rows > 0) {
			$paramRow = $paramResult->fetch_object();
			$params['canLink'] = true;
			$params['actualJNo'] = $paramRow->actualJNo;
			$params['respNo'] = $paramRow->respNo;
		}
		return $params;
	}

	function processSurvey($chrono, $jsonArray) {
		//Kint::dump($jsonArray);
		$useEligibility = $jsonArray['useEligibilityQ'] ? 1 : 0;
		$jType = $jsonArray['jType'];
		$eligibilityChoice = -1;
		$choiceLabel = "";
		$useCode = $jsonArray['useRecruitmentCode'] ? 1 : 0;
		$rCodeText = $useCode == 1 ? $jsonArray['recruitmentCodeText'] : "no code";
		$bypassPages = $jsonArray['bypassPages'] ? 1 : 0;
		$responseItem = [
			'chrono' => $chrono,
			'useEligibility' => $useEligibility,
			'jType' => $jType,
			'eligibilityChoice' => $eligibilityChoice,
			'choiceLabel' => $choiceLabel,
			'useCode' => $useCode,
			'rCodeText' => $rCodeText,
			'sections' => []
		];
		$sectionList = [];
		if ($useEligibility == 1) {
			for ($i=0; $i < count($jsonArray['eqOptions']); $i++) {
				if ($jsonArray['eqOptions'][$i]['optionSelected'] == $i) {
					// note, this could be 0,1,2 etc if many eligibility criteria are set (unusual but possible)
					$eligibilityChoice = $i;
					$jType = $jsonArray['eqOptions'][$i]['jType'];
					$choiceLabel = $jsonArray['eqOptions'][$i]['label'];
				}
			}
		}
		else {
			// no eligibility, so only use eqOptions[0]
			if ($bypassPages == 0) {
				for ($esNo = 0; $esNo < count($jsonArray['eqOptions'][0]['eligibleSections']); $esNo++) {
					if ($jsonArray['eqOptions'][0]['eligibleSections'][$esNo]['filterPage']) {
						// if a filter page, then just use questions that match the filter option
						$filterSelection = getChosenOption($jsonArray['eqOptions'][0]['eligibleSections'][$esNo]['fqOptions']);
					}
					else {
						$filterSelection = 0;
					}
					$sectionResponseList = [
						'isFilter'=> $jsonArray['eqOptions'][0]['eligibleSections'][$esNo]['filterPage'],
						'filterSelection'=> $filterSelection,
						'sectionResponses'=> []
					];
					for ($i=0; $i<count($jsonArray['eqOptions'][0]['eligibleSections'][$esNo]['filterOptions'][$filterSelection]['questions']); $i++) {
						$questionResponse = getQuestionResponse($jsonArray['eqOptions'][0]['eligibleSections'][$esNo]['filterOptions'][$filterSelection]['questions'][$i]);
						array_push($sectionResponseList['sectionResponses'], $questionResponse);
					}
					$eSection = ['eligibleSectionNo' => $esNo, 'section'=> $sectionResponseList];
					array_push($sectionList, $eSection);
				}
			}

		}
		$responseItem['sections'] = $sectionList;
		return $responseItem;
	}

	function processForm($chrono, $jsonArray) {
		global $formType;
		$restartUID = $jsonArray['restartUID'];
		// check whether restartUID is attached to real data
		switch ($formType) {
			// s2 pre
			case 6: {
				$userParams = getStep2Params($restartUID);
				break;
			}
			// s2 post
			case 7: {
				$userParams = getStep2Params($restartUID);
				break;
			}
			// is2 pre
			case 12: {
				$userParams = getInvertedStep2Params($restartUID);
				break;
			}
			// is2 post
			case 13: {
				$userParams = getInvertedStep2Params($restartUID);
				break;
			}
			// s4 pre
			case 10: {
				$userParams = getStep4Params($restartUID);
				break;
			}
			// s4 post
			case 11: {
				$userParams = getStep4Params($restartUID);
				break;
			}
		}
		if ($userParams['canLink']) {
			$responseItem = processSurvey($chrono, $jsonArray);
			$responseDef = [
				'canLink' => true,
				'restartUID' => $restartUID,
				'surveyResponse' => $responseItem
			];
		}
		else {
			$responseDef = [
				'canLink' => false,
				'restartUID' => $restartUID,
				'surveyResponse' => []
			];
		}
		return $responseDef;
	}

	function GetAnswerLabel($answerIndex, $options) {
		foreach ($options as $i => $option) {
			if ($option['id'] == $answerIndex)
				return $option['label'];
		}
	}

	//</editor-fold>

	//<editor-fold defaultstate="collapsed" desc=" JQM forms functions">

	private function ProcessJQMform($formDef, $chrono, $jsonArray) {
		$formItem = new stdClass();
		$formItem->chrono = $chrono;
		$formItem->jType = $jsonArray["jType"];
		$formItem->restartUID  = $jsonArray["restartUID"];
		$formItem->combinedPageResponses = [];

		//recruitment code rarely used
		$formItem->useRecruitment = $jsonArray["useRecruitment"];
		if ($jsonArray["useRecruitment"] == 1) {
			$formItem->recruitmentCode = $jsonArray["recruitmentCode"];
		}
		else {
			$formItem->recruitmentCode = -1;
		}

		// deal with eligibility, but this is really only a selector used in pre-forms, mainly in step2 or inverted step2, so any response that reaches here must be eligible
		if ($jsonArray["useEligibility"] == 1) {
			$formItem->useEligibility = 1;
			$formItem->eligibilitySelection = $jsonArray["eligibilitySelection"];
			$formItem->eligibilityResponse = $this->GetEligibilityResponse($formDef["eligibilityQ"], $jsonArray["eligibilitySelection"]);

		}
		else {
			$formItem->useEligibility = 0;
			$formItem->eligibilitySelection = -1;
			$formItem->eligibilityResponse = "";
		}

		// in a jqm form, the submitted json only contains the questions shown to a respondent,
		// so formDef is not required to be able to imply form flow, but is still needed to de-reference answer values
		$pages = $this->GetPagesForJType($formDef["registrationViews"], $jsonArray["jType"]);


		for ($i=0; $i<count($jsonArray["pageResponses"]); $i++) {
			$pageResponse = $jsonArray["pageResponses"][$i];
			$pageDef = $pages[$i];

			$pageQuestionsResponses = new stdClass();
			$pageQuestionsResponses->pageNo = $i;

			if (count($pageResponse["fQuestions"]) > 0) {
				// is a filter-question page

				// get the questions relevant to the selection made
				$relevantQuestions = $this->GetRelevantQuestions($pageDef["questions"], $pageResponse["fQuestions"][0]["qAnswerNumber"]);
				// get the frQuestions relevant to the selection made
				$relevantAnswers = $pageResponse["frQuestions"][$pageResponse["fQuestions"][0]["qAnswerNumber"]];

				$pageQuestionsResponses->isFilter = 1;
				$pageQuestionsResponses->filterResponses = [];
				$pageQuestionsResponses->nonfilterResponses = [];
				$pageQuestionsResponses->filterQuestion = $pageDef["questions"][0]["qLabel"];
				$pageQuestionsResponses->filterSelection = $this->GetAnswer($pageDef["questions"][0]["options"], $pageResponse["fQuestions"][0]);

				for ($j=0; $j<count($relevantQuestions); $j++) {
					$filterResponse = new stdClass();
					$filterResponse->question = $relevantQuestions[$j]["qLabel"];
					$filterResponse->answer = $this->GetAnswer($relevantQuestions[$j]["options"], $relevantAnswers[$j]);
					array_push($pageQuestionsResponses->filterResponses, $filterResponse);
				}
			}
			else {
				// might be a non-filter-question page
				if (count($pageResponse["nfQuestions"]) > 0) {
					// non-filter question
					$pageQuestionsResponses->isFilter = 0;
					$pageQuestionsResponses->filterResponses = [];
					$pageQuestionsResponses->nonfilterResponses = [];
					$pageQuestionsResponses->filterQuestion = "";
					$pageQuestionsResponses->filterSelection = "";

					for ($j=0; $j<count($pageResponse["nfQuestions"]); $j++) {
						$nonfilterResponse = new stdClass();

						$nonfilterResponse->question = $pageDef["questions"][$j]["qLabel"];
						$nonfilterResponse->answer = $this->GetAnswer($pageDef["questions"][$j]["options"], $pageResponse["nfQuestions"][$j]);
						array_push($pageQuestionsResponses->nonfilterResponses, $nonfilterResponse);
					}
				}

			}
			array_push($formItem->combinedPageResponses, $pageQuestionsResponses);
		}
		return $formItem;
	}

	private function GetAnswer($options, $response) {
		switch ($response["qType"]) {

			case "radiobutton":
				foreach ($options as $option) {
					if ($option["id"] == $response["qAnswerNumber"]) {
						return $option["label"];
					}
				}
				break;

			case "single-line edit":
			case "numericInput":
				return $response["qAnswerText"];
				break;

			default : {
				break;
			}
		}
	}

	private function GetRelevantQuestions($questions, $selection) {
		$relevantQs = [];
		// start from question1 as question0 is the filter question
		for ($i = 1; $i<count($questions); $i++) {
			if ($questions[$i]["qContingentValue"] == $selection){
				array_push($relevantQs, $questions[$i]);
			}
		}
		return $relevantQs;
	}

	private function GetPagesForJType($pages, $jType) {
		$i = 0;
		$applicablePages = [];
		foreach ($pages as $page) {
			if (($page["jType"] == $jType || $page["jType"] == -1) && $page["ignorePage"] == 0) {
				$page["pNo"] = $i;
				$i++;
				array_push($applicablePages, $page);
			}
		}
		return $applicablePages;
	}

	private function GetEligibilityResponse($eQ, $choice) {
		foreach ($eQ["options"] as $o) {
			if ($o["id"] == $choice) {
				return $o["label"];
			}
		}
		return "";
	}

	//</editor-fold>

	public function GetFormJSON()
	{
		global $igrtSqli;

		$stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
		$formDef          = $stepFormsHandler->getForm();
		$responseList     = [];
		$this->markFieldsWithQuotes($formDef);

		$tblName         = "zz_json_" . $this->exptId;
		$responsesQry    = sprintf("SELECT * FROM %s WHERE formType='%s' ORDER BY chrono ASC", $tblName, $this->formType);
		$responsesResult = $igrtSqli->query($responsesQry);

		if ($responsesResult->num_rows > 0)
		{
			while ($responsesRow = $responsesResult->fetch_object())
			{
				$chrono    = $responsesRow->chrono;
				$json      = $this->unescape($responsesRow->json);
				$cleanJson = $this->removeQuotes($json);
				$jsonArray = json_decode($cleanJson, true);

				// temp store for these responses
				$jsonArray['processedAnswers'] = [];
				//
				if ($responsesRow->isJQM == 1)
				{
					array_push($responseList, $this->ProcessJQMform($formDef, $chrono, $jsonArray));
				}
				else
				{
					array_push($responseList, $this->ProcessForm($chrono, $jsonArray));
				}

			}
			//    Kint::dump($responseList);
			$jsonOut                = new stdClass();
			$jsonOut->formType      = $this->formType;
			$jsonOut->formName      = $this->formName;
			$jsonOut->exptId        = $this->exptId;
			$jsonOut->judgeTypes    = $formDef["judgeTypeOptions"];
			$jsonOut->oddResponses  = [];
			$jsonOut->evenResponses = [];
			foreach ($responseList as $response)
			{
				if ($response->jType == '0')
				{
					array_push($jsonOut->evenResponses, $response);
				}
				else
				{
					array_push($jsonOut->oddResponses, $response);
				}
			}
			return $jsonOut;
		}
	}



}