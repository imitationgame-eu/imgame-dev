<?php
/**
 * can get a s4jno for Step4
 *
 * @author mh
 */

if (!isSet($root_path)) {
  $full_ws_path=realpath(dirname(__FILE__));
  $root_path = substr($full_ws_path, 0, strlen($full_ws_path)-14);  // -14 accounts for /helpers/step4
}
include_once $root_path.'/domainSpecific/mySqlObject.php';

class step4JudgeNumberController {
  public $s4startedCount;
  public $s4jCount;
  
  function isClosed($exptId, $jType) {
    global $igrtSqli;
    $s4jCountQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
    $s4jCountResult = $igrtSqli->query($s4jCountQry);
    if ($igrtSqli->affected_rows > 0) {
      $s4jCountRow = $s4jCountResult->fetch_object();
      $this->s4jCount = ($jType == 0) ? $s4jCountRow->evenS4JudgeCount : $s4jCountRow->oddS4JudgeCount;
      $s4NoStartedCountQry = sprintf("SELECT * FROM wt_Step4judgeStatus WHERE exptId='%s' AND jType='%s' AND started=1", $exptId, $jType);
      $s4startedResult = $igrtSqli->query($s4NoStartedCountQry);
      $this->s4startedCount = $igrtSqli->affected_rows;
      return ($this->s4startedCount == $this->s4jCount) ? 1 : 0;
    }
    else {
      return 1;
    }
  }
  
  function getS4jNo($exptId, $jType) {
    global $igrtSqli;
    $checkQry = sprintf("SELECT * FROM wt_Step4judgeStatus WHERE exptId='%s' AND jType='%s'", $exptId, $jType);
    $checkArray = array();
    $checkResult = $igrtSqli->query($checkQry);
    while ($checkRow = $checkResult->fetch_object()) {
      $key = $checkRow->s4jNo;
      $checkArray[$key] = $checkRow->started;
    }
    $this->testVal = print_r($checkArray, true);
    $found = -1;
    while ($found == -1) {
      $targetNo = mt_rand(1,40);
      if ($checkArray[$targetNo] == 0) {
        $updateQry = sprintf("UPDATE wt_Step4judgeStatus SET started=1 WHERE exptId='%s' AND jType='%s' AND s4jNo='%s'",
            $exptId, $jType, $targetNo);
        $igrtSqli->query($updateQry);
        $found = $targetNo;
      }
    }
    return $targetNo;
  }

  //--------------------------------------------------------------------------
  // constructor and initialisation
  //--------------------------------------------------------------------------   
    
  function __construct() {
  }
}
