<?php
// -----------------------------------------------------------------------------
// web service to export transcript of each DS within an 
// experiment/jType where the Step4 judging is complete
// for rendering as with real character encoding
// It exports as JSON to ko-js script for rendering
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/parseJSON.php');
require_once($root_path.'/helpers/class.dataHandler.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$permissions=$_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $debug = "";
  $dHandler = new dataHandler($igrtSqli); 
  $getDSNoQry = sprintf("SELECT * FROM wt_Step2summaries WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $exptId, $jType);
  //$debug.= $getDSNoQry.';';
  $getDSNoResult = $igrtSqli->query($getDSNoQry);
  $dsItems = array();
  $dsPtr = 0;
  while ($dsRow = $getDSNoResult->fetch_object()) {
    $dayNo = $dsRow->dayNo;
    $sessionNo = $dsRow->sessionNo;
    $jNo = $dsRow->jNo;
    $actualJNo = $dsRow->actualJNo;
    // check whether this Step1 judge has been excluded
    $discTerm = ($jType == 0) ? "evenDiscards" : "oddDiscards";
    $s1discardQry = sprintf("SELECT %s AS discards FROM wt_Step1Discards WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s'", $discTerm, $exptId, $dayNo, $sessionNo);
    $s1discardResult = $igrtSqli->query($s1discardQry);
    $s1discardRow = $s1discardResult->fetch_object();
    $s1discards = $s1discardRow->discards;
    $comp = pow(2, $jNo);
    $s1CanUse = (($s1discards & $comp) == $comp) ? false : true;
    if ($s1CanUse) {
      $dsItem = array(
        "igr" => $actualJNo,
        "dayNo" => $dayNo,
        "sessionNo" => $sessionNo,
        "jNo" => $jNo,
        "passRate" => $dHandler->calculatePassRate($exptId, $jType),
        "questions" => array(),
        "step2Pretenders" => array(),
      );
      $s1Qry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY qNo ASC",
        $exptId, $jType, $dayNo, $sessionNo, $jNo);
      //if ($dsPtr == 4) { $debug.= $s1Qry.';'; }
      $s1Result = $igrtSqli->query($s1Qry);
      while ($s1Row = $s1Result->fetch_object()) {
        $qNo = $s1Row->qNo;
        $q = $s1Row->q;
        $npr = $s1Row->npr;
        $pr = $s1Row->pr;
        $turnItem = array("qNo"=>$qNo, "q"=>$q, "npr"=>$npr, "pr"=>$pr);
        array_push($dsItem["questions"], $turnItem);
        unset($turnItem);
      }
      // get Pretenders in this DS from shuffle (but only from 1st half of shuffle, 2nd half is added to the s2 pretender item
      $s2Qry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND shuffleHalf=1 ORDER BY s3respNo ASC",
        $exptId, $jType, $actualJNo);
      //if ($dsPtr == 4) { $debug.= $s2Qry.';'; }
      $s2Result = $igrtSqli->query($s2Qry);
      while ($s2Row = $s2Result->fetch_object()) {
        $s2PretenderItem = array("details"=>array(), "answers"=>array(), "judgements"=>array());
        $s3respNo = $s2Row->s3respNo;
        $respNo = $s2Row->respNo;
        $detailItem = array(
          "logicalP"=>$s3respNo, 
          "respNo"=>$respNo, 
        );
        $s2PretenderItem["details"] = $detailItem;
        // get each Q for this s2 respondent
        $pretenderQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND reviewedRespNo='%s' ORDER BY qNo ASC",
          $exptId, $jType, $actualJNo, $s3respNo);
        //if ($dsPtr == 4) { $debug.= $pretenderQry.';'; }
        $pretenderResult = $igrtSqli->query($pretenderQry);
        while ($pretenderRow = $pretenderResult->fetch_object()) {
          $canUse = $pretenderRow->canUse;
          $pQNo = $pretenderRow->qNo;
          $reply = $pretenderRow->reply;
          $s2PItem = array("qNo"=>$pQNo, "pr"=>$reply, "canUse"=>$canUse); 
          array_push($s2PretenderItem["answers"], $s2PItem);
        }
        $judgementItem = array("judgement1"=>-1, "confidence1"=>"interval0", "reason1"=>"not set", "s4jNo1"=>-1, "judgement2"=>-1, "confidence2"=>"interval0", "reason2"=>"not set", "s4jNo2"=>-1 );
        // get each shuffleHalf judgement for this s2 respondent
        $s4Qry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s' ORDER BY shuffleHalf ASC",
          $exptId, $jType, $actualJNo, $s3respNo, $shuffleHalf);
        //if ($dsPtr == 4) { $debug.= $s4Qry.';'; }
        $s4Result = $igrtSqli->query($s4Qry);
        while ($s4Row = $s4Result->fetch_object()) {
          $actualRespNo = $s4Row->respNo;
          $choice = $s4Row->choice;
          $confidence = $s4Row->confidence;
          $reason = $s4Row->reason;
          $s4jNo = $s4Row->s4jNo;
          if ($shuffleHalf == 1) {
            $judgementItem["judgement1"] = $choice;
            $judgementItem["confidence1"] = $confidence;
            $judgementItem["reason1"] = $reason;            
            $judgementItem["s4jNo1"] = $s4jNo;            
          }
          else {
            $judgementItem["judgement2"] = $choice;
            $judgementItem["confidence2"] = $confidence;
            $judgementItem["reason2"] = $reason;                        
            $judgementItem["s4jNo2"] = $s4jNo;            
          }                 
        }
        $s2PretenderItem["judgements"] = $judgementItem;
        array_push($dsItem["step2Pretenders"], $s2PretenderItem);
      }
      array_push($dsItems, $dsItem);
      ++$dsPtr;
    }
  }
//  $debug.= print_r($dsItems, true);
//  echo $debug;
  $jSonRep = "{\"completeQsets\":[";
  $i = 0;
  foreach($dsItems as $dsItem) {
    if ($i++ > 0) { $jSonRep.=","; }  // prepend any judge after the first
    $jSonRep.= "{";
      $jSonRep.= "\"igrNo\":" . $dsItem['igr'] . ",";
      $jSonRep.= "\"dayNo\":\"" . $dsItem['dayNo'] . "\","; 
      $jSonRep.= "\"sessionNo\":\"" . $dsItem['sessionNo'] . "\","; 
      $jSonRep.= "\"step1jNo\":\"" . $dsItem['jNo'] . "\","; 
      $jSonRep.= "\"passRate\":\"" . $dsItem['passRate'] . "\","; 
      $jSonRep.= "\"exptId\":\"" . $exptId . "\","; 
      $jSonRep.= "\"jType\":\"" . $jType . "\","; 
      $jSonRep.= "\"questions\": [";
      $j = 0;
      foreach ($dsItem['questions'] as $q) {
        if ($j++ > 0) { $jSonRep.= ","; } // prepend any step1 question-set after the first
        $jSonRep.= "{";
        $jSonRep.= "\"qNo\":" . $q['qNo'] . ",";
        $jSonRep.= "\"q\":" . JSONparse($q['q']). ",";
        $jSonRep.= "\"npr\":" . JSONparse($q['npr']). ",";
        $jSonRep.= "\"pr\":" . JSONparse($q['pr']);
        $jSonRep.= "}";
      }
      $jSonRep.= "],";
      $jSonRep.= "\"step2Pretenders\": [";
      $j = 0;
      foreach ($dsItem['step2Pretenders'] as $s2P) {
        if ($j++ > 0) { $jSonRep.= ","; } // prepend any step2 pretender after the first
        $jSonRep.= "{";
        $jSonRep.= "\"logicalP\":" . $s2P['details']['logicalP'] . ",";
        $jSonRep.= "\"respNo\":" . $s2P['details']['respNo'] . ",";
        $jSonRep.= "\"answers\": [";
        $k = 0;
        foreach ($s2P['answers'] as $answer) {
          if ($k++ > 0) { $jSonRep.= ","; } // prepend any answer after the first
          $jSonRep.= "{";
          $jSonRep.= "\"qNo\":" . $answer['qNo'] . ",";
          $jSonRep.= "\"pr\":" . JSONparse($answer['pr']) . ",";
          $jSonRep.= "\"canUse\":" . $answer['canUse'];          
          $jSonRep.= "}";                  
        }
        $jSonRep.= "],";
        $jSonRep.= "\"s4jNo1\":" . $s2P['judgements']['s4jNo1'] . ",";
        $jSonRep.= "\"judgement1\":" . $s2P['judgements']['judgement1'] . ",";
        $jSonRep.= "\"confidence1\":" . substr($s2P['judgements']['confidence1'], -1) . ",";
        $jSonRep.= "\"reason1\":" . JSONparse($s2P['judgements']['reason1']) . ",";
        $jSonRep.= "\"s4jNo2\":" . $s2P['judgements']['s4jNo2'] . ",";
        $jSonRep.= "\"judgement2\":" . $s2P['judgements']['judgement2'] . ",";
        $jSonRep.= "\"confidence2\":" . substr($s2P['judgements']['confidence2'], -1) . ",";
        $jSonRep.= "\"reason2\":" . JSONparse($s2P['judgements']['reason2']) . "";        
        $jSonRep.= "}";        
      }
      $jSonRep.= "]";
    $jSonRep.= "}";
  }
  $jSonRep.= "]}";
  echo $jSonRep;
}
