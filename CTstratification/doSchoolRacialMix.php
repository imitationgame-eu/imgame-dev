<?php
// stratification of school racial mix 
// 1 = very mixed
// 2 = a little mixed
// 3 = not mixed
// [1+2 combined]


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

  function getS1attributes($iUid, $npUid, $pUid) {
    global $igrtSqli;
    $ia = 'x';
    $npa = 'x';
    $pa = 'x';
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $iUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $ia = $aRow->highSchoolRacialMix == 3  ? "0" : "1"; 
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $npUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $npa = $aRow->highSchoolRacialMix == 3  ? "0" : "1"; 
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $pUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $pa = $aRow->highSchoolRacialMix == 3  ? "0" : "1";  
    }
    return $ia.$npa.$pa;
  }

  function getS1Interactions($actualJNo) {
    global $igrtSqli;
    $retArray = array("oLabel"=>'', "eLabel"=>'');
    $evenQry = sprintf("SELECT * FROM xs_S1Allocations_239 WHERE actualJNo='%s' AND jType=0", $actualJNo);
    $evenResult = $igrtSqli->query($evenQry);
    if ($igrtSqli->affected_rows > 0) {
      $evenRow = $evenResult->fetch_object();
      $iUid = $evenRow->uid;
      $npUid = $evenRow->npUid;
      $pUid = $evenRow->pUid;
      $retArray["eLabel"] = getS1attributes($iUid, $npUid, $pUid);
    }
    $oddQry = sprintf("SELECT * FROM xs_S1Allocations_239 WHERE actualJNo='%s' AND jType=1", $actualJNo);
    $oddResult = $igrtSqli->query($oddQry);
    if ($igrtSqli->affected_rows > 0) {
      $oddRow = $oddResult->fetch_object();
      $iUid = $oddRow->uid;
      $npUid = $oddRow->npUid;
      $pUid = $oddRow->pUid;
      $retArray["oLabel"] = getS1attributes($iUid, $npUid, $pUid);
    }
    return $retArray;
  }
  
  function getS2attributes($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS20"=>array(),"oS21"=>array(), "eS20"=>array(), "eS21"=>array());
    $eQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=0 ORDER BY respNo ASC", $actualJNo);
    $eResult = $igrtSqli->query($eQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($eRow = $eResult->fetch_object()) {
        if ($eRow->highSchoolRacialMix == 3) {
          array_push($retArray["eS20"], $eRow->respNo);
        }
        else {
          array_push($retArray["eS21"], $eRow->respNo);          
        }
      }      
    }
    $oQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=1 ORDER BY respNo ASC", $actualJNo);
    $oResult = $igrtSqli->query($oQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($oRow = $oResult->fetch_object()) {
        if ($oRow->highSchoolRacialMix == 3) {
          array_push($retArray["oS20"], $oRow->respNo);
        }
        else {
          array_push($retArray["oS21"], $oRow->respNo);          
        }
      }      
    }
    return $retArray;    
  }
  
  function getS4attributes($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS40"=>array(),"oS41"=>array(), "eS40"=>array(), "eS41"=>array()); 
    $eQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId=239 AND jType=0 AND actualJNo='%s' ORDER BY s4JNo ASC", $actualJNo);
    $eResult = $igrtSqli->query($eQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($eRow = $eResult->fetch_object()) {
        $s4jNo = $eRow->s4jNo;
        $respNo = $eRow->respNo;
        $s3respNo = $eRow->s3respNo;
        $shuffleHalf = $eRow->shuffleHalf;       
        // get rating and confidence
        $ratingQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId=239 AND jType=0 AND s4jNo='%s' AND actualJNo='%s' AND s3respNo='%s' AND respNo='%s' AND shuffleHalf='%s'",
            $s4jNo, $actualJNo, $s3respNo, $respNo, $shuffleHalf);
        $ratingResult = $igrtSqli->query($ratingQry);
        if ($igrtSqli->affected_rows > 0) {
          $ratingRow = $ratingResult->fetch_object();
          $correct = $ratingRow->correct;
          $confidence = substr($ratingRow->confidence, -1);
        }
        $s4judge = array('s4jNo' => $s4jNo, 'respNo'=>$respNo, 's3respNo'=>$s3respNo, 'shuffleHalf'=>$shuffleHalf, 'correct'=>$correct, 'confidence'=>$confidence);
        // decide S4 attribute
        $aQry = sprintf("SELECT * FROM xs_S4_239 WHERE s4jNo='%s' AND jType=0", $s4jNo);
        $aResult = $igrtSqli->query($aQry);
        if ($igrtSqli->affected_rows > 0) {
          $aRow = $aResult->fetch_object();
          $a = $aRow->highSchoolRacialMix == 3 ? 0 : 1;
          if ($a == 1) {
            array_push($retArray["eS41"], $s4judge);
          }
          else {
            array_push($retArray["eS40"], $s4judge);          
          }
        }
      }
    }
    $oQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId=239 AND jType=1 AND actualJNo='%s' ORDER BY s4JNo ASC", $actualJNo);
    $oResult = $igrtSqli->query($oQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($oRow = $oResult->fetch_object()) {
        $s4jNo = $oRow->s4jNo;
        $respNo = $oRow->respNo;
        $s3respNo = $oRow->s3respNo;
        $shuffleHalf = $oRow->shuffleHalf;       
        // get rating and confidence
        $ratingQry = sprintf("SELECT * FROM dataSTEP4 WHERE exptId=239 AND jType=1 AND s4jNo='%s' AND actualJNo='%s' AND s3respNo='%s' AND respNo='%s' AND shuffleHalf='%s'",
            $s4jNo, $actualJNo, $s3respNo, $respNo, $shuffleHalf);
        $ratingResult = $igrtSqli->query($ratingQry);
        if ($igrtSqli->affected_rows > 0) {
          $ratingRow = $ratingResult->fetch_object();
          $correct = $ratingRow->correct;
          $confidence = substr($ratingRow->confidence, -1);
        }
        $s4judge = array('s4jNo' => $s4jNo, 'respNo'=>$respNo, 's3respNo'=>$s3respNo, 'shuffleHalf'=>$shuffleHalf, 'correct'=>$correct, 'confidence'=>$confidence);
        $aQry = sprintf("SELECT * FROM xs_S4_239 WHERE s4jNo='%s' AND jType=1", $s4jNo);
        $aResult = $igrtSqli->query($aQry);
        if ($igrtSqli->affected_rows > 0) {
          $aRow = $aResult->fetch_object();
          $a = $aRow->highSchoolRacialMix == 3 ? 0 : 1;
          if ($a == 1) {
            array_push($retArray["oS41"], $s4judge);
          }
          else {
            array_push($retArray["oS40"], $s4judge);          
          }
        }
      }
    }
    return $retArray;    
  }

  function hasS2ofAttribute($needle, $haystack) {
    for ($i=0; $i<count($haystack); $i++) {
      if ($needle == $haystack[$i]) { return true; }
    }
    return false;
  }
  
  function doScores($jType, $s4a, $ia, $npa, $pa, $s2a) {
    echo '<br/>';
    global $stratificationStructure;
    $scores = array();
    $ic = $ia == 1 ? '1' : '0';
    $npc = $npa == 1 ? '1' : '0';
    $pc = $pa == 1 ? '1' : '0';
    $s1code = $ic.$npc.$pc;
    $dataset = $jType == 0 ? $stratificationStructure[$s1code]['structure']['e'] : $stratificationStructure[$s1code]['structure']['o'];
    foreach($dataset as $qs) {
      $qsNo = $qs['qs'];
      $s2list = $s2a == 1 ? $qs['s2']['s21'] : $qs['s2']['s20'];
      //echo print_r($s2list, true).'<br/>';
      $s4list = $s4a == 1 ? $qs['s4']['s41'] : $qs['s4']['s40'];
      //echo print_r($s4list, true).'<br/>';
      foreach ($s4list as $s4j) {
        //echo $s4j['respNo'].'<br/>';
        if (hasS2ofAttribute($s4j['respNo'], $s2list)) {
          //echo $s4j['respNo'].'<br/>';
          $scoreDetail = array(
            's4jNo'=>$s4j['s4jNo'], 
            'shuffleHalf'=>$s4j['shuffleHalf'],
            'qs' => $qsNo,
            'respNo'=>$s4j['respNo'], 
            's3respNo'=>$s4j['s3respNo'], 
            'correct'=>$s4j['correct'], 
            'confidence'=>$s4j['confidence']            
          );
          array_push($scores, $scoreDetail);
        }
      }
    }
    $ojType = $jType == 1 ? 1 : 2;
    $s4aType = $s4a == 1 ? 2 : 1;
    $iaType = $ia == 1 ? 2 : 1;
    $npaType = $npa == 1 ? 2 : 1;
    $paType = $pa == 1 ? 2 : 1;
    $s2aType = $s2a == 1 ? 2 : 1;
    $cntC = 0;
    $cntDK = 0;
    $cntW = 0;
    for ($i=0; $i<count($scores); $i++) {
      echo $s4aType.',';
      echo $iaType.',';
      echo $npaType.',';
      echo $paType.',';
      echo $s2aType.',';
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
  
  // 1=mixed
  // 0=not mixed
$placeHolder = array('o'=>array(), 'e'=>array());
$stratificationStructure = array(
  '000' => array('label'=>'000', 'structure'=>$placeHolder),
  '001' => array('label'=>'001', 'structure'=>$placeHolder),
  '010' => array('label'=>'010', 'structure'=>$placeHolder),
  '011' => array('label'=>'011', 'structure'=>$placeHolder),
  '100' => array('label'=>'100', 'structure'=>$placeHolder),
  '101' => array('label'=>'101', 'structure'=>$placeHolder),
  '110' => array('label'=>'110', 'structure'=>$placeHolder),
  '111' => array('label'=>'111', 'structure'=>$placeHolder)
);
$qsQry = "SELECT DISTINCT(actualJNo) FROM xs_S1Allocations_239 ORDER BY actualJNo ASC";
$qsResult = $igrtSqli->query($qsQry);
if ($igrtSqli->affected_rows > 0) {
  while ($qsRow = $qsResult->fetch_object()) {
    $actualJNo = $qsRow->actualJNo;
    $s1interactions = getS1Interactions($actualJNo);  // returns "000" "011" etc -
    $s2 = getS2attributes($actualJNo);            // returns list of m and n RespNo for each jType
    $s4 = getS4attributes($actualJNo);            // returns list of m and f s4jNo for each jType
    $eS2array = array(
      's20'=>$s2['eS20'],
      's21'=>$s2['eS21']
    );
    $oS2array = array(
      's20'=>$s2['oS20'],
      's21'=>$s2['oS21']
    );
    $eS4array = array(
      's40'=>$s4['eS40'],
      's41'=>$s4['eS41']
    );
    $oS4array = array(
      's40'=>$s4['oS40'],
      's41'=>$s4['oS41']
    );
    array_push($stratificationStructure[$s1interactions['eLabel']]['structure']['e'], array('qs'=>$actualJNo, 's2'=>$eS2array, 's4'=>$eS4array));
    array_push($stratificationStructure[$s1interactions['oLabel']]['structure']['o'], array('qs'=>$actualJNo, 's2'=>$oS2array, 's4'=>$oS4array));    
  }
  //displayRawStructure($stratificationStructure);
  echo 'for columns A to E,1=mixed,2=not mixed<br/>';
  echo 'for column F,1=Odd,2=Even<br/>';
  
  echo 'Step4 Judge,Step1 Interrogator,Step1 NP,Step 1 P,Step2 P,Odd or Even,qs,s4jNo,shuffleHalf,respNo,correct,confidence,8 point scale,t-test scale,count right,count DK,count wrong<br/>';
  for ($v=0; $v<2; $v++) {
    for ($w=0; $w<2; $w++) {
      for ($x=0; $x<2; $x++) {
        for ($y=0; $y<2; $y++) {
          for ($z=0; $z<2; $z++) {
            for ($k=0; $k<2; $k++) {
              doScores($v,$w,$x,$y,$z,$k);
            }
          }
        }
      }
    }
  }
  
}