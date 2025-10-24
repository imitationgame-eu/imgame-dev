<?php
// -----------------------------------------------------------------------------
// web service to output Step4 results as csv
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
//$uid = $_POST['uid'];
//$exptId = $_POST['exptId'];
//$jType = $_POST['jType'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';     
//echo $permissions;
if ($permissions >= 128) {
  $s4jNoQry = "SELECT DISTINCT(s4jNo) FROM wt_LinkedStep4datasets";
  //echo $s4jNoQry;
  $s4jNoResult = $igrtSqli->query($s4jNoQry);
  $fileName = "LEdata.csv";
  header("Content-Disposition: attachment; filename=" . urlencode($fileName));    
  header("Content-Type: text/csv");
  header("Content-Description: File Transfer");             
  $fileBody = fopen('php://output', 'w');
  fputcsv($fileBody, ['s4 jNo','exptId','igNo','correct','choice','pretenderRight','reason']);
  while ($s4jRow = $s4jNoResult->fetch_object()) {
    $s4jNo = $s4jRow->s4jNo;
    $getShuffle = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE s4jNo='%s' ORDER BY exptId ASC, jNo ASC", $s4jNo);
    $shuffleResult = $igrtSqli->query($getShuffle);
    while ($shuffleRow = $shuffleResult->fetch_object()) {
      $getDataQry = sprintf("SELECT * FROM dataLinkedSTEP4 WHERE s4jNo='%s' AND exptId='%s' AND igNo='%s'", $s4jNo, $shuffleRow->exptId, $shuffleRow->jNo );
      $getDataResult = $igrtSqli->query($getDataQry);
      while ($getDataRow = $getDataResult->fetch_object()) {
        $confInt = mb_substr($getDataRow->confidence, -1);
        $rowArray = array(
          $s4jNo, 
          $getDataRow->exptId, 
          $getDataRow->igNo, 
          $getDataRow->correct, 
          $getDataRow->choice, 
          $getDataRow->pretenderRight, 
          $confInt, 
          $getDataRow->reason,
        );
        fputcsv($fileBody, $rowArray);      
      }
    }
  }
  fclose($fileBody);
}


