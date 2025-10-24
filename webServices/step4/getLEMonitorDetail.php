<?php
// -----------------------------------------------------------------------------
// web service to list status of each Step4 judge within an experiment/jType 
// and export as JSON to ko-js script.
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
require_once($root_path.'/helpers/models/class.experimentModel.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
//$exptId = 327; //$_GET['exptId'];
//$jType = $_GET['jType'];


if ($permissions>=128) {
  $eModel  = new experimentModel($exptId);
  $exptTitle = $eModel->title;
  //$oddS1Label = $eModel->oddS1Label;
  $evenS1Label = $eModel->evenS1Label;
  $operationLabel = $jType==0 ? "$evenS1Label judges" : "$oddS1Label judges";
  // get s4 judges list
  $s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM wt_LinkedStep4datasets ORDER BY s4jNo ASC", $exptId, $jType);
  $s4jResult = $igrtSqli->query($s4jQry);
  $s4judges = array();
  while ($s4jRow = $s4jResult->fetch_object()) {
    $s4jNo = $s4jRow->s4jNo;
    $dsQry = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE s4jNo='%s' ORDER BY rated DESC, exptId ASC, jNo ASC", $s4jNo);
    $dsResult = $igrtSqli->query($dsQry);
    $transcriptCount = $dsREsult ? $dsResult->num_rows : 0;
    $ratedCount = 0;
    $s4jdatasets = array();
    while ($dsRow = $dsResult->fetch_object()) {
      $correct = -1;
      $confidence = null;
      $reason = null;
      $exptId = $dsRow->exptId;
      $jNo = $dsRow->jNo;
      $rated = $dsRow->rated;
      if ($rated == 1) {
        ++$ratedCount;
        // get s4 response
        $getRatingQry = sprintf("SELECT * FROM dataLinkedSTEP4 WHERE exptId='%s' AND jType='0' "
            . "AND s4jNo='%s' AND igNo='%s'",
            $exptId, $s4jNo, $jNo);
        //echo $getRatingQry.'<br />';
        $ratingResult = $igrtSqli->query($getRatingQry);
        if ($ratingResult) {
          $ratingRow = $ratingResult->fetch_object();
          $correct = $ratingRow->correct;
          $confidence = $ratingRow->confidence;
          $reason = $ratingRow->reason;
        }
      }
      // get transcript
      $getTranscriptQry = sprintf("SELECT * FROM md_dataStep1reviewed "
        . "WHERE exptId='%s' AND jType=0 AND jNo='%s' AND canUse=1 ORDER BY qNo ASC",
        $exptId, $jNo);
      $transcriptResult = $igrtSqli->query($getTranscriptQry);
      $turns = [];
      while ($transcriptRow = $transcriptResult->fetch_object()) {
        $turn = [
          'q'=>$transcriptRow->q,
          'pr'=>$transcriptRow->pr,
          'npr'=>$transcriptRow->npr            
        ];
        array_push($turns, $turn);
      }
      $datasetDef = array (
        'exptId' => $exptId,
        'jNo' => $jNo,
        'rated' => $rated,
        'turns' => $turns,
        'correct' => $correct,
        'confidence' => $confidence,
        'reason' => $reason
      );
      array_push($s4jdatasets, $datasetDef);
    }
    $s4judge = array(
      's4jNo' => $s4jNo,
      'url' => "les4_" . $s4jNo,
      'percentFinished' => intval( ($ratedCount * 100) / $transcriptCount),
      'transcripts' => $s4jdatasets,
    );
    array_push($s4judges, $s4judge);
  }
  $jSonRep = "{";
  $jSonRep.= "\"judgeLabel\":".JSONparse($operationLabel).",";
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
        $jSonRep.= "\"jNo\":" . ($transcript['jNo']+1). ",";
        $jSonRep.= "\"exptId\":" . $transcript['exptId']. ",";
        $jSonRep.= "\"rated\":" . $transcript['rated']. ",";
        $jSonRep.= "\"correct\":" . $transcript['correct']. ",";
        $jSonRep.= "\"confidence\":\"" . $transcript['confidence']. "\",";
        $jSonRep.= "\"reason\":" . JSONparse($transcript['reason']) . ",";
        $jSonRep.= "\"turns\":[";
        $k = 0;
        foreach ($transcript['turns'] as $turn) {
          if ($k++ > 0) { $jSonRep.= ","; }   // prepend any turn after the first
          $jSonRep.= "{";
          $jSonRep.= "\"q\":" . JSONparse($turn['q']) . ",";
          $jSonRep.= "\"pr\":" . JSONparse($turn['pr']) . ",";
          $jSonRep.= "\"npr\":" . JSONparse($turn['npr']) . "";         
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
