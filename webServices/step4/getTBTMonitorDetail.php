<?php


// -----------------------------------------------------------------------------
// web service to list status of each Step4 judge within an experiment/jType 
// and export as JSON to ko-js script.
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
require_once($root_path.'/helpers/models/class.experimentModel.php');
require_once($root_path.'/helpers/json/class.jsonSerialiser.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId = 328; //$_GET['exptId'];
//$jType = $_GET['jType'];


if ($permissions>=128) {
  $eModel  = new experimentModel($exptId);
  $exptTitle = $eModel->title;
  //$oddS1Label = $eModel->oddS1Label;
  $evenS1Label = $eModel->evenS1Label;
  $operationLabel = $jType==0 ? "$evenS1Label judges" : "$oddS1Label judges";
  // get s4 judges list
  $s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM wt_LinkedTBTStep4datasets ORDER BY s4jNo ASC", $exptId, $jType);
  $s4jResult = $igrtSqli->query($s4jQry);
  //echo $s4jQry.PHP_EOL;
  $s4judges = [];
  while ($s4jRow = $s4jResult->fetch_object()) {
    $s4jNo = $s4jRow->s4jNo;
    $dsQry = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' ORDER BY rated DESC, exptId ASC, jNo ASC, qNo ASC", $s4jNo);
    //echo $dsQry.PHP_EOL;
    $dsResult = $igrtSqli->query($dsQry);
    $transcriptCount = $dsREsult ? $dsResult->num_rows : 0;
    $ratedCount = 0;
    $s4jdatasets = array();
    $previousQNo = -1;
    while ($dsRow = $dsResult->fetch_object()) {
      $correct = -1;
      $confidence = null;
      $reason = null;
      $exptId = $dsRow->exptId;
      $jNo = $dsRow->jNo;
      $rated = $dsRow->rated;
      $qNo = $dsRow->qNo;
      if ($rated == 1) {
        ++$ratedCount;
        // get s4 response
        $getRatingQry = sprintf("SELECT * FROM dataLinkedTBTSTEP4 WHERE exptId='%s' AND jType='0' "
            . "AND s4jNo='%s' AND igNo='%s' AND qNo='%s'",
            $exptId, $s4jNo, $jNo, $qNo);
        //echo $getRatingQry.'<br />';
        $ratingResult = $igrtSqli->query($getRatingQry);
        if ($ratingResult) {
	        if ($qNo == $previousQNo) {
		        // final complete rating
		        for ($i=0; $i<$ratingResult->num_rows; $i++) {
			        $ratingRow = $ratingResult->fetch_object();
		        }
		        $correct = $ratingRow->correct;
		        $confidence = $ratingRow->confidence;
		        $reason = $ratingRow->reason;
		        $intention = $ratingRow->intention;
		        $alignment1 = $ratingRow->alignment1;
		        $alignment2 = $ratingRow->alignment2;
		        $pretenderRight = $ratingRow->pretenderRight;
	        }
	        else {
		        if ($ratingResult) {
			        $ratingRow = $ratingResult->fetch_object();
			        $correct = $ratingRow->correct;
			        $confidence = $ratingRow->confidence;
			        $reason = $ratingRow->reason;
			        $intention = $ratingRow->intention;
			        $alignment1 = $ratingRow->alignment1;
			        $alignment2 = $ratingRow->alignment2;
			        $pretenderRight = $ratingRow->pretenderRight;
		        }
	        }
        }
       }
      $previousQNo = $qNo;
      // get transcript
      $getTurnQry = sprintf("SELECT * FROM md_dataStep1reviewed "
        . "WHERE exptId='%s' AND jType=0 AND jNo='%s' AND qNo='%s'",
        $exptId, $jNo, $qNo);
      $turnResult = $igrtSqli->query($getTurnQry);
      $turnRow = $turnResult->fetch_object();
      $datasetDef = array (
        'exptId' => $exptId,
        'jNo' => $jNo,
        'rated' => $rated,
        'qNo'=> $qNo,
        'q'=>$turnRow->q,
        'pr'=>$turnRow->pr,
        'npr'=>$turnRow->npr,           
        'correct' => $correct,
        'confidence' => $confidence,
        'reason' => $reason,
        'intention'=> $intention,
        'alignment1'=> $alignment1,
        'alignment2'=> $alignment2,
        'pretenderRight'=> $pretenderRight
      );
      array_push($s4jdatasets, $datasetDef);
    }
    $s4judge = array(
      's4jNo' => $s4jNo,
      'url' => "tbts4_" . $s4jNo,
      'percentFinished' => intval( ($ratedCount * 100) / $transcriptCount),
      'transcripts' => $s4jdatasets,
    );
    array_push($s4judges, $s4judge);
  }
  //echo print_r($s4judges, true);
  $op = json_encode(new ArrayValue(['s4judges'=>$s4judges]), JSON_PRETTY_PRINT); 
  echo $op;     
}
