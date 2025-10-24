<?php
// -----------------------------------------------------------------------------
// 
//    
// -----------------------------------------------------------------------------

if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';      

$uidQry = "SELECT DISTINCT(jUid) FROM sysdiags_STEP1Recovery WHERE chrono LIKE '%2014-11-15%' ORDER BY jUid ASC";
//echo $uidQry;
$uidResult = $igrtSqli->query($uidQry);
if ($uidResult) {
  while ($uidRow = $uidResult->fetch_object()) {
    $uid = $uidRow->jUid;
    $dsQry = sprintf("SELECT * FROM sysdiags_STEP1Recovery WHERE jUid='%s' ORDER BY chrono ASC", $uid);
    //echo $dsQry;
    $dsResult = $igrtSqli->query($dsQry);
    if ($dsResult) {
      $qNo = 1;
      while ($dsRow = $dsResult->fetch_object()) {
        $uid = $dsRow->jUid;
        $jType = $dsRow->jType;
        $jNo = $dsRow->jNo;
        $insertQry = sprintf("INSERT INTO tmpdataSTEP1 (uid,exptId,jType,jNo,dayNo,sessionNo,qNo,q,npr,pr) "
            . "VALUES('%s','277','%s','%s','1','1','%s','%s','%s','%s')",
            $dsRow->jUid,
            $dsRow->jType,
            $dsRow->jNo,
            $qNo++,
            $dsRow->jQ,
            $dsRow->npA,
            $dsRow->pA
            );
        $igrtSqli->query($insertQry);
      }
      $insertQry = sprintf("INSERT INTO tmpdataSTEP1 (uid,exptId,jType,jNo,dayNo,sessionNo,qNo,q,npr,pr) "
            . "VALUES('%s','277','%s','%s','1','1','%s','FINAL','FINAL','FINAL')",
            $uid,
            $jType,
            $jNo,
            $qNo++
            );
        $igrtSqli->query($insertQry);
    }
  }
}
echo 'done';