<?php
/**
 * model representing a step1 session
 *
 * @author MartinHall
 */
class step1Model {
  public $exptId;
  public $sessionNo;
  public $dayNo;
  public $jCnt;
  public $evenJudges=array();
  public $oddJudges=array();
  public $started;
  public $eModel;
  

  function __construct($_exptId, $_dayNo, $_sessionNo, $_jCnt, $_eModel) {
    $this->exptId=$_exptId;
    $this->sessionNo=$_sessionNo;
    $this->dayNo=$_dayNo;
    $this->jCnt=$_jCnt;
    $this->started = 0;
    $this->eModel = $_eModel;
  }
}

