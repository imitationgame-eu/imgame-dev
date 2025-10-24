<?php
/**
 * Linked Step4 Manager 
 * top-level controller to configure, view & run sessions
 * @author MartinHall
 */

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/html/class.htmlBuilder.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/domainSpecific/domainInfo.php';
include_once $root_path.'/helpers/models/class.experimentModel.php'; 

class step4Controller {
  private $htmlBuilder;   // control builder
  private $tabIndex = 1;
  private $eModel;

  // <editor-fold defaultstate="collapsed" desc=" visible methods for interface (step4RunController.php)">
    
  public function tbtGetStep4CurrentStatus($s4jNo) {
    $turnArray = $this->getTurn($s4jNo);
    return $turnArray;
  }
  
  public function storeTranscript($s4jNo, $content) {
    $this->storeIndividualTurn($s4jNo, $content);
  }
  
  public function getStep4LinkStatus($exptId, $jType) {
    global $igrtSqli;
    global $domainName;
    $html = '';
    $getJcntQry = sprintf("SELECT DISTINCT(s4jNo) FROM wt_Step3shuffles WHERE exptId='%s' AND jType='%s' ORDER BY s4jNo ASC", $exptId, $jType);
    $jResult = $igrtSqli->query($getJcntQry);
    if ($jResult) {
      while ($jRow = $jResult->fetch_object()) {
        $s4jNo = $jRow->s4jNo;
        $jNoLabel = $s4jNo + 1;
        $transcriptCnt = $jResult->num_rows;
        $getS4progressQry = sprintf("SELECT * FROM wt_Step3shuffles WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' ORDER BY datasetPtr ASC", $exptId, $jType, $s4jNo);
        $progressResult = $igrtSqli->query($getS4progressQry);
        $completedCnt = 0;
        while ($progressRow = $progressResult->fetch_object()) {
          if ($progressRow->completed == 1) { ++$completedCnt; }
        }
        $status = "not started";
        if ($completedCnt > 0) {
          $status = ($completedCnt == $transcriptCnt) ? "completed" : "in progress - $completedCnt done"; 
        }
        $url = $domainName."/s4_".$exptId.'_'.$jType."_".$jNoLabel;
        $html.= sprintf("<div class=\"judgeHeader\">Judge #: %s   active URL: %s     status: %s</div>", $jNoLabel, $url, $status);
        $html.= $this->getFullTranscript($exptId, $jType, $s4jNo);
      }
    }
    else {
      $html = 'no judge sets found';
    }
    return $html;
  }

  public function getPageFurniture() {
    $html = '';
    $html.= sprintf("<jTab>%s</jTab>", $this->eModel->jTab);
    $html.= sprintf("<jTabUnconnected>%s</jTabUnconnected>", $this->eModel->jTabUnconnected);
    $html.= sprintf("<jTabWaiting>%s</jTabWaiting>", $this->eModel->jTabWaiting);
    $html.= sprintf("<jTabActive>%s</jTabActive>", $this->eModel->jTabActive);
    $html.= sprintf("<jTabRating>%s</jTabRating>", $this->eModel->jTabRating);
    $html.= sprintf("<jTabDone>%s</jTabDone>", $this->eModel->jTabDone);
    $html.= sprintf("<jWaitingToStart>%s</jWaitingToStart>", $this->eModel->jWaitingToStart);
    $html.= sprintf("<jPleaseAsk>%s</jPleaseAsk>", $this->eModel->jPleaseAsk);
    $html.= sprintf("<jAskButton>%s</jAskButton>", $this->eModel->jAskButton);
    $html.= sprintf("<jWaitingForReplies>%s</jWaitingForReplies>", $this->eModel->jWaitingForReplies);
    $html.= sprintf("<jHistoryTitle>%s</jHistoryTitle>", $this->eModel->jHistoryTitle);
    $html.= sprintf("<jRatingTitle>%s</jRatingTitle>", $this->eModel->jRatingTitle);
    $html.= sprintf("<jFinalRatingTitle>%s</jFinalRatingTitle>", $this->eModel->jFinalRatingTitle);
    $html.= sprintf("<jRatingYourQuestion>%s</jRatingYourQuestion>", $this->eModel->jRatingYourQuestion);
    $html.= sprintf("<jRatingQ>%s</jRatingQ>", $this->eModel->jRatingQ);
    $html.= sprintf("<jRatingR1>%s</jRatingR1>", $this->eModel->jTab);
    $html.= sprintf("<jRatingR2>%s</jRatingR2>", $this->eModel->jRatingR2);
    $html.= sprintf("<jAskAnotherB>%s</jAskAnotherB>", $this->eModel->jAskAnotherB);
    $html.= sprintf("<jNoMoreB>%s</jNoMoreB>", $this->eModel->jNoMoreB);
    $html.= sprintf("<jSaveFinalB>%s</jSaveFinalB>", $this->eModel->jSaveFinalB);
    $html.= sprintf("<jFinalMsg>%s</jFinalMsg>", $this->eModel->jFinalMsg);
    $html.= sprintf("<jConfirmHead>%s</jConfirmHead>", $this->eModel->jConfirmHead);
    $html.= sprintf("<jConfirmBody>%s</jConfirmBody>", $this->eModel->jConfirmBody);
    $html.= sprintf("<jConfirmOK>%s</jConfirmOK>", $this->eModel->jConfirmOK);
    $html.= sprintf("<jConfirmCancel>%s</jConfirmCancel>", $this->eModel->jConfirmCancel);
    $html.= sprintf("<step4_startMsg>%s</step4_startMsg>", $this->eModel->step4_startMsg);
//    echo print_r($this->eModel, true);
    $html.= sprintf("<step4_startBLabel>%s</step4_startBLabel>", $this->eModel->step4_startBLabel);
    $html.= sprintf("<step4_finalMsg>%s</step4_finalMsg>", $this->eModel->step4_finalMsg);
    $html.= sprintf("<step4_closedMsg>%s</step4_closedMsg>", $this->eModel->step4_closedMsg);
    $html.= sprintf("<step4_judgeNumberMsg>%s</step4_judgeNumberMsg>", $this->eModel->step4_judgeNumberMsg);
    $html.= sprintf("<step4_nextBLabel>%s</step4_nextBLabel>", $this->eModel->step4_nextBLabel);
    $html.= sprintf("<step4_reasonMsg>%s</step4_reasonMsg>", $this->eModel->step4_reasonMsg);
    return $html;
  } 
  
  public function getUsePost($exptId) {
//    global $igrtSqli;
//    $getSQL = "SELECT * FROM edExptStatic_refactor WHERE exptId=$exptId";
//    $getResult = $igrtSqli->query($getSQL);
//    if ($getResult->num_rows > 0) {
//      $row = $getResult->fetch_object();
//      $retValue = $row->step4PostForm;
//      return $retValue;
//    }
//    return false;
    return true;
  }
  
  public function getAlignmentandValidationParameters() {
    $html = sprintf("<reqLikert>%s</reqLikert>", $this->eModel->useLikert);
    $html.= sprintf("<useS4IndividualTurn>%s</useS4IndividualTurn>", $this->eModel->useS4IndividualTurn);
    $html.= sprintf("<useS4CharacterLimit>%s</useS4CharacterLimit>", $this->eModel->useS4CharacterLimit);
    $html.= sprintf("<s4CharacterLimitValue>%s</s4CharacterLimitValue>", $this->eModel->s4CharacterLimitValue);
    $html.= sprintf("<s4RandomiseSide>%s</s4RandomiseSide>", $this->eModel->s4RandomiseSide);
    $html.= sprintf("<useS4Intention>%s</useS4Intention>", 0);
    $html.= sprintf("<useS4IntentionMin>%s</useS4IntentionMin>", 0);
    $html.= sprintf("<s4IntentionMin>%s</s4IntentionMin>", $this->eModel->s4IntentionMin);
    $html.= sprintf("<useS4AlignmentControl>%s</useS4AlignmentControl>", 1);
    $html.= sprintf("<useS4QCategoryControl>%s</useS4QCategoryControl>", 0);
    return $html;
  }
  
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" store responses">
  
  private function storeIndividualTurn($exptId, $content) {
//    contentArray[1] = 'haveResponse';
//    contentArray[2] = choice;
//    contentArray[3] = intervalId;
//    contentArray[4] = reason;
//    contentArray[5] = pretenderRight;
//    contentArray[6] = jNo;
//    contentArray[7] = exptId;
//    contentArray[8] = qNo;
//    contentArray[9] = respondent1IntervalId;
//    contentArray[10] = respondent2IntervalId;
//    contentArray[11] = intention;
//    contentArray[12] = s4jNo;
//    contentArray[13] = isFullRating;
    global $igrtSqli;
    $choice = $content[2];
    $confidence = $content[3];
    $udreason = urldecode($content[4]);
    $reason = $igrtSqli->real_escape_string($udreason);
    $pretenderRight = $content[5];
    if ($this->eModel->choosingNP == 1) {
      $correct = ($choice == $pretenderRight) ? 0 : 1;                
    }
    else {
      $correct = ($choice == $pretenderRight) ? 1 : 0;        
    }
    $actualJNo = $content[6];
    $exptId = $content[7];
    $qNo = $content[8];
    $resp1Alignment = $content[9];
    $resp2Alignment = $content[10];
    $uds4IntentionText = urldecode($content[11]);
    $s4IntentionText = $igrtSqli->real_escape_string($uds4IntentionText);
    $s4jNo = $content[12];
    $isFullRating = $content[13];
    $storeQry = sprintf("INSERT INTO dataLinkedTBTSTEP4 "
        . "(isFinalRating, exptId, jType, s4jNo, igNo, "
        . "qNo, correct, choice, pretenderRight, "
        . "confidence, reason, intention, alignment1, alignment2) "
        . "VALUES ("
        . "'%s', '%s', '%s', '%s', '%s', "
        . "'%s', '%s', '%s', '%s', "
        . "'%s', '%s', '%s', '%s', '%s')",
        $isFullRating, $exptId, 0, $s4jNo, $actualJNo, 
        $qNo, $correct, $choice, $pretenderRight, 
        $confidence, $reason, $s4IntentionText, $resp1Alignment, $resp2Alignment);
    $igrtSqli->query($storeQry);
    if ($isFullRating == 0) {
      // update status to move onto next Q
      $update = sprintf("UPDATE wt_LinkedTBTStep4datasets SET rated=1 WHERE s4jNo='%s' AND exptId='%s' AND jNo='%s' AND qNo='%s' AND isFinalRating='0'",
        $s4jNo, $exptId, $actualJNo, $qNo);
    }
    else {
      // update status of finalRating row to move onto next Q
      $update = sprintf("UPDATE wt_LinkedTBTStep4datasets SET rated=1 WHERE s4jNo='%s' AND exptId='%s' AND jNo='%s' AND isFinalRating='1'",
        $s4jNo, $exptId, $actualJNo);
      
    }
    $igrtSqli->query($update);
  }
  
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" build transcripts and parameters">
    
  
  private function getTurn($s4jNo) {
    global $igrtSqli;
    //get min exptId where rated = 0
    $anyExptIdqry = sprintf("SELECT * FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND rated=0", $s4jNo);
    $anyResult = $igrtSqli->query($anyExptIdqry);
    //echo $anyResult->affected_rows.' '.$igrtSqli->affected_rows.PHP_EOL;
    if ($igrtSqli->affected_rows !=0 ) {
      $getMinEIqry = sprintf("SELECT MIN(exptId) as exptId FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND rated=0", $s4jNo);
      $minEIResult = $igrtSqli->query($getMinEIqry);
      if ($minEIResult) {
        $minEIRow = $minEIResult->fetch_object();
        $exptId = $minEIRow->exptId;
        // get min ig no for this exptId
        $getMinIGqry = sprintf("SELECT MIN(jNo) AS jNo FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND exptId='%s' AND rated=0",
          $s4jNo, $exptId);
        $minIGResult = $igrtSqli->query($getMinIGqry);
        $minIGRow = $minIGResult->fetch_object();
        $jNo = $minIGRow->jNo;
        // get next qNo
        $getMinQNoqry = sprintf("SELECT MIN(qNo) as qNo,isFinalRating FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND exptId='%s' AND jNo='%s' AND rated=0",
          $s4jNo, $exptId, $jNo);
        $minQNoResult = $igrtSqli->query($getMinQNoqry);
        $minQNoRow = $minQNoResult->fetch_object();
        if ($minQNoRow->isFinalRating == "1") {
          $turnValues = $this->getAllTurnValues($exptId, $jNo);
          $pretenderRight = mt_rand(0,1);
          $transcriptHtml = $this->assembleAllTurns($turnValues, $pretenderRight);
          $ratingHtml = $this->getFinalJudgeRatingHtml();
          return [
            'status'=>'s4FinalRating',
            'pretenderRight'=> $pretenderRight,
            'exptId'=> $exptId,
            'jNo'=> $jNo,
            'transcript'=> $transcriptHtml . $ratingHtml
          ];             
        }
        else {
          $qNo = $minQNoRow->qNo;
          $turnValues = $this->getTurnValues($exptId, $jNo, $qNo);
          $pretenderRight = mt_rand(0,1);
          $transcriptHtml = $this->assembleTurn($turnValues, $pretenderRight);
          $ratingHtml = $this->getTBTJudgeRatingHtml();
          return [
            'status'=>'s4Transcript',
            'pretenderRight'=> $pretenderRight,
            'exptId'=> $exptId,
            'jNo'=> $jNo,
            'qNo'=> $qNo, 
            'transcript'=> $transcriptHtml . $ratingHtml
          ];             
        }
      }
      
    }
    else {
     return ['status'=>'done'];       
    }
  }

  private function getTurnValues($exptId, $jNo, $qNo) {
    global $igrtSqli;    
    // for a linked experiment where both replies are coming from the md_dataStep1reviewed table, the 
    // transcripts are built very differently from a normal Step4
    $qList =[];
    $pList = [];
    $npList = [];
    $qry = sprintf("SELECT * FROM md_dataStep1reviewed "
      . "WHERE exptId='%s' AND dayNo=1 AND sessionNo=1 AND jType=0 AND canUse=1 AND jNo='%s' AND qNo='%s'",
      $exptId, $jNo, $qNo);
    $result = $igrtSqli->query($qry);
    $row = $result->fetch_object();
    return [
      'q'=> $row->q,
      'pr'=> $row->pr,
      'npr'=> $row->npr
    ];
  }
  
  private function assembleTurn($tValues, $pretenderRight) {
    $html = '<div class=\"s4transcript\">';
    $q = $tValues['q'];
    $pr = $tValues['pr'];
    $npr = $tValues['npr'];
    $html.= "<div class=\"previousQuestions\"><h3>$q</h3></div>";
    $paraP = explode('\n', $pr);
    $paraNP = explode('\n', $npr);
    if ($pretenderRight == 0) { 
      $html.= "<div class=\"responseOne\">";
      foreach ($paraP as $pp) {
        $html.= '<p>'.$pp.'</p>';
      }
      $html.= '</div>';
      $html.= "<div class=\"responseTwo\">";
      foreach ($paraNP as $pnp) {
        $html.= '<p>'.$pnp.'</p>';
      }
      $html.= '</div>';       
    }
    else {
      $html.= "<div class=\"responseOne\">";
      foreach ($paraNP as $pnp) {
        $html.= '<p>'.$pnp.'</p>';
      }
      $html.= '</div>';
      $html.= "<div class=\"responseTwo\">";
      foreach ($paraP as $pp) {
        $html.= '<p>'.$pp.'</p>';
      }
      $html.= '</div>';        
    }
    $html.= "</div>";
    return $html;
    
  }

  private function getTBTJudgeRatingHtml() {
    $this->eModel = new experimentModel(328); // ensure reflexivity settings from 328 as this has correct labels 
    $html = '';
    $html.= $this->htmlBuilder->makeS1AlignmentRespondentLikerts($this->eModel, 0);   // use S1 not S4 as this uses the RB controls in this special case    
    $html.=$this->htmlBuilder->makeJudgeChoice("jRating",$this->eModel->labelChoice, "judgement");
    $html.="<hr/>";
    //$html.= $this->htmlBuilder->makeS4IntentionControl($this->eModel);     
    if ($this->eModel->useReasons) {
      $html.=$this->htmlBuilder->makeJudgeReason("jReason",$this->eModel->labelReasons, $this->eModel->step4_reasonGuidance);           
    }
    if ($this->eModel->useLikert) {
      $html.=$this->htmlBuilder->makeJudgeLikert($this->eModel->instLikert, $this->eModel->labelLikert);            
    }    
//    if ($this->eModel->useS4QCategoryControl == 1) {
//      $html.= $this->htmlBuilder->makeS4AlignmentCategoryLikert($this->eModel);
//    }
    $html.= "<input id=\"s4nextB\" type=\"Submit\" class=\"buttonBlue\" value=\"Save >> next transcript\" />";
    return $html;
  }
  
  private function getAllTurnValues($exptId, $jNo) {
    global $igrtSqli;    
    $qList =[];
    $pList = [];
    $npList = [];
    $qry = sprintf("SELECT * FROM md_dataStep1reviewed "
      . "WHERE exptId='%s' AND dayNo=1 AND sessionNo=1 AND jType=0 AND canUse=1 AND jNo='%s' ORDER BY qNo",
      $exptId, $jNo);
    $result = $igrtSqli->query($qry);
    while ($row = $result->fetch_object()) {
      if ($row->q != 'FINAL') {
        array_push($qList, $row->q);
        array_push($pList, $row->pr);
        array_push($npList, $row->npr);
      }
    }
    return [
      'q'=> $qList,
      'pr'=> $pList,
      'npr'=> $npList
    ];
  }

  private function assembleAllTurns($tValues, $pretenderRight) {
    $html = '<div class=\"s4transcript\">';
    $qList = $tValues['q'];
    $pList = $tValues['pr'];
    $npList = $tValues['npr'];
    for ($i=0; $i<count($qList); $i++) {
      $q = $qList[$i];
      $npr = $npList[$i];
      $pr = $pList[$i];
      $html.= "<div class=\"previousQuestions\"><h3>$q</h3></div>";
      $paraP = explode('\n', $pr);
      $paraNP = explode('\n', $npr);
      if ($pretenderRight == 0) { 
        $html.= "<div class=\"responseOne\">";
        foreach ($paraP as $pp) {
          $html.= '<p>'.$pp.'</p>';
        }
        $html.= '</div>';
        $html.= "<div class=\"responseTwo\">";
        foreach ($paraNP as $pnp) {
          $html.= '<p>'.$pnp.'</p>';
        }
        $html.= '</div>';       
      }
      else {
        $html.= "<div class=\"responseOne\">";
        foreach ($paraNP as $pnp) {
          $html.= '<p>'.$pnp.'</p>';
        }
        $html.= '</div>';
        $html.= "<div class=\"responseTwo\">";
        foreach ($paraP as $pp) {
          $html.= '<p>'.$pp.'</p>';
        }
        $html.= '</div>';        
      }
      $html.= "</div>";      
    }
    return $html;    
  }

  private function getFinalJudgeRatingHtml() {
    $html = '';
    $html.=$this->htmlBuilder->makeJudgeChoice("jRating",$this->eModel->labelChoice, "judgement");
    $html.="<hr/>";
    if ($this->eModel->useReasons) {
      $html.=$this->htmlBuilder->makeJudgeReason("jReason",$this->eModel->labelReasons, $this->eModel->step4_reasonGuidance);           
    }
    if ($this->eModel->useLikert) {
      $html.=$this->htmlBuilder->makeJudgeLikert($this->eModel->instLikert, $this->eModel->labelLikert);            
    }    
    $html.= "<input id=\"s4nextB\" type=\"Submit\" class=\"buttonBlue\" value=\"Save >> next transcript\" />";
    return $html;    
  }
     
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" constructor and initialisation">

  function __construct($exptId) {
    $this->htmlBuilder = new htmlBuilder();
    $this->tabIndex = 1;   // 
    $this->eModel = new experimentModel($exptId);
  }

  // </editor-fold>
  
}

