<?php

use DateTime;
// -----------------------------------------------------------------------------
// 
// web service to list detail of inverted STEP2 balancer datasets 
// that are in progress
//  
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$exptId=$_GET['exptId'];
$jType=$_GET['jType'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$htmlBuilder = new htmlBuilder();


if ($permissions>=128) {
  $oddS1Label = 'odd';
  $evenS1Label = 'even';
  $exptDetailsQry = sprintf("SELECT * FROM igExperiments WHERE exptId='%s'", $exptId);
  $exptDetailsResult = $igrtSqli->query($exptDetailsQry);
  if ($exptDetailsResult) {
    $exptDetails = $exptDetailsResult->fetch_object();
    $oddS1Label = $exptDetails->oddS1Label;
    $evenS1Label = $exptDetails->evenS1Label;
  }
  $operationLabel = ($jType==0) ? "$evenS1Label responding naturally as $evenS1Label" : "$oddS1Label responding naturally as $oddS1Label";
  $qListQry = sprintf("SELECT * FROM wt_Step2BalancerInverted WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $exptId, $jType);
  $qResult = $igrtSqli->query($qListQry);
  $html = "no inverted Step2 respondents started";
  if ($qResult) {
    $html = "<h2>step2-NP datasets $operationLabel</h2>";
    while ($qdsRow = $qResult->fetch_object()) {
      $html.= sprintf("<br/><div id=\"dsHeader_%s\">",$qdsRow->label);
      $html.= sprintf("<h2>%s</h2></div>", $qdsRow->label);
      if ($qdsRow->respCount > 0) {
        $html.= "<div>";  // opening of container underneath header - not used if no repsondents for this header
        $tintNo = 0;
        $doneRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
            . "actualJNo='%s' AND finished=1 ORDER BY chrono ASC",
            $exptId, $jType, $qdsRow->actualJNo);
        $doneRespResult = $igrtSqli->query($doneRespQry);
        $doneCnt = $doneRespResult->num_rows;
        $startedRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
            . "actualJNo='%s' AND finished=0 AND discarded=0 ORDER BY chrono ASC",
            $exptId, $jType, $qdsRow->actualJNo);
        $startedRespResult = $igrtSqli->query($startedRespQry);
        $startedCnt = $startedRespResult->num_rows;
        $discardedRespQry = sprintf("SELECT * FROM wt_Step2pptStatusInverted WHERE exptId='%s' AND jType='%s' AND "
            . "actualJNo='%s' AND discarded=1 ORDER BY chrono ASC",
            $exptId, $jType, $qdsRow->actualJNo);
        $discardedRespResult = $igrtSqli->query($discardedRespQry);
        $discardedCnt = $discardedRespResult->num_rows;
        if ($qdsRow->closed == 1) {
          $closedHtml = "closed";
        }
        else {
          $closedHtml = "open";        
        }
        $html.= "<div class=\"formRow dark\"><table><tr>";
        $html.= sprintf("<td width=\"130px\" >%s</td>", $closedHtml);
        $html.= sprintf("<td width=\"130px\" class=\"done\">%s finished</td>", $doneCnt);
        $html.= sprintf("<td width=\"130px\" class=\"started\">%s started</td>", $startedCnt);
        $html.= sprintf("<td class=\"discarded\">%s discarded</td>", $discardedCnt);
        $html.= "</tr></table></div>";
        while ($respRow = $doneRespResult->fetch_object()) {
          $restartUID = $respRow->restartUID;
          if ($restartUID == 0) {
            // add id to ppt code in special cases of early inverted experiments where restartUID not correctly initialised
            $pptCode = 's2_'.$exptId.'_'.$jType.'_'.$qdsRow->actualJNo.'_'.$restartUID.'_'.$respRow->id;
          }
          else {
            $pptCode = 's2_'.$exptId.'_'.$jType.'_'.$qdsRow->actualJNo.'_'.$restartUID;
          }
          $tint = $tintNo % 2 == 0 ? "light" : "dark";
          $resetbuttonId = "reset&".$pptCode;
          $buttonHtml = "saved";
          //$startTime = new DateTime($respRow->chrono);
          $startTime = $respRow->chrono;
          $transcript = ""; //getTranscript($respRow->userCode);  for speed, load transcript on the fly later
          $html.= sprintf("<div class=\"s2Resp closed\" id=\"%s\">%s <span class=\"%s\">%s</span> [%s]</div>", 
            $pptCode, 
            $pptCode, 
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
        $html.= "</div>";
      }
    }
  }
  echo $html;
}

