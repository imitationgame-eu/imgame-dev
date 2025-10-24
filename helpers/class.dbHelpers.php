<?php
/**
 * misc db functions
 *
 * @author mh
 */
class DBHelper {
  private $igrtSqli;
  
  public function getExptDaySessionCounts($exptId) {
    $s2detailQry = sprintf("CALL getStep2Details(%s, @dayCnt, @sessionCnt)", $exptId);
    $this->igrtSqli->query($s2detailQry);
    $s2result = $this->igrtSqli->query("SELECT @dayCnt as dayCnt, @sessionCnt as sessionCnt");
    if ($this->igrtSqli->affected_rows > 0) {
      $s2dRow = $s2result->fetch_object();
      $dayCnt = $s2dRow->dayCnt;
      $sessionCnt = $s2dRow->sessionCnt;
      $retArray = array("status" => "ok", "dayCnt" => $dayCnt, "sessionCnt" => $sessionCnt);
    }
    else {
      $retArray = array("status" => "error");
    }
    return $retArray;
  }
  
  public function getStep4Status($exptId) {
    $getShuffleCntQry = sprintf("SELECT * FROM wt_Step4JudgeCounts WHERE exptId='%s'", $exptId);
    $shuffleResult = $this->igrtSqli->query($getShuffleCntQry);
    if ($this->igrtSqli->affected_rows > 0) {
      $s4Row = $shuffleResult->fetch_object();
      $evenS4JudgeCnt = (is_null($s4Row->evenS4JudgeCount)) ? 0 : $s4Row->evenS4JudgeCount;
      $oddS4JudgeCnt = (is_null($s4Row->oddS4JudgeCount)) ? 0 : $s4Row->oddS4JudgeCount;
      $s4Details = array(
        "oddS4JudgeCnt" => $oddS4JudgeCnt,
        "evenS4JudgeCnt" => $evenS4JudgeCnt,
        "unfinishedOddCnt" => 0,
        "startedOddCnt" => 0,
        "unfinishedEvenCnt" => 0,
        "startedEvenCnt" => 0,
        "unfinishedOddList" => array(),
        "startedOddList" => array(),
        "unfinishedEvenList" => array(),
        "startedEvenList" => array(),
      );
      if ($oddS4JudgeCnt > 0) {
        $unratedOddQry = sprintf("SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE exptId='%s' AND jType=1 AND rated=0 ORDER BY s4jNo", $exptId);        
        $unratedOddResult = $this->igrtSqli->query($unratedOddQry);
        $s4Details["unfinishedOddCnt"] = $this->igrtSqli->affected_rows;
        while ($unratedOddRow = $unratedOddResult->fetch_object()) {
          array_push($s4Details["unfinishedOddList"], $unratedOddRow->s4jNo);
        }
        $ratedOddQry = sprintf("SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE exptId='%s' AND jType=1 AND rated=1 ORDER BY s4jNo", $exptId);        
        $ratedOddResult = $this->igrtSqli->query($ratedOddQry);
        $s4Details["startedOddCnt"] = $this->igrtSqli->affected_rows;
        while ($ratedOddRow = $ratedOddResult->fetch_object()) {
          array_push($s4Details["startedOddList"], $ratedOddRow->s4jNo);
        }        
      }
      if ($evenS4JudgeCnt > 0) {
        $unratedEvenQry = sprintf("SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE exptId='%s' AND jType=0 AND rated=0 ORDER BY s4jNo", $exptId);        
        $unratedEvenResult = $this->igrtSqli->query($unratedEvenQry);
        $s4Details["unfinishedEvenCnt"] = $this->igrtSqli->affected_rows;
        while ($unratedEvenRow = $unratedEvenResult->fetch_object()) {
          array_push($s4Details["unfinishedEvenList"], $unratedEvenRow->s4jNo);
        }
        $ratedEvenQry = sprintf("SELECT DISTINCT(s4jNo) FROM wt_Step4datasets WHERE exptId='%s' AND jType=0 AND rated=1 ORDER BY s4jNo", $exptId);        
        $ratedEvenResult = $this->igrtSqli->query($ratedEvenQry);
        $s4Details["startedEvenCnt"] = $this->igrtSqli->affected_rows;
        while ($ratedEvenRow = $ratedEvenResult->fetch_object()) {
          array_push($s4Details["startedEvenList"], $ratedEvenRow->s4jNo);
        }        
      }
      return $s4Details;
    }
    else {
      return array("status" => "not configured");
    }
  }
  
  public function getExptTitleFromId($exptId) {
    $qry ="CALL getExperimentDetails($exptId,@title)";
    $this->igrtSqli->query($qry);
    $sr2 = $this->igrtSqli->query("SELECT @title as exptTitle");
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $sr2->fetch_object();
      return $row->exptTitle;    
    }
    else {
      return null;
    }
  }

  public function getEmailFromUid($uid) {
    $qry = sprintf("SELECT * FROM igUsers WHERE id='%s'", $uid);
    $result = $this->igrtSqli->query($qry);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return $row->email;
    }
    return null;
  }
  
  public function getJTypeLabel($exptId, $jType) {
    $qry = sprintf("SELECT * FROM igExperiments WHERE exptId='%s'", $exptId);
    $result = $this->igrtSqli->query($qry);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return ($jType == 0) ? $row->evenS1Label : $row->oddS1Label;
    }
    return null;
  }

  function __construct($_igrtSqli) {
    $this->igrtSqli=$_igrtSqli;
  }
}
