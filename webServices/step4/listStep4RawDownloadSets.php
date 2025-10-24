<?php
// -----------------------------------------------------------------------------
// 
// web service to list experiments where Step4 has been configured so that raw
// transcripts can be injected into a page and characters rendered correctly
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
  $s4Qry = "SELECT * FROM wt_Step4JudgeCounts ORDER BY exptId DESC";
  $s4Result = $igrtSqli->query($s4Qry);
  if ($s4Result) {
    $html = "<table><tr><th>expt #</th><th>title</th><th width='33%'>Odd</th><th width='33%'>Even</th></tr>";   
    while ($s4Row = $s4Result->fetch_object()) {
      $exptId = $s4Row->exptId;
      // get experiment details
      $qry ="CALL getExperimentDetails($exptId,@title)";
      $igrtSqli->query($qry);
      $sr2 = $igrtSqli->query("SELECT @title as exptTitle");
      if ($sr2) {
        $row = $sr2->fetch_object();
        $exptTitle = $row->exptTitle;    
      }
      // get counts of even & odd s4judges configured
      $evenS4JudgeCount = $s4Row->evenS4JudgeCount;
      $oddS4JudgeCount = $s4Row->oddS4JudgeCount;
      $html .= "<tr>";
        $html .= "<td>$exptId</td>";
        $html .= "<td>$exptTitle</td>";
        if ($oddS4JudgeCount > 0) {
        $buttonId = sprintf("downloadB_%s_1", $exptId);
        $html .= "<td>".$htmlBuilder->makeButton($buttonId, "view", "button")."</td>";        
        }
        else {
          $html.= "<td>no data</td>";        
        }
        if ($evenS4JudgeCount > 0) {
          $buttonId = sprintf("downloadB_%s_0", $exptId);
          $html .= "<td>".$htmlBuilder->makeButton($buttonId, "view", "button")."</td>";
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
