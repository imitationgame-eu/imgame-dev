<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from step2 participant
//  
// -----------------------------------------------------------------------------
error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/step2/class.step2Controller.php');
$permissions = $_GET['permissions'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];

// <editor-fold defaultstate="collapsed" desc=" empty">
// </editor-fold>


  function processMessage($_messageType, $_content, $exptId, $jType) {
    $s2Controller = new step2Controller($exptId);
    $msg = null;
    $pptNo = null;
    $stage = null;
    $pageFurniture = null;
    switch ($_messageType) {
      case "step2Settings": {
        $msg = $s2Controller->getStep2Settings();
        break;
      }
      case "start" : {
        $restartUID = $_content[0];
        $userCode = isset($_content[1]) ? $_content[1] : '';
        $msg = $s2Controller->getStep2RespParameters($jType, $restartUID, $userCode);
        break;
      }
      case "getStep2Status": {
        $pptNo = $_content[0];
        $respId = $_content[1];
        $igrChosen = $_content[2];
				$qNo = $_content[3];
				$step2PageArray = $s2Controller->getStep2Page($exptId, $jType, $respId, $qNo);
				if ($step2PageArray['s2turn']) {
					$msg = sprintf("<message><messageType>step2Page</messageType>"
							. "<jQ><![CDATA[%s]]></jQ>"
							. "<qNo>%s</qNo><dataQNo>%s</dataQNo>"
							. "</message>", 
							$step2PageArray['s2q'], $step2PageArray['qNo'], $step2PageArray['dataQNo']);
				}
				else {
          // this should show closed message
					$msg = "<message><messageType>step2Closed</messageType><usePost>0</usePost></message>";
				}
        break;
      }
      case "storeStep2reply": {
        $qNo = $_content[0];
        $pReply = $_content[1];
        $pptNo = $_content[2];
        $respId = $_content[3];
        $alignment = $_content[4];
        $isAligned = $_content[5];
        $correctedReply = $_content[6];
        
        $step2PageArray = $s2Controller->storeStep2Reply($exptId, $jType, $pptNo, $respId, $qNo, $pReply, $alignment, $isAligned, $correctedReply);
        if ($step2PageArray["s2turn"] == true) {
          $msg = sprintf("<message><messageType>step2Page</messageType><jQ><![CDATA[%s]]></jQ><qNo>%s</qNo></message>", $step2PageArray["s2q"], $step2PageArray["qNo"]);          
        }
        else {
          $usePost = $s2Controller->getUsePost();
					$msg = "<message><messageType>step2Done</messageType><usePost>$usePost</usePost></message>";
        }
        break;
      }
      default : {
        //uncaught, but pass back to JS
        $msg .= "<message><messageType>blank</messageType><content>0</content></message>";
      }
    }
    return $msg;
  }


//ensure admin
if ($permissions >= 128) {
  $retMsg = processMessage($messageType, $content, $exptId, $jType);
  echo $retMsg;
}
