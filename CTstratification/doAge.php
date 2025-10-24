<?php
// stratification of "age"


// <editor-fold defaultstate="collapsed" desc=" debugging functions">
  
  function getQSHeader($label) {
    $iRole = substr($label, 0 , 1);
    $npRole = substr($label, 1, 1);
    $pRole = substr($label, 2, 2);
    $header = $label.' : (Interrogator '.$iRole;
    $header.= ' , Non-Pretender '.$npRole;
    $header.= ' , Pretender '.$pRole.')';
    return $header;
  }
     
  function displayRawStructure($stratificationStructure) {
    foreach ($stratificationStructure as $s1) {
      echo getQSHeader($s1['label']).'<br/>';
      echo 'Even question sets<br/>';
      foreach ($s1['structure']['e'] as $qs) {
        echo 'QS '.$qs['qs'].'<br/>';
        echo 'male P: ';
        foreach ($qs['s2']['s2m'] as $s2) {
          echo $s2.' ';
        }
        echo '<br/>';
        echo 'female P: ';
        foreach ($qs['s2']['s2f'] as $s2) {
          echo $s2.' ';
        }
        echo '<br/>';
        echo 'male S4j:<br/>';
        foreach ($qs['s4']['s4m'] as $s4) {
          echo print_r($s4,true).'<br>';
        }
        echo 'female S4j:<br/>';
        foreach ($qs['s4']['s4f'] as $s4) {
          echo print_r($s4,true).'<br>';
        }
      }
      echo 'Odd question sets<br/>';
      foreach ($s1['structure']['o'] as $qs) {
        echo 'QS '.$qs['qs'].'<br/>';
        echo 'male P: ';
        foreach ($qs['s2']['s2m'] as $s2) {
          echo $s2.' ';
        }
        echo '<br/>';
        echo 'female P: ';
        foreach ($qs['s2']['s2f'] as $s2) {
          echo $s2.' ';
        }
        echo '<br/>';
        echo 'male S4j:<br/>';
        foreach ($qs['s4']['s4m'] as $s4) {
          echo print_r($s4,true).'<br>';
        }
        echo 'female S4j:<br/>';
        foreach ($qs['s4']['s4f'] as $s4) {
          echo print_r($s4,true).'<br>';
        }
      }
    }    
  }

// </editor-fold>

ini_set('display_errors', 'On');
error_reporting(E_ALL);
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-17);  
include_once $root_path.'/domainSpecific/mySqlObject.php';        
  
  function doScores($jType) {
    echo '<br/>';
    global $dataset;
    $scores = array();
    $data = $jType == 0 ? $dataset['eS4'] : $dataset['oS4'];
    for($i=0; $i<count($data); $i++) {
      $s4j = $data[$i];
      $scoreDetail = array(
        's4jNo'=>$s4j['s4jNo'], 
        'shuffleHalf'=>$s4j['shuffleHalf'],
        'qs' => $s4j['qsNo'],
        'respNo'=>$s4j['respNo'], 
        's3respNo'=>$s4j['s3respNo'], 
        'correct'=>$s4j['correct'], 
        'confidence'=>$s4j['confidence'],
        's4age'=> $s4j['age'],
        's2age'=> $s4j['s2respondent'],
        'iage' => $s4j['s1attributes']['iage'],
        'npage' => $s4j['s1attributes']['npage'],
        'page' => $s4j['s1attributes']['page']
      );
      array_push($scores, $scoreDetail);
    }
    $ojType = $jType == 1 ? 1 : 2;
    $cntC = 0;
    $cntDK = 0;
    $cntW = 0;
    for ($i=0; $i<count($scores); $i++) {
      echo $scores[$i]['s4age'].',';
      echo $scores[$i]['iage'].',';
      echo $scores[$i]['npage'].',';
      echo $scores[$i]['page'].',';
      echo $scores[$i]['s2age'].',';
      echo $ojType.',';
      echo $scores[$i]['qs'].',';
      echo $scores[$i]['s4jNo'].',';
      echo $scores[$i]['shuffleHalf'].',';
      echo $scores[$i]['respNo'].',';
      echo $scores[$i]['correct'].',';
      echo $scores[$i]['confidence'].',';
      $point8scale = $scores[$i]['correct'] == 1 ? $scores[$i]['confidence'] : 0-$scores[$i]['confidence'];
      $ttest = 255;
      switch ($point8scale) {
        case -4 : { $ttest = -1; $c=0; $dk=0; $ic=1; break; }
        case -3 : { $ttest = -1; $c=0; $dk=0; $ic=1;  break; }
        case -2 : { $ttest = 0; $c=0; $dk=1; $ic=0;  break; }
        case -1 : { $ttest = 0; $c=0; $dk=1; $ic=0;  break; }
        case 1 : { $ttest = 0; $c=0; $dk=1; $ic=0;  break; }
        case 2 : { $ttest = 0; $c=0; $dk=1; $ic=0;  break; }
        case 3 : { $ttest = 1; $c=1; $dk=0; $ic=0;  break; }
        case 4 : { $ttest = 1; $c=1; $dk=0; $ic=0;  break; }
      }
      echo $point8scale.',';
      echo $ttest.',';
      echo $c.',';
      echo $dk.',';
      echo $ic.'<br/>';
      $cntC+= $c;
      $cntDK+= $dk;
      $cntW+= $ic;
    }
  }

  function getS1attributes($iUid, $npUid, $pUid) {
    global $igrtSqli;
    $ia = 'x';
    $npa = 'x';
    $pa = 'x';
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $iUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $iage = $aRow->age;
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $npUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $npage = $aRow->age;
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $pUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $page = $aRow->age;
    }
    return array(
      'iage' => $iage,
      'npage' => $npage,
      'page' => $page
    );
  }

  function getS1Interactions($jType, $actualJNo) {
    global $igrtSqli;
    $aQry = sprintf("SELECT * FROM xs_S1Allocations_239 WHERE actualJNo='%s' AND jType='%s'", $actualJNo, $jType);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $iUid = $aRow->uid;
      $npUid = $aRow->npUid;
      $pUid = $aRow->pUid;
      return getS1attributes($iUid, $npUid, $pUid);
    }
    return array();
  }
  
  function getS2Attributes($jType, $actualJNo, $respNo) {
    global $igrtSqli;
    $s2Qry = sprintf("SELECT * FROM xs_S2_239 WHERE jType='%s' AND qs='%s' AND respNo='%s'", $jType, $actualJNo, $respNo);
    $s2Result = $igrtSqli->query($s2Qry);
    if ($igrtSqli->affected_rows > 0) {
      $s2Row = $s2Result->fetch_object();
      return $s2Row->age;
    }
    return -1;
  }
  
  function processS4($jType, $maxJNo) {
    global $igrtSqli;
    $retArray = array();
    for ($i=0; $i<$maxJNo; $i++) {
      $actualJNo = $i + 1;
      $eQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId=239 AND jType='%s' AND actualJNo='%s' ORDER BY s4JNo ASC", $jType, $actualJNo);
      $eResult = $igrtSqli->query($eQry);
      if ($igrtSqli->affected_rows > 0) {
        while ($eRow = $eResult->fetch_object()) {
          $s4jNo = $eRow->s4jNo;
          $respNo = $eRow->respNo;
          $s3respNo = $eRow->s3respNo;
          $shuffleHalf = $eRow->shuffleHalf;       
          // get rating and confidence
          $ratingQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId=239 AND jType='%s' AND s4jNo='%s' AND actualJNo='%s' AND s3respNo='%s' AND respNo='%s' AND shuffleHalf='%s'",
              $jType, $s4jNo, $actualJNo, $s3respNo, $respNo, $shuffleHalf);
          $ratingResult = $igrtSqli->query($ratingQry);
          if ($igrtSqli->affected_rows > 0) {
            $ratingRow = $ratingResult->fetch_object();
            $correct = $ratingRow->correct;
            $confidence = substr($ratingRow->confidence, -1);
          }
          $aQry = sprintf("SELECT * FROM xs_S4_239 WHERE s4jNo='%s' AND jType=0", $s4jNo);
          $aResult = $igrtSqli->query($aQry);
          if ($igrtSqli->affected_rows > 0) {
            $aRow = $aResult->fetch_object();
            $s4judge = array(
              's4jNo'=> $s4jNo,
              'qsNo'=> $actualJNo,
              'respNo'=> $respNo, 
              's3respNo'=> $s3respNo, 
              'shuffleHalf'=> $shuffleHalf, 
              'correct'=> $correct, 
              'confidence'=> $confidence,
              'age' => $aRow->age,
              's2respondent' => getS2Attributes($jType, $actualJNo, $respNo),
              's1attributes' => getS1Interactions($jType, $actualJNo)
            );
            array_push($retArray, $s4judge);
          }
        }
      }

    }   
    return $retArray;
  }
  
  function getS4Attributes() {
    $retArray = array("oS4"=>array(), "eS4"=>array());
    // for Capetown data, only actual JNo 1-12 used, as 13-15 injected from 2013 and no allocation data known
    $retArray["eS4"] = processS4(0, 12);
    $retArray["oS4"] = processS4(1, 12);
    return $retArray;    
  }

$dataset = getS4Attributes();
//echo print_r($dataset, true);
echo 'for columns A to E,age in years<br/>';
echo 'for column F,1=Odd,2=Even<br/>';

echo 'Step4 Judge,Step1 Interrogator,Step1 NP,Step 1 P,Step2 P,Odd or Even,qs,s4jNo,shuffleHalf,respNo,correct,confidence,8 point scale,t-test scale,count right,count DK,count wrong<br/>';
for ($i=0; $i<2; $i++) { doScores($i); }  

  
