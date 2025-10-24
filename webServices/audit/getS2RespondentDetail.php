<?php
// -----------------------------------------------------------------------------
// web service to export status of all Step2 Respondents 
// for a specific experiment/jType
// exports discarded (at review), unfinished, & complete
// for rendering as with real character encoding
// It exports as JSON to ko-js script for rendering
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
require_once($root_path.'/helpers/class.dataHandler.php');
require_once($root_path.'/helpers/class.dbHelpers.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$permissions=$_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $dHandler = new dataHandler($igrtSqli);
  $dHandler->calculateS4PassRates($exptId, $jType);
  $dbHelper  = new DBHelper($igrtSqli);
  // get title, session and day counts
  $exptTitle = $dbHelper->getExptTitleFromId($exptId);
  $exptArray = $dbHelper->getExptDaySessionCounts($exptId);
  if ($exptArray["status"] == "ok") {
    $dayCnt = $exptArray["dayCnt"];
    $sessionCnt = $exptArray["sessionCnt"];        
  }
  else {
    $dayCnt = 0;
    $sessionCnt = 0;                
  }
  // get list of respondents ignored during Step2 review
  $ignorePpts = array();
  $ignoreQry = sprintf("SELECT * FROM wt_Step2pptReviews WHERE exptId='%s' AND jType='%s' AND ignorePpt=1", $exptId, $jType);
  $ignoreResult = $igrtSqli->query($ignoreQry);
  while ($ignoreRow = $ignoreResult->fetch_object()) {
    if (isset($S2ppt)) { unset($S2ppt); }
    $reviewedRespNo = $ignoreRow->reviewedRespNo;
    $respNo = $ignoreRow->respNo;
    $actualJNo = $ignoreRow->actualJNo;
    $jNo = $ignoreRow->jNo;
    $dayNo = $ignoreRow->dayNo;
    $sessionNo = $ignoreRow->sessionNo;
    $S2ppt = array(
      "reviewedRespNo" => $reviewedRespNo, 
      "respNo" => $respNo, 
      "actualJNo" => $actualJNo,
      "jNo" => $jNo,
      "dayNo" => $dayNo,
      "sessionNo" => $sessionNo,
      "turns"=>array()
    );
    $answersQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND reviewedRespNo='%s' AND respNo='%s' ORDER BY qNo ASC"
        , $exptId, $jType, $actualJNo, $reviewedRespNo, $respNo);
    $answersResult = $igrtSqli->query($answersQry);
    while ($answersRow = $answersResult->fetch_object()) {
      $q = $answersRow->q;
      $reply = $answersRow->reply;
      if (isset($turn)) { unset($turn); }
      $turn = array("q"=>$q, "reply"=>$reply);
      array_push($S2ppt["turns"], $turn);
    }
    array_push($ignorePpts, $S2ppt);
  }
  // get list of respondents started but discarded within balancer
  $discardPpts = array();
  $discardQry = sprintf("SELECT * FROM wt_Step2pptStatus WHERE exptId='%s' AND jType='%s' AND discarded=1", $exptId, $jType);
  $discardResult = $igrtSqli->query($discardQry);
  while ($discardRow = $discardResult->fetch_object()) {
    $actualJNo = $discardRow->actualJNo;
    $respNo = $discardRow->respNo;
    $chrono = $discardRow->chrono;
    $userCode = $discardRow->userCode;
    // get dayNo and sessionNo from actualJNo => wt_Step2Balancer
    $labelQry = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", $exptId, $jType, $actualJNo);
    $labelResult = $igrtSqli->query($labelQry);
    $labelRow = $labelResult->fetch_object();
    $jNo = $labelRow->jNo;
    $dayNo = $labelRow->dayNo;
    $sessionNo = $labelRow->sessionNo;    
    if (isset($S2ppt)) { unset($S2ppt); }
    $S2ppt = array("respNo" => $respNo, "actualJNo" => $actualJNo, "chrono" => $chrono, "turns" => array());   
    $answersQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType='%s' AND pptNo='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY qNo ASC"
        , $exptId, $jType, $respNo, $dayNo, $sessionNo, $jNo);
    $answersResult = $igrtSqli->query($answersQry);
    while ($answersRow = $answersResult->fetch_object()) {
      $q = ""; // question not in raw data  - could be obtained from dataSTEP1 but not important
      $reply = $answersRow->reply;
      if (isset($turn)) { unset($turn); }
      $turn = array("q"=>$q, "reply"=>$reply);
      array_push($S2ppt["turns"], $turn);
    }
    array_push($discardPpts, $S2ppt);
  }
  // get list of included and complete respondents
  $goodPpts = array();
  $goodQry = sprintf("SELECT * FROM wt_Step2pptReviews WHERE exptId='%s' AND jType='%s' AND ignorePpt=0", $exptId, $jType);
  $goodResult = $igrtSqli->query($goodQry);
  while ($goodRow = $goodResult->fetch_object()) {
    if (isset($S2ppt)) { unset($S2ppt); }
    $reviewedRespNo = $goodRow->reviewedRespNo;
    $respNo = $goodRow->respNo;
    $actualJNo = $goodRow->actualJNo;
    $jNo = $goodRow->jNo;
    $dayNo = $goodRow->dayNo;
    $sessionNo = $goodRow->sessionNo;
    $S2ppt = array(
      "reviewedRespNo" => $reviewedRespNo, 
      "respNo" => $respNo, 
      "actualJNo" => $actualJNo,
      "jNo" => $jNo,
      "dayNo" => $dayNo,
      "sessionNo" => $sessionNo,
      "turns" => array()
    );
    $answersQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND reviewedRespNo='%s' AND respNo='%s' ORDER BY qNo ASC"
        , $exptId, $jType, $actualJNo, $reviewedRespNo, $respNo);
    $answersResult = $igrtSqli->query($answersQry);
    while ($answersRow = $answersResult->fetch_object()) {
      $q = $answersRow->q;
      $reply = $answersRow->reply;
      if (isset($turn)) { unset($turn); }
      $turn = array("q"=>$q, "reply"=>$reply);
      array_push($S2ppt["turns"], $turn);
    }
    array_push($goodPpts, $S2ppt);
  }
  $debug = false;
  if ($debug) {
    echo "ignore<br />";
    echo print_r($ignorePpts, true);
    echo "<br />discard<br />";
    echo print_r($discardPpts, true);
    echo "<br />good<br />";
    echo print_r($goodPpts, true);
  }
  else 
  {
    // send as JSON object
    $jSonRep = "{";
    $jSonRep.= "\"exptId\":\"" . $exptId . "\","; 
    $jSonRep.= "\"exptTitle\":\"" . $exptTitle . "\","; 
    $jSonRep.= "\"jType\":\"" . $jType . "\","; 
    $jSonRep.= "\"dayCnt\":\"" . $dayCnt . "\","; 
    $jSonRep.= "\"sessionCnt\":\"" . $sessionCnt . "\","; 
    $jSonRep.= "\"ignoredS2RespondentsCnt\":\"" . count($ignorePpts) . "\","; 
    $jSonRep.= "\"ignoredS2Respondents\":[";
    for ($i=0; $i<count($ignorePpts); $i++) {
      if ($i > 0) { $jSonRep.=","; }  // prepend any judge after the first
      $jSonRep.= "{";
        $jSonRep.= "\"reviewedRespNo\":\"" . $ignorePpts[$i]['reviewedRespNo'] . "\",";
        $jSonRep.= "\"respNo\":\"" . $ignorePpts[$i]['respNo'] . "\",";
        $jSonRep.= "\"actualJNo\":\"" . $ignorePpts[$i]['actualJNo'] . "\",";
        $jSonRep.= "\"jNo\":\"" . $ignorePpts[$i]['jNo'] . "\",";
        $jSonRep.= "\"dayNo\":\"" . $ignorePpts[$i]['dayNo'] . "\",";
        $jSonRep.= "\"index\":\"" . $i . "\",";
        $jSonRep.= "\"sessionNo\":\"" . $ignorePpts[$i]['sessionNo'] . "\",";
        $jSonRep.= "\"turnsCnt\":\"" . count($ignorePpts[$i]['turns']) . "\",";        
        $jSonRep.= "\"turns\": [";
        for ($j=0; $j<count($ignorePpts[$i]['turns']); $j++) {      
          if ($j > 0) { $jSonRep.= ","; } // prepend any step1 question-set after the first
          $jSonRep.= "{";
          $jSonRep.= "\"qNo\":\"" . $j . "\",";
          $jSonRep.= "\"q\":" . JSONparse($ignorePpts[$i]['turns'][$j]['q']) . ",";
          $jSonRep.= "\"reply\":" . JSONparse($ignorePpts[$i]['turns'][$j]['reply']);
          $jSonRep.= "}";
        }
        $jSonRep.= "]";
      $jSonRep.= "}";
    }
    $jSonRep.= "],";
    $jSonRep.= "\"discardedS2RespondentsCnt\":\"" . count($discardPpts) . "\","; 
    $jSonRep.= "\"discardedS2Respondents\":[";
    for ($i=0; $i<count($discardPpts); $i++) {
      if ($i > 0) { $jSonRep.=","; }  // prepend any judge after the first
      $jSonRep.= "{";
        $jSonRep.= "\"chrono\":\"" . $discardPpts[$i]['chrono'] . "\",";
        $jSonRep.= "\"respNo\":\"" . $discardPpts[$i]['respNo'] . "\","; 
        $jSonRep.= "\"actualJNo\":\"" . $discardPpts[$i]['actualJNo'] . "\","; 
        $jSonRep.= "\"index\":\"" . $i . "\",";
        $jSonRep.= "\"turnsCnt\":\"" . count($discardPpts[$i]['turns']) . "\",";        
        $jSonRep.= "\"turns\": [";
        for ($j=0; $j<count($discardPpts[$i]['turns']); $j++) {
          if ($j > 0) { $jSonRep.= ","; } // prepend any step1 question-set after the first
          $jSonRep.= "{";
          $jSonRep.= "\"qNo\":\"" . $j . "\",";
          $jSonRep.= "\"q\":" . JSONparse($discardPpts[$i]['turns'][$j]['q']) . ",";
          $jSonRep.= "\"reply\":" . JSONparse($discardPpts[$i]['turns'][$j]['reply']);
          $jSonRep.= "}";
        }
        $jSonRep.= "]";
      $jSonRep.= "}";
    }
    $jSonRep.= "],";
    $jSonRep.= "\"goodS2RespondentsCnt\":\"" . count($goodPpts) . "\","; 
    $jSonRep.= "\"goodS2Respondents\":[";
    for ($i=0; $i<count($goodPpts); $i++) {
      if ($i > 0) { $jSonRep.=","; }  // prepend any judge after the first
      $jSonRep.= "{";
        $jSonRep.= "\"reviewedRespNo\":\"" . $goodPpts[$i]['reviewedRespNo'] . "\",";
        $jSonRep.= "\"respNo\":\"" . $goodPpts[$i]['respNo'] . "\","; 
        $jSonRep.= "\"actualJNo\":\"" . $goodPpts[$i]['actualJNo'] . "\",";
        $jSonRep.= "\"jNo\":\"" . $goodPpts[$i]['jNo'] . "\",";
        $jSonRep.= "\"index\":\"" . $i . "\",";
        $jSonRep.= "\"dayNo\":\"" . $goodPpts[$i]['dayNo'] . "\",";
        $jSonRep.= "\"sessionNo\":\"" . $goodPpts[$i]['sessionNo'] . "\",";
        $jSonRep.= "\"turnsCnt\":\"" . count($goodPpts[$i]['turns']) . "\",";        
        $jSonRep.= "\"turns\": [";
        for ($j=0; $j<count($goodPpts[$i]['turns']); $j++) {
          if ($j > 0) { $jSonRep.= ","; } // prepend any step1 question-set after the first
          $jSonRep.= "{";
          $jSonRep.= "\"qNo\":\"" . $j . "\",";
          $jSonRep.= "\"q\":" . JSONparse($goodPpts[$i]['turns'][$j]['q']) . ",";
          $jSonRep.= "\"reply\":" . JSONparse($goodPpts[$i]['turns'][$j]['reply']);
          $jSonRep.= "}";
        }
        $jSonRep.= "]";
      $jSonRep.= "}";
    }
    $jSonRep.= "]";
    $jSonRep.= "}";
    echo $jSonRep;
  }
}
