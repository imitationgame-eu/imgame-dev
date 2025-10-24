<?php
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';     

// using 303 (Cardiff Alignment) as a test-bed for Step2 simulator and Step4 alignment test
//clear down
$exptId = 303;
$d = sprintf("DELETE FROM dataSTEP2 WHERE exptId='%s'", $exptId);
$igrtSqli->query($d);
$ds = sprintf("DELETE FROM wt_Step2pptStatus WHERE exptId='%s'", $exptId);
$igrtSqli->query($ds);
$dss = sprintf("DELETE FROM wt_Step2summaries WHERE exptId='%s'", $exptId);
$igrtSqli->query($dss);
$dr = sprintf("DELETE FROM md_dataStep2reviewed WHERE exptId='%s'", $exptId);
$igrtSqli->query($dr);
$balQry = "SELECT * FROM wt_Step2Balancer WHERE exptId=303 ORDER BY jType ASC, dayNo ASC, sessionNo ASC, jNo ASC";
$balResult = $igrtSqli->query($balQry);
if ($balResult) {
  while ($s2bRow = $balResult->fetch_object()) {
    $maxReplies = $s2bRow->respMax;
    $jType = $s2bRow->jType;
    $dayNo = $s2bRow->dayNo;
    $sessionNo = $s2bRow->sessionNo;
    $jNo = $s2bRow->jNo;
    $actualJNo = $s2bRow->actualJNo;
    $updateQry = sprintf("UPDATE wt_Step2Balancer SET respCount = '%s' WHERE "
        . "exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s'",
        $maxReplies, $exptId, $jType, $dayNo, $sessionNo, $jNo);
    $igrtSqli->query($updateQry);
    echo $updateQry.'<br />';
    $summaryQry = sprintf("INSERT INTO wt_Step2summaries (exptId, jType, dayNo, sessionNo, jNo, pptCnt, actualJNo) "
        . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
        $exptId, $jType, $dayNo, $sessionNo, $jNo, $maxReplies, $actualJNo);
    $igrtSqli->query($summaryQry);
    echo $summaryQry.'<br />';
    for ($i=0; $i<$maxReplies; $i++) {
      $qQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE "
          . "exptId='%s' AND jType='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' ORDER BY qNo ASC",
          $exptId, $jType, $dayNo, $sessionNo, $jNo);
      $qResult = $igrtSqli->query($qQry);
      if ($qResult) {
        while ($qRow = $qResult->fetch_object()) {
          $pReply = $qRow->pr;
          $qNo = $qRow->qNo;
          if ($pReply != 'FINAL') {
            $insertReply = $igrtSqli->real_escape_string($pReply . " simulated P " . $i);
            $insertQry = sprintf("INSERT INTO dataSTEP2 (exptId, dayNo, sessionNo, chrono, jType, jNo, qNo, pptNo, reply) "
                . "VALUES ('%s', '%s', '%s', NOW(), '%s', '%s', '%s', '%s', '%s')",
                $exptId, $dayNo, $sessionNo, $jType, $jNo, $qNo, $i+1, $insertReply);
            $igrtSqli->query($insertQry); 
            echo $insertQry.'<br />';
          }
        }
        $pptStatusQry = sprintf("INSERT INTO wt_Step2pptStatus "
            . "(exptId, jType, actualJNo, respNo, finished, chrono) "
            . "VALUES ('%s', '%s', '%s', '%s', 1, NOW())",
            $exptId, $jType, $actualJNo, $i+1);
        $igrtSqli->query($pptStatusQry);              
        echo $pptStatusQry.'<br />';
      }
    }    
  } 
}
echo 'done';

