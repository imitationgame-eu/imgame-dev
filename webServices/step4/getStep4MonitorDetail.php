<?php
// -----------------------------------------------------------------------------
// web service to list status of each Step4 judge within an experiment/jType 
// and export as JSON to ko-js script.
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
require_once($root_path.'/helpers/models/class.experimentModel.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $eModel  = new experimentModel($exptId);
	$exptTitle = $eModel->title;
	$oddS1Label = $eModel->oddS1Label;
	$evenS1Label = $eModel->evenS1Label;
	$operationLabel = $jType==0 ? "$evenS1Label judges" : "$oddS1Label judges";

	if ($eModel->useS4IndividualTurn == 1) {
		$s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM wt_TBTStep4datasets WHERE exptId='%s' AND jType='%s' ORDER BY s4jNo ASC", $exptId, $jType);
		$s4jResult = $igrtSqli->query($s4jQry);
		$s4judges = array();
		while ($s4jRow = $s4jResult->fetch_object())
		{
			$s4jNo = $s4jRow->s4jNo;
			$actualJNoQry = sprintf("SELECT DISTINCT(actualJNo) as actualJNo FROM wt_TBTStep4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s'", $exptId, $jType, $s4jNo);
			$actualJNoResult = $igrtSqli->query($actualJNoQry);
			$ratedCount = 0;
			$transcriptCount = 0;
			$s4jdatasets = array();
			while ($actualJNoRow = $actualJNoResult->fetch_object()) {
				$actualJNo = $actualJNoRow->actualJNo;
				$dsQry = sprintf("SELECT * FROM wt_TBTStep4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' AND actualJNo='%s' ORDER BY rated DESC, qNo ASC", $exptId, $jType, $s4jNo, $actualJNo);
				$dsResult = $igrtSqli->query($dsQry);


				while ($dsRow = $dsResult->fetch_object())
				{
					$datasetDef = array (
						'respNo' => -1,
						's3respNo' => -1,
						's3rnLabel' => -1,
						'actualJNo' => $actualJNo,
						'qNo' => -1,
						'rated' => -1,
						'isFinalRating' => -1,
						'turns' => []
					);
					++$transcriptCount;
					$correct = -1;
					$confidence = null;
					$reason = null;
					$s3respNo = $dsRow->s3respno;
					$actualJNo = $dsRow->actualJNo;
					$dayNo = $dsRow->dayNo;
					$sessionNo = $dsRow->sessionNo;
					$jNo = $dsRow->jNo;
					$rated = $dsRow->rated;
					$respNo = $dsRow->respNo;
					$qNo = $dsRow->qNo;
					$isFinalRating = is_null($dsRow->isFinalRating) ? 0 : $dsRow->isFinalRating;
					$datasetDef['respNo'] = $respNo;
					$datasetDef['s3respNo'] = $s3respNo;
					$datasetDef['s3rnLabel'] = $s3respNo + 1;
					$datasetDef['rated'] = $rated;
					$datasetDef['qNo'] = $qNo;
					$datasetDef['isFinalRating'] = $isFinalRating;
					$turn = [];
					if ($dsRow->rated == 1) {
						// get response
						$getRatingQry = sprintf("SELECT * FROM dataSTEP4SingleTurns WHERE exptId='%s' AND jType='%s' "
							. "AND s4jNo='%s' AND actualJNo='%s' AND qNo='%s'",
							$exptId, $jType, $s4jNo, $actualJNo, $qNo);
						//echo $getRatingQry.'<br />';
						$ratingResult = $igrtSqli->query($getRatingQry);
						if ($ratingResult) {
							++$ratedCount;
							$ratingRow = $ratingResult->fetch_object();
							$correct = $ratingRow->correct;
							$confidence = $ratingRow->confidence;
							$reason = $ratingRow->reason;
							$intention = $ratingRow->intention;
							$pAlignment = $ratingRow->pAlignment;
							$npAlignment = $ratingRow->npAlignment;
							$categoryChoice = $ratingRow->categoryChoice;
							$turn = [
								'rated'=>1,
								'correct'=>$correct,
								'confidence'=>$confidence,
								'reason'=>$reason,
								'intention'=>$intention,
								'pAlignment'=>$pAlignment,
								'npAlignment'=>$npAlignment,
								'categoryChoice'=>$categoryChoice,
								'qNo'=>$qNo
							];
						}
					}
					else {
						$turn = [
							'rated'=>0,
							'correct'=>-1,
							'confidence'=>-1,
							'reason'=>'',
							'intention'=>'',
							'pAlignment'=>-1,
							'npAlignment'=>-1,
							'categoryChoice'=>-1,
								'qNo'=>$qNo
						];
					}
					array_push($datasetDef['turns'], $turn);
					//$datasetDef['rated'] = $turn['rated'];
					array_push($s4jdatasets, $datasetDef);
				}
			}
			$s4judge = array(
				's4jNo' => $s4jNo,
				'url' => "s4_" . $exptId . "_" . $jType . "_" . $s4jNo,
				'percentFinished' => intval( ($ratedCount * 100) / $transcriptCount),
				'transcripts' => $s4jdatasets,
			);
			array_push($s4judges, $s4judge);
		}
	}
	else {
		$s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' ORDER BY s4jNo ASC", $exptId, $jType);
		$s4jResult = $igrtSqli->query($s4jQry);
		$s4judges = array();
		$debug = '';
		while ($s4jRow = $s4jResult->fetch_object()) {
			$s4jNo = $s4jRow->s4jNo;
			$dsQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' ORDER BY rated DESC, actualJNo ASC", $exptId, $jType, $s4jNo);
			$dsResult = $igrtSqli->query($dsQry);
			//echo $dsQry.'<br />';
			$transcriptCount = $dsResult->num_rows;
			$ratedCount = 0;
			$s4jdatasets = array();
			while ($dsRow = $dsResult->fetch_object()) {
				$correct = -1;
				$confidence = null;
				$reason = null;
				$s3respNo = $dsRow->s3respNo;
				$actualJNo = $dsRow->actualJNo;
				$dayNo = $dsRow->dayNo;
				$sessionNo = $dsRow->sessionNo;
				$jNo = $dsRow->jNo;
				$rated = $dsRow->rated;
				$respNo = $dsRow->respNo;
				$correct = -1;
				$confidence = -1;
				$reason = 'unrated';
				if ($dsRow->rated == 1) {
					++$ratedCount;
					// get response
					$getRatingQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' "
						. "AND s4jNo='%s' AND actualJNo='%s'",
						$exptId, $jType, $s4jNo, $actualJNo);
					//echo $getRatingQry.'<br />';
					$ratingResult = $igrtSqli->query($getRatingQry);
					if ($ratingResult) {
						$ratingRow = $ratingResult->fetch_object();
						$correct = $ratingRow->correct;
						$confidence = $ratingRow->confidence;
						$reason = $ratingRow->reason;
					}
				}
				// get turn
				$getTurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' "
					. "AND actualJNo='%s' AND respNo='%s' AND canUse=1 ORDER BY qNo ASC",
					$exptId, $jType, $actualJNo, $respNo);
				//echo $getTurnsQry.'<br />';
				$turnsResult = $igrtSqli->query($getTurnsQry);
				$turns = array();
				$datasetDef = array (
					'respNo' => $respNo,
					's3respNo' => $s3respNo,
					's3rnLabel' => $s3respNo + 1,
					'actualJNo' => $actualJNo,
					'rated' => $rated,
					'turns' => $turns,
					'correct' => $correct,
					'confidence' => $confidence,
					'reason' => $reason,
				);
				array_push($s4jdatasets, $datasetDef);
			}
			$s4judge = array(
				's4jNo' => $s4jNo,
				'url' => "s4_" . $exptId . "_" . $jType . "_" . $s4jNo,
				'percentFinished' => intval( ($ratedCount * 100) / $transcriptCount),
				'transcripts' => $s4jdatasets,
			);
			array_push($s4judges, $s4judge);
		}
	}

  // get s4 judgel list
//  $debug = print_r($s4judges, true);
//  echo $debug;
  $jSonRep = "{";
	$jSonRep.= "\"judgeLabel\":".JSONparse($operationLabel).",";
	$jSonRep.= "\"turnbyturn\":".JSONparse($eModel->useS4IndividualTurn).",";
  $jSonRep.= "\"s4judges\":[";
  $i = 0;
  foreach($s4judges as $s4judge) {
    if ($i++ > 0) { $jSonRep.=","; }  // prepend any judge after the first
    $jSonRep.= "{";
      $jSonRep.= "\"s4jNo\":" . $s4judge['s4jNo'] . ",";
      $jSonRep.= "\"url\":\"" . $s4judge['url'] . "\","; 
      $jSonRep.= "\"show\":\"False\","; 
      $jSonRep.= "\"percentFinished\":" . $s4judge['percentFinished'] . ","; 
      $jSonRep.= "\"transcripts\": [";
      $j = 0;
      foreach ($s4judge['transcripts'] as $transcript) {
        if ($j++ > 0) { $jSonRep.= ","; } // prepend any transcript after the first
        $jSonRep.= "{";
        $jSonRep.= "\"transcriptNo\":" . $j . ",";
	      $op = isset($transcript['actualJNo']) ? $transcript['actualJNo'] : "missing!";
	      $jSonRep.= "\"actualJNo\":" . JSONparse($op) . ",";
	      $op = isset($transcript['qNo']) ? $transcript['qNo'] : "missing!";    // a turn-by-turn step4 will have a qNo for each transcript
	      $jSonRep.= "\"qNo\":" . JSONparse($op) . ",";
	      $op = isset($transcript['isFinalRating']) ? $transcript['isFinalRating'] : "missing!";    // a turn-by-turn step4 will have a isFinalRating field for each transcript
	      $jSonRep.= "\"isFinalRating\":" . JSONparse($op) . ",";
        $op = isset($transcript['respNo']) ? $transcript['respNo'] : "missing!";
        $jSonRep.= "\"respNo\":" . JSONparse($op) . ",";
        $op = isset($transcript['s3respNo']) ? $transcript['s3respNo'] : "missing!";                    
        $jSonRep.= "\"s3respNo\":" . JSONparse($op). ",";
        $op = isset($transcript['s3rnLabel']) ? $transcript['s3rnLabel'] : "missing!";          
        $jSonRep.= "\"s3rnLabel\":" . JSONparse($op). ",";
        $op = isset($transcript['rated']) ? $transcript['rated'] : "missing!";          
        $jSonRep.= "\"rated\":" . JSONparse($op). ",";
        $op = isset($transcript['correct']) ? $transcript['correct'] : "missing!";          
        $jSonRep.= "\"correct\":" . JSONparse($op). ",";
        $op = isset($transcript['confidence']) ? $transcript['confidence'] : "missing!";          
        $jSonRep.= "\"confidence\":" . JSONparse($op). ",";
        $op = isset($transcript['reason']) ? $transcript['reason'] : "missing!";
        $jSonRep.= "\"reason\":" . JSONparse($op) . ",";
        $jSonRep.= "\"turns\":[";
        $k = 0;
        foreach ($transcript['turns'] as $turn) {
          if ($k++ > 0) { $jSonRep.= ","; }   // prepend any turn after the first
          $jSonRep.= "{";
	        $op = isset($turn['rated']) ? $turn['rated'] : "missing!";
	        $jSonRep.= "\"rated\":" . JSONparse($op) . ",";
	        $op = isset($turn['qNo']) ? $turn['qNo'] : "missing!";
	        $jSonRep.= "\"rated\":" . JSONparse($op) . ",";
	        $op = isset($turn['q']) ? $turn['q'] : "missing!";
	        $jSonRep.= "\"q\":" . JSONparse($op) . ",";
          $op = isset($turn['pr']) ? $turn['pr'] : "missing!";
          $jSonRep.= "\"pr\":" . JSONparse($op) . ",";
          $op = isset($turn['npr']) ? $turn['npr'] : "missing!";
          $jSonRep.= "\"npr\":" . JSONparse($op) . "";
          $jSonRep.= "}";
        }
        $jSonRep.= "]";
        $jSonRep.= "}";          
      }
      $jSonRep.= "]";
    $jSonRep.= "}";
  }
  $jSonRep.= "]}";
  echo $jSonRep;
}
