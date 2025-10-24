<?php
class dataHandler {
  private $igrtSqli;
  private $qsPassRate = array();
  private $meanQsPassRate = 0;
  private $meanJ1PassRate = 0;
  private $meanJ2PassRate = 0;

  function getSummaryDetails() {
    return array('passRate'=>$this->meanQsPassRate, 'j1passRate'=>$this->meanJ1PassRate, 'j2passRate'=>$this->meanJ2PassRate);
  }
  
  function getPassRateDetails($qsNo) {
    foreach ($this->qsPassRate as $qs) {
      if ($qs["qsNo"] == $qsNo) { return $qs; }
    }
  }
  
  function calculateS4PassRates($exptId, $jType) {
    $qsNoQry = sprintf("SELECT DISTINCT(actualJNo) as qsNo FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo", $exptId, $jType);
    $qsNoResult = $this->igrtSqli->query($qsNoQry);
    while ($qsNoRow = $qsNoResult->fetch_object()) {
      $qsNo = $qsNoRow->qsNo;
      $responseQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", $exptId, $jType, $qsNo);
      $responseResult = $this->igrtSqli->query($responseQry);
      $correctCnt = 0;
      $dontKnowCnt = 0;
      $incorrectCnt = 0;
      $J1CorrectCnt = 0;
      $J1DontKnowCnt = 0;
      $J1IncorrectCnt = 0;
      $J2CorrectCnt = 0;
      $J2DontKnowCnt = 0;
      $J2IncorrectCnt = 0;
      while ($responseRow = $responseResult->fetch_object()) {
        $correct = $responseRow->correct;
        $confidence = $responseRow->confidence;
        $shuffleHalf = $responseRow->shuffleHalf;
        if ($correct == 1) {
          if (($confidence == "interval3") || ($confidence == "interval4")) {
            ++$correctCnt;
            if ($shuffleHalf == 1) {
              ++$J1CorrectCnt;
            } 
            else {
              ++$J2CorrectCnt;              
            }
          }
          else {
            ++$dontKnowCnt;
            if ($shuffleHalf == 1) {
              ++$J1DontKnowCnt;
            }
            else {
              ++$J2DontKnowCnt;
            }
          }
        }
        else {
          if (($confidence == "interval3") || ($confidence == "interval4")) {
            ++$incorrectCnt;
            if ($shuffleHalf == 1) {
              ++$J1IncorrectCnt;              
            }
            else {
              ++$J2IncorrectCnt;                            
            }
          }
          else {
            ++$dontKnowCnt;
            if ($shuffleHalf == 1) {
              ++$J1DontKnowCnt;
            }
            else {
              ++$J2DontKnowCnt;
            }
          }
        }
      }
      $passRate = 1 - ( ($correctCnt - $incorrectCnt) / ($correctCnt + $dontKnowCnt + $incorrectCnt) );
      $j1passRate = 1 - (($J1CorrectCnt - $J1IncorrectCnt) / ($J1CorrectCnt + $J1DontKnowCnt + $J1IncorrectCnt));
      $j2passRate = 1 - (($J2CorrectCnt - $J2IncorrectCnt) / ($J2CorrectCnt + $J2DontKnowCnt + $J2IncorrectCnt));    
      $qs = array(
        "qsNo" => $qsNo, 
        "passRate"=>$passRate, 
        "j1passRate"=>$j1passRate, 
        "j2passRate"=>$j2passRate, 
        "correctCnt"=>$correctCnt, 
        "dontKnowCnt"=>$dontKnowCnt, 
        "incorrectCnt"=>$incorrectCnt,
        "J1CorrectCnt"=>$J1CorrectCnt, 
        "J1DontKnowCnt"=>$J1DontKnowCnt, 
        "J1IncorrectCnt"=>$J1IncorrectCnt,
        "J2CorrectCnt"=>$J2CorrectCnt, 
        "J2DontKnowCnt"=>$J2DontKnowCnt, 
        "J2IncorrectCnt"=>$J2IncorrectCnt
      );
      array_push($this->qsPassRate, $qs);
    }
    $this->calculateMeans(); 
  }
  
  function calculateMeans() {
    $prTot = 0;
    $j1Tot = 0;
    $j2Tot = 0;
    $meanCnt = count($this->qsPassRate);
    foreach($this->qsPassRate as $qs) {
      $prTot+= $qs['passRate'];
      $j1Tot+= $qs['j1passRate'];
      $j2Tot+= $qs['j2passRate'];
    }
    $this->meanQsPassRate = $prTot / $meanCnt;    
    $this->meanJ1PassRate = $j1Tot / $meanCnt;    
    $this->meanJ2PassRate = $j2Tot / $meanCnt;    
  }

  function __construct($_igrtSqli) {
    $this->igrtSqli=$_igrtSqli;
  }
}
