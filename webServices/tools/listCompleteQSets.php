<?php
// -----------------------------------------------------------------------------
// 
// web service to list experiments where Step4 has been finished so that
// complete question sets can be downloaded
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
require_once($root_path.'/helpers/class.dbHelpers.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$htmlBuilder = new htmlBuilder();

include_once $root_path.'/domainSpecific/mySqlObject.php';      

if ($permissions>=128) {
  $dbHelper = new DBHelper($igrtSqli);
  $html = "<p>No Step4 data exists.</p>";
  $s4Qry = "SELECT * FROM wt_Step4JudgeCounts ORDER BY exptId DESC";
  $s4Result = $igrtSqli->query($s4Qry);
  if ($s4Result) {
    $html = "<table><tr><th>expt #</th><th>title</th><th width='33%'>Odd</th><th width='33%'>Even</th></tr>";   
    while ($s4Row = $s4Result->fetch_object()) {
      $exptId = $s4Row->exptId;
      // get experiment details
      $exptTitle = $dbHelper->getExptTitleFromId($exptId);
      $s4Details = $dbHelper->getStep4Status($exptId);
      $html.= "<tr>";
        $html.= "<td>$exptId</td>";
        $html.= "<td>$exptTitle</td>";
        if ($s4Details["status"] == "not configured") {
          $html.= "<td>not configured</td><td>not configured</td>";
        }
        else {
          if ( ($s4Details["oddS4JudgeCnt"] == $s4Details["startedOddCnt"]) && ($s4Details["oddS4JudgeCnt"] > 0)) {
            $statusOdd = sprintf("all %s started", $s4Details["oddS4JudgeCnt"]);
            $buttonId = sprintf("downloadB_%s_1", $exptId);          
            $html.= "<td><p>".$statusOdd."</p>".$htmlBuilder->makeButton($buttonId, "view", "button")."</td>";        
          }
          else {
            if ($s4Details["startedOddCnt"] == 0) {
              $html.= "<td><p>no data collected</td>";                    
            }
            else {
              $html.= "<td>";
              $html.= sprintf("<p>%s/%s judges incomplete</p>", $s4Details["unfinishedOddCnt"], $s4Details["oddS4JudgeCnt"]);
              $html.= sprintf("<p>%s/%s judges started</p>", $s4Details["startedOddCnt"], $s4Details["oddS4JudgeCnt"]);
              $buttonId = sprintf("downloadB_%s_1", $exptId);
              $html.= $htmlBuilder->makeButton($buttonId, "view", "button");
              $html.= "</td>";
            }
          }
          if ( ($s4Details["evenS4JudgeCnt"] == $s4Details["startedEvenCnt"]) && ($s4Details["evenS4JudgeCnt"] > 0)) {
            $statusEven = sprintf("all %s started", $s4Details["evenS4JudgeCnt"]);
            $buttonId = sprintf("downloadB_%s_0", $exptId);          
            $html.= "<td><p>".$statusEven."</p>".$htmlBuilder->makeButton($buttonId, "view", "button")."</td>";        
          }
          else {
            if ($s4Details["startedEvenCnt"] == 0) {
              $html.= "<td><p>no data collected</td>";                    
            }
            else {
              $html.= "<td>";
              $html.= sprintf("<p>%s/%s judges incomplete</p>", $s4Details["unfinishedEvenCnt"], $s4Details["evenS4JudgeCnt"]);
              $html.= sprintf("<p>%s/%s judges started</p>", $s4Details["startedEvenCnt"], $s4Details["evenS4JudgeCnt"]);
              $buttonId = sprintf("downloadB_%s_0", $exptId);
              $html.= $htmlBuilder->makeButton($buttonId, "view", "button");
              $html.= "</td>";
            }
          }          
        }
      $html.= "</tr>";        
    }
    $html.= "</table>";
  }
  echo $html;
}
