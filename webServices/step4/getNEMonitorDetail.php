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
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $eModel  = new experimentModel($exptId);
  $exptTitle = $eModel->title;
  $oddS1Label = $eModel->oddS1Label;
  $evenS1Label = $eModel->evenS1Label;
  $operationLabel = $jType==0 ? "$evenS1Label judges" : "$oddS1Label judges";
  // get s4 judgel list
  $s4jQry = sprintf("SELECT DISTINCT(s4jNo) as s4jNo FROM ne_Step4datasets WHERE exptId='%s' AND jType='%s' ORDER BY s4jNo ASC", $exptId, $jType);
  $s4jResult = $igrtSqli->query($s4jQry);
  $s4judges = array();
  $debug = '';
  while ($s4jRow = $s4jResult->fetch_object()) {
    $s4jNo = $s4jRow->s4jNo;
    $dsQry = sprintf("SELECT * FROM ne_Step4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' ORDER BY rated DESC, actualJNo ASC", $exptId, $jType, $s4jNo);
    $dsResult = $igrtSqli->query($dsQry);
    $transcriptCount = $desResult ? $dsResult->num_rows : 0;
    $ratedCount = 0;
    $s4jdatasets = array();
    while ($dsRow = $dsResult->fetch_object()) {
      $correct = -1;
      $confidence = null;
      $reason = null;
      $s3respNo1 = $dsRow->s3respNo1;
      $s3respNo2 = $dsRow->s3respNo2;
      $actualJNo = $dsRow->actualJNo;
      $dayNo = $dsRow->dayNo;
      $sessionNo = $dsRow->sessionNo;
      $jNo = $dsRow->jNo;
      $rated = $dsRow->rated;
      // need to get actual respNo = s3respNo mappings from wt_step3summaries for both P respondents
      $respNoQry = sprintf("SELECT * FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s'",
          $exptId, $jType, $actualJNo, $s3respNo1);
      $respNoResult = $igrtSqli->query($respNoQry);
      $respNoRow = $respNoResult->fetch_object();      
      $respNo1 = $respNoRow->respNo;
      $respNoQry = sprintf("SELECT * FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s'",
          $exptId, $jType, $actualJNo, $s3respNo2);
      $respNoResult = $igrtSqli->query($respNoQry);
      $respNoRow = $respNoResult->fetch_object();      
      $respNo2 = $respNoRow->respNo;
      
      if ($dsRow->rated == 1) {
        ++$ratedCount;
        // get response
        $getRatingQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType='%s' "
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
      // get q and pr1 turn
      $getTurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' "
          . "AND actualJNo='%s' AND respNo='%s' AND canUse=1 ORDER BY qNo ASC", 
          $exptId, $jType, $actualJNo, $respNo1);
      $turnsResult = $igrtSqli->query($getTurnsQry);
      //echo $getTurnsQry.'<br />';
      $turns = [];
      while ($turnRow = $turnsResult->fetch_object()) {
        $q = $turnRow->q;
        $pr1 = $turnRow->reply;
        $qNo = $turnRow->qNo;
        // this is expensive but accounts for excluded replies from respNo2
        $getR2TurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' "
            . "AND actualJNo='%s' AND respNo='%s' AND canUse=1 AND qNo='%s'", 
            $exptId, $jType, $actualJNo, $respNo2, $qNo);
        $turnsR2Result = $igrtSqli->query($getR2TurnsQry);
        if ($turnsR2Result) {
          $turnR2Row = $turnsR2Result->fetch_object();         
          $turnDef = array(
            'q' => $q,
            'pr1' => $pr1,
            'pr2' => $turnR2Row->reply
          );
          array_push($turns, $turnDef);        
        }
      }
      $datasetDef = array (
        'respNo1' => $respNo1,
        's3respNo1' => $s3respNo1,
        's3rnLabel1' => $s3respNo1 + 1,
        'respNo2' => $respNo2,
        's3respNo2' => $s3respNo2,
        's3rnLabel2' => $s3respNo2 + 1,
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
        $jSonRep.= "\"actualJNo\":" . $transcript['actualJNo']. ",";
        $jSonRep.= "\"respNo1\":" . $transcript['respNo1']. ",";
        $jSonRep.= "\"s3respNo1\":" . $transcript['s3respNo1']. ",";
        $jSonRep.= "\"s3rnLabel1\":" . $transcript['s3rnLabel1']. ",";
        $jSonRep.= "\"respNo2\":" . $transcript['respNo2']. ",";
        $jSonRep.= "\"s3respNo2\":" . $transcript['s3respNo2']. ",";
        $jSonRep.= "\"s3rnLabel2\":" . $transcript['s3rnLabel2']. ",";
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
          $jSonRep.= "\"pr1\":" . JSONparse($turn['pr1']) . ",";
          $jSonRep.= "\"pr2\":" . JSONparse($turn['pr2']) . "";         
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
