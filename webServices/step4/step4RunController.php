<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from step4
// configuration and runtime pages
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/step4/class.step4Controller.php');
$s4jNo = $_GET['s4jNo'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];
$permissions = $_GET['permissions'];
$qNo = isset($_GET['qNo']) ? $_GET['qNo'] : -1;
$actualJNo = isset($_GET['actualJNo']) ? $_GET['actualJNo'] : -1;
$respNo = isset($_GET['respNo']) ? $_GET['respNo'] : -1;
//
//
  function processMessage($_messageType, $_content, $s4jNo, $exptId, $jType, $actualJNo, $respNo, $qNo) {
    $s4Controller = new step4Controller($exptId);
    $msg = '';
    switch ($_messageType) {
      //
      // config and sessions messages 
      //
      case "judgeSummary": {
        //gets list of even or odd links for a specific expt, with completion status
        $html = $s4Controller->getStep4LinkStatus($exptId, $jType);
        $msg = sprintf("<message><messageType>judgeSummary</messageType><config><![CDATA[%s]]></config></message>", $html);
        break;
      }
      //
      // runtime messages
      //
      case "step4RunConnect": {
        // get contentDef
        $html = $s4Controller->getPageFurniture();
        $msg = sprintf("<message><messageType>contentDef</messageType>%s</message>", $html);
        break;
      }   
      case "ratingParams": {
        // get which rating controls need validation
        $html = $s4Controller->getAlignmentandValidationParameters();
        $msg = sprintf("<message><messageType>ratingParams</messageType>%s</message>", $html);        
        break;
      }   
      case "startPage": {
        $msg = "<message><messageType>step4startPage</messageType></message>";
        break;
      }
      case "nextPage": {
        $s4params = $s4Controller->getStep4CurrentStatus($exptId, $jType, $s4jNo);
        switch ($s4params['status']) {
	        case 'done': {
		        $usePost = $s4Controller->getUsePost($exptId) == 1 ? "usePost" : "noPost";
		        $msg = sprintf("<message><messageType>%s</messageType><usePost>%s</usePost></message>", $s4params['status'], $usePost);
	        	break;
	        }
	        default: {
		        $msg = sprintf("<message>"
			        . "<messageType>%s</messageType>"
			        . "<shuffleHalf>%s</shuffleHalf>"
			        . "<pretenderRight>%s</pretenderRight>"
			        . "<actualJNo>%s</actualJNo>"
			        . "<respNo>%s</respNo>"
			        . "<s3respNo>%s</s3respNo>"
			        . "<qNo>%s</qNo>"
			        . "<form><![CDATA[%s]]></form>"
			        . "</message>",
			        $s4params['status'],
			        $s4params['shuffleHalf'],
			        $s4params['pretenderRight'],
			        $s4params['actualJNo'],
			        $s4params['respNo'],
			        $s4params['s3respNo'],
			        $s4params['qNo'],
			        $s4params['transcript']);
	        }
        }
        break;
      }
      case "step4storeRating": {
        $s4Controller->storeTranscript($exptId, $jType, $s4jNo, $_content);
        $s4params = $s4Controller->getStep4CurrentStatus($exptId, $jType, $s4jNo);
        if ($s4params['status'] == 'done') {
          $usePost = $s4Controller->getUsePost($exptId) == 1 ? "usePost" : "noPost";
          $msg = sprintf("<message><messageType>s4complete</messageType><usePost>%s</usePost></message>", $usePost);
        }
        else {
          $msg = sprintf("<message>"
              . "<messageType>step4Transcript</messageType>"
              . "<shuffleHalf>%s</shuffleHalf>"
              . "<pretenderRight>%s</pretenderRight>"
              . "<actualJNo>%s</actualJNo>"
              . "<respNo>%s</respNo>"
              . "<s3respNo>%s</s3respNo>"
              . "<qNo>%s</qNo>"
              . "<form><![CDATA[%s]]></form>"
              . "</message>", 
              $s4params['shuffleHalf'], 
              $s4params['pretenderRight'], 
              $s4params['actualJNo'], 
              $s4params['respNo'], 
              $s4params['s3respNo'], 
              $s4params['qNo'], 
              $s4params['transcript']);
        }
        break;
      }
      default : {
        //uncaught, but pass back to JS
        $msg = "<message><messageType>blank</messageType><content>0</content></message>";
      }
    }
    return $msg;
  }
//
//
////ensure admin
if ($permissions >= 128) {
  // echo $root_path;
  $retMsg = processMessage($messageType, $content, $s4jNo, $exptId, $jType, $actualJNo, $respNo, $qNo);
  echo $retMsg;
}
