<?php
  ini_set('display_errors', 'On');
  error_reporting(E_ALL); 
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
  include_once $root_path.'/domainSpecific/mySqlObject.php';        
  include_once $root_path.'/helpers/models/class.experimentModel.php';       
  include_once $root_path.'/helpers/html/class.htmlBuilder.php';       // access to html builder
  
    function makeHistory($npLeft, $reverseHistory) {
      global $eModel;
      $j_html = '<div></div>';
      if ($npLeft == 1) {
        // counter-balance left/right responses
        foreach ($reverseHistory as $v) {
          $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
          $j_html.=sprintf("<p><span>%s</span>%s</p>", $eModel->jRatingQ, $v['jQuestion']);
          $j_html.=sprintf("<div class=\"responseOne\"><h3>%s</h3><p>%s</p></div>", $eModel->jRatingR1, $v['npReply']);
          $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s</h3><p>%s</p></div>", $eModel->jRatingR2, $v['pReply']);
          $j_html.='</div>';
//          --$lastNumber;
        }
      }
      else {
        foreach ($reverseHistory as $v) {
          $j_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
          $j_html.=sprintf("<p><span>%s</span>%s</p>",$eModel->jRatingQ, $v['jQuestion']);
          $j_html.=sprintf("<div class=\"responseOne\"><h3>%s</h3><p>%s</p></div>",$eModel->jRatingR1, $v['pReply']);
          $j_html.=sprintf("<div class=\"responseTwo\"><h3>%s</h3><p>%s</p></div>",$eModel->jRatingR2, $v['npReply']);
          $j_html.='</div>';
//          --$lastNumber;
        }
      } 
      return $j_html;
    }
    
    function makeFullTranscript($npLeft, $finalHistory) {
      global $eModel;
      $fj_html = '<div></div>';
      if ($npLeft == 1) {
        // counter-balance left/right responses
        foreach ($finalHistory as $v) {
          $fj_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
          $fj_html.=sprintf("<p><span>%s</span>%s</p>", $eModel->jRatingQ, $v['jQuestion']);
          $fj_html.=sprintf("<div class=\"responseOne\"><h3>%s</h3><p>%s</p></div>", $eModel->jRatingR1, $v['npReply']);
          $fj_html.=sprintf("<div class=\"responseTwo\"><h3>%s</h3><p>%s</p></div>", $eModel->jRatingR2, $v['pReply']);
          $fj_html.='</div>';
        }
      }
      else {
        foreach ($finalHistory as $v) {
          $fj_html.='<div class="previousQuestion"><p>.....................................................................................................................................................................</p>';
          $fj_html.=sprintf("<p><span>%s</span>%s</p>",$eModel->jRatingQ, $v['jQuestion']);
          $fj_html.=sprintf("<div class=\"responseOne\"><h3>%s</h3><p>%s</p></div>",$eModel->jRatingR1, $v['pReply']);
          $fj_html.=sprintf("<div class=\"responseTwo\"><h3>%s</h3><p>%s</p></div>",$eModel->jRatingR2, $v['npReply']);
          $fj_html.='</div>';
        }
      }
      return $fj_html;
    }
  
  $uid = $_POST['userid'];
  $exptId = $_POST['experimentID'];
  $groupNo = $_POST['groupNo'];
  $jType = -1;
  $eModel = new experimentModel($exptId);
  $HtmlBuilder=new htmlBuilder();
  
  // initial all items in array with '.' so that not null, which will upset xmlDoc in .js
  $jSonOut = array (
    'historyHtml'=>'.',
    'jRatingHtml'=>'.',
    'npLeft'=>'.',
    'jFinalRatingHtml'=>'.',
    'jState'=>'.',
    'groupNo'=> $groupNo,
    'qNo'=> -1,
    'jQ'=>'.',
    'lContent'=>'.',
    'rContent'=>'.',
    'useLikert'=>'.',
    'useFinalLikert'=>'.',
    'useReasons'=>'.',
    'useReasonFinalRating'=>'.',
  );
  // get history
  $reverseHistory = array();
  $sql = sprintf("SELECT * FROM dataClassic WHERE owner='%s' AND npA>'' AND pA>'' ORDER BY insertDT DESC",$uid);
  $result = $igrtSqli->query($sql);
  if ($result) {
    while ($row=$result->fetch_object()) {
      $det=array(
        'jQuestion'=>$row->jQ,
        'npReply'=>$row->npA,
        'pReply'=>$row->pA,
      );
      array_push($reverseHistory, $det);
    }               
  } 
  $lastNumber = count($reverseHistory); // lastNumber is number of completed turns
  $finalHistory = array_reverse($reverseHistory);
  // use side randomisation or older style uid randomisation as appropriate
  if ($eModel->randomiseSideS1 == 1) {
    $j_html = '.';
    $npLeft = mt_rand(0, 1);
  }
  else {
    $npLeft = $uid % 2 == 0 ? 1 : 0;
    $j_html = makeHistory($npLeft, $reverseHistory);
  }
  $fj_html = makeFullTranscript($npLeft, $finalHistory);
  $jSonOut['qNo'] = $lastNumber;
  $jSonOut['npLeft'] = $npLeft;
  $jSonOut['historyHtml'] = $j_html;
  $jSonOut['finalTranscript'] = $fj_html;
  // get current values
  // get np and p replies
  $sql = sprintf("SELECT * FROM dataClassic WHERE owner='%s' ORDER BY insertDT DESC",$uid);
  $result=$igrtSqli->query($sql);
  if ($result) {
    $row = $result->fetch_object(); // most recent is top of the list
    if ($row->jQ > '') {$jSonOut['jQ']=$row->jQ;}
    if ($npLeft == 1) {
      if ($row->npA > '') {$jSonOut['lContent']=$row->npA;}
      if ($row->pA > '') {$jSonOut['rContent']=$row->pA;}
    }
    else {
      if ($row->pA > '') {$jSonOut['lContent']=$row->pA;}
      if ($row->npA > '') {$jSonOut['rContent']=$row->npA;}
    }    
  }
  // get l & r 
  $sql = sprintf("SELECT * FROM igActiveClassicUsers WHERE uid='%s'",$uid);
  $currentResult = $igrtSqli->query($sql);
  if ($currentResult) {
    $row = $currentResult->fetch_object();
    switch ($row->jState) {
      case 0: {
        $jSonOut['jState']='active';
        break;
      }
      case 1: {
        ++$jSonOut['qNo'];  // take account of incomplete turn
        // check whether both respondents have now replied
        $sqlBothCheck=sprintf("SELECT * FROM igActiveClassicUsers WHERE "
            . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND groupNo='%s' AND respState='2'",
            $row->exptId, $row->dayNo, $row->sessionNo, $groupNo);
        $bcResult=$igrtSqli->query($sqlBothCheck);
        if ($bcResult->num_rows == 2) {
          $jSonOut['jState']='rating';
          // push 2 back into db, even though it should have been done by respPost
          $sqlBoth=sprintf("UPDATE igActiveClassicUsers SET jState='2' WHERE "
              . "uid='%s' AND exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND role='J'",
              $uid, $row->exptId, $row->dayNo, $row->sessionNo);
          $igrtSqli->query($sqlBoth);
        } 
        else {
          $jSonOut['jState']='waiting';
        }
      break;
      }
      case 2: {
        $jSonOut['jState']='rating';
        break;
      }
      case 3: {
        $jSonOut['jState']='finalRating';
        break;
      }
    }    
  }
  // set values for optional rating parameters required for client-side use and validation
	$jSonOut['useLikert'] = ($eModel->useLikert)?1:0;
	$jSonOut['useFinalLikert'] = ($eModel->useFinalLikert)?1:0;
$jSonOut['useReasonFinalRating'] = ($eModel->useReasonFinalRating)?1:0;
$jSonOut['useS1AlignmentControl'] = ($eModel->useS1AlignmentControl)?1:0;
  //build j Rating HTML
  $jrHtml = $HtmlBuilder->makeJudgeChoice("jRating",$eModel->labelChoice,"judgement");
if ($eModel->useS1AlignmentControl) {
	$jrHtml.= $HtmlBuilder->makeJudgeAlignmentOptions($eModel, $jType);
}
  if ($eModel->useLikert) {
    $jrHtml.=$HtmlBuilder->makeJudgeLikert($eModel->instLikert,$eModel->labelLikert);            
  }
  $jrHtml.=$HtmlBuilder->makeJudgeReason("jReason",$eModel->labelReasons, '');           
  $jSonOut['jRatingHtml'] = $jrHtml;
  if ($eModel->useS1AlignmentControl) {
  	$jrHtml.= $HtmlBuilder->makeJudgeAlignmentOptions($eModel, $jType);
	}
  //build j Final Rating HTML
  $jfrhtml = $HtmlBuilder->makeJudgeFinalChoice("judgesFinalChoice",$eModel->labelChoiceFinalRating,"finalJudgement");
  if ($eModel->useReasonFinalRating) {
    $jfrhtml.=$HtmlBuilder->makeJudgeFinalReason("judgesMainReason",$eModel->labelReasonFinalRating);           
  }
  if ($eModel->useFinalLikert) {
    $jfrhtml.=$HtmlBuilder->makeFinalJudgeLikert($eModel->instFinalLikert,$eModel->labelFinalLikert);
  }
  $jSonOut['jFinalRatingHtml'] = $jfrhtml;

  $xml=sprintf("<message><messageType>jStateUpdate</messageType>"
      . "<jState>%s</jState>"
      . "<npLeft>%s</npLeft>"
      . "<randomiseSideS1>%s</randomiseSideS1>"
      . "<historyHtml><![CDATA[%s]]></historyHtml>"
      . "<jRatingHtml><![CDATA[%s]]></jRatingHtml>"
      . "<jFinalRatingHtml><![CDATA[%s]]></jFinalRatingHtml>"
	  . "<jQ><![CDATA[%s]]></jQ>"
	  . "<qNo>%s</qNo>"
      . "<lContent><![CDATA[%s]]></lContent>"
      . "<rContent><![CDATA[%s]]></rContent>"
      . "<finalTranscript><![CDATA[%s]]></finalTranscript>"
		. "<useLikert>%s</useLikert>"
		. "<useFinalLikert>%s</useFinalLikert>"
      . "<useReasonFinalRating>%s</useReasonFinalRating>"
      . "</message>",
      $jSonOut['jState'],
      $jSonOut['npLeft'],
      $eModel->randomiseSideS1,
      $jSonOut['historyHtml'],
      $jSonOut['jRatingHtml'],
      $jSonOut['jFinalRatingHtml'],
	  $jSonOut['jQ'],
	  $jSonOut['qNo'],
      $jSonOut['lContent'],
      $jSonOut['rContent'],
      $jSonOut['finalTranscript'],
		$jSonOut['useLikert'],
		$jSonOut['useFinalLikert'],
      $jSonOut['useReasonFinalRating']
  );
  echo $xml;    

