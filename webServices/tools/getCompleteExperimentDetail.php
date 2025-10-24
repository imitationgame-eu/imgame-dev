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
require_once($root_path.'/helpers/models/class.experimentModel.php');
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$permissions=$_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];


if ($permissions>=128) {
  $debug = "";
  $dHandler = new dataHandler($igrtSqli);
  $dHandler->calculateS4PassRates($exptId, $jType);
  $summaryDetails = $dHandler->getSummaryDetails();
  $eModel  = new experimentModel($exptId);
  $exptTitle = $eModel->title;
  $getajNoQry = sprintf("SELECT DISTINCT(actualJNo) FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $exptId, $jType);
  //echo $getajNoQry.';';
  $getajNoResult = $igrtSqli->query($getajNoQry);
  $dsItems = [];
  $dsPtr = 0;
  while ($dsRow = $getajNoResult->fetch_object()) {
    // derive day and session from actualJNo
    $actualJNo = $dsRow->actualJNo;
    $getDSNoQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", $exptId, $jType, $actualJNo);
    //echo $getDSNoQry.';';
    $dsResult = $igrtSqli->query($getDSNoQry);
    if ($dsResult) {
      $dsDetailRow = $dsResult->fetch_object();
      $dayNo = $dsDetailRow->dayNo;
      $sessionNo = $dsDetailRow->sessionNo;
      $jNo = $dsDetailRow->jNo;
      $s4passrateDetails = $dHandler->getPassRateDetails($actualJNo);
      $dsItem = array(
        "igr" => $actualJNo,
        "dayNo" => $dayNo,
        "sessionNo" => $sessionNo,
        "jNo" => $jNo,
        "finalCorrect" => -1,
        "finalReason" => "not set",
        "finalConfidence" => -1,
        "s1passRate" => -1,
        "s4passRate" => round($s4passrateDetails['passRate'], 5, PHP_ROUND_HALF_UP),
        "j1passRate" => round($s4passrateDetails['j1passRate'], 5, PHP_ROUND_HALF_UP),
        "j2passRate" => round($s4passrateDetails['j2passRate'], 5, PHP_ROUND_HALF_UP),
        "s4correctCnt" => round($s4passrateDetails['correctCnt'], 5, PHP_ROUND_HALF_UP),
        "s4dontKnowCnt" => round($s4passrateDetails['dontKnowCnt'], 5, PHP_ROUND_HALF_UP),
        "s4incorrectCnt" => round($s4passrateDetails['incorrectCnt'], 5, PHP_ROUND_HALF_UP),
        "j1correctCnt" => round($s4passrateDetails['J1CorrectCnt'], 5, PHP_ROUND_HALF_UP),
        "j1dontKnowCnt" => round($s4passrateDetails['J1DontKnowCnt'], 5, PHP_ROUND_HALF_UP),
        "j1incorrectCnt" => round($s4passrateDetails['J1IncorrectCnt'], 5, PHP_ROUND_HALF_UP),
        "j2correctCnt" => round($s4passrateDetails['J2CorrectCnt'], 5, PHP_ROUND_HALF_UP),
        "j2dontKnowCnt" => round($s4passrateDetails['J2DontKnowCnt'], 5, PHP_ROUND_HALF_UP),
        "j2incorrectCnt" => round($s4passrateDetails['J2IncorrectCnt'], 5, PHP_ROUND_HALF_UP),
        "questions" => array(),
        "step2Pretenders" => array(),
      );
      $s1Qry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY qNo ASC",
        $exptId, $jType, $dayNo, $sessionNo, $jNo);
      //echo $s1Qry.';'; 
      $s1Result = $igrtSqli->query($s1Qry);
      while ($s1Row = $s1Result->fetch_object()) {
        $qNo = $s1Row->qNo;
        $q = $s1Row->q;
        $npr = $s1Row->npr;
        $pr = $s1Row->pr;
        $s1CanUse = $s1Row->canUse;
        // overwrite npr if this is an inverted Step2 experiment
        $useInvertedS2 = $jType == 0 ? $eModel->useEvenInvertedS2 : $eModel->useOddInvertedS2;
        $npSrcTable = $useInvertedS2 == 1 ? "dataSTEP2inverted" : "md_dataStep1reviewed";
        if ($npSrcTable == "dataSTEP2inverted") {
          $checkQry = "SELECT * FROM md_invertedStep2reviewed WHERE exptId=$exptId AND jType=$jType";
          //echo $checkQry.';';
          $fr = $igrtSqli->query($checkQry);
          if ($fr) {
            $npSrcTable = "md_invertedStep2reviewed";
            $getNPTurnsQry = sprintf("SELECT * FROM %s WHERE exptId='%s' AND actualJNo='%s' AND "
                . "jType='%s' AND qNo='%s'", 
                $npSrcTable, $exptId, $actualJNo, $jType, $qNo);
          }
          else {
            $npSrcTable = "dataSTEP2inverted";
            $getNPTurnsQry = sprintf("SELECT * FROM %s WHERE exptId='%s' AND dayNo='%s' "
                . "AND sessionNo='%s' AND jType='%s' AND jNo='%s' AND qNo='%s'", 
                $npSrcTable, $exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo);
          }
          //echo $getNPTurnsQry.';';
          $npTurnsResult = $igrtSqli->query($getNPTurnsQry);
          $npRow = $npTurnsResult->fetch_object();
          $npr = $npRow->reply;                       
        }
        $turnItem = array("qNo"=>$qNo, "q"=>$q, "npr"=>$npr, "pr"=>$pr, "s1CanUse"=>$s1CanUse, "s1confidence"=>-1, "s1correct"=>-1, "s1reason"=>"not set");
        array_push($dsItem["questions"], $turnItem);
        unset($turnItem);
      }
      $s1Qry = sprintf("SELECT * FROM dataSTEP1 WHERE exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY qNo ASC",
        $exptId, $jType, $dayNo, $sessionNo, $jNo);
      //echo $s1Qry.';';
      $s1Result = $igrtSqli->query($s1Qry);
      $correctCnt = 0;
      $possibleCnt = 0;
      while ($s1Row = $s1Result->fetch_object()) {
        $qNo = $s1Row->qNo;
        $confidence = $s1Row->rating;
        $choice = $s1Row->choice;
        $reason = $s1Row->reason;
        $npLeft = $s1Row->npLeft;
        if ($npLeft == 1) {
          // np is on left
          $correct = ($choice == 1) ? 1 : 0;
        }
        else {
          $correct = ($choice == 0) ? 1 : 0;
        }
        if ($s1Row->q == 'FINAL') {
          $dsItem["finalCorrect"] = $correct;
          $dsItem["finalConfidence"] = substr($confidence, -1);
          $dsItem["finalReason"] = $reason;
        }
        else {
          ++$possibleCnt;
          if ($correct == 1) { ++$correctCnt; }
          $dsItem["questions"][$qNo-1]["s1confidence"] = substr($confidence, -1);
          $dsItem["questions"][$qNo-1]["s1correct"] = $correct;
          $dsItem["questions"][$qNo-1]["s1reason"] = $reason;                  
        }
      }
      $correctRate = $correctCnt / $possibleCnt;
      $dsItem["s1passRate"] = round((1 - $correctRate), 5, PHP_ROUND_HALF_UP);
      // get Pretenders in this DS from shuffle (but only from 1st half of shuffle, 2nd half s4 judging is added to the s2 pretender item
      $s2Qry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND shuffleHalf=1 ORDER BY s3respNo ASC",
        $exptId, $jType, $actualJNo);
      //echo $s2Qry.';';
      $s2Result = $igrtSqli->query($s2Qry);
      while ($s2Row = $s2Result->fetch_object()) {
        $s2PretenderItem = array("details"=>array(), "answers"=>array(), "judgements"=>array());
        $s3respNo = $s2Row->s3respNo;
        // need to get actual respNo = s3respNo mappings from wt_step3summaries
        $respNoQry = sprintf("SELECT * FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s'",
            $exptId, $jType, $actualJNo, $s3respNo);
        //echo $respNoQry.';';
        $respNoResult = $igrtSqli->query($respNoQry);
        $respNoRow = $respNoResult->fetch_object();      
        $respNo = $respNoRow->respNo;
        $detailItem = array(
          "logicalP"=>$s3respNo+1, 
          "respNo"=>$respNo, 
        );
        $s2PretenderItem["details"] = $detailItem;
        // get each Q for this s2 respondent
        $pretenderQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s' ORDER BY qNo ASC",
          $exptId, $jType, $actualJNo, $respNo);  
        //echo $pretenderQry.';';
        $pretenderResult = $igrtSqli->query($pretenderQry);
        while ($pretenderRow = $pretenderResult->fetch_object()) {
          $canUse = $pretenderRow->canUse;
          $pQNo = $pretenderRow->qNo;
          $reply = $pretenderRow->reply;
          $s2PItem = array("qNo"=>$pQNo, "pr"=>$reply, "canUse"=>$canUse); 
          array_push($s2PretenderItem["answers"], $s2PItem);
        }
        $judgementItem = array("judgement1"=>-1, "correct1"=>-1, "pRight1"=>-1, "confidence1"=>"interval0", "reason1"=>"not set", "s4jNo1"=>-1, "judgement2"=>-1, "correct2"=>-1, "pRight2"=>-1, "confidence2"=>"interval0", "reason2"=>"not set", "s4jNo2"=>-1 );
        // get each shuffleHalf judgement for this s2 respondent 
        // find s4jNo for each half
        $qry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s' ORDER BY shuffleHalf ASC",
            $exptId, $jType, $actualJNo, $s3respNo);
        //echo $qry.';';
        $result = $igrtSqli->query($qry);
        if ($result) {
          while ($row = $result->fetch_object()) {
            $s4jNo = $row->s4jNo;
            $shuffleHalf = $row->shuffleHalf;
            $s4Qry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s4jNo='%s'",
              $exptId, $jType, $actualJNo, $s4jNo);
            //echo $s4Qry.';';
            $s4Result = $igrtSqli->query($s4Qry);
            while ($s4Row = $s4Result->fetch_object()) {
              //$actualRespNo = $s4Row->respNo;
              $choice = $s4Row->choice;
              $confidence = $s4Row->confidence;
              $correct = $s4Row->correct;
              $pretenderRight = $s4Row->pretenderRight;
              $reason = $s4Row->reason;
              if ($shuffleHalf == 1) {
                $judgementItem["judgement1"] = $choice;
                $judgementItem["correct1"] = $correct;
                $judgementItem["pRight1"] = $pretenderRight;
                $judgementItem["confidence1"] = $confidence;
                $judgementItem["reason1"] = $reason;            
                $judgementItem["s4jNo1"] = $s4jNo;            
              }
              else {
                $judgementItem["judgement2"] = $choice;
                $judgementItem["correct2"] = $correct;
                $judgementItem["pRight2"] = $pretenderRight;
                $judgementItem["confidence2"] = $confidence;
                $judgementItem["reason2"] = $reason;                        
                $judgementItem["s4jNo2"] = $s4jNo;            
              }                 
            }
          }
        }
        $s2PretenderItem["judgements"] = $judgementItem;
        array_push($dsItem["step2Pretenders"], $s2PretenderItem);
      }
      array_push($dsItems, $dsItem);
      ++$dsPtr;    
    }
  }

  
  
  $jSonRep = "{";
  $jSonRep.= "\"exptId\":\"" . $exptId . "\","; 
  $jSonRep.= "\"exptTitle\":\"" . $exptTitle . "\","; 
  $jSonRep.= "\"meanPassRate\":\"" . round($summaryDetails['passRate'], 5, PHP_ROUND_HALF_UP) . "\","; 
  $jSonRep.= "\"meanJ1PassRate\":\"" . round($summaryDetails['j1passRate'], 5, PHP_ROUND_HALF_UP) . "\","; 
  $jSonRep.= "\"meanJ2PassRate\":\"" . round($summaryDetails['j2passRate'], 5, PHP_ROUND_HALF_UP) . "\","; 
  $jSonRep.= "\"completeQsets\":[";
  $i = 0;
  foreach($dsItems as $dsItem) {
    if ($i++ > 0) { $jSonRep.=","; }  // prepend any judge after the first
    $jSonRep.= "{";
      $jSonRep.= "\"igrNo\":" . $dsItem['igr'] . ",";
      $jSonRep.= "\"dayNo\":\"" . $dsItem['dayNo'] . "\","; 
      $jSonRep.= "\"sessionNo\":\"" . $dsItem['sessionNo'] . "\","; 
      $jSonRep.= "\"step1jNo\":\"" . $dsItem['jNo'] . "\","; 
      $jSonRep.= "\"finalConfidence\":\"" . $dsItem['finalConfidence'] . "\","; 
      $jSonRep.= "\"finalCorrect\":\"" . $dsItem['finalCorrect'] . "\","; 
      $jSonRep.= "\"finalReason\":" . JSONparse($dsItem['finalReason']) . ","; 
      $jSonRep.= "\"s1passRate\":\"" . $dsItem['s1passRate'] . "\","; 
      $jSonRep.= "\"s4passRate\":\"" . $dsItem['s4passRate'] . "\","; 
      $jSonRep.= "\"j1passRate\":\"" . $dsItem['j1passRate'] . "\","; 
      $jSonRep.= "\"j2passRate\":\"" . $dsItem['j2passRate'] . "\","; 
      $jSonRep.= "\"s4correctCnt\":\"" . $dsItem['s4correctCnt'] . "\","; 
      $jSonRep.= "\"s4dontKnowCnt\":\"" . $dsItem['s4dontKnowCnt'] . "\","; 
      $jSonRep.= "\"s4incorrectCnt\":\"" . $dsItem['s4incorrectCnt'] . "\","; 
      $jSonRep.= "\"j1correctCnt\":\"" . $dsItem['j1correctCnt'] . "\","; 
      $jSonRep.= "\"j1dontKnowCnt\":\"" . $dsItem['j1dontKnowCnt'] . "\","; 
      $jSonRep.= "\"j1incorrectCnt\":\"" . $dsItem['j1incorrectCnt'] . "\","; 
      $jSonRep.= "\"j2correctCnt\":\"" . $dsItem['j2correctCnt'] . "\","; 
      $jSonRep.= "\"j2dontKnowCnt\":\"" . $dsItem['j2dontKnowCnt'] . "\","; 
      $jSonRep.= "\"j2incorrectCnt\":\"" . $dsItem['j2incorrectCnt'] . "\","; 
      $jSonRep.= "\"jType\":\"" . $jType . "\","; 
      $jSonRep.= "\"questions\": [";
      $j = 0;
      foreach ($dsItem['questions'] as $q) {
        if ($j++ > 0) { $jSonRep.= ","; } // prepend any step1 question-set after the first
        $jSonRep.= "{";
        $jSonRep.= "\"qNo\":" . $q['qNo'] . ",";
        $jSonRep.= "\"s1CanUse\":" . $q['s1CanUse'] . ",";
        $jSonRep.= "\"s1confidence\":" . $q['s1confidence'] . ",";
        $jSonRep.= "\"s1correct\":" . $q['s1correct'] . ",";
        $jSonRep.= "\"s1reason\":" . JSONparse($q['s1reason']) . ",";
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
        $jSonRep.= "\"correct1\":" . $s2P['judgements']['correct1'] . ",";
        $jSonRep.= "\"pRight1\":" . $s2P['judgements']['pRight1'] . ",";
        $jSonRep.= "\"confidence1\":" . substr($s2P['judgements']['confidence1'], -1) . ",";
        $jSonRep.= "\"reason1\":" . JSONparse($s2P['judgements']['reason1']) . ",";
        $jSonRep.= "\"s4jNo2\":" . $s2P['judgements']['s4jNo2'] . ",";
        $jSonRep.= "\"judgement2\":" . $s2P['judgements']['judgement2'] . ",";
        $jSonRep.= "\"correct2\":" . $s2P['judgements']['correct2'] . ",";
        $jSonRep.= "\"pRight2\":" . $s2P['judgements']['pRight2'] . ",";
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
