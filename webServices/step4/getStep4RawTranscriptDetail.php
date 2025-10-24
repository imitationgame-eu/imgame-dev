<?php
// -----------------------------------------------------------------------------
// web service to export transcript of each Step4 judge within an experiment/jType
// for rendering as a raw transcript check
// It only exports the first half of the shuffle to avoid double checking the same
// transcript twice.
// It exports as JSON to ko-js script for rendering
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];

include_once $root_path.'/domainSpecific/mySqlObject.php';      

if ($permissions>=128) {
  // get s4 judgel list
  $s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' ORDER BY s4jNo ASC", $exptId, $jType);
  $s4jResult = $igrtSqli->query($s4jQry);
  $s4judges = array();
  $debug = '';
  while ($s4jRow = $s4jResult->fetch_object()) {
    $s4jNo = $s4jRow->s4jNo;
    $dsQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' AND shuffleHalf=1 ORDER BY rated DESC", $exptId, $jType, $s4jNo);
    $dsResult = $igrtSqli->query($dsQry);
    $transcriptCount = $dsREsult ? $dsResult->num_rows : 0;
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
      // need to get actual respNo = s3respNo mappings from wt_step3summaries
      $respNoQry = sprintf("SELECT * FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s'",
          $exptId, $jType, $actualJNo, $s3respNo);
      $respNoResult = $igrtSqli->query($respNoQry);
      $respNoRow = $respNoResult->fetch_object();      
      $respNo = $respNoRow->respNo;
      
      if ($dsRow->rated == 1) {
        ++$ratedCount;
        // get response
        $getRatingQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' "
            . "AND s4jNo='%s' AND respNo='%s' AND actualJNo='%s'",
            $exptId, $jType, $s4jNo, $respNo, $actualJNo);
        $ratingResult = $igrtSqli->query($getRatingQry);
        if ($ratingResult) {
          $ratingRow = $ratingResult->fetch_object();
          $correct = $ratingRow->correct;
          $confidence = $ratingRow->confidence;
          $reason = $ratingRow->reason;
        }
        else {
          $debug.= $getRatingQry;
          $correct = "255";
          $confidence = "interval9";
          $reason = "junk";
        }
      }
      // get turn
      $getTurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' "
          . "AND actualJNo='%s' AND respNo='%s' AND canUse=1 ORDER BY qNo ASC", 
          $exptId, $jType, $actualJNo, $respNo);
      $turnsResult = $igrtSqli->query($getTurnsQry);
      $turns = array();
      while ($turnRow = $turnsResult->fetch_object()) {
        $q = $turnRow->q;
        $pr = $turnRow->reply;
        $qNo = $turnRow->qNo;
        $getNPTurnsQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND "
            . "sessionNo='%s' AND jType='%s' AND jNo='%s' AND qNo='%s'", 
            $exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo);
        $getNPResult = $igrtSqli->query($getNPTurnsQry);
        $getNPRow = $getNPResult->fetch_object();
        $npr = $getNPRow->npr;
        $turnDef = array(
          'q' => $q,
          'pr' => $pr,
          'npr' => $npr,
        );
        array_push($turns, $turnDef);        
      }
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
//  $debug = print_r($s4judges, true);
  //echo $debug;
  $jSonRep = "{\"s4judges\":[";
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
        $jSonRep.= "\"actualJNo\":" . $transcript['actualJNo']. ",";
        $jSonRep.= "\"respNo\":" . $transcript['respNo']. ",";
        $jSonRep.= "\"s3respNo\":" . $transcript['s3respNo']. ",";
        $jSonRep.= "\"s3rnLabel\":" . $transcript['s3rnLabel']. ",";
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
