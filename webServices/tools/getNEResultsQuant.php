<?php
// -----------------------------------------------------------------------------
// web service to output Step4 results as csv
// 
// -----------------------------------------------------------------------------
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
$permissions = $_POST['permissions'];
$uid = $_POST['uid'];
$exptId = $_POST['exptId'];
$jType = $_POST['jType'];
include_once $root_path.'/domainSpecific/mySqlObject.php';      
include_once $root_path.'/helpers/parseJSON.php';     
//echo $permissions;
if ($permissions >= 128) {
  $s4jNoQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
  //echo $s4jNoQry;
  $s4jNoResult = $igrtSqli->query($s4jNoQry);
  $s4jNoRow = $s4jNoResult->fetch_object();
  $jCnt = ($jType == 0) ? $s4jNoRow->evenS4JudgeCount : $s4jNoRow->oddS4JudgeCount;
//  echo $jCnt;
  $fileName = "step4data_".$exptId."_".$jType.".csv";
  header("Content-Disposition: attachment; filename=" . urlencode($fileName));    
//  header("Content-Type: application/force-download");
//  header("Content-Type: application/octet-stream");
//  header("Content-Type: application/download");
  header("Content-Type: text/csv");
  header("Content-Description: File Transfer");             
  $fileBody = fopen('php://output', 'w');
  for ($i=0; $i<$jCnt; $i++) {
    $s4jNo = $i + 1;
    $getDataQry = sprintf("SELECT * FROM ne_dataSTEP4 WHERE exptId='%s' AND jType='%s' AND s4jNo='%s'", $exptId, $jType, $s4jNo);
    $getDataResult = $igrtSqli->query($getDataQry);
    while ($getDataRow = $getDataResult->fetch_object()) {
      $confInt = mb_substr($getDataRow->confidence, -1);
      if ($getDataRow->choice == 0) {
        $npChoice = 2;
      }
      else {
        $npChoice = 1;
      }
      if ($getDataRow->pretenderRight == 1) {
        $npPlayer = 1;
      }
      else {
        $npPlayer = 2;
      }
      $rowArray = array(
        $s4jNo, 
        $getDataRow->shuffleHalf, 
        $getDataRow->actualJNo, 
        $getDataRow->s3respNo1, 
        $getDataRow->s3respNo2, 
        $npChoice, 
        $confInt, 
        $getDataRow->reason,
        $npPlayer,
      );
      fputcsv($fileBody, $rowArray);
    }
  }
  fclose($fileBody);
}


