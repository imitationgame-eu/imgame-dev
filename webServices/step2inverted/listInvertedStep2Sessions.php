<?php
// -----------------------------------------------------------------------------
// web service to list inverted STEP 2 datasets that are ready for injection into
// Knockout-JS Step2 reviewer 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
$htmlBuilder = new htmlBuilder();

if ($permissions>=128) {
  $html = "<p>No completed inverted step2 sessions exist.</p>";
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM dataSTEP2inverted ORDER BY exptId DESC";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult->numd_rows > 0) {
    $html = "<table><tr><th>expt #</th><th>title</th><th>Odd</th><th>Even</th></tr>";
    while ($uidRow = $uidResult->fetch_object()) {
      $title = 'not defined';
      $oddS1Label = 'odd';
      $evenS1Label = 'even';
      $exptId = $uidRow->exptId;
//      // get expt title, and attach to day, session etc
      $titleSql = sprintf("SELECT * FROM igExperiments WHERE exptId='%s'", $exptId);
      $titleResult = $igrtSqli->query($titleSql);
      if ($titleResult) {
        $titleRow = $titleResult->fetch_object();
        $title = $titleRow->title;
        $oddS1Label = $titleRow->oddS1Label;
        $evenS1Label = $titleRow->evenS1Label;
      }
      // $reviewedOdd and $reviewedEven will be used to defined button action and label
      // 0 - disable, not ready for review
      // 1 - raw data ready - first review
      // 2 - reload reviewed data

      $uniqueOddPPTQry = "SELECT actualJNo FROM wt_Step2BalancerInverted WHERE exptId=$exptId AND jType=1 ORDER BY actualJNo ASC";
      $upptOddResult = $igrtSqli->query($uniqueOddPPTQry);
      $expectedOddCnt = $upptOddResult->num_rows;
      $reviewedOddQry = "SELECT DISTINCT(actualJNo) FROM md_invertedStep2reviewed WHERE exptId=$exptId AND jType=1";
      $reviewedOddResult = $igrtSqli->query($reviewedOddQry);
      $reviewedOdd = (($reviewedOddResult->num_rows == $expectedOddCnt) && ($expectedOddCnt > 0)) ? 2 : 0;
      if ($reviewedOdd == 0) {
        $rawOddCnt = 0;
        $rawOddDayQry = "SELECT DISTINCT(dayNo) FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=1";
        $rawOddDayResult = $igrtSqli->query($rawOddDayQry);
        if ($rawOddDayResult) {
          $rawOddDayRow = $rawOddDayResult->fetch_object();
          $dayNo = $rawOddDayRow->dayNo;
          $rawOddSessionQry = "SELECT DISTINCT(sessionNo) FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=1 AND dayNo=$dayNo";
          $rawOddSessionResult = $igrtSqli->query($rawOddSessionQry);
          if ($rawOddSessionResult) {
            $rawOddSessionRow = $rawOddSessionResult->fetch_object();
            $sessionNo = $rawOddSessionRow->sessionNo;
            $oddJCntQry = "SELECT DISTINCT(jNo) FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=1 AND dayNo=$dayNo AND sessionNo=$sessionNo";
            $oddJCntResult = $igrtSqli->query($oddJCntQry);
            $rawOddCnt+= $oddJCntResult->num_rows;
          }
        }
        if ($rawOddCnt > 0) { $reviewedOdd = 1; }
      }

      $uniqueEvenPPTQry = "SELECT actualJNo FROM wt_Step2BalancerInverted WHERE exptId=$exptId AND jType=0 ORDER BY actualJNo ASC";
      $upptEvenResult = $igrtSqli->query($uniqueEvenPPTQry);
      $expectedEvenCnt = $upptEvenResult->num_rows;
      $reviewedEvenQry = "SELECT DISTINCT(actualJNo) FROM md_invertedStep2reviewed WHERE exptId=$exptId AND jType=0";
      $reviewedEvenResult = $igrtSqli->query($reviewedEvenQry);
      $reviewedEven = (($reviewedEvenResult->num_rows == $expectedEvenCnt) && ($expectedEvenCnt > 0)) ? 2 : 0;
      if ($reviewedEven == 0) {
        $rawEvenCnt = 0;
        $rawEvenDayQry = "SELECT DISTINCT(dayNo) AS dayNo FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=0";
        $rawEvenDayResult = $igrtSqli->query($rawEvenDayQry);
        if ($rawEvenDayResult->num_rows > 0) {
          $rawEvenDayRow = $rawEvenDayResult->fetch_object();
          $dayNo = $rawEvenDayRow->dayNo;
          $rawEvenSessionQry = "SELECT DISTINCT(sessionNo) AS sessionNo FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=0 AND dayNo=$dayNo";
          $rawEvenSessionResult = $igrtSqli->query($rawEvenSessionQry);
          if ($rawEvenSessionResult->num_rows > 0) {
            $rawEvenSessionRow = $rawEvenSessionResult->fetch_object();
            $sessionNo = $rawEvenSessionRow->sessionNo;
            $evenJCntQry = "SELECT DISTINCT(jNo) FROM dataSTEP2inverted WHERE exptId=$exptId AND jType=0 AND dayNo=$dayNo AND sessionNo=$sessionNo";
            $evenJCntResult = $igrtSqli->query($evenJCntQry);
            $rawEvenCnt+= $evenJCntResult->num_rows;
          }
        }
        if ($rawEvenCnt > 0) { $reviewedEven = 1; }
      }
      
      
      $html.= "<tr>";
        $html.= "<td>$exptId</td>";
        $html.= "<td>$title</td>";
        $html.= "<td>";
          $html.= "<p>$expectedOddCnt expected $oddS1Label responding as $oddS1Label NP sets</p>";
          switch ($reviewedOdd) {
            case 0 : {
              $html.= "<p>$rawOddCnt $oddS1Label NP sets</p>";
              $buttonGreyed = "greyed";
              $buttonLabel = "not ready";
              break;
            }
            case 1 : {
              $html.= "<p>$rawOddCnt $oddS1Label NP sets</p>";
              $buttonGreyed = "";
              $buttonLabel = "first review";
              break;
            }
            case 2 : {
              $html.= "<p>previously reviewed</p>";
              $buttonGreyed = "";
              $buttonLabel = "re-review";
              break;
            }
          }
          $buttonId = sprintf("reviewB_%s_1_%s", $exptId, $reviewedOdd);
          $html.= $htmlBuilder->makeButton($buttonId, $buttonLabel, "button", null, null, null, $buttonGreyed);
        $html.= "</td>";
        $html.= "<td>";
          $html.= "<p>$expectedEvenCnt expected $evenS1Label responding as $evenS1Label NP sets</p>";
          switch ($reviewedEven) {
            case 0 : {
              $html.= "<p>$rawEvenCnt $evenS1Label NP sets</p>";
              $buttonGreyed = "greyed";
              $buttonLabel = "not ready";
              break;
            }
            case 1 : {
              $html.= "<p>$rawEvenCnt $evenS1Label NP sets</p>";
              $buttonGreyed = "";
              $buttonLabel = "first review";
              break;
            }
            case 2 : {
              $html.= "<p>previously reviewed</p>";
              $buttonGreyed = "";
              $buttonLabel = "re-review";
              break;
            }
          }
          $buttonId = sprintf("reviewB_%s_0_%s", $exptId, $reviewedEven);
          $html.= $htmlBuilder->makeButton($buttonId, $buttonLabel, "button", null, null, null, $buttonGreyed);
        $html.= "</td>";

      $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}

