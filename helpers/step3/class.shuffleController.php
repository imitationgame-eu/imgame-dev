<?php

//class ArrayValue implements JsonSerializable {
//
//  public function __construct(array $array) {
//      $this->array = $array;
//  }
//
//  public function jsonSerialize() {
//      return $this->array;
//  }
//
//}


class shuffleControllerClass {
  private $igrtSqli;
  private $exptId;
  

  function doVirtualProcess() {
    global $noS4judges;
    global $judgeList;
    for ($i=0; $i<$noS4judges; $i++) {
      while (judgeTooManyVirtual($i)) {
        $virtualParameters = judgeFirstVirtual($i);
        $actualJNo = $virtualParameters['actualJNo'];
        $shuffleHalf = $virtualParameters['shuffleHalf'];
        echo 'multiple in '.$i.' ajN='.$actualJNo;

        if ($actualJNo > -1) {
          $newJudge = getJudgeForSwap($i, $actualJNo, $shuffleHalf);
          echo 'new judge '.$newJudge;
          if ($newJudge > -1) {
            //do swap here
            $srcPtr = getDataPtr($i, $actualJNo);
            $destPtr = getDataPtr($newJudge, $actualJNo);
            echo 'from:'.$i.':'.$srcPtr.' ------ to:'.$newJudge.':'.$destPtr;
            $srcDef = $judgeList[$i]['transcriptList'][$srcPtr];
            echo print_r($srcDef, true);
            $destDef = $judgeList[$newJudge]['transcriptList'][$destPtr];
            echo print_r($destDef, true);
            $judgeList[$i]['transcriptList'][$srcPtr] = $destDef;
            $judgeList[$newJudge]['transcriptList'][$destPtr] = $srcDef;           
          }
        }
      }
    }   
  }
  
  function getDataPtr($jPtr, $actualJNo) {
    global $judgeList;
    for ($i=0; $i<count($judgeList[$jPtr]['transcriptList']); $i++) {
      if ($judgeList[$jPtr]['transcriptList'][$i]['actualJNo'] == $actualJNo) { return $i; }
    }
    return -1;
  }

  function judgeTooManyVirtual($jPtr) {
    global $judgeList;
    $noVirtual = 0;
    for ($i=0; $i<count($judgeList[$jPtr]['transcriptList']); $i++) {
      if ($judgeList[$jPtr]['transcriptList'][$i]['isVirtual'] == 1) { ++$noVirtual; }
    }
    return $noVirtual > 1 ? true : false;
  }

  function judgeCanSwap($jPtr) {
    global $judgeList;
    for ($i=0; $i<count($judgeList[$jPtr]['transcriptList']); $i++) {
      if ($judgeList[$jPtr]['transcriptList'][$i]['isVirtual'] == 1) { return false; }
    }
    return true;
  } 

  function judgeHasQS($jPtr, $actualJNo, $shuffleHalf) {
    global $judgeList;
    for ($i=0; $i<count($judgeList[$jPtr]['transcriptList']); $i++) {
      if (
        ($judgeList[$jPtr]['transcriptList'][$i]['actualJNo'] == $actualJNo) &&
        ($judgeList[$jPtr]['transcriptList'][$i]['shuffleHalf'] == $shuffleHalf) ) { return true; }
    }
    return false;
  } 
  
  function judgeFirstVirtual($jPtr) {
    global $judgeList;
    for ($i=0; $i<count($judgeList[$jPtr]['transcriptList']); $i++) {
      if ($judgeList[$jPtr]['transcriptList'][$i]['isVirtual'] == 1) { 
        $retArray = array (
          'actualJNo' => $judgeList[$jPtr]['transcriptList'][$i]['actualJNo'],
          'shuffleHalf' => $judgeList[$jPtr]['transcriptList'][$i]['shuffleHalf']
        );
        return $retArray; 
      }
    }
    return array();
  }
  
  function getJudgeForSwap($jPtr, $actualJNo, $shuffleHalf) {
    global $noS4judges;
    for ($i=0; $i<$noS4judges; $i++) {
      if ($i != $jPtr) {
        if (judgeHasQS($i, $actualJNo, $shuffleHalf)) {
          echo 'has QS'.$i;
          if (judgeCanSwap($i)) {
            return $i;
          }
        }
      }
    }
    return -1;
  }
  
// <editor-fold defaultstate="collapsed" desc="snow-shuffle routines">

  function doSnowShuffle($jType, $noJudges) {
    // the snow shuffle is only applicable to a null experiment
    // currently exptId = 309, "noise test 6 rebooted"
    global $igrtSqli;
    $maxCnt = 0;
    $dsList = [];
    $dsTranscriptList = [];
    $reverseTranscriptList = [];
    $judgeList = [];
    $arQry = sprintf("SELECT * FROM wt_Step2summaries WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $this->exptId, $jType);
    $arResult = $this->igrtSqli->query($arQry);    
    // need to get the actual count of usable respondents for each iQS
    $iQSList = [];
    while ($arRow = $arResult->fetch_object()) {
      $actualJNo = $arRow->actualJNo;
      $dayNo = $arRow->dayNo;
      $sessionNo = $arRow->sessionNo;
      $jNo = $arRow->jNo;
      $dsLabel = sprintf("%s_%s_%s_%s_%s_",$jType, $actualJNo, $dayNo, $sessionNo, $jNo);
      $getrespCntQry = "SELECT * FROM wt_Step3summaries WHERE exptId=$this->exptId AND jType=$jType AND actualJNo=$actualJNo ORDER BY s3respNo ASC";
      $rcResult = $this->igrtSqli->query($getrespCntQry);
      array_push( $iQSList, 
        [
          'actualJNo'=> $actualJNo, 
          'dayNo'=>$dayNo, 
          'sessionNo'=>$sessionNo,
          'jNo'=> $jNo,
          'activeS2Cnt'=>$this->igrtSqli->affected_rows,
          'chosenCombinations'=> []
        ]
      );
    }
    // create number list of 45 and 55 internal combinations for iQSs with 10 and 11 S2 Ps respectively
    $ptr45 = [];
    $ptr55 = [];
    for ($i=1; $i<10; $i++) {
      for ($j=0; $j<$i; $j++) {
        array_push($ptr45, ['np1'=> $i, 'np2'=>$j]);        
      }
    }
    for ($i=1; $i<11; $i++) {
      for ($j=0; $j<$i; $j++) {
        array_push($ptr55, ['np1'=> $i, 'np2'=>$j]);        
      }
    }
    // create lists/counts of those with 10 and 11 S2 Ps
    $no_10 = 0;
    $no_11 = 0;
    $iQS_10 = [];
    $iQS_11 = [];
    for ($i=0; $i<count($iQSList); $i++) {
      if ($iQSList[$i]['activeS2Cnt'] == 10) {
        array_push($iQS_10, $iQSList[$i]);
        ++$no_10;
      }
      else {
        array_push($iQS_11, $iQSList[$i]);
        ++$no_11;        
      }
    }
    $ptr_10 = 0;
    $ptr_11 = 0;
    $consecutive_10 = 0;
    $consecutive_11 = 0;
    $selector_10 = 0;
    $selector_11 = 0;
    
    $iQSPtr = 0;
    $allSelected10 = false;
    $allSelected11 = false;
    while ( (!$allSelected10) || (!$allSelected11) ) {    
      if ($iQSList[$iQSPtr]['activeS2Cnt'] == 10) {
        if ($selector_10 < 45) { array_push($iQSList[$iQSPtr]['chosenCombinations'], $ptr45[$selector_10]); }
        if (($selector_10 >= 44) && ($consecutive_10 == 1)) {
          $allSelected10 = true;
        }
        if (++$consecutive_10 == 2) {
          $consecutive_10 = 0;
          ++$selector_10;
        }
      }
      else {
        if ($selector_11 < 55) { array_push($iQSList[$iQSPtr]['chosenCombinations'], $ptr55[$selector_11]); }
        if (($selector_11 >= 54) && ($consecutive_11 == 1)) {
          $allSelected11 = true;
        }
        if (++$consecutive_11 == 2) {
          $consecutive_11 = 0;
          ++$selector_11;
        }
      }
      if (++$iQSPtr == count($iQSList)) { $iQSPtr = 0; }
    }
//    $debug = print_r($iQSList, true);
//    return $debug;
    $maxCnt = 0;
    if (isset($interimSet)) { unset($interimSet); }
    $interimSet = [];
    for ($i=0; $i<count($iQSList); $i++) { 
      $combinationCount = count($iQSList[$i]['chosenCombinations']);
      if ($combinationCount > $maxCnt) { $maxCnt = $combinationCount; }
      for ($j=0; $j<$combinationCount; $j++) {
        array_push($interimSet, 
          [
            'dsNo' => $iQSList[$i]['actualJNo'] - 1,
            'actualJNo' => $iQSList[$i]['actualJNo'],
            's3respNo1' => $iQSList[$i]['chosenCombinations'][$j]['np1'],   
            's3respNo2' => $iQSList[$i]['chosenCombinations'][$j]['np2'],   
            'dayNo' => $iQSList[$i]['dayNo'],
            'sessionNo' => $iQSList[$i]['sessionNo'],
            'jNo' => $iQSList[$i]['jNo'],
            'shuffleHalf' => 0
          ]
        );         
      }
    }
    $interimCnt = count($interimSet);
    //$reverseInterimSet = array_reverse($interimSet);
    $reverseInterimSet = array_values($interimSet);
    for ($j=0; $j<$interimCnt; $j++) {
      $interimSet[$j]['shuffleHalf'] = 1;
      array_push($dsTranscriptList, $interimSet[$j]);
      $reverseInterimSet[$j]['shuffleHalf'] = 2;
      array_push($reverseTranscriptList, $reverseInterimSet[$j]);
    }
//    $debug = count($interimSet). '_' . count($dsTranscriptList) . '_' . count($reverseTranscriptList);
//    return $debug;
    for ($i = 0; $i < $noJudges; $i++) {
      array_push($judgeList, ['transcriptList' => []]);
    }
    $jPtr = 0;
    // allocate  first list of dataset/pptNo combo
    for ($transcriptPtr = 0; $transcriptPtr < count($dsTranscriptList); $transcriptPtr++) {
      array_push($judgeList[$jPtr]['transcriptList'], $dsTranscriptList[$transcriptPtr]);
      if (++$jPtr == $noJudges) { $jPtr = 0 ;}
    }
    // append second lot of dataset/pptNo combo, but offset to next to be filled
    $jPtr = $maxCnt;
    for ($transcriptPtr = 0; $transcriptPtr <count($dsTranscriptList); $transcriptPtr++) {
      array_push($judgeList[$jPtr]['transcriptList'], $reverseTranscriptList[$transcriptPtr]);
      if (++$jPtr == $noJudges) { $jPtr = 0 ;}
    }
//    // store judge allocations in ne_ (null experiment) tables for use by ne_Step4
    $delQry = "DELETE FROM ne_Step4datasets WHERE exptId=$this->exptId AND jType=$jType"; 
    $this->igrtSqli->query($delQry);
    $delQry = "DELETE FROM ne_Step4judgeStatus WHERE exptId=$this->exptId AND jType=$jType"; 
    $this->igrtSqli->query($delQry);
    for ($i=0; $i<$noJudges; $i++) {
      $insertS4jStatus = sprintf("INSERT INTO ne_Step4judgeStatus (exptId, jType, s4jNo, started) VALUES('%s', '%s', '%s', 0)", 
          $this->exptId, $jType, ($i + 1));
      $this->igrtSqli->query($insertS4jStatus);
      for ($j=0; $j<count($judgeList[$i]['transcriptList']); $j++) {
        $tp = $judgeList[$i]['transcriptList'][$j];
        $actualJNo = $tp['actualJNo'];
        $dayNo = $tp['dayNo'];
        $sessionNo = $tp['sessionNo'];
        $jNo = $tp['jNo'];
        $s3respNo1 = $tp['s3respNo1'];
        $s3respNo2 = $tp['s3respNo2'];
        $shuffleHalf = $tp['shuffleHalf'];
        $insQry = sprintf("INSERT INTO ne_Step4datasets (exptId, jType, s4jNo, actualJNo, dayNo, sessionNo, jNo,  s3respNo1, s3respNo2, shuffleHalf, rated)
                           VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                           $this->exptId, $jType, ($i + 1), $actualJNo, $dayNo, $sessionNo, $jNo, $s3respNo1, $s3respNo2, $shuffleHalf, 0);  
        $igrtSqli->query($insQry);
      }    
    }
    return $judgeList;
  }
    
  function makeSnowShuffleJSON($shuffleLabel, $judgeList) {
    $maxColCnt = 0;
    for ($i=0; $i<count($judgeList); $i++) {
      foreach($judgeList[$i]['transcriptList'] as $tp) {
        if (count($judgeList[$i]['transcriptList']) > $maxColCnt) {
          $maxColCnt = count($judgeList[$i]['transcriptList']);
        }
      }
    }
    $json = "{";
    $json.= "\"maxCols\":".$maxColCnt.",";
    $json.= "\"shuffleLabel\":\"".$shuffleLabel."\",";
    $json.= "\"judges\":[";
    for ($i=0; $i<count($judgeList); $i++) {
      $jLabel =  $i + 1;
      if ($i>0) { $json.=","; }
      $json.= "{";
        $json.= "\"s4jNo\":".$i.",";
        $json.= "\"s4jLabel\":\"".$jLabel."\",";
        $json.= "\"transcripts\":[";
        for ( $j=0; $j<count($judgeList[$i]['transcriptList']); $j++) {
          $tp = $judgeList[$i]['transcriptList'][$j];
          if ($j>0) { $json.=","; }
          $json.= "{";
            $json.= "\"dsLabel\":\"".$tp['actualJNo']."\",";
            $json.= "\"shuffleHalf\":".$tp['shuffleHalf'].",";
            $respLabel = $tp['s3respNo1'] + 1;
            $json.= "\"respLabel1\":\"".$respLabel."\","; 
            $respLabel = $tp['s3respNo2'] + 1;
            $json.= "\"respLabel2\":\"".$respLabel."\""; 
          $json.="}";
        }
      $json.= "]}";
    }    
    $json.="]";
    $json.= "}";
    return $json;
  }

  function storeSnowShuffleJSON($oddJudgeList, $evenJudgeList) {
    global $igrtSqli;
    $json = "{";
      $json.= "\"exptId\":".$this->exptId.",";
      $json.= "\"halfs\":[";
        $json.= $this->makeSnowShuffleJSON("odd shuffle", $oddJudgeList);
        $json.= ",";
        $json.= $this->makeSnowShuffleJSON("even shuffle", $evenJudgeList);
      $json.= "]";
    $json.= "}";
    $sql =sprintf("SELECT * FROM wt_Step3ShuffleJSON WHERE exptId='%s'", $this->exptId);
    
    $sr = $igrtSqli->query($sql);
    if ($sr) {
      $sql = sprintf("UPDATE wt_Step3ShuffleJSON SET json='%s' WHERE exptId='%s'", $igrtSqli->real_escape_string($json), $this->exptId);
    }
    else {
      $sql = sprintf("INSERT INTO wt_Step3ShuffleJSON (exptId, json) VALUES('%s', '%s')", $this->exptId, $igrtSqli->real_escape_string($json) );      
    }
    $igrtSqli->query($sql);
  }

  function getSnowShuffleJSON() {
    $sql = sprintf("SELECT * FROM wt_Step3ShuffleJSON WHERE exptId='%s'", $this->exptId);
    $result = $this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return $row->json;
    }
  }
  
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="linked-experiment shuffle routines">

  function doLEShuffle($jType, $noJudges) {
    // the LE shuffle currently only applies to 
    // exptId 327 which is linked to 328, 329, 330
    global $igrtSqli;
    $iCounts = [];
    $iPtrs = [];
    $iCntQry1 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=327 AND jType='%s'", $jType);
    $result1 = $igrtSqli->query($iCntQry1);
    $row = $result1->fetch_object();
    $iCounts[0] = $row->maxJNo;
    $iCntQry2 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=328 AND jType='%s'", $jType);
    $result2 = $igrtSqli->query($iCntQry2);
    $row = $result2->fetch_object();
    $iCounts[1] = $row->maxJNo;
    $iCntQry3 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=329 AND jType='%s'", $jType);
    $result3 = $igrtSqli->query($iCntQry3);
    $row = $result3->fetch_object();
    $iCounts[2] = $row->maxJNo;
    $iCntQry4 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=330 AND jType='%s'", $jType);
    $result4 = $igrtSqli->query($iCntQry4);
    $row = $result4->fetch_object();
    $iCounts[3] = $row->maxJNo;
    $totalTranscripts = 0;
    $transcriptList = [];
    $iSelected = [];
    for ($i=0; $i<4; $i++) {
      $totalTranscripts+= $iCounts[$i]+1; //jNo 0-indexed in tables
      $selected = [];
      for ($j=0; $j<=$iCounts[$i]; $j++) {
        $selected[$j] = 0;
      }
      $iSelected[$i] = $selected;
    }
//    echo print_r($iSelected, true);
    for ($i=0; $i<$totalTranscripts; $i++) {
      $gotTI = false;
      while (!$gotTI) {
        $exptPtr = mt_rand(0,3);
        $igPtr = mt_rand(0, $iCounts[$exptPtr]);
        if ($iSelected[$exptPtr][$igPtr] == 0) {
          $iSelected[$exptPtr][$igPtr] = 1;
          $iTranscript = new stdClass();
          $iTranscript->exptPtr = $exptPtr;
          $iTranscript->igPtr = $igPtr;
          array_push($transcriptList, $iTranscript);   
          $gotTI = true;
        }
      }
    }
    $longList = [];
    for ($i=0; $i<10; $i++) {
      for ($j=0; $j<count($transcriptList); $j++) {
        array_push($longList, $transcriptList[$j]);
      }
    }
//    for ($i=0; $i<count($longList); $i++) {
//      echo $longList[$i]->exptPtr.','.$longList[$i]->igPtr.PHP_EOL;
//    }
    $igrtSqli->query("TRUNCATE wt_LinkedStep4datasets");
    $transcriptPtr = 0;
    // do 42 judges who get 8 transcripts each
    for ($i=0; $i<41; $i++) {
      for ($j=0; $j<8; $j++) {
        switch ($longList[$transcriptPtr]->exptPtr) {
          case 0 : {
            $exptId = 327;
            break;
          }
          case 1 : {
            $exptId = 328;
            break;
          }
          case 2 : {
            $exptId = 329;
            break;
          }
          case 3 : {
            $exptId = 330;
            break;
          }
        }
        $insert = sprintf("INSERT INTO wt_LinkedStep4datasets (exptId, jType, s4jNo, dayNo, sessionNo, jNo, rated) "
          . "VALUES('%s', 0, '%s', 0, 0, '%s', 0)",
          $exptId, $i, $longList[$transcriptPtr]->igPtr);
        $igrtSqli->query($insert);
        ++$transcriptPtr;
      }
    }
    // do 6 judges who get 7 transcripts each
    for ($i=42; $i<48; $i++) {
      for ($j=0; $j<7; $j++) {
        switch ($longList[$transcriptPtr]->exptPtr) {
          case 0 : {
            $exptId = 327;
            break;
          }
          case 1 : {
            $exptId = 328;
            break;
          }
          case 2 : {
            $exptId = 329;
            break;
          }
          case 3 : {
            $exptId = 330;
            break;
          }
        }
        $insert = sprintf("INSERT INTO wt_LinkedStep4datasets (exptId, jType, s4jNo, dayNo, sessionNo, jNo, rated) "
          . "VALUES('%s', 0, '%s', 0, 0, '%s', 0)",
          $exptId, $i, $longList[$transcriptPtr]->igPtr);
        $igrtSqli->query($insert);
        ++$transcriptPtr;
      }
    }
    return $this->makeLEShuffleJSON('le TBT shuffle');
  }
  
  function makeLEShuffleJSON($shuffleLabel) {
    // get details from database
    $getDistinctQry = "SELECT DISTINCT(s4jNo) FROM wt_LinkedStep4datasets ORDER BY s4jNo ASC";
    $getDistinctResult = $this->igrtSqli->query($getDistinctQry);
    $json = "{";
    $json.= "\"shuffleLabel\":\"".$shuffleLabel."\",";
    $json.= "\"judges\":[";
    $i = 0;
    while ($s4Row = $getDistinctResult->fetch_object()) {
      $jLabel =  $i + 1;
      if ($i>0) { $json.=","; }
      $json.= "{";
      $json.= "\"s4jNo\":".$i.",";
      $json.= "\"s4jLabel\":\"".$jLabel."\",";
      $json.= "\"transcripts\":[";
      $getQry = sprintf("SELECT * FROM wt_LinkedStep4datasets WHERE s4jNo='%s' ORDER BY exptId ASC, jNo ASC", $s4Row->s4jNo);
      $result = $this->igrtSqli->query($getQry);
      $j = 0;
      while ($dsRow = $result->fetch_object()) {
        if ($j>0) { $json.=","; }
        $json.= "{";
          $json.= "\"exptId\":\"".$dsRow->exptId."\",";
          $json.= "\"jNo\":".$dsRow->jNo."";
        $json.="}";
        $j++;
      }
      $json.= "]}";
      ++$i;
    }
    $json.="]";
    $json.= "}";
    return $json;
  }

  function storeLEShuffleJSON($json) {
    global $igrtSqli;
    $sql =sprintf("SELECT * FROM wt_Step3LEShuffleJSON WHERE exptId='%s'", $this->exptId);    
    $sr = $igrtSqli->query($sql);
    if ($sr) {
      $sql = sprintf("UPDATE wt_Step3LEShuffleJSON SET json='%s' WHERE exptId='%s'", $igrtSqli->real_escape_string($json), $this->exptId);
    }
    else {
      $sql = sprintf("INSERT INTO wt_Step3LEShuffleJSON (exptId, json) VALUES('%s', '%s')", $this->exptId, $igrtSqli->real_escape_string($json) );      
    }
    $igrtSqli->query($sql);
  }

  function getLEShuffleJSON() {
    $sql = sprintf("SELECT * FROM wt_Step3LEShuffleJSON WHERE exptId='%s'", $this->exptId);
    $result = $this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return $row->json;
    }
  }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="linked-experiment (turn by turn) shuffle routines">

  function doTBTShuffle($jType, $noJudges) {
    // the LE-TBT shuffle currently only applies to 
    // exptId 327 which is linked to 328, 329, 330
    global $igrtSqli;
    $iCounts = [];
    $iPtrs = [];
    $iCntQry1 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=327 AND jType='%s'", $jType);
    $result1 = $igrtSqli->query($iCntQry1);
    $row = $result1->fetch_object();
    $iCounts[0] = $row->maxJNo;
    $iCntQry2 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=328 AND jType='%s'", $jType);
    $result2 = $igrtSqli->query($iCntQry2);
    $row = $result2->fetch_object();
    $iCounts[1] = $row->maxJNo;
    $iCntQry3 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=329 AND jType='%s'", $jType);
    $result3 = $igrtSqli->query($iCntQry3);
    $row = $result3->fetch_object();
    $iCounts[2] = $row->maxJNo;
    $iCntQry4 = sprintf("SELECT MAX(jNo) AS maxJNo FROM md_dataStep1reviewed WHERE exptId=330 AND jType='%s'", $jType);
    $result4 = $igrtSqli->query($iCntQry4);
    $row = $result4->fetch_object();
    $iCounts[3] = $row->maxJNo;
    $totalTranscripts = 0;
    $transcriptList = [];
    $iSelected = [];
    for ($i=0; $i<4; $i++) {
      $totalTranscripts+= $iCounts[$i]+1; //jNo 0-indexed in tables
      $selected = [];
      for ($j=0; $j<=$iCounts[$i]; $j++) {
        $selected[$j] = 0;
      }
      $iSelected[$i] = $selected;
    }
//    echo print_r($iSelected, true);
    for ($i=0; $i<$totalTranscripts; $i++) {
      $gotTI = false;
      while (!$gotTI) {
        $exptPtr = mt_rand(0,3);
        $igPtr = mt_rand(0, $iCounts[$exptPtr]);
        if ($iSelected[$exptPtr][$igPtr] == 0) {
          $iSelected[$exptPtr][$igPtr] = 1;
          $iTranscript = new stdClass();
          $iTranscript->exptPtr = $exptPtr;
          $iTranscript->igPtr = $igPtr;
          array_push($transcriptList, $iTranscript);   
          $gotTI = true;
        }
      }
    }
    $longList = [];
    for ($i=0; $i<10; $i++) {
      for ($j=0; $j<count($transcriptList); $j++) {
        array_push($longList, $transcriptList[$j]);
      }
    }
//    for ($i=0; $i<count($longList); $i++) {
//      echo $longList[$i]->exptPtr.','.$longList[$i]->igPtr.PHP_EOL;
//    }
    $igrtSqli->query("TRUNCATE wt_LinkedTBTStep4datasets");
    $transcriptPtr = 0;
    // do 75 judges who get 5 transcripts each
    for ($i=0; $i<74; $i++) {
      for ($j=0; $j<5; $j++) {
        switch ($longList[$transcriptPtr]->exptPtr) {
          case 0 : {
            $exptId = 327;
            break;
          }
          case 1 : {
            $exptId = 328;
            break;
          }
          case 2 : {
            $exptId = 329;
            break;
          }
          case 3 : {
            $exptId = 330;
            break;
          }
        }
        $getValidQNos = sprintf("SELECT DISTINCT(qNo) AS qNo FROM md_dataStep1reviewed "
          . "WHERE exptId='%s' AND jType=0 AND dayNo=1 AND sessionNo=1 AND jNo='%s' AND canUse=1 "
          . "ORDER BY qNo ASC",
          $exptId, $longList[$transcriptPtr]->igPtr);
        $getValidResult = $igrtSqli->query($getValidQNos);
        while ($getValidRow=$getValidResult->fetch_object()) {
          $qNo = $getValidRow->qNo;
          $insert = sprintf("INSERT INTO wt_LinkedTBTStep4datasets (isFinalRating, exptId, jType, s4jNo, dayNo, sessionNo, jNo, qNo, rated) "
            . "VALUES('0', '%s', 0, '%s', 0, 0, '%s', '%s', 0)",
            $exptId, $i, $longList[$transcriptPtr]->igPtr, $qNo);
          echo $insert.PHP_EOL;
          $igrtSqli->query($insert);
        }
        // now insert marker to indicate change of IG and hence a 'standard' S4 judgement is now needed before going to 
        // next IG (or survey)
        $insert = sprintf("INSERT INTO wt_LinkedTBTStep4datasets (isFinalRating, exptId, jType, s4jNo, dayNo, sessionNo, jNo, qNo, rated) "
          . "VALUES('1', '%s', 0, '%s', 0, 0, '%s', '%s', 0)",
          $exptId, $i, $longList[$transcriptPtr]->igPtr, $qNo);
        $igrtSqli->query($insert);        
        ++$transcriptPtr;
      }
    }
    return $this->makeTBTShuffleJSON('le TBT shuffle');
  }
  
  function makeTBTShuffleJSON($shuffleLabel) {
    global $igrtSqli;
    // get details from database
    $s4judges = [];
    $getDistinctQry = "SELECT DISTINCT(s4jNo) FROM wt_LinkedTBTStep4datasets ORDER BY s4jNo ASC";
    $getDistinctResult = $igrtSqli->query($getDistinctQry);
    while ($s4Row = $getDistinctResult->fetch_object()) {
      $getExptIdQry = sprintf("SELECT DISTINCT(exptId) AS exptId FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' ORDER BY exptId ASC", $s4Row->s4jNo);
      $exptIds = [];
      $exptIdResult = $igrtSqli->query($getExptIdQry);
      while ($exptIdRow = $exptIdResult->fetch_object()) {
        $getJNoQry = sprintf("SELECT DISTINCT(jNo) AS jNo FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND exptId='%s' ORDER BY jNo ASC", $s4Row->s4jNo, $exptIdRow->exptId);
        $jNoTxt = '';
        $jNoResult = $igrtSqli->query($getJNoQry);
        while ($jNoRow = $jNoResult->fetch_object()) {
          $turnQry = sprintf("SELECT qNo FROM wt_LinkedTBTStep4datasets WHERE s4jNo='%s' AND exptId='%s' AND jNo='%s' ORDER BY qNo ASC", $s4Row->s4jNo, $exptIdRow->exptId, $jNoRow->jNo);
          $turnResult = $igrtSqli->query($turnQry);
          $turnsTxt = '';
          while ($turnRow = $turnResult->fetch_object()) {
            $turnsTxt.= $turnRow->qNo.' ';
          }
          $jNoTxt.= $jNoRow->jNo. ': '.$turnsTxt.PHP_EOL;
        }
        array_push($exptIds,['exptId'=>$exptIdRow->exptId, 'igs'=>$jNoTxt]);
      }
      array_push($s4judges, ['s4jNo'=>$s4Row->s4jNo, 'exptIds'=>$exptIds]);
    }
    $jsonArray = ['s4judges'=>$s4judges];
    return json_encode(new ArrayValue($jsonArray), JSON_PRETTY_PRINT);    
  }

  function storeTBTShuffleJSON($json) {
    global $igrtSqli;
    $sql =sprintf("SELECT * FROM wt_Step3TBTShuffleJSON WHERE exptId='%s'", $this->exptId);    
    $sr = $igrtSqli->query($sql);
    if ($sr) {
      $sql = sprintf("UPDATE wt_Step3TBTShuffleJSON SET json='%s' WHERE exptId='%s'", $igrtSqli->real_escape_string($json), $this->exptId);
    }
    else {
      $sql = sprintf("INSERT INTO wt_Step3TBTShuffleJSON (exptId, json) VALUES('%s', '%s')", $this->exptId, $igrtSqli->real_escape_string($json) );      
    }
    $igrtSqli->query($sql);
  }

  function getTBTShuffleJSON() {
    $sql = sprintf("SELECT * FROM wt_Step3TBTShuffleJSON WHERE exptId='%s'", $this->exptId);
    $result = $this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return $row->json;
    }
  }

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="traditional shuffle routines">
  
  function doShuffle($jType, $noJudges) {
    global $igrtSqli;
    $maxCnt = 0;
    $dsList = array();
    $dsTranscriptList = array();
    $reverseTranscriptList =array();
    $judgeList = array();
    $ajnQry = sprintf("SELECT * FROM wt_Step2summaries WHERE exptId='%s' AND jType='%s' ORDER BY actualJNo ASC", $this->exptId, $jType);
    $ajnResult = $igrtSqli->query($ajnQry);
    while ($ajnRow = $ajnResult->fetch_object()) {
      $actualJNo = $ajnRow->actualJNo;
      $dayNo = $ajnRow->dayNo;
      $sessionNo = $ajnRow->sessionNo;
      $jNo = $ajnRow->jNo;
      $dsLabel = sprintf("%s_%s_%s_%s_%s_",$jType, $actualJNo, $dayNo, $sessionNo, $jNo);
      $getrespCntQry = "SELECT * FROM wt_Step3summaries WHERE exptId=$this->exptId AND jType=$jType AND actualJNo=$actualJNo ORDER BY s3respNo ASC";
      $rcResult = $igrtSqli->query($getrespCntQry);
      if (isset($interimSet)) { unset($interimSet); }
      $interimSet = [];
      while ($rcRow = $rcResult->fetch_object()) {
        $transcriptDef = array(
          'dsNo' => $actualJNo-1,
          'actualJNo' => $actualJNo,
          's3respNo' => $rcRow->s3respNo,   // shuffle resp ptr (0..n)
          'respNo' => $rcRow->respNo,       // actual respNo in reviewed data
          'dsLabel' => $dsLabel,
          'dayNo' => $dayNo,
          'sessionNo' => $sessionNo,
          'isVirtual' => $rcRow->isVirtual,
          'jNo' => $jNo,
          'shuffleHalf' => 0,
        );
        array_push($interimSet, $transcriptDef);
      }
      $interimCnt = count($interimSet);
      if ($interimCnt > $maxCnt) { $maxCnt = $interimCnt; }
      $reverseInterimSet = array_reverse($interimSet);
      for ($j=0; $j<$interimCnt; $j++) {
        $interimSet[$j]['shuffleHalf'] = 1;
        array_push($dsTranscriptList, $interimSet[$j]);
        $reverseInterimSet[$j]['shuffleHalf'] = 2;
        array_push($reverseTranscriptList, $reverseInterimSet[$j]);
      }
    }
    for ($i = 0; $i < $noJudges; $i++) {
      $jListTemp = array('transcriptList' => array());
      array_push($judgeList, $jListTemp);
    }
    $jPtr = 0;
    // allocate  first list of dataset/pptNo combo
    for ($transcriptPtr = 0; $transcriptPtr < count($dsTranscriptList); $transcriptPtr++) {
      array_push($judgeList[$jPtr]['transcriptList'], $dsTranscriptList[$transcriptPtr]);
      if (++$jPtr == $noJudges) { $jPtr = 0 ;}
    }
    // append second lot of dataset/pptNo combo, but offset to next to be filled
    $jPtr = $maxCnt;
    for ($transcriptPtr = 0; $transcriptPtr <count($dsTranscriptList); $transcriptPtr++) {
      array_push($judgeList[$jPtr]['transcriptList'], $reverseTranscriptList[$transcriptPtr]);
      if (++$jPtr == $noJudges) { $jPtr = 0 ;}
    }
    // this next stage is optional for shuffles of datasets that are injected and processed (e.g 292 and 293 splitting S2 into 2 experiments)
    //doVirtualProcess();



    // store judge allocations in table for use by Step4
    $delQry = "DELETE FROM wt_Step4datasets WHERE exptId=$this->exptId AND jType=$jType"; 
    $igrtSqli->query($delQry);
    $delQry = "DELETE FROM wt_Step4judgeStatus WHERE exptId=$this->exptId AND jType=$jType"; 
    $igrtSqli->query($delQry);
    $delQry = "DELETE FROM wt_TBTStep4datasets WHERE exptId=$this->exptId AND jType=$jType"; 
    $igrtSqli->query($delQry);
    for ($i=0; $i<$noJudges; $i++) {
      $s4jNo = ($i + 1);
      $insertS4jStatus = sprintf("INSERT INTO wt_Step4judgeStatus (exptId, jType, s4jNo, started) VALUES('%s', '%s', '%s', 0)", 
          $this->exptId, $jType, $s4jNo);
      $igrtSqli->query($insertS4jStatus);
      for ($j=0; $j<count($judgeList[$i]['transcriptList']); $j++) {
        $tp = $judgeList[$i]['transcriptList'][$j];
        $actualJNo = $tp['actualJNo'];
        $dayNo = $tp['dayNo'];
        $sessionNo = $tp['sessionNo'];
        $jNo = $tp['jNo'];
        $respNo = $tp['respNo'];
        $s3respNo = $tp['s3respNo'];
        $dsLabel = $tp['dsLabel'] + $s3respNo;
        $shuffleHalf = $tp['shuffleHalf'];
        $isVirtual = $tp['isVirtual'];
        $insQry = sprintf("INSERT INTO wt_Step4datasets (exptId, jType, s4jNo, actualJNo, dayNo, sessionNo, jNo,  respNo, s3respNo, shuffleHalf, dsLabel, isVirtual, rated)
                            VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                           $this->exptId, $jType, ($i + 1), $actualJNo, $dayNo, $sessionNo, $jNo, $respNo, $s3respNo, $shuffleHalf, $dsLabel, $isVirtual, $isVirtual);  // set rated = isVirtual so that monitor screens are correct
        $igrtSqli->query($insQry);
        // now do dataset for turn-by-turn judging at STEP4, even though it will not be used in all experiments
        // get count of questions (and ignore discarded questions)
        $getQNoQry = sprintf("SELECT * FROM md_dataStep1reviewed WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s' AND jNo='%s' AND canUse=1 AND q!='FINAL' ORDER BY qNo ASC", $this->exptId, $dayNo, $sessionNo, $jType, $jNo);
        $getQNoResult = $this->igrtSqli->query($getQNoQry);
        while ($getQNoRow = $getQNoResult->fetch_object()) {
          $qNo = $getQNoRow->qNo;
          $insQRating = sprintf("INSERT INTO wt_TBTStep4datasets (exptId, jType, dayNo, sessionNo, s4jNo, jNo, qNo, isFinalRating, rated, respNo, s3respNo, actualJNo, shuffleHalf) VALUES "
            . "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '0', '0', '%s', '%s', '%s', '%s')",
            $this->exptId, $jType, $dayNo, $sessionNo, $s4jNo, $jNo, $qNo, $respNo, $s3respNo, $actualJNo, $shuffleHalf);
          $igrtSqli->query($insQRating);
        }
        // insert final rating row
        $insQRating = sprintf("INSERT INTO wt_TBTStep4datasets (exptId, jType, dayNo, sessionNo, s4jNo, jNo, qNo, isFinalRating, rated, respNo, s3respNo, actualJNo, shuffleHalf) VALUES "
          . "('%s', '%s', '%s', '%s', '%s', '%s', '%s', '1', '0', '%s', '%s', '%s', '%s')",
          $this->exptId, $jType, $dayNo, $sessionNo, $s4jNo, $jNo, $qNo, $respNo, $s3respNo, $actualJNo, $shuffleHalf);
        $igrtSqli->query($insQRating);
      }    
    }
    return $judgeList;
  }

  function makeJSON($shuffleLabel, $judgeList) {
    $maxColCnt = 0;
    for ($i=0; $i<count($judgeList); $i++) {
      foreach($judgeList[$i]['transcriptList'] as $tp) {
        if (count($judgeList[$i]['transcriptList']) > $maxColCnt) {
          $maxColCnt = count($judgeList[$i]['transcriptList']);
        }
      }
    }
    $json = "{";
    $json.= "\"maxCols\":".$maxColCnt.",";
    $json.= "\"shuffleLabel\":\"".$shuffleLabel."\",";
    $json.= "\"judges\":[";
    for ($i=0; $i<count($judgeList); $i++) {
      $jLabel =  $i + 1;
      if ($i>0) { $json.=","; }
      $json.= "{";
        $json.= "\"s4jNo\":".$i.",";
        $json.= "\"s4jLabel\":\"".$jLabel."\",";
        $json.= "\"transcripts\":[";
        for ( $j=0; $j<count($judgeList[$i]['transcriptList']); $j++) {
          $tp = $judgeList[$i]['transcriptList'][$j];
          if ($j>0) { $json.=","; }
          $json.= "{";
            $json.= "\"dsLabel\":\"".$tp['actualJNo']."\",";
            $json.= "\"shuffleHalf\":".$tp['shuffleHalf'].",";
            $respLabel = $tp['isVirtual'] == 1 ? "[V]" : $tp['s3respNo'] + 1;
            $json.= "\"respLabel\":\"".$respLabel."\""; 
          $json.="}";
        }
      $json.= "]}";
    }    
    $json.="]";
    $json.= "}";
    return $json;
  }
  
  function storeJSON($oddJudgeList, $evenJudgeList) {
    global $igrtSqli;
    $json = "{";
      $json.= "\"exptId\":".$this->exptId.",";
      $json.= "\"halfs\":[";
        $json.= $this->makeJSON("odd shuffle", $oddJudgeList);
        $json.= ",";
        $json.= $this->makeJSON("even shuffle", $evenJudgeList);
      $json.= "]";
    $json.= "}";
    $sql =sprintf("SELECT * FROM wt_Step3ShuffleJSON WHERE exptId='%s'", $this->exptId);
    $sjr = $igrtSqli->query($sql);
    if ($sjr) {
      $sql = sprintf("UPDATE wt_Step3ShuffleJSON SET json='%s' WHERE exptId='%s'", $igrtSqli->real_escape_string($json), $this->exptId);
    }
    else {
      $sql = sprintf("INSERT INTO wt_Step3ShuffleJSON (exptId, json) VALUES('%s', '%s')", $this->exptId, $igrtSqli->real_escape_string($json) );      
    }
    $igrtSqli->query($sql);
  }
  
  function getShuffleJSON() {
    $sql = sprintf("SELECT * FROM wt_Step3ShuffleJSON WHERE exptId='%s'", $this->exptId);
    $result = $this->igrtSqli->query($sql);
    if ($this->igrtSqli->affected_rows > 0) {
      $row = $result->fetch_object();
      return $row->json;
    }
  }
  
// </editor-fold>  

  function __construct($igrtSqli, $exptId, $evenS4judges, $oddS4judges) {
    $this->igrtSqli = $igrtSqli;
    $this->exptId = $exptId;
    $this->evenS4judges = $evenS4judges;
    $this->oddS4judges = $oddS4judges;
  }
  
}
