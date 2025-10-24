<?php
// -----------------------------------------------------------------------------
// web service to list STEP 2 datasets that are ready for download to nVivo
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
require_once($root_path.'/helpers/models/class.experimentModel.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$htmlBuilder = new htmlBuilder();

if ($permissions>=128) {
  $html = "<p>No completed Step2 sessions exist.</p>";
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM dataSTEP2 ORDER BY exptId DESC";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult->num_rows > 0) {
    $html = "<table><tr><th>expt #</th><th>title</th><th># of QS</th><th>Odd</th><th>Even</th></tr>";
    while ($uidRow = $uidResult->fetch_object()) {
      $exptId = $uidRow->exptId;
      $eModel = new experimentModel($exptId);
      $title = $eModel->title;
      $uniqueEvenPPTQry = "SELECT * FROM wt_Step2Balancer WHERE exptId=$exptId AND jType=0 ORDER BY actualJNo ASC";
      $upptEvenResult = $igrtSqli->query($uniqueEvenPPTQry);
      $pptEvenCnt = $upptEvenResult->num_rows;
      if ($pptEvenCnt > 0) {
        $eRow = $upptEvenResult->fetch_object();        
        // check for data
        $dataQry = sprintf("SELECT * FROM dataSTEP2 WHERE "
            . "exptId='%s' AND jType='0' AND dayNo='%s' AND sessionNo='%s'",
            $exptId, $eRow->dayNo, $eRow->sessionNo);
        $dr = $igrtSqli->query($dataQry);
        if ($dr->num_rows == 0) { $eStatusTxt = '<p>no responses</p>'; } else { $eStatusTxt = ''; }
      }
      $uniqueOddPPTQry = "SELECT * FROM wt_Step2Balancer WHERE exptId=$exptId AND jType=1 ORDER BY actualJNo ASC";
      $upptOddResult = $igrtSqli->query($uniqueOddPPTQry);
      $pptOddCnt = $upptOddResult->num_rows;
      if ($pptOddCnt > 0) {
        $oRow = $upptEvenResult->fetch_object();        
        // check for data
        $dataQry = sprintf("SELECT * FROM dataSTEP2 WHERE "
            . "exptId='%s' AND jType='1' AND dayNo='%s' AND sessionNo='%s'",
            $exptId, $eRow->dayNo, $eRow->sessionNo);
        $dr = $igrtSqli->query($dataQry);
        if ($dr->num_rows == 0) { $oStatusTxt = '<p>no responses</p>'; } else { $oStatusTxt = ''; }
      }
      $html .= "<tr>";
        $html .= "<td>$exptId</td>";
        $html .= "<td>$title</td>";
        $html .= "<td>$pptOddCnt Odd P sets and $pptEvenCnt Even P sets</td>";
        if ($pptOddCnt == 0) {
          $bHtml = 'no balancer configuration';
        }
        else {
          if ($oStatusTxt == '') {
            $buttonId = sprintf("downloadB_%s_1", $exptId);
            $bHtml = $htmlBuilder->makeButton($buttonId, "Odd P sets", "button");
          }
          else {
            $bHtml = $oStatusTxt;
          }
        }
        $html .= "<td>".$bHtml."</td>";
        if ($pptEvenCnt == 0) {
          $bHtml = 'no balancer configuration';          
        }
        else {
          if ($eStatusTxt == '') {
            $buttonId = sprintf("downloadB_%s_0", $exptId);
            $bHtml = $htmlBuilder->makeButton($buttonId, "Even P sets", "button");
          }
          else {
            $bHtml = $eStatusTxt;
          }
        }
        $html .= "<td>".$bHtml."</td>";
      $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}

