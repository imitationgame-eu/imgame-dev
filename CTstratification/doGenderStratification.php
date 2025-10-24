<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-17);  
include_once $root_path.'/domainSpecific/mySqlObject.php';        

  function getS1Genders($iUid, $npUid, $pUid) {
    global $igrtSqli;
    $iGender = 'x';
    $npGender = 'x';
    $pGender = 'x';
    $genderQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $iUid);
    //echo $genderQry.'<br />';
    $genderResult = $igrtSqli->query($genderQry);
    if ($igrtSqli->affected_rows > 0) {
      $genderRow = $genderResult->fetch_object();
      $iGender = $genderRow->gender == 1 ? "m" : "f"; 
    }
    $genderQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $npUid);
    //echo $genderQry.'<br />';
    $genderResult = $igrtSqli->query($genderQry);
    if ($igrtSqli->affected_rows > 0) {
      $genderRow = $genderResult->fetch_object();
      $npGender = $genderRow->gender == 1 ? "m" : "f"; 
    }
    $genderQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $pUid);
    //echo $genderQry.'<br />';
    $genderResult = $igrtSqli->query($genderQry);
    if ($igrtSqli->affected_rows > 0) {
      $genderRow = $genderResult->fetch_object();
      $pGender = $genderRow->gender == 1 ? "m" : "f"; 
    }
    //echo '<br />';
    return $iGender.$npGender.$pGender;
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
      $retArray["eLabel"] = getS1Genders($iUid, $npUid, $pUid);
    }
    $oddQry = sprintf("SELECT * FROM xs_S1Allocations_239 WHERE actualJNo='%s' AND jType=1", $actualJNo);
    $oddResult = $igrtSqli->query($oddQry);
    if ($igrtSqli->affected_rows > 0) {
      $oddRow = $oddResult->fetch_object();
      $iUid = $oddRow->uid;
      $npUid = $oddRow->npUid;
      $pUid = $oddRow->pUid;
      $retArray["oLabel"] = getS1Genders($iUid, $npUid, $pUid);
    }
    return $retArray;
  }
  
  function getS2genders($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS2m"=>array(),"oS2f"=>array(), "eS2m"=>array(), "eS2f"=>array());
    $eQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=0 ORDER BY respNo ASC", $actualJNo);
    $eResult = $igrtSqli->query($eQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($eRow = $eResult->fetch_object()) {
        if ($eRow->gender == 1) {
          array_push($retArray["eS2m"], $eRow->respNo);
        }
        else {
          array_push($retArray["eS2f"], $eRow->respNo);          
        }
      }      
    }
    $oQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=1 ORDER BY respNo ASC", $actualJNo);
    $oResult = $igrtSqli->query($oQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($oRow = $oResult->fetch_object()) {
        if ($oRow->gender == 1) {
          array_push($retArray["oS2m"], $oRow->respNo);
        }
        else {
          array_push($retArray["oS2f"], $oRow->respNo);          
        }
      }      
    }
    return $retArray;    
  }
  
  function getS4gendersScores($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS4m"=>array(),"oS4f"=>array(), "eS4m"=>array(), "eS4f"=>array()); 
    $eQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId=239 AND jType=0 AND actualJNo='%s' ORDER BY s4JNo ASC", $actualJNo);
    //echo $eQry.'<br/>';
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
        // decide S4 gender
        $gQry = sprintf("SELECT * FROM xs_S4_239 WHERE s4jNo='%s' AND jType=0", $s4jNo);
        $gResult = $igrtSqli->query($gQry);
        if ($igrtSqli->affected_rows > 0) {
          $gRow = $gResult->fetch_object();
          $gender = $gRow->gender;
          if ($gender == 1) {
            array_push($retArray["eS4m"], $s4judge);
          }
          else {
            array_push($retArray["eS4f"], $s4judge);          
          }
        }
      }
    }
    $oQry = sprintf("SELECT * FROM wt_Step4datasets WHERE exptId=239 AND jType=1 AND actualJNo='%s' ORDER BY s4JNo ASC", $actualJNo);
    //echo $oQry.'<br/>';
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
        $gQry = sprintf("SELECT * FROM xs_S4_239 WHERE s4jNo='%s' AND jType=1", $s4jNo);
    //echo $eQry.'<br/>';
        $gResult = $igrtSqli->query($gQry);
        if ($igrtSqli->affected_rows > 0) {
          $gRow = $gResult->fetch_object();
          $gender = $gRow->gender;
          if ($gender == 1) {
            array_push($retArray["oS4m"], $s4judge);
          }
          else {
            array_push($retArray["oS4f"], $s4judge);          
          }
        }
      }
    }
    return $retArray;    
  }
  
  function getQSHeader($label) {
    $iRole = substr($label, 0 , 1);
    $npRole = substr($label, 1, 1);
    $pRole = substr($label, 2, 2);
    $header = $label.' : (Interrogator '.$iRole;
    $header.= ' , Non-Pretender '.$npRole;
    $header.= ' , Pretender '.$pRole.')';
    return $header;
  }
  
  function hasS2ofGender($needle, $haystack) {
    for ($i=0; $i<count($haystack); $i++) {
      if ($needle == $haystack[$i]) { return true; }
    }
    return false;
  }
  
  function doScores($jType, $s4g, $ig, $npg, $pg, $s2g) {
    //echo $jType.$s4g.$ig.$npg.$pg.'<br/>';
    echo '<br/>';
    global $stratificationStructure;
    $scores = array();
    $ic = $ig == 1 ? 'm' : 'f';
    $npc = $npg == 1 ? 'm' : 'f';
    $pc = $pg == 1 ? 'm' : 'f';
    $s1code = $ic.$npc.$pc;
    $dataset = $jType == 0 ? $stratificationStructure[$s1code]['structure']['e'] : $stratificationStructure[$s1code]['structure']['o'];
    foreach($dataset as $qs) {
      $qsNo = $qs['qs'];
      $s2list = $s2g == 1 ? $qs['s2']['s2m'] : $qs['s2']['s2f'];
      //echo print_r($s2list, true).'<br/>';
      $s4list = $s4g == 1 ? $qs['s4']['s4m'] : $qs['s4']['s4f'];
      //echo print_r($s4list, true).'<br/>';
      foreach ($s4list as $s4j) {
        //echo $s4j['respNo'].'<br/>';
        if (hasS2ofGender($s4j['respNo'], $s2list)) {
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
    $s4gType = $s4g == 1 ? 2 : 1;
    $igType = $ig == 1 ? 2 : 1;
    $npgType = $npg == 1 ? 2 : 1;
    $pgType = $pg == 1 ? 2 : 1;
    $s2gType = $s2g == 1 ? 2 : 1;
    $cntC = 0;
    $cntDK = 0;
    $cntW = 0;
    for ($i=0; $i<count($scores); $i++) {
      echo $s4gType.',';
      echo $igType.',';
      echo $npgType.',';
      echo $pgType.',';
      echo $s2gType.',';
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
//    if ($cntC>0 || $cntDK>0 || $cntW>0) {
//      $pr = 1 - (($cntC-$cntW)/($cntC+$cntDK+$cntW));
//      echo $pr.'<br/>';      
//    }
//    echo '<br/>';
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

$placeHolder = array('o'=>array(), 'e'=>array());
$stratificationStructure = array(
  'mmm' => array('label'=>'mmm', 'structure'=>$placeHolder),
  'mmf' => array('label'=>'mmf', 'structure'=>$placeHolder),
  'mfm' => array('label'=>'mfm', 'structure'=>$placeHolder),
  'mff' => array('label'=>'mff', 'structure'=>$placeHolder),
  'fff' => array('label'=>'fff', 'structure'=>$placeHolder),
  'ffm' => array('label'=>'ffm', 'structure'=>$placeHolder),
  'fmf' => array('label'=>'fmf', 'structure'=>$placeHolder),
  'fmm' => array('label'=>'fmm', 'structure'=>$placeHolder)
);
$qsQry = "SELECT DISTINCT(actualJNo) FROM xs_S1Allocations_239 ORDER BY actualJNo ASC";
$qsResult = $igrtSqli->query($qsQry);
if ($igrtSqli->affected_rows > 0) {
  while ($qsRow = $qsResult->fetch_object()) {
    $actualJNo = $qsRow->actualJNo;
    $s1interactions = getS1Interactions($actualJNo);  // returns "mmm" "mff" etc
    $s2genders = getS2Genders($actualJNo);            // returns list of m and f RespNo for each jType
    //echo print_r($s2genders, true);
    $s4genders = getS4GendersScores($actualJNo);            // returns list of m and f s4jNo for each jType
    $eS2array = array(
      's2m'=>$s2genders['eS2m'],
      's2f'=>$s2genders['eS2f']
    );
    $oS2array = array(
      's2m'=>$s2genders['oS2m'],
      's2f'=>$s2genders['oS2f']
    );
    $eS4array = array(
      's4m'=>$s4genders['eS4m'],
      's4f'=>$s4genders['eS4f']
    );
    $oS4array = array(
      's4m'=>$s4genders['oS4m'],
      's4f'=>$s4genders['oS4f']
    );
    array_push($stratificationStructure[$s1interactions['eLabel']]['structure']['e'], array('qs'=>$actualJNo, 's2'=>$eS2array, 's4'=>$eS4array));
    array_push($stratificationStructure[$s1interactions['oLabel']]['structure']['o'], array('qs'=>$actualJNo, 's2'=>$oS2array, 's4'=>$oS4array));    
  }
  //displayRawStructure($stratificationStructure);
  echo 'for columns A to E,1=female,2=male<br/>';
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