<?php
// -----------------------------------------------------------------------------
//  
// web service to output Linked Experiment normal STEP4 and TBT marking results 
// as 2 csv files
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_GET['permissions'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';     
//echo $permissions;
if ($permissions >= 128) {
  $fileName = "data_LE_TBT_STEP4.csv";
  header("Content-Disposition: attachment; filename=" . urlencode($fileName));    
//  header("Content-Type: application/force-download");
//  header("Content-Type: application/octet-stream");
//  header("Content-Type: application/download");
  header("Content-Type: text/csv");
  header("Content-Description: File Transfer");             
  $fileBody = fopen('php://output', 'w');
  $rowArray = array(
    "s4jNo", 
    "exptId", 
    "igNo",
    "qNo",
    "confInt", 
    "reason",
    "pretenderRight",
    "choice",
    "correct",
    "alignmentLeft",
    "alignmentRight"
  );
  fputcsv($fileBody, $rowArray);
  $s4jNoQry = "SELECT DISTINCT(s4jNo) AS s4jNo FROM wt_LinkedTBTStep4datasets";
  $s4jNoResult = $igrtSqli->query($s4jNoQry);
  while ($s4jNoRow = $s4jNoResult->fetch_object()) {
    $s4jNo = $s4jNoRow->s4jNo;
    $prevExptId = -1;
    $prevIgNo = -1;
    $prevqNo = -1;
    $getDataQry = sprintf("SELECT * FROM dataLinkedTBTSTEP4 WHERE isFinalRating=0 AND s4jNo='%s' ORDER BY exptId ASC, igNo ASC, qNo ASC", $s4jNo);
    $getDataResult = $igrtSqli->query($getDataQry);
    while ($getDataRow = $getDataResult->fetch_object()) {
      $exptId = $getDataRow->exptId;
      $igNo = $getDataRow->igNo;
      $qNo = $getDataRow->qNo;
      if ($prevExptId == $exptId  && $prevIgNo == $igNo && $prevqNo == $qNo) {
        //ignore as duplicate
      }
      else {
        $prevExptId = $exptId;
        $prevIgNo = $igNo;
        $prevqNo = $qNo;
        $confInt = mb_substr($getDataRow->confidence, -1);
        $rowArray = array(
          $s4jNo, 
          $exptId, 
          $igNo,
          $qNo,        
          $confInt, 
          $getDataRow->reason,
          $getDataRow->pretenderRight,
          $getDataRow->choice,
          $getDataRow->correct,
          $getDataRow->alignment1,
          $getDataRow->alignment2
        );
        fputcsv($fileBody, $rowArray);        
      }
    } 
  }
  fclose($fileBody);
}


