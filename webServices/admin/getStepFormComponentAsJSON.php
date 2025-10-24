<?php
// -----------------------------------------------------------------------------
//
// web service to return step form definition as JSON object for rendering
// by KO-JS
//
// uses class.stepFormsHandler.php which is used at this configuration stage
// and also at runtime where it builds the form
//
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
$exptId = isset($_GET['exptId']) ? $_GET['exptId'] : -1;
$jType = isset($_GET['jType']) ? $_GET['jType'] : -1;
$componentType = isset($_GET['componentType']) ? $_GET['componentType'] : -1;

//echo $exptId.' '.$jType.' '.$formType;

//ensure admin
if ($permissions >= 128) {
	switch ($componentType) {
		case "newQuestion" : {
			echo getNewPageQuestion($_GET['currentPNo'], $_GET['currentQNo']);
			break;
		}
		case "newQuestionOption" : {
			echo getNewPageQuestionOption($_GET['currentONo']);
			break;
		}
		case "newEQOption" : {
			$newEQOptionNo = $_GET['newOptionNo'];
			echo getNewEQOptionJSON($newEQOptionNo);
			break;
		}
		case "newPage" : {
			$newPageNo = $_GET['newPageNo'];
			echo getNewPageJSON($newPageNo);
			break;
		}
	}
}

function getNewPageQuestionOption($optionNo) {
	$json = '';
	$json.= '{
		"id": "'. $optionNo .'",
		"label": "option label"
	}';
	return $json;
}

function getNewPageQuestion($pNo, $currentQNo) {
	$json = '{';
	$json.= '			
			"pNo": "'. $pNo . '",
			"qNo": "'. $currentQNo. '",
			"qType": "5",
			"qLabel": "label",
			"qAccordionClosed": "0",
			"optionsAccordionClosed": "1",
			"qContingentValue": "-1",
			"qContingentText": null,
			"qValidationMsg": "validation message",
			"qContinuousSliderMax": "100",
			"qMandatory": "1",
			"qGridTarget": "0",
			"qGridInstruction": "",
			"gridColumns": [{
				"colValue": "0",
				"label": "0"
			}, {
				"colValue": "1",
				"label": "1"
			}, {
				"colValue": "2",
				"label": "2"
			}, {
				"colValue": "3",
				"label": "3"
			}, {
				"colValue": "4",
				"label": "4"
			}, {
				"colValue": "5",
				"label": "5"
			}],
			"gridRows": [{
				"rowNo": "0",
				"label": "0"
			}, {
				"rowNo": "1",
				"label": "1"
			}, {
				"rowNo": "2",
				"label": "2"
			}],
			"options": [{
				"id": "0",
				"label": "option label"
			}]
		}';
	return $json;
}

function getNewEQOptionJSON($newEQOptionNo) {
	$json = '';
	$json.= '{
		"id": "'.$newEQOptionNo.'",
		"jType": "'.$newEQOptionNo.'",
		"label": "new eligibility option"
	}';
	return $json;
}

function getNewPageJSON($newPageNo) {
	$json = '';
	$json.= '{';
	$json.= '"pNo":"' . $newPageNo. '",
		"pageTitle": "Page Title",
		"pageInst": "Page instruction",
		"pageButtonLabel": "next",
				"contingentPage": "0",
				"useFilter": "0",
				"pageAccordionClosed":"1",
				"contingentValue":"-1",
				"contingentText":"",
				"ignorePage": "0",
				"jType":"0",';
	$json.= '"questions": [
		{
			"pNo" : "' . $newPageNo . '",
			"qNo":"0",
			"qType":"5",
			"qLabel":"question instruction",
			"qAccordionClosed":"1",
			"optionsAccordionClosed":"1",
			"qContingentValue": "-1",
			"qContingentText": null,
			"qValidationMsg":"validation message",
			"qContinuousSliderMax":"100",
			"qMandatory": "1",
			"qGridTarget": "0",
			"qGridInstruction": "",
			"gridColumns": [{
				"colValue": "0",
				"label": "0"
			}, {
				"colValue": "1",
				"label": "1"
			}, {
				"colValue": "2",
				"label": "2"
			}, {
				"colValue": "3",
				"label": "3"
			}, {
				"colValue": "4",
				"label": "4"
			}, {
				"colValue": "5",
				"label": "5"
			}],
			"gridRows": [{
				"rowNo": "0",
				"label": "0"
			}, {
				"rowNo": "1",
				"label": "1"
			}, {
				"rowNo": "2",
				"label": "2"
			}],
			"options": [{
				"id": "0",
				"label": "Cardiff University"
			}]
		}]
	}';

	return $json;
}


