<?php
// -----------------------------------------------------------------------------
// 
//    
// -----------------------------------------------------------------------------
  function insertVirtualReview($targetId, $originalRow, $reviewedRespNo) {
    global $igrtSqli;
    $insertReview = sprintf("INSERT INTO wt_Step2pptReviews (exptId, jType, actualJNo, jNo, dayNo, sessionNo, reviewedRespNo, respNo, ignorePpt, reviewed, finished, isVirtual) "
        . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
        $targetId,
        $originalRow->jType,
        $originalRow->actualJNo,
        $originalRow->jNo,
        $originalRow->dayNo,
        $originalRow->sessionNo,
        $originalRow->reviewedRespNo+1,
        $originalRow->respNo+1,
        $originalRow->ignorePpt,
        $originalRow->reviewed,
        $originalRow->finished,
        1
    );
    $igrtSqli->query($insertReview);
    echo $insertReview.'(virtual)<br/>';
  }

  function insertReview($targetId, $originalRow, $reviewedRespNo) {
    global $igrtSqli;
    $insertReview = sprintf("INSERT INTO wt_Step2pptReviews (exptId, jType, actualJNo, jNo, dayNo, sessionNo, reviewedRespNo, respNo, ignorePpt, reviewed, finished, isVirtual) "
        . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
        $targetId,
        $originalRow->jType,
        $originalRow->actualJNo,
        $originalRow->jNo,
        $originalRow->dayNo,
        $originalRow->sessionNo,
        $originalRow->reviewedRespNo,
        $originalRow->respNo,
        $originalRow->ignorePpt,
        $originalRow->reviewed,
        $originalRow->finished,
        $originalRow->isVirtual
    );
    $igrtSqli->query($insertReview);
    echo $insertReview.'<br/>';
  }
  
  function copyData($targetId, $originalRow, $reviewedRespNo) {
    global $igrtSqli;
    $getData = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId=280 AND jType=0 AND actualJNo='%s' AND respNo='%s'",
        $originalRow->actualJNo, $originalRow->respNo);
    $getResult = $igrtSqli->query($getData);
    $getUIDQry = sprintf("SELECT id FROM wt_Step2pptReviews WHERE exptId=280 AND jType=0 AND actualJNo='%s' AND respNo='%s'",
        $originalRow->actualJNo, $originalRow->respNo);
    $getUIDResult = $igrtSqli->query($getUIDQry);
    if ($getUIDResult) {
      $getUIDRow = $getUIDResult->fetch_object();
      $uid = $getUIDRow->id;
    }
    if ($getUIDResult) {
      while ($getRow = $getResult->fetch_object()) {
        $insertData = sprintf("INSERT INTO md_dataStep2reviewed (uid,exptId,chrono,jType,actualJNo,reviewedRespNo,respNo,qNo,canUse,q,reply,restartUID) "
            . "VALUES('%s','%s',NOW(),'%s','%s','%s','%s','%s','%s','%s','%s','%s')",
            $uid,
            $targetId,
            $getRow->jType,
            $getRow->actualJNo,
            $getRow->reviewedRespNo,
            $getRow->respNo,
            $getRow->qNo,
            $getRow->canUse,
            $igrtSqli->real_escape_string($getRow->q),
            $igrtSqli->real_escape_string($getRow->reply),
            $getRow->restartUID
          );
        $igrtSqli->query($insertData);
        //echo $insertData.'<br />';
      }
    }    
  }

  function copyVirtualData($targetId, $originalRow, $reviewedRespNo) {
    global $igrtSqli;
    $getData = sprintf("SELECT * FROM md_dataStep2reviewed WHERE exptId=280 AND jType=0 AND actualJNo='%s' AND respNo='%s'",
        $originalRow->actualJNo, $originalRow->respNo);
    $getResult = $igrtSqli->query($getData);
    $uid = -1;
    if ($getResult) {
      while ($getRow = $getResult->fetch_object()) {
        $insertData = sprintf("INSERT INTO md_dataStep2reviewed (uid,exptId,chrono,jType,actualJNo,reviewedRespNo,respNo,qNo,canUse,q,reply,restartUID) "
            . "VALUES('%s','%s',NOW(),'%s','%s','%s','%s','%s','%s','%s','%s','%s')",
            $uid,
            $targetId,
            $getRow->jType,
            $getRow->actualJNo,
            ($getRow->reviewedRespNo + 1),
            ($getRow->respNo + 1),
            $getRow->qNo,
            $getRow->canUse,
            $igrtSqli->real_escape_string("virtual padding"),
            $igrtSqli->real_escape_string("virtual padding"),
            $getRow->restartUID
          );
        $igrtSqli->query($insertData);
        echo $insertData.'<br />';
      }
    }    
  }

ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      
// clear down first
$clearReview = "DELETE FROM wt_Step2pptReviews WHERE (exptId=292 OR exptId=293) AND jType=0";
$igrtSqli->query($clearReview);
//echo $clearReview.'<br />';
$clearData = "DELETE FROM md_dataStep2reviewed WHERE (exptId=292 OR exptId=293) AND jType=0";
$igrtSqli->query($clearData);
//echo $clearData.'<br />';



$s2jNo = "SELECT DISTINCT(actualJNo) FROM wt_Step2pptReviews WHERE exptId=280 AND jType=0 ORDER BY actualJNo ASC";
$s2jNoResult = $igrtSqli->query($s2jNo);
if ($s2jNoResult) {
  while ($s2jNoRow = $s2jNoResult->fetch_object()) {
    $actualJNo = $s2jNoRow->actualJNo;
    $s2usedQry = sprintf("SELECT * FROM wt_Step2pptReviews WHERE exptId=280 AND jType=0 AND actualJNo='%s' AND ignorePpt=0 AND finished=1", $actualJNo);
    //echo $s2usedQry;
    $s2usedResult = $igrtSqli->query($s2usedQry);
    $eligibleS2count = $s2usedResult? $s2usedResult->num_rows : 0;
    if ($eligibleS2count > 0) {
      echo "original count: $eligibleS2count <br/>";
      if ($eligibleS2count %2 == 0) {
        // even number so equal split for each experiment - just use normal halves
        $splitNo = $eligibleS2count / 2;
        echo $actualJNo.' even --- '.$splitNo.'<br />';
        for ($i=0; $i<$splitNo; $i++) {
          $originalRow = $s2usedResult->fetch_object();
          insertReview(292, $originalRow, $i);
          copyData(292, $originalRow, $i);
        }
        for ($i=0; $i<$splitNo; $i++) {
          $originalRow = $s2usedResult->fetch_object();
          insertReview(293, $originalRow, $i);
          copyData(293, $originalRow, $i);
        }
      }
      else {
        $splitNo = ($eligibleS2count + 1) / 2;
        echo $actualJNo.' odd --- '.$splitNo.'<br />';
        for ($i=0; $i<$splitNo; $i++) {
          $originalRow = $s2usedResult->fetch_object();
          insertReview(292, $originalRow, $i);
          copyData(292, $originalRow, $i);
        }
        for ($i=0; $i<$splitNo - 1; $i++) {
          $originalRow = $s2usedResult->fetch_object();
          insertReview(293, $originalRow, $i);
          copyData(293, $originalRow, $i);
        }
        insertVirtualReview(293, $originalRow, ($splitNo-1)); 
        copyVirtualData(293, $originalRow, $i);
      }
    }
  }
}
echo 'done';