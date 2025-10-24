<?php

use DateTime;
// -----------------------------------------------------------------------------
// 
// web service to support AJAX calls from inverted Step2Respondent Monitor
// (accordions and discards)
//  
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid = $_GET['uid'];
$exptId = $_GET['exptId'];
$jType = $_GET['jType'];
$messageType = $_GET['messageType'];
$content = $_GET['content'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$htmlBuilder = new htmlBuilder();

  function getTranscript($userCode) {
    global $igrtSqli;
    global $exptId;
    global $jType;
    $params = explode('_', $userCode);
    $actualJNo = $params[3];
    $restartUID = $params[4];
    if ($restartUID == 0) {
      // probably not correctly initialised
      $pptUid = $params[5];
    }
    $balQry = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'",
        $exptId, $jType, $actualJNo);
    $balResult = $igrtSqli->query($balQry);
    $balRow = $balResult->fetch_object();
    $jNo = $balRow->jNo;
    $dayNo = $balRow->dayNo;
    $sessionNo = $balRow->sessionNo;
    //return "$jNo $dayNo $sessionNo";
    $getQ = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND jType='%s' AND dayNo='%s' "
        . "AND sessionNo='%s' AND jNo='%s' AND canUse=1 ORDER BY qNo ASC",
        $exptId, $jType, $dayNo, $sessionNo, $jNo);
    //$debug.= $getQ;
    $qResult = $igrtSqli->query($getQ);
    $questions = array();
    $replies = array();
    while ($qRow = $qResult->fetch_object()) {
      array_push($questions, $qRow->q);
      // this is an expensive way of querying, BUT is important to avoid duplicate answers 
      // caused by accidental restarts with back-button
      $getSingleResponseQry = sprintf("SELECT * FROM dataSTEP2inverted WHERE exptId='%s' AND jType='%s' "
          . "AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND uid='%s' "
          . "AND qNo='%s'",
          $exptId, $jType, $dayNo, $sessionNo, $jNo, $pptUid, $qRow->qNo);
      $rResult = $igrtSqli->query($getSingleResponseQry);
      //$debug.= $getSingleResponseQry;
      $rRow = $rResult->fetch_object();
      $reply = $rRow->reply;
      array_push($replies, $reply);
    }
    $html = '';
    $qCnt = count($questions);
    for ($i=0; $i<$qCnt; $i++) {
      unset($qParas);
      $qHtml = '';
      $qParas = explode('\n', $questions[$i]);
      foreach($qParas as $q) {
        $qHtml.="<p class=\"s2q\">".$q."</p>";
      }
      unset($rParas);
      $rHtml = '';
      $rParas = explode('\n', $replies[$i]);
      foreach($rParas as $r) {
        $rHtml.="<p class=\"s2r\">".$r."</p>";
      }
      $html.="<div>".$qHtml."</div><div>".$rHtml."</div>";
    }
    return $html;    
  }
  
  function getJudgeDataSet($exptId, $jType, $actualJNo) {
    global $igrtSqli;
    global $htmlBuilder;
    $tintNo = 0;
    $doneRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
        . "actualJNo='%s' AND finished=1 ORDER BY chrono ASC",
        $exptId, $jType, $actualJNo);
    $doneRespResult = $igrtSqli->query($doneRespQry);
    $doneCnt = $doneRespResult->num_rows;
    $startedRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
        . "actualJNo='%s' AND finished=0 AND discarded=0 ORDER BY chrono ASC",
        $exptId, $jType, $actualJNo);
    $startedRespResult = $igrtSqli->query($startedRespQry);
    $startedCnt = $startedRespResult->num_rows;
    $discardedRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
        . "actualJNo='%s' AND discarded=1 ORDER BY chrono ASC",
        $exptId, $jType, $actualJNo);
    $discardedRespResult = $igrtSqli->query($discardedRespQry);
    $discardedCnt = $discardedRespResult->num_rows;
    $html.= "<div class=\"formRow dark\"><table><tr>";
    $html.= sprintf("<td width=\"180px\" class=\"done\">%s finished</td>", $doneCnt);
    $html.= sprintf("<td width=\"180px\" class=\"started\">%s started</td>", $startedCnt);
    $html.= sprintf("<td class=\"discarded\">%s discarded</td>", $discardedCnt);
    $html.= "</tr></table></div>";
    while ($respRow = $doneRespResult->fetch_object()) {
      $tint = $tintNo % 2 == 0 ? "light" : "dark";
      $restartUID = $row->restartUID;
      $userCode = 's2_'.$exptId.'_'.$jType.'_'.$restartUID;
      $resetbuttonId = "reset&".$userCode;
      $buttonHtml = "saved";
      //$startTime = new DateTime($respRow->chrono);
      $startTime = $respRow->chrono;
      $transcript = ""; //getTranscript($respRow->userCode);  for speed, load transcript on the fly later
      $html.= sprintf("<div class=\"s2Resp closed\" id=\"%s\">%s <span class=\"%s\">%s</span> [%s]</div>", 
        $userCode, 
        $userCode, 
        "done",
        "done",
        $respRow->chrono);
      $html.= sprintf("<div class=\"formRow %s\"><table><tr><td class=\"left\" width=\"800\">%s</td><td class=\"right\">%s</td></tr></table></div>",
        $tint,
        $transcript,
        $buttonHtml);              
    }
    while ($respRow = $startedRespResult->fetch_object()) {
      $tint = $tintNo % 2 == 0 ? "dark" : "light";
      $resetbuttonId = "reset&".$respRow->userCode;
      $buttonHtml = $htmlBuilder->makeButton($resetbuttonId, "Discard/ignore", "button");
      //$startTime = new DateTime($respRow->chrono);
      $startTime = $respRow->chrono;
      $transcript = ""; //getTranscript($respRow->userCode);  for speed, load transcript on the fly later
      $html.= sprintf("<div class=\"s2Resp closed\" id=\"%s\">%s <span class=\"%s\">%s</span> [%s]</div>", 
        $respRow->userCode, 
        $respRow->userCode, 
        "started",
        "started",
        $respRow->chrono);
      $html.= sprintf("<div class=\"formRow %s\"><table><tr><td class=\"left\" width=\"800\">%s</td><td class=\"right\">%s</td></tr></table></div>",
        $tint,
        $transcript,
        $buttonHtml);
    }
    while ($respRow = $discardedRespResult->fetch_object()) {
      $tint = $tintNo % 2 == 0 ? "dark" : "light";
      $resetbuttonId = "reset&".$respRow->userCode;
      $buttonHtml = "discarded";
      //$startTime = new DateTime($respRow->chrono);
      $startTime = $respRow->chrono;
      $transcript = ""; //getTranscript($respRow->userCode);  for speed, load transcript on the fly later
      $html.= sprintf("<div class=\"s2Resp closed\" id=\"%s\">%s <span class=\"%s\">%s</span> [%s]</div>", 
        $respRow->userCode, 
        $respRow->userCode, 
        "discarded",
        "discarded",
        $respRow->chrono);
      $html.= sprintf("<div class=\"formRow %s\"><table><tr><td class=\"left\" width=\"800\">%s</td><td class=\"right\">%s</td></tr></table></div>",
        $tint,
        $transcript,
        $buttonHtml);              
    }
    return $html;
  }
  
  function processDiscardPpt($exptId, $jType, $userCode) {
    global $igrtSqli;
    $details = explode('_', $userCode);
    $dsStr = substr($details[3], 2); // remove leading "ds"
    $actualJNo = intval($dsStr);
    $updateQry = sprintf("UPDATE wt_Step2pptStatusInverted SET discarded=1 WHERE userCode='%s'", $userCode);
    $igrtSqli->query($updateQry);
    $label = "s2_".$exptId."_".$jType."_ds".$dsStr;
    $updateBalQry = sprintf("UPDATE wt_Step2BalancerInverted SET respMax=respMax+1 WHERE label='%s'", $label);
    $igrtSqli->query($updateBalQry);
    return getJudgeDataSet($exptId, $jType, $actualJNo);    
  }
  
  function processMessage($exptId, $jType, $messageType, $content) {
    switch ($messageType) {
      case "getTranscript": {
        $userCode = $content[0];
        $html = getTranscript($userCode);
        break;
      }
      case "discardPpt": {
        $userCode = $content[0];
        $html = processDiscardPpt($exptId, $jType, $userCode);
        break;
      }
    }
    return $html;
  }

if ($permissions>=128) {
  //ensure admin
  $retMsg = processMessage($exptId, $jType, $messageType, $content);
  echo $retMsg;
}

