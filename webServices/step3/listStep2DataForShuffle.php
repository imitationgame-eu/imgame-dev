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
  $uniqueIdQry = "SELECT DISTINCT(exptId) AS exptId FROM wt_Step2pptReviews ORDER BY exptId DESC";
  $uidResult = $igrtSqli->query($uniqueIdQry);
  if ($uidResult->num_rows > 0) {
    $html = "<table><tr><th>expt #</th><th>title</th><th>odd S4 judges</th><th>Odd</th><th>even S4 judges</th><th>Even</th></tr>";   
    while ($eidRow = $uidResult->fetch_object()) {
      $exptId = $eidRow->exptId;
      $exptTitle = 'not set';
      $oddS1Label = 'odd';
      $evenS1Label = 'even';
      $exptDetailsQry = sprintf("SELECT * FROM igExperiments WHERE exptId='%s'", $exptId);
      $exptDetailsResult = $igrtSqli->query($exptDetailsQry);
      if ($exptDetailsResult->num_rows > 0) {
        $exptDetails = $exptDetailsResult->fetch_object();
        $exptTitle = $exptDetails->title;
        $oddS1Label = $exptDetails->oddS1Label;
        $evenS1Label = $exptDetails->evenS1Label;
      }
      $prevShuffle = false;
      // get counts of odd and even s4 judges (if previously shuffled)
      $s4jQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
      $s4jResult = $igrtSqli->query($s4jQry);
      if ($s4jResult->num_rows > 0) {
        $prevShuffle = true;
        $s4jRow = $s4jResult->fetch_object();
        $prevEvenS4jc = (is_null($s4jRow->evenS4JudgeCount)) ? 0 : $s4jRow->evenS4JudgeCount ;
        $prevOddS4jc = (is_null($s4jRow->oddS4JudgeCount)) ? 0 : $s4jRow->oddS4JudgeCount ;
      }
      // get count of even datasets
      $evendsQry = "SELECT DISTINCT(actualJNo) FROM wt_Step3summaries WHERE jType=0 AND exptId=$exptId";
      $evendsResult = $igrtSqli->query($evendsQry);
      $evendsCnt = $evendsResult->num_rows;
      // get count of odd datasets
      $odddsQry = "SELECT DISTINCT(actualJNo) FROM wt_Step3summaries WHERE jType=1 AND exptId=$exptId";
      $odddsResult = $igrtSqli->query($odddsQry);
      $odddsCnt = $odddsResult->num_rows;      
      // get count of reviewed even S2 for this exptId
      $evenRespCntQry = "SELECT * FROM wt_Step2pptReviews WHERE reviewed=1 AND ignorePpt=0 AND jType=0 AND exptId=$exptId";
      $fr = $igrtSqli->query($evenRespCntQry);
      $evenRespCnt = $fr->num_rows;
      // get count of reviewed odd S2 for this exptId
      $oddRespCntQry = "SELECT * FROM wt_Step2pptReviews WHERE reviewed=1 AND ignorePpt=0 AND jType=1 AND exptId=$exptId";
      $fr = $igrtSqli->query($oddRespCntQry);
      $oddRespCnt = $fr->num_rows;
      $html .= "<tr>";
        $html .= "<td>$exptId</td>";
        $html .= "<td>$exptTitle</td>";
        $html .= "<td>";
        if ($prevShuffle) {
          if ($prevOddS4jc == 0) {
            $prevOddLegend = "choose s4 judges";
            $oddS4jc = 40;
          }
          else {
            $prevOddLegend = "<b>shuffled s4 judges</b>";
            $oddS4jc = $prevOddS4jc;            
          }
        }
        else {
          $prevOddLegend = "choose s4 judges";
          $oddS4jc = 40;        
        }
        $html .= $htmlBuilder->makeSelect(sprintf("jc_%s_1",$exptId), $prevOddLegend, "shuffle", true, $keyValuePairs, 0, $oddS4jc);
        $html .= "</td>";
        $buttonId = sprintf("shuffleB_%s_1", $exptId);
        $buttonGreyed = ($oddRespCnt > 0) ? "" : "greyed";
        $html .= "<td>$odddsCnt reviewed $oddS1Label datasets<br />".$htmlBuilder->makeButton($buttonId, "$oddRespCnt $evenS1Label S2s", "button shuffle", null, null, null, $buttonGreyed)."</td>";
        $html .= "<td>";
        if ($prevShuffle) {
          if ($prevEvenS4jc == 0) {
            $prevEvenLegend = "choose s4 judges";
            $evenS4jc = 40;
          }
          else {
            $prevEvenLegend = "<b>shuffled s4 judges</b>";
            $evenS4jc = $prevEvenS4jc;            
          }
        }
        else {
          $prevEvenLegend = "choose s4 judges";
          $evenS4jc = 40;       
        }
        $html .= $htmlBuilder->makeSelect(sprintf("jc_%s_0",$exptId), $prevEvenLegend, "shuffle", true, $keyValuePairs, 0, $evenS4jc);
        $html .= "</td>";
        $buttonId = sprintf("shuffleB_%s_0", $exptId);
        $buttonGreyed = ($evenRespCnt > 0) ? "" : "greyed";
        $html .= "<td>$evendsCnt reviewed $evenS1Label datasets<br />".$htmlBuilder->makeButton($buttonId, "$evenRespCnt $oddS1Label S2s", "button shuffle", null, null, null, $buttonGreyed)."</td>";
       $html .= "</tr>";
    }
    $html .= "</table>";
  }
  echo $html;
}
