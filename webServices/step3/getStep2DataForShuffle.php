<?php
// -----------------------------------------------------------------------------
// web service to list marked STEP 2 datasets that are ready for shuffle into
// Step4 data set
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
require_once($root_path.'/helpers/html/class.htmlBuilder.php');
$permissions=$_GET['permissions'];
$uid=$_GET['uid'];
$htmlBuilder = new htmlBuilder();

include_once $root_path.'/domainSpecific/mySqlObject.php';      

if ($permissions>=128) {
  $keyValuePairs = array();
  for ($i=10; $i<100; $i++) {
    $temp = array('id' => $i, 'label' => "$i");
    array_push($keyValuePairs, $temp);
  }
  $html = "<p>No marked/reviewed Step2 sessions exist.</p>";
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM wt_Step2pptReviews";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult) {
    $html = "<table><tr><th>expt #</th><th>title</th><th>even S4 judges</th><th>Even</th><th>odd S4 judges</th><th>Odd</th></tr>";   
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
      $oddS4jc = 40;
      $evenS4jc = 40;
      // get counts of odd and even s4 judges (if previously shuffled)
      $s4jQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
      $s4jResult = $igrtSqli->query($s4jQry);
      if ($s4jResult) {
        $s4jRow = $s4jResult->fetch_object();
        $evenS4jc = $s4jRow->evenS4JudgeCount;
        $oddS4jc = $s4jRow->oddS4JudgeCount;
      }
      // get count of even datasets
      $evendsQry = "SELECT DISTINCT(actualJNo) FROM wt_Step3summaries WHERE jType=0 AND exptId=$exptId";
      $evendsResult = $igrtSqli->query($evendsQry);
      $evendsCnt = $evendsResult->num_rows;
      // get count of odd datasets
      $odddsQry = "SELECT DISTINCT(actualJNo) FROM wt_Step3summaries WHERE jType=1 AND exptId=$exptId";
      $odddsResult = $igrtSqli->query($odddsQry);
      $odddsCnt = $odddsResult->num_rows;      
      // get count of reviewed even judges for this exptId
      $evenRespCntQry = "SELECT * FROM wt_Step2pptReviews WHERE reviewed=1 AND ignorePpt=0 AND jType=0 AND exptId=$exptId";
      $fr = $igrtSqli->query($evenRespCntQry);
      $evenRespCnt = $fr->num_rows;
      // get count of reviewed odd judges for this exptId
      $oddRespCntQry = "SELECT * FROM wt_Step2pptReviews WHERE reviewed=1 AND ignorePpt=0 AND jType=1 AND exptId=$exptId";
      $fr = $igrtSqli->query($oddRespCntQry);
      $oddRespCnt = $fr->num_rows;
      $html .= "<tr>";
        $html .= "<td>$exptId</td>";
        $html .= "<td>$exptTitle</td>";
        $html .= "<td>";
        $html .= $htmlBuilder->makeSelect(sprintf("jc_%s_0",$exptId), "# recruited", "", true, $keyValuePairs, 0, $evenS4jc);
        $html .= "</td>";
        $buttonId = sprintf("shuffleB_%s_0", $exptId);
        $html .= "<td>$evendsCnt datasets<br />".$htmlBuilder->makeButton($buttonId, "$evenRespCnt resps", "button")."</td>";
        $html .= "<td>";
        $html .= $htmlBuilder->makeSelect(sprintf("jc_%s_1",$exptId), "# recruited", "", true, $keyValuePairs, 0, $oddS4jc);
        $html .= "</td>";
        $buttonId = sprintf("shuffleB_%s_1", $exptId);
        $html .= "<td>$odddsCnt datasets<br />".$htmlBuilder->makeButton($buttonId, "$oddRespCnt resps", "button")."</td>";
       $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}
