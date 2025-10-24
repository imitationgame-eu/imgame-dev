<?php
// -----------------------------------------------------------------------------
// 
// web service to surface version history
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      

$permissions=$_GET['permissions'];
$uid=$_GET['uid'];

if ($permissions>=128) {
  $html = "<p>No Step2 data exists.</p>";
  $dbHelper = new DBHelper($igrtSqli);
  $sQry = "SELECT DISTINCT(exptId) AS exptId FROM dataSTEP2 ORDER BY exptId DESC";
  $sResult = $igrtSqli->query($sQry);
  if ($sResult) {
    $html = "<table><tr><th>expt #</th><th>title</th><th width='33%'>Odd</th><th width='33%'>Even</th></tr>";   
    while ($sRow = $sResult->fetch_object()) {
      $exptId = $sRow->exptId;
      $exptTitle = $dbHelper->getExptTitleFromId($exptId);
      $exptArray = $dbHelper->getExptDaySessionCounts($exptId);
      if ($exptArray["status"] == "ok") {
        $dayCnt = $exptArray["dayCnt"];
        $sessionCnt = $exptArray["sessionCnt"];        
      }
      else {
        $dayCnt = 0;
        $sessionCnt = 0;                
      }
      $dayStr = $dayCnt > 1 ? "$dayCnt days" : "$dayCnt day";
      $sessionStr = $sessionCnt > 1 ? "$sessionCnt sessions" : "$sessionCnt session";
      $oddExistsQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=1", $exptId);
      $or = $igrtSqli->query($oddExistsQry);
      $oddCnt= $or->num_rows;
      $oddButtonId = sprintf("arB_%s_1", $exptId);
      $oddBHtml = $oddCnt > 0 ? $htmlBuilder->makeButton($oddButtonId, "view audit data", "button") : "no Step2 respondents";

      $evenExistsQry = sprintf("SELECT * FROM dataSTEP2 WHERE exptId='%s' AND jType=0", $exptId);
      $er = $igrtSqli->query($evenExistsQry);
      $evenCnt = $er->num_rows;
      $evenButtonId = sprintf("arB_%s_0", $exptId);
      $evenBHtml = $oddCnt > 0 ? $htmlBuilder->makeButton($evenButtonId, "view audit data", "button") : "no Step2 respondents";
      
      $html.= sprintf("<tr><td>%s</td><td>%s<p>%s</p><p>%s</p></td><td>%s</td><td>%s</td></tr>", 
        $exptId, $exptTitle, $dayStr, $sessionStr, $oddBHtml, $evenBHtml);
        //$html .= "</tr>";        
    }
    $html.= "</table>";
  }
  echo $html;
}
