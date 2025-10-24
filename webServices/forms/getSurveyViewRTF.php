<?php
// -----------------------------------------------------------------------------
// 
// build JSON of all survey responses for viewing in KO-JS page
// but can be used to output RTF from the JSON with an extra parameter
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/kint/Kint.class.php';
require_once $root_path.'/helpers/rtf/PHPRtfLite.php';
include_once $root_path.'/webServices/forms/buildSurveyDefinition.php';

$permissions = $_POST['permissions'];
$formType = $_POST['formType'];
$exptId = $_POST['exptId'];


//ensure admin
if ($permissions >= 128) {
	$surveyBuilder = new buildSurveyDefinition($exptId, $formType);
	$jsonOut = $surveyBuilder->GetFormJSON();
	ProcessAsRTF($jsonOut);
}


function ProcessAsRTF($jsonOut) {
	PHPRtfLite::registerAutoloader();
	$rtf = new PHPRtfLite();
// create document styles
	$headerFont = new PHPRtfLite_Font(16, 'Arial', '#4f6f9f', '#ffffff');
	$datasetHeaderFont = new PHPRtfLite_Font(14, 'Arial', '#4f6f9f', '#ffffff');
	$datasetSummaryFont = new PHPRtfLite_Font(12, 'Arial', '#1f2f4f', '#ffffff');
	$turnsFontQDark = new PHPRtfLite_Font(12, 'Arial', '#3f5f9f', '#dfdfdf');
	$turnsFontRDark = new PHPRtfLite_Font(12, 'Arial', '#4f7faf', '#cfcfcf' );
	$turnsFontQLight = new PHPRtfLite_Font(12, 'Arial', '#4f6faf', '#efefef' );
	$turnsFontRLight = new PHPRtfLite_Font(12, 'Arial', '#5f8fbf', '#dfdfdf' );
// summary section
	$summarySection = $rtf->addSection();
	$summarySection->writeText('experiment # ' . $jsonOut->exptId . '<br />', $headerFont);
	$summarySection->writeText('form type '. $jsonOut->formType.'<br />', $headerFont);

	if (count($jsonOut->evenResponses) > 0) {
		$summarySection->writeText('even responses <br />', $datasetHeaderFont);
		foreach ($jsonOut->evenResponses as $response) {
			$summarySection->writeText(sprintf("ID %s <br />", $response->restartUID), $datasetSummaryFont);
			$summarySection->writeText(sprintf("Datetime %s <br />", $response->chrono), $datasetSummaryFont);
			foreach($response->combinedPageResponses as $pageResponse) {
				$summarySection->writeText(sprintf("Page %s <br />", $pageResponse->pageNo), $datasetSummaryFont);
				if ($pageResponse->isFilter == 1) {
					$summarySection->writeText(sprintf("%s <br />", $pageResponse->filterQuestion), $turnsFontQDark);
					$summarySection->writeText(sprintf("%s <br />", $pageResponse->filterSelection), $turnsFontRDark);
					foreach ($pageResponse->filterResponses as $filterResponse) {
						$summarySection->writeText(sprintf("%s <br />", $filterResponse->question), $turnsFontQDark);
						$summarySection->writeText(sprintf("%s <br />", $filterResponse->answer), $turnsFontRDark);
					}
					$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
				}
				else {
					foreach ($pageResponse->nonfilterResponses as $nonfilterResponse) {
						$summarySection->writeText(sprintf("%s <br />", $nonfilterResponse->question), $turnsFontQLight);
						$summarySection->writeText(sprintf("%s <br />", $nonfilterResponse->answer), $turnsFontRLight);
					}
					$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
				}
			}
			$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
		}
		foreach ($jsonOut->oddResponses as $response) {
			$summarySection->writeText(sprintf("ID %s <br />", $response->restartUID), $datasetSummaryFont);
			$summarySection->writeText(sprintf("Datetime %s <br />", $response->chrono), $datasetSummaryFont);
			foreach($response->combinedPageResponses as $pageResponse) {
				$summarySection->writeText(sprintf("Page %s <br />", $pageResponse->pageNo), $datasetSummaryFont);
				if ($pageResponse->isFilter == 1) {
					$summarySection->writeText(sprintf("%s <br />", $pageResponse->filterQuestion), $turnsFontQDark);
					$summarySection->writeText(sprintf("%s <br />", $pageResponse->filterSelection), $turnsFontRDark);
					foreach ($pageResponse->filterResponses as $filterResponse) {
						$summarySection->writeText(sprintf("%s <br />", $filterResponse->question), $turnsFontQDark);
						$summarySection->writeText(sprintf("%s <br />", $filterResponse->answer), $turnsFontRDark);
					}
					$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
				}
				else {
					foreach ($pageResponse->nonfilterResponses as $nonfilterResponse) {
						$summarySection->writeText(sprintf("%s <br />", $nonfilterResponse->question), $turnsFontQLight);
						$summarySection->writeText(sprintf("%s <br />", $nonfilterResponse->answer), $turnsFontRLight);
					}
					$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
				}
			}
			$summarySection->writeText(sprintf("<br />", ''), $datasetSummaryFont);
		}

	}

	// send to browser
	$fileName = "expt_surveys_" . $jsonOut->exptId . '_' . $jsonOut->formType . ".rtf";
	$rtf->sendRtf($fileName);

}
