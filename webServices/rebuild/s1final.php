<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';     
  include_once $root_path.'/helpers/models/class.experimentModel.php';
  include_once $root_path.'/helpers/html/class.htmlBuilder.php';       // access to html builder
  
  
    function getPageFurniture($exptId) {
      $eModel = new experimentModel($exptId);
      $xml=sprintf("<message><messageType>pageFurniture</messageType>"
          . "<rFinalMsg>%s</rFinalMsg>"
          . "<rYourAnswer>%s</rYourAnswer>"
          . "<rHistoryTitle>%s</rHistoryTitle>"
          . "<npTab>%s</npTab>"
          . "<pTab>%s</pTab>"
          . "<rTabActive>%s</rTabActive>"
          . "<rTabInactive>%s</rTabInactive>"
          . "<rTabWaiting>%s</rTabWaiting>"
          . "<rWaitNext>%s</rWaitNext>"
          . "<rWaitFirst>%s</rWaitFirst>"
          . "<rCurrentQ>%s</rCurrentQ>"
          . "<rInstruction>%s</rInstruction>"
          . "<rSendB>%s</rSendB>"
          . "<rGuidanceHeader>%s</rGuidanceHeader>"
          . "<npGuidance>%s</npGuidance>"
          . "<pGuidance>%s</pGuidance>"
          . "<rTabDone>%s</rTabDone>"
          . "<jTab>%s</jTab>"
          . "<jTabUnconnected>%s</jTabUnconnected>"
          . "<jTabWaiting>%s</jTabWaiting>"
          . "<jTabActive>%s</jTabActive>"
          . "<jTabRating>%s</jTabRating>"
          . "<jTabDone>%s</jTabDone>"
          . "<jWaitingToStart>%s</jWaitingToStart>"
          . "<jPleaseAsk>%s</jPleaseAsk>"
          . "<jAskButton>%s</jAskButton>"
          . "<jWaitingForReplies>%s</jWaitingForReplies>"
          . "<jHistoryTitle>%s</jHistoryTitle>"
          . "<jRatingTitle>%s</jRatingTitle>"
          . "<jFinalRatingTitle>%s</jFinalRatingTitle>"
          . "<jRatingYourQuestion>%s</jRatingYourQuestion>"
          . "<jRatingQ>%s</jRatingQ>"
          . "<jRatingR1>%s</jRatingR1>"
          . "<jRatingR2>%s</jRatingR2>"
          . "<jAskAnotherB>%s</jAskAnotherB>"
          . "<jNoMoreB>%s</jNoMoreB>"
          . "<jSaveFinalB>%s</jSaveFinalB>"
          . "<jFinalMsg>%s</jFinalMsg>"
          . "<jConfirmHead>%s</jConfirmHead>"
          . "<jConfirmBody>%s</jConfirmBody>"
          . "<jConfirmOK>%s</jConfirmOK>"
          . "<jConfirmCancel>%s</jConfirmCancel>"
          . "</message>",
          $eModel->rFinalMsg,
          $eModel->rYourAnswer,
          $eModel->rHistoryTitle,
          $eModel->npTab,
          $eModel->pTab,
          $eModel->rTabActive,
          $eModel->rTabInactive,
          $eModel->rTabWaiting,
          $eModel->rWaitNext,
          $eModel->rWaitFirst,
          $eModel->rCurrentQ,
          $eModel->rInstruction,
          $eModel->rSendB,
          $eModel->rGuidanceHeader,
          $eModel->npGuidance,
          $eModel->pGuidance,
          $eModel->rTabDone,
          $eModel->jTab,
          $eModel->jTabUnconnected,
          $eModel->jTabWaiting,
          $eModel->jTabActive,
          $eModel->jTabRating,
          $eModel->jTabDone,
          $eModel->jWaitingToStart,
          $eModel->jPleaseAsk,
          $eModel->jAskButton,
          $eModel->jWaitingForReplies,
          $eModel->jHistoryTitle,
          $eModel->jRatingTitle,
          $eModel->jFinalRatingTitle,
          $eModel->jRatingYourQuestion,
          $eModel->jRatingQ,
          $eModel->jRatingR1,
          $eModel->jRatingR2,
          $eModel->jAskAnotherB,
          $eModel->jNoMoreB,
          $eModel->jSaveFinalB,
          $eModel->jFinalMsg,
          $eModel->jConfirmHead,
          $eModel->jConfirmBody,
          $eModel->jConfirmOK,
          $eModel->jConfirmCancel
      );
      return $xml;      
    }
    
    function getUidFromEmail($emailNo) {
      global $igrtSqli;
      $email = $emailNo . '@s1.com';
      $sql = sprintf("SELECT * FROM igUsers WHERE email='%s'", $email);
      $result = $igrtSqli->query($sql);
      if ($result) {
        $row = $result->fetch_object();
        return $row->id;
      }
      return -1;
    }

    function getFinalRating($exptId, $emailNo) {
      global $igrtSqli;
      $eModel = new experimentModel($exptId);
      $jhtmlBuilder = new htmlBuilder();
      $uid = getUidFromEmail($emailNo);
      if ($uid > 0) {
        $history = [];
        $jfrhtml = "";
        $sql = sprintf("SELECT * FROM dataSTEP1 WHERE uid='%s' AND npr!='FINAL' ORDER BY qNo ASC", $uid);
        //echo $sql;
        $result = $igrtSqli->query($sql);
        if ($result) {
          while ($row = $result->fetch_object()) {
            $det=array(
              'jQuestion'=>$row->q,
              'npReply'=>$row->npr,
              'pReply'=>$row->pr,
            );
            array_push($history,$det);
          }               
        }
        if ($uid%2==0) {
          // counter-balance left/right responses
          foreach ($history as $v) {
            $jfrhtml.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
            $jfrhtml.=sprintf("<p><span>%s</span>%s</p>", $eModel->jRatingQ, $v['jQuestion']);
            $jfrhtml.=sprintf("<div class=\"responseOne\"><h3>%s </h3><p>%s</p></div>", $eModel->jRatingR1, $v['npReply']);
            $jfrhtml.=sprintf("<div class=\"responseTwo\"><h3>%s </h3><p>%s</p></div>", $eModel->jRatingR2, $v['pReply']);
            $jfrhtml.='</div>';
          }
        }
        else {
          foreach ($history as $v) {
            $jfrhtml.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
            $jfrhtml.=sprintf("<p><span>%s</span>%s</p>",$eModel->jRatingQ ,$v['jQuestion']);
            $jfrhtml.=sprintf("<div class=\"responseOne\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR1, $v['pReply']);
            $jfrhtml.=sprintf("<div class=\"responseTwo\"><h3>%s </h3><p>%s</p></div>",$eModel->jRatingR2, $v['npReply']);
            $jfrhtml.='</div>';
          }
        }
        $jfrhtml.= $jhtmlBuilder->makeJudgeFinalChoice("judgesFinalChoice",$eModel->labelChoiceFinalRating,"finalJudgement");
        $jfrhtml.= $jhtmlBuilder->makeJudgeFinalReason("judgesMainReason",$eModel->labelReasonFinalRating);           
        $jfrhtml.= $jhtmlBuilder->makeFinalJudgeLikert($eModel->instFinalLikert,$eModel->labelFinalLikert);        
      }

      $xml=sprintf("<message><messageType>fRating</messageType>"
          . "<uid>%s</uid>"
          . "<jFinalRatingHtml><![CDATA[%s]]></jFinalRatingHtml>"
          . "</message>",
          $uid, $jfrhtml
      );
      return $xml;
    }
    
    function buildRatingHtml() {
  //build j Final Rating HTML
  $jfrhtml = $HtmlBuilder->makeJudgeFinalChoice("judgesFinalChoice",$eModel->labelChoiceFinalRating,"finalJudgement");
  if ($eModel->useReasonFinalRating) {
    $jfrhtml.=$HtmlBuilder->makeJudgeFinalReason("judgesMainReason",$eModel->labelReasonFinalRating);           
  }
  if ($eModel->useFinalLikert) {
    $jfrhtml.=$HtmlBuilder->makeFinalJudgeLikert($eModel->instFinalLikert,$eModel->labelFinalLikert);
  }
  $jSonOut['jFinalRatingHtml'] = $jfrhtml;

      
    }
  
  
  $exptId = $_POST['exptId'];
  $emailNo = $_POST['emailNo'];
  $msgType = $_POST['msgType'];
  $xml = "<message><messageType>NOOP</messageType></message>";
  switch ($msgType) {
    case 'init' : {
      $xml = getPageFurniture($exptId);
      break;
    }
    case 'fRating' : {
      $xml = getFinalRating($exptId, $emailNo);
      break;
    }
  }
  echo $xml;    

  

