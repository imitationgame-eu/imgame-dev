<?php
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls generated from inverted step2 participant 
//  
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/step2inverted/class.iStep2Controller.php');
$permissions = $_GET['permissions'];
$guid = $_GET['uid'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];

// <editor-fold defaultstate="collapsed" desc=" empty">
// </editor-fold>


  function processMessage($_messageType, $_content, $uid, $exptId, $jType) {
    $is2Controller = new iStep2Controller($exptId);
    $msg = null;
    $pptNo = null;
    $stage = null;
    $pageFurniture = null;
    switch ($_messageType) {
      case "istep2Settings": {
        $msg = $is2Controller->getIStep2Settings();
        break;
      }
      case "start" : {
        $restartUID = $_content[0];
        $msg = $is2Controller->getIStep2RespParameters($jType, $restartUID);
        break;
      }
      case "getIStep2Status": {
        $pptNo = $_content[0];
        $respId = $_content[1];
        $igrChosen = $_content[2];
				$qNo = $_content[3];
				$iStep2PageArray = $is2Controller->getIStep2Page($exptId, $jType, $respId, $qNo);
				if ($iStep2PageArray['s2turn']) {
					$msg = sprintf("<message><messageType>istep2Page</messageType>"
							. "<jQ><![CDATA[%s]]></jQ>"
							. "<qNo>%s</qNo><dataQNo>%s</dataQNo>"
							. "</message>", 
							$iStep2PageArray['s2q'], $iStep2PageArray['qNo'], $iStep2PageArray['dataQNo']);
				}
				else {
          // this should show closed message
					$msg = "<message><messageType>istep2Closed</messageType><usePost>0</usePost></message>";
				}
        break;
      }
      case "storeIStep2reply": {
        $qNo = $_content[0];
        $pReply = $_content[1];
        $pptNo = $_content[2];
        $respId = $_content[3];
        $alignment = $_content[4];
        $isAligned = $_content[5];
        $correctedReply = $_content[6];
        
        $iStep2PageArray = $is2Controller->storeIStep2Reply($exptId, $jType, $pptNo, $respId, $qNo, $pReply, $alignment, $isAligned, $correctedReply);
        if ($iStep2PageArray["s2turn"] == true) {
          $msg = sprintf("<message><messageType>istep2Page</messageType><jQ><![CDATA[%s]]></jQ><qNo>%s</qNo></message>", $iStep2PageArray["s2q"], $iStep2PageArray["qNo"]);          
        }
        else {
          $usePost = $is2Controller->getUsePost();
					$msg = "<message><messageType>istep2Done</messageType><usePost>$usePost</usePost></message>";
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
  $retMsg = processMessage($messageType, $content, $guid, $exptId, $jType);
  echo $retMsg;
}
