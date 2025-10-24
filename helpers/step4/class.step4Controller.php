<?php
/**
 * Step4 Manager
 * top-level controller to configure, view & run sessions
 * @author MartinHall
 */
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/html/class.htmlBuilder.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/domainSpecific/domainInfo.php';
include_once $root_path.'/helpers/debug/class.debugLogger.php';
include_once $root_path.'/helpers/models/class.experimentModel.php'; 

class step4Controller {
  private $htmlBuilder;   // control builder
  private $tabIndex = 1;
  private $eModel;

  // <editor-fold defaultstate="collapsed" desc=" visible methods for interface (step4RunController.php)">
  
  public function getStep4CurrentStatus($exptId, $jType, $s4jNo) {
    if ($this->eModel->useS4IndividualTurn == 1) {
      $transcriptArray = $this->getTranscriptTurn($exptId, $jType, $s4jNo);
      return $transcriptArray;     
    }
    else {
      $transcriptArray = $this->getWholeTranscript($exptId, $jType, $s4jNo);
      return $transcriptArray;
    }
  }
  
  public function storeTranscript($exptId, $jType, $s4jNo, $content) {
    if ($this->eModel->useS4IndividualTurn == 1) {
      $this->storeIndividualTurn($exptId, $jType, $s4jNo, $content);
    }
    else {
      $this->storeWholeTranscript($exptId, $jType, $s4jNo, $content);
    }
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
    global $igrtSqli;
    $getSQL = "SELECT * FROM edExptStatic_refactor WHERE exptId=$exptId";
    $getResult = $igrtSqli->query($getSQL);
    if ($getResult) {
      $row = $getResult->fetch_object();
      $retValue = $row->step4PostForm;
      return $retValue;
    }
    return false;
  }
  
  public function getAlignmentandValidationParameters() {
    $html = sprintf("<reqLikert>%s</reqLikert>", $this->eModel->useLikert);
    $html.= sprintf("<useS4IndividualTurn>%s</useS4IndividualTurn>", $this->eModel->useS4IndividualTurn);
    $html.= sprintf("<useS4CharacterLimit>%s</useS4CharacterLimit>", $this->eModel->useS4CharacterLimit);
    $html.= sprintf("<s4CharacterLimitValue>%s</s4CharacterLimitValue>", $this->eModel->s4CharacterLimitValue);
    $html.= sprintf("<s4RandomiseSide>%s</s4RandomiseSide>", $this->eModel->s4RandomiseSide);
    $html.= sprintf("<useS4Intention>%s</useS4Intention>", $this->eModel->useS4Intention);
    $html.= sprintf("<useS4IntentionMin>%s</useS4IntentionMin>", $this->eModel->useS4IntentionMin);
    $html.= sprintf("<s4IntentionMin>%s</s4IntentionMin>", $this->eModel->s4IntentionMin);
    $html.= sprintf("<useS4AlignmentControl>%s</useS4AlignmentControl>", $this->eModel->useS4AlignmentControl);
    $html.= sprintf("<useS4QCategoryControl>%s</useS4QCategoryControl>", $this->eModel->useS4QCategoryControl);
    return $html;
  }
  
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" store responses">
  
  private function storeIndividualTurn($exptId, $jType, $s4jNo, $content) {
//      contentArray[0] = surveyFlag; // not now used
//      contentArray[1] = 'haveResponse' OR discarded;
//      contentArray[2] = choice;
//      contentArray[3] = intervalId;
//      contentArray[4] = reason;
//      contentArray[5] = pretenderRight;
//      contentArray[6] = shuffleHalf;
//      contentArray[7] = actualJNo;
//      contentArray[8] = respNo;
//      contentArray[9] = qNo;
//      contentArray[10] = s4IntentionText;
//      contentArray[11] = pAlignment;
//      contentArray[12] = npAlignment;
//      contentArray[13] = categoryChoice;
//      contentArray[14] = s3respNo; 
    global $igrtSqli;
    // decide whether discarded or not
	  $discarded = $content[1] == 'discardedResponse';
		if (!$discarded) {
	// store in dataSTEP4SingleTurns - decision on whether to move to next QS is taken elsewhere
		  $choice = $content[2];
		  $pretenderRight = $content[5];
		  if ($this->eModel->choosingNP == 1) {
			  $correct = ($choice == $pretenderRight) ? 0 : 1;
		  }
		  else {
			  $correct = ($choice == $pretenderRight) ? 1 : 0;
		  }
		  $confidence = $content[3];
		  $udreason = urldecode($content[4]);
		  $reason = $igrtSqli->real_escape_string($udreason);
		  $pretenderRight = $content[5];
		  $shuffleHalf = $content[6];
		  $actualJNo = $content[7];
		  $respNo = $content[8];
		  $qNo = $content[9];
		  $uds4IntentionText = urldecode($content[10]);
		  $s4IntentionText = $igrtSqli->real_escape_string($uds4IntentionText);
		  $pAlignment = substr($content[11], -1);
		  $npAlignment = substr($content[12], -1);
		  $categoryChoice = substr($content[13], -1);
		  $s3respNo = $content[14];
		  $storeQry = sprintf("INSERT INTO dataSTEP4SingleTurns "
			  . "(exptId, jType, s4jNo, actualJNo, "
			  . "respNo, s3respNo, qNo, pAlignment, "
			  . "npAlignment, correct, choice, pretenderRight, "
			  . "shuffleHalf, confidence, reason, intention, "
			  . "categoryChoice) "
			  . "VALUES ("
			  . "'%s', '%s', '%s', '%s', "
			  . "'%s', '%s', '%s', '%s', "
			  . "'%s', '%s', '%s', '%s', "
			  . "'%s', '%s', '%s', '%s', "
			  . "'%s')",
			  $exptId, $jType, $s4jNo, $actualJNo,
			  $respNo, $s3respNo, $qNo, $pAlignment,
			  $npAlignment, $correct, $choice, $pretenderRight,
			  $shuffleHalf, $confidence, $reason, $s4IntentionText,
			  $categoryChoice);
		  $igrtSqli->query($storeQry);

	  }
	  $shuffleHalf = $content[6];
	  $actualJNo = $content[7];
	  $respNo = $content[8];
	  $qNo = $content[9];

    // mark row in wt_TBTStep4datasets - either as discarded (255) because ignored in Step2 review or rated
	  $updateSql = sprintf("UPDATE wt_TBTStep4datasets SET rated='%s' WHERE exptId='%s' AND jType='%s' AND shuffleHalf='%s' AND s4jNo='%s' AND actualJNo='%s' AND respNo='%s' AND qNo='%s'",
		   $content[1] == 'discarded' ? 255 : 1,
				$exptId, $jType, $shuffleHalf, $s4jNo, $actualJNo, $respNo, $qNo);
	  $igrtSqli->query($updateSql);
  }

  private function storeWholeTranscript($exptId, $jType, $s4jNo, $content) {
    global $igrtSqli;
    $getS4Qry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' AND rated=0 ORDER BY actualJNo ASC", $exptId, $jType, $s4jNo);
    //return $getS4Qry;
    $getS4Result = $igrtSqli->query($getS4Qry);
    if ($getS4Result) {
      $shuffleRow = $getS4Result->fetch_object();
      $actualJNo = $shuffleRow->actualJNo;  // step2 judge set
      $s3respNo = $shuffleRow->s3respNo;    // shuffle s2 resp ptr
      $respNo = $shuffleRow->respNo;        // step2 pretender ptr to md_dataStep2reviewed
      $id = $shuffleRow->id;
      // store in dataSTEP4 and then mark transcript as complete in wt_Step3shuffles
      $choice = $content[2];
      $pretenderRight = $content[5];
      if ($this->eModel->choosingNP == 1) {
        $correct = ($choice == $pretenderRight) ? 0 : 1;                
      }
      else {
        $correct = ($choice == $pretenderRight) ? 1 : 0;        
      }
      $confidence = $content[3];
      $reason = urldecode($content[4]);
      $reason = $igrtSqli->real_escape_string($reason);
      $pretenderRight = $content[5];
      $shuffleHalf = $content[6];
      $storeQry = sprintf("INSERT INTO dataSTEP4 (exptId, jType, s4jNo, actualJNo, respNo, s3respNo, correct, choice, pretenderRight, shuffleHalf, confidence, reason) 
          VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $exptId, $jType, $s4jNo, $actualJNo, $respNo, $s3respNo, $correct, $choice, $pretenderRight, $shuffleHalf, $confidence, $reason);
      $igrtSqli->query($storeQry);
      $updateQry = sprintf("UPDATE wt_Step4datasets SET rated=1 WHERE exptId='%s' AND jType='%s' AND id='%s'",
          $exptId, $jType, $id);
      $igrtSqli->query($updateQry);    
    }
    //return 'done';
  }
  
  // </editor-fold>

  // <editor-fold defaultstate="collapsed" desc=" build transcripts and parameters">

  private function getTranscriptTurn($exptId, $jType, $s4jNo) {
    // get available turn - this deals with picking up a previously started S4
    global $igrtSqli;
    $getS4Qry = sprintf("SELECT * FROM wt_TBTStep4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' AND rated=0 ORDER BY jNo ASC, dayNo ASC, sessionNo ASC, qNo ASC", $exptId, $jType, $s4jNo);
    $getS4Result = $igrtSqli->query($getS4Qry);

    if (!is_null($getS4Result)) {
      $shuffleRow = $getS4Result->fetch_object();
      $dayNo = $shuffleRow->dayNo;
      $sessionNo = $shuffleRow->sessionNo;
      $jNo = $shuffleRow->jNo;
      $actualJNo = $shuffleRow->actualJNo;  // step2 judge set
	    $respNo = $shuffleRow->respNo;  // shuffle s2 resp ptr
	    $s3respNo = $shuffleRow->s3respno;  // bad column name in table note s3respno not s3respNo
	    $qNo = $shuffleRow->qNo;
	    $shuffleHalf = $shuffleRow->shuffleHalf;
      $tValues = $this->getSingleTurnTranscriptValues($exptId, $jType, $actualJNo, $s3respNo, $respNo, $dayNo, $sessionNo, $jNo, $qNo);
      switch ($tValues['tStatus']) {
	      case 's4turn': {
		      if ($this->eModel->s4RandomiseSide == "1") {
			      $pretenderRight = mt_rand(0, 1);
		      }
		      else {
			      $pretenderRight = $qNo%2;
		      }
		      $turnHtml = $this->assembleTurn($tValues, $pretenderRight);
		      $ratingHtml = $this->getJudgeRatingHtml();
		      return [
			      'status'=>'step4Transcript',
			      'pretenderRight'=> $pretenderRight,
			      'respNo'=> $respNo,
			      's3respNo'=> $s3respNo,
			      'shuffleHalf'=> $shuffleHalf,
			      'jNo'=> $respNo,
			      'dayNo'=> $dayNo,
			      'sessionNo'=> $sessionNo,
			      'actualJNo' => $actualJNo,
			      'qNo'=> $qNo,
			      'transcript'=> $turnHtml . $ratingHtml
		      ];
	      	break;
	      }
	      default:
	      	return ['status'=> $tValues['tStatus'],
		              'pretenderRight'=> 255,
		              'respNo'=> $respNo,
		              's3respNo'=> $s3respNo,
		              'shuffleHalf'=> $shuffleHalf,
		              'jNo'=> $respNo,
		              'dayNo'=> $dayNo,
		              'sessionNo'=> $sessionNo,
		              'actualJNo' => $actualJNo,
		              'qNo'=> $qNo,
		              'transcript'=> ''
		      ];
      }
    }
    else {
      return ['status' => 'done'];
    }
  }
  
  private function getSingleTurnTranscriptValues($exptId, $jType, $actualJNo, $s3respNo, $respNo, $dayNo, $sessionNo, $jNo, $qNo) {
    global $igrtSqli;
    // get q-pr first, and then build up npr 
    $getTurnQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE "
        . "exptId='%s' AND jType='%s' AND actualJNo='%s' AND respNo='%s' AND qNo='%s'",
        $exptId, $jType, $actualJNo, $respNo, $qNo);
    $turnResult = $igrtSqli->query($getTurnQry);
    if ($turnResult) {
      $turnRow = $turnResult->fetch_object();
      if ($turnRow->canUse != 2) {
	      $q = $turnRow->q;
	      $pr = $turnRow->reply;
	      // need to decide whether to use data from inverted Step2, or natural
	      // igExperiments->useEvenInvertedS2, igExperiments->useOddInvertedS2
	      $useInvertedS2Qry = sprintf("SELECT * FROM edExptStatic_refactor WHERE exptId='%s'", $exptId);
	      $useInvertedS2Result = $igrtSqli->query($useInvertedS2Qry);
	      if ($useInvertedS2Result) {
		      $useInvertedS2Row = $useInvertedS2Result->fetch_object();
		      $useInvertedS2 = $jType == 0 ? $useInvertedS2Row->useEvenInvertedS2 : $useInvertedS2Row->useOddInvertedS2;
	      }
	      $npSrcTable = $useInvertedS2 == 1 ? "dataSTEP2inverted" : "md_dataStep1reviewed";
	      $getNPTurnsQry = sprintf("SELECT * FROM %s WHERE exptId='%s' AND dayNo='%s' AND "
		      . "sessionNo='%s' AND jType='%s' AND jNo='%s' AND qNo='%s'",
		      $npSrcTable, $exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo);
	      $npTurnsResult = $igrtSqli->query($getNPTurnsQry);
	      $npRow = $npTurnsResult->fetch_object();
	      $npr = $useInvertedS2 == 1 ? $npRow->reply : $npRow->npr;
	      return ['tStatus'=>'s4turn', 'q'=>$q, 'pr'=>$pr, 'npr'=>$npr];
      }
      else {
      	return ['tStatus'=>'discarded'];
      }
    }
    else {
      return ['tStatus'=> 'S2missing'];
    }
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
  
  private function getWholeTranscript($exptId, $jType, $s4jNo) {
    global $igrtSqli;
    $getS4Qry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' AND rated=0 AND isVirtual=0 ORDER BY actualJNo ASC", $exptId, $jType, $s4jNo);
    $getS4Result = $igrtSqli->query($getS4Qry);
    if ($getS4Result) {
      $shuffleRow = $getS4Result->fetch_object();
      $dayNo = $shuffleRow->dayNo;
      $sessionNo = $shuffleRow->sessionNo;
      $jNo = $shuffleRow->jNo;
      $actualJNo = $shuffleRow->actualJNo;  // step2 judge set
      $s3respNo = $shuffleRow->s3respNo;  // shuffle s2 resp ptr
      $shuffleHalf = $shuffleRow->shuffleHalf; 
      // need to get actual respNo = s3respNo mappings from wt_step3summaries
      $respNoQry = sprintf("SELECT * FROM wt_Step3summaries WHERE exptId='%s' AND jType='%s' AND actualJNo='%s' AND s3respNo='%s'",
          $exptId, $jType, $actualJNo, $s3respNo);
      $respNoResult = $igrtSqli->query($respNoQry);
      $respNoRow = $respNoResult->fetch_object();      
      $respNo = $respNoRow->respNo;
      $tValues = $this->getTranscriptValues($exptId, $jType, $respNo, $actualJNo, $dayNo, $sessionNo, $jNo  );
      $pretenderRight = (($respNo % 2) == 0) ? 1 : 0;
      $transcriptHtml = $this->assembleTranscript($tValues, $pretenderRight);
      $ratingHtml = $this->getJudgeRatingHtml();
      return [
        'status'=>'step4Transcript',
        'shuffleHalf'=> $shuffleHalf,
        'pretenderRight'=> $pretenderRight,
        'respNo'=> $respNo,
        's3respNo'=> $s3respNo,
        'actualJNo'=> $actualJNo, 
        'qNo'=> -1, 
        'transcript'=> $transcriptHtml . $ratingHtml
      ];     
    }      
    return ['status'=>'done'];
  }
  
  private function getTranscriptValues($exptId, $jType, $respNo, $actualJNo, $dayNo, $sessionNo, $jNo) {
    global $igrtSqli;
    $qList = array();
    $replyListP = array();
    $replyListNP = array();
    // get replies/questions first, and then build up npr 
    $getTurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' "
        . "AND actualJNo='%s' AND respNo='%s' AND canUse=1 ORDER BY qNo ASC", 
        $exptId, $jType, $actualJNo, $respNo);
    $turnsResult = $igrtSqli->query($getTurnsQry);
    while ($turnRow = $turnsResult->fetch_object()) {
      $q = $turnRow->q;
      $pr = $turnRow->reply;
      $qNo = $turnRow->qNo;
      
      // need to decide whether to use data from inverted Step2, or natural
      // igExperiments->useEvenInvertedS2, igExperiments->useOddInvertedS2
      $useInvertedS2Qry = sprintf("SELECT * FROM edExptStatic_refactor WHERE exptId='%s'", $exptId);
      $useInvertedS2Result = $igrtSqli->query($useInvertedS2Qry);
      if ($useInvertedS2Result) {
        $useInvertedS2Row = $useInvertedS2Result->fetch_object();
        $useInvertedS2 = $jType == 0 ? $useInvertedS2Row->useEvenInvertedS2 : $useInvertedS2Row->useOddInvertedS2;
      }
      $npSrcTable = $useInvertedS2 == 1 ? "dataSTEP2inverted" : "md_dataStep1reviewed";
      $getNPTurnsQry = sprintf("SELECT * FROM %s WHERE exptId='%s' AND dayNo='%s' AND "
          . "sessionNo='%s' AND jType='%s' AND jNo='%s' AND qNo='%s'", 
          $npSrcTable, $exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo);
      $npTurnsResult = $igrtSqli->query($getNPTurnsQry);
      $npRow = $npTurnsResult->fetch_object();
      $npr = $useInvertedS2 == 1 ? $npRow->reply : $npRow->npr;
      array_push($qList, array('q' => $q));
      array_push($replyListP, array('pr' => $pr));      
      array_push($replyListNP, array('npr' => $npr));
    }
    $retArray = array(
      'qArray' => $qList,
      'pArray' => $replyListP,
      'npArray' => $replyListNP
    );
    return $retArray;
  }

  private function assembleTranscript($tValues, $pretenderRight) {
    $html = '<div class=\"s4transcript\">';
    $turnNo = count($tValues['qArray']);
    for ($i=0; $i<$turnNo; $i++) {
      $q = $tValues['qArray'][$i]['q'];
      $pr = $tValues['pArray'][$i]['pr'];
      $npr = $tValues['npArray'][$i]['npr'];
      $html.= "<div class=\"previousQuestions\"><strong>";
      $paraQ = explode('\n', $q);
      foreach ($paraQ as $pq) {
        $html.= "<p>".$pq."</p>";
      }
      $html.= "</strong></div>";
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
    }
    $html.= "</div>";
    return $html;
  }
  
  private function getFullTranscript($exptId, $jType, $s4jNo) {
    global $igrtSqli;
    $html = '';
    $getS4Qry = sprintf("SELECT * FROM wt_Step3shuffles WHERE exptId='%s' AND jType='%s' AND s4jNo='%s' ORDER BY s4jNo ASC, datasetPtr ASC", $exptId, $jType, $s4jNo);
    $getS4Result = $igrtSqli->query($getS4Qry);
    if ($getS4Result) {
      $html.= "<div class=\"judgeDetails\">";
      while ($shuffleRow = $getS4Result->fetch_object()) {
        $dayNo = $shuffleRow->dayNo;
        $sessionNo = $shuffleRow->sessionNo;
        $jNo = $shuffleRow->jNo;
        $datasetNo = $shuffleRow->datasetNo;    // step2 judge set
        $respNo = $shuffleRow->respNo;          // step2 pretender#
        $pptNo = $shuffleRow->pptNo;
        $dsLabel = $datasetNo + 1;
        $rnLabel = $respNo + 1;
        $html.= "s4_$s4jNo"."_$dsLabel"."_$rnLabel"."_$pptNo<br />";
        $qList = array();
        $replyListP = array();
        $replyListNP = array();
        $getTurnsQry = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId='%s' AND jType='%s' AND pptNo='%s' ORDER BY qNo ASC", $exptId, $jType, $pptNo);
        $turnsResult = $igrtSqli->query($getTurnsQry);
        while ($turnRow = $turnsResult->fetch_object()) {
          array_push($qList, array('q' => $turnRow->q));
          array_push($replyListP, array('pr' => $turnRow->reply));
        }
        $getNPTurnsQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s' AND jNo='%s' ORDER BY qNo ASC", $exptId, $dayNo, $sessionNo, $jType, $jNo);
        $npTurnsResult = $igrtSqli->query($getNPTurnsQry);
        while ($npturnRow = $npTurnsResult->fetch_object()) {
          array_push($replyListNP, array('npr' => $npturnRow->npr));
        }
        for ($i=0; $i<count($qList); $i++) {
          $q = $qList[$i]['q'];
          $pr = $replyListP[$i]['pr'];
          $npr = $replyListNP[$i]['npr'];
          $html.= "<div class=\"previousQuestions\">$q</div>";
          $html.= "<div class=\"responseOne\">$pr</div>";
          $html.= "<div class=\"responseTwo\">$npr</div>";
        }
      }      
      $html.= "</div>";
    }
    else {
      $html.= "<div class=\"judgeDetails\">no transcript details</div>";
    }
    return $html;
  }
  
  private function getJudgeRatingHtml() {
    $html = '';
    if ($this->eModel->useS4AlignmentControl) {
      $html.= $this->htmlBuilder->makeS4JudgeAlignmentOptions($this->eModel);     
    }
    $html.=$this->htmlBuilder->makeJudgeChoice("jRating",$this->eModel->labelChoice, "judgement");
    $html.="<hr/>";
    if ($this->eModel->useS4Intention) {
      $html.= $this->htmlBuilder->makeS4IntentionControl($this->eModel);     
    }
    if ($this->eModel->useReasons) {
      $html.=$this->htmlBuilder->makeJudgeReason("jReason",$this->eModel->labelReasons, $this->eModel->step4_reasonGuidance);           
    }
    if ($this->eModel->useLikert) {
      $html.=$this->htmlBuilder->makeJudgeLikert($this->eModel->instLikert, $this->eModel->labelLikert);            
    }    
    if ($this->eModel->useS4QCategoryControl == 1) {
      $html.= $this->htmlBuilder->makeS4AlignmentCategoryLikert($this->eModel);
    }
    $html.= "<input id=\"s4nextB\" type=\"Submit\" class=\"buttonBlue\" value=\"Save >> next transcript\" />";
    return $html;
  }
     
  // </editor-fold>
  
  // <editor-fold defaultstate="collapsed" desc=" constructor and initialisation">

  function __construct($exptId) {
    $this->htmlBuilder = new htmlBuilder();
    $this->tabIndex = 1;   // 
    $this->logger = new debugLogger();
    $this->eModel = new experimentModel($exptId);
  }

  // </editor-fold>
  
}

