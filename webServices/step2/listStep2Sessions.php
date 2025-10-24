<?php
// -----------------------------------------------------------------------------
// web service to list STEP 2 datasets that are ready for injection into
// Knockout-JS Step2 reviewer 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/models/class.experimentModel.php';     
$htmlBuilder = new htmlBuilder();

if ($permissions>=128) {
  $html = "<p>No completed Step2 sessions exist.</p>";
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM dataSTEP2 ORDER BY exptId DESC";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult->num_rows > 0) {
    $html = "<table><tr><th>expt #</th><th>title</th><th>Odd</th><th>Even</th></tr>";
    while ($uidRow = $uidResult->fetch_object()) {
      $exptId = $uidRow->exptId;
//      // get expt title, and attach to day, session etc
      $eModel = new experimentModel($exptId);
      $title = $eModel->title;
      $oddS1Label = $eModel->oddS1Label;
      $evenS1Label = $eModel->evenS1Label;
      $uniqueOddPPTQry = "SELECT actualJNo FROM wt_Step2Balancer WHERE exptId=$exptId AND jType=1 ORDER BY actualJNo ASC";
      $upptOddResult = $igrtSqli->query($uniqueOddPPTQry);
      $pptOddCnt = $upptOddResult->num_rows;
      $reviewedOddQry = "SELECT * FROM md_dataStep2reviewed WHERE exptId=$exptId AND jType=1";
      $reviewedOddResult = $igrtSqli->query($reviewedOddQry);
      $reviewedOdd = ($reviewedOddResult->num_rows > 0) ? 1 : 0;
      $uniqueEvenPPTQry = "SELECT actualJNo FROM wt_Step2Balancer WHERE exptId=$exptId AND jType=0 ORDER BY actualJNo ASC";
      $upptEvenResult = $igrtSqli->query($uniqueEvenPPTQry);
      $pptEvenCnt = $upptEvenResult->num_rows;
      $reviewedEvenQry = "SELECT * FROM md_dataStep2reviewed WHERE exptId=$exptId AND jType=0";
      $reviewedEvenResult = $igrtSqli->query($reviewedEvenQry);
      $reviewedEven = ($reviewedEvenResult->num_rows > 0) ? 1 : 0;
      $html.= "<tr>";
        $html.= "<td>$exptId</td>";
        $html.= "<td>$title</td>";
        $html.= "<td><p>$pptOddCnt $evenS1Label P sets</p>";
        $buttonId = sprintf("reviewB_%s_1", $exptId);
        $buttonGreyed = ($pptOddCnt > 0) ? "" : "greyed";
        $buttonLabel = ($reviewedOdd == 0) ? "initial review" : "re-review";
        $html.= $htmlBuilder->makeButton($buttonId, $buttonLabel, "button", null, null, null, $buttonGreyed);
        $html.= "<br />$evenS1Label pretending to be $oddS1Label</td>";
        $html.= "<td><p>$pptEvenCnt $oddS1Label P sets </p>";
        $buttonId = sprintf("reviewB_%s_0", $exptId);
        $buttonGreyed = ($pptEvenCnt > 0) ? "" : "greyed";;
        $buttonLabel = ($reviewedEven == 0) ? "initial review" : "re-review";
        $html .= $htmlBuilder->makeButton($buttonId, $buttonLabel, "button", null, null, null, $buttonGreyed);
        $html.="<br />$oddS1Label pretending to be $evenS1Label</td>";
      $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}

