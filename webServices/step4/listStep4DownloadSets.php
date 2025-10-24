<?php
// -----------------------------------------------------------------------------
// web service to list experiments where Step4 data exists
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$htmlBuilder = new htmlBuilder();

include_once $root_path.'/domainSpecific/mySqlObject.php';      

if ($permissions>=128) {
  $html = "<p>No Step4 data exists.</p>";
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM dataSTEP4";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult) {
    $html = "<table><tr><th>expt #</th><th>title</th><th width='33%'>Odd</th><th width='33%'>Even</th></tr>";   
    while ($eidRow = $uidResult->fetch_object()) {
      $exptId = $eidRow->exptId;
      // get experiment details
      $qry ="CALL getExperimentDetails($exptId,@title)";
      $igrtSqli->query($qry);
      $sr2 = $igrtSqli->query("SELECT @title as exptTitle");
      if ($sr2) {
        $row = $sr2->fetch_object();
        $exptTitle = $row->exptTitle;    
      }
      // get count of even s4judges
      $s4jQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
      $s4jResult = $igrtSqli->query($s4jQry);
      $s4jRow = $s4jResult->fetch_object();
      $evenS4JudgeCount = $s4jRow->evenS4JudgeCount;
      $oddS4JudgeCount = $s4jRow->oddS4JudgeCount;
      // get count of uncompleted odd judges
      $ojQry = "SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE jType=1 AND exptId=$exptId AND rated=0";
      $ojResult = $igrtSqli->query($ojQry);
      $ojCnt = $ojREsult ? $ojResult->num_rows : 0;
      $goodO = $oddS4JudgeCount - $ojCnt;
      // get count of uncompleted even judges
      $ejQry = "SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE jType=0 AND exptId=$exptId AND rated=0";
      $ejResult = $igrtSqli->query($ejQry);
      $ejCnt = $ejResult ? $ejResult->num_rows : 0;
      $goodE = $evenS4JudgeCount - $ejCnt;
      $html .= "<tr>";
        $html .= "<td>$exptId</td>";
        $html .= "<td>$exptTitle</td>";
        if ($oddS4JudgeCount > 0) {
          $buttonId = sprintf("downloadB_%s_1", $exptId);
          $html .= "<td><p>$goodO complete judges out of $oddS4JudgeCount</p>".$htmlBuilder->makeButton($buttonId, "download", "button")."</td>";        
        }
        else {
          $html.= "<td>no data</td>";        
        }
        if ($evenS4JudgeCount > 0) {
          $buttonId = sprintf("downloadB_%s_0", $exptId);
          $html .= "<td><p>$goodE complete judges out of $evenS4JudgeCount</p>".$htmlBuilder->makeButton($buttonId, "download", "button")."</td>";
        }
        else {
          $html.= "<td>no data</td>";
        }
      $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}
