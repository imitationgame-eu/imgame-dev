<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from  a linked-experiment step4
// monitor and runtime registrationViews
// 
// -----------------------------------------------------------------------------
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/step4/class.linkedStep4Controller.php');
//
//
$permissions = $_GET['permissions'];

  function processMessage() {
    $s4jNo = $_GET['s4jNo'];
    $messageType = $_GET['messageType'];
    $content = isset($_GET['content']) ? $_GET['content'] : '';
    $igNo = isset($_GET['igNo']) ? $_GET['igJNo'] : -1;
    $s4Controller = new step4Controller(327); // 327 is the base experiment for the current linked experiments - 327, 328, 329, 330
    $msg = '';
    switch ($messageType) {
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
        $s4params = $s4Controller->leGetStep4CurrentStatus($s4jNo);
        if ($s4params['status'] == 'done') {
          $usePost = "usePost";
          $msg = sprintf("<message><messageType>s4complete</messageType><usePost>%s</usePost></message>", $usePost);
        }
        else {
          $msg = sprintf("<message>"
            . "<messageType>step4Transcript</messageType>"
            . "<pretenderRight>%s</pretenderRight>"
            . "<jNo>%s</jNo>"
            . "<exptId>%s</exptId>"
            . "<form><![CDATA[%s]]></form>"
            . "</message>", 
            $s4params['pretenderRight'], 
            $s4params['jNo'], 
            $s4params['exptId'], 
            $s4params['transcript']);
        }
        break;
      }
      case "step4storeRating": {
        $s4Controller->storeTranscript($s4jNo, $content);
        $s4params = $s4Controller->leGetStep4CurrentStatus($s4jNo);
        if ($s4params['status'] == 'done') {
          $usePost = "usePost";
          $msg = sprintf("<message><messageType>s4complete</messageType><usePost>%s</usePost></message>", $usePost);
        }
        else {
          $msg = sprintf("<message>"
            . "<messageType>step4Transcript</messageType>"
            . "<pretenderRight>%s</pretenderRight>"
            . "<jNo>%s</jNo>"
            . "<exptId>%s</exptId>"
            . "<form><![CDATA[%s]]></form>"
            . "</message>", 
            $s4params['pretenderRight'], 
            $s4params['jNo'], 
            $s4params['exptId'], 
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
  $retMsg = processMessage();
  echo $retMsg;
}
