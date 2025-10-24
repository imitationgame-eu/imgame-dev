<?php
// stratification for other-group rating 
// (self assesment of knowledge about other group)


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

  function getS1OGR($iUid, $npUid, $pUid) {
    global $igrtSqli;
    $iOGR = 'x';
    $npOGR = 'x';
    $pOGR = 'x';
    $ogrQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $iUid);
    $ogrResult = $igrtSqli->query($ogrQry);
    if ($igrtSqli->affected_rows > 0) {
      $ogrRow = $ogrResult->fetch_object();
      $iOGR = $ogrRow->understandingOtherGroup < 6 ? "b" : "g"; 
    }
    $ogrQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $npUid);
    $ogrResult = $igrtSqli->query($ogrQry);
    if ($igrtSqli->affected_rows > 0) {
      $ogrRow = $ogrResult->fetch_object();
      $npOGR = $ogrRow->understandingOtherGroup < 6 ? "b" : "g"; 
    }
    $ogrQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $pUid);
    $ogrResult = $igrtSqli->query($ogrQry);
    if ($igrtSqli->affected_rows > 0) {
      $ogrRow = $ogrResult->fetch_object();
      $pOGR = $ogrRow->understandingOtherGroup < 6 ? "b" : "g"; 
    }
    return $iOGR.$npOGR.$pOGR;
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
      $retArray["eLabel"] = getS1OGR($iUid, $npUid, $pUid);
    }
    $oddQry = sprintf("SELECT * FROM xs_S1Allocations_239 WHERE actualJNo='%s' AND jType=1", $actualJNo);
    $oddResult = $igrtSqli->query($oddQry);
    if ($igrtSqli->affected_rows > 0) {
      $oddRow = $oddResult->fetch_object();
      $iUid = $oddRow->uid;
      $npUid = $oddRow->npUid;
      $pUid = $oddRow->pUid;
      $retArray["oLabel"] = getS1OGR($iUid, $npUid, $pUid);
    }
    return $retArray;
  }
  
  function getS2OGR($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS2g"=>array(),"oS2b"=>array(), "eS2g"=>array(), "eS2b"=>array());
    $eQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=0 ORDER BY respNo ASC", $actualJNo);
    $eResult = $igrtSqli->query($eQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($eRow = $eResult->fetch_object()) {
        if ($eRow->understandingOtherGroup < 6) {
          array_push($retArray["eS2b"], $eRow->respNo);
        }
        else {
          array_push($retArray["eS2g"], $eRow->respNo);          
        }
      }      
    }
    $oQry = sprintf("SELECT * FROM xs_S2_239 WHERE qs='%s' AND jType=1 ORDER BY respNo ASC", $actualJNo);
    $oResult = $igrtSqli->query($oQry);
    if ($igrtSqli->affected_rows > 0) {
      while ($oRow = $oResult->fetch_object()) {
        if ($oRow->understandingOtherGroup < 6) {
          array_push($retArray["oS2b"], $oRow->respNo);
        }
        else {
          array_push($retArray["oS2g"], $oRow->respNo);          
        }
      }      
    }
    return $retArray;    
  }
  
  function getS4OGR($actualJNo) {
    global $igrtSqli;
    $retArray = array("oS4all"=>array(), "eS4all"=>array()); // there is no ogr info about s4 unfortunately, so just complete set
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
        array_push($retArray["eS4all"], $s4judge);
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
        array_push($retArray["oS4all"], $s4judge);
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
    $ic = $ia == 1 ? 'g' : 'b';
    $npc = $npa == 1 ? 'g' : 'b';
    $pc = $pa == 1 ? 'g' : 'b';
    $s1code = $ic.$npc.$pc;
    $dataset = $jType == 0 ? $stratificationStructure[$s1code]['structure']['e'] : $stratificationStructure[$s1code]['structure']['o'];
    foreach($dataset as $qs) {
      $qsNo = $qs['qs'];
      $s2list = $s2a == 1 ? $qs['s2']['s2g'] : $qs['s2']['s2b'];
      //echo print_r($s2list, true).'<br/>';
      $s4list = $qs['s4']['s4all'];
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
    //$s4aType = $s4a == 1 ? 2 : 1;
    $iaType = $ia == 1 ? 2 : 1;
    $npaType = $npa == 1 ? 2 : 1;
    $paType = $pa == 1 ? 2 : 1;
    $s2aType = $s2a == 1 ? 2 : 1;
    $cntC = 0;
    $cntDK = 0;
    $cntW = 0;
    for ($i=0; $i<count($scores); $i++) {
      echo $s4a.',';
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
  
  // b=bad knowledge of other group
  // g=good knowledge of other group
$placeHolder = array('o'=>array(), 'e'=>array());
$stratificationStructure = array(
  'ggg' => array('label'=>'ggg', 'structure'=>$placeHolder),
  'ggb' => array('label'=>'ggb', 'structure'=>$placeHolder),
  'gbg' => array('label'=>'gbg', 'structure'=>$placeHolder),
  'gbb' => array('label'=>'gbb', 'structure'=>$placeHolder),
  'bgg' => array('label'=>'bgg', 'structure'=>$placeHolder),
  'bgb' => array('label'=>'bgb', 'structure'=>$placeHolder),
  'bbg' => array('label'=>'bbg', 'structure'=>$placeHolder),
  'bbb' => array('label'=>'bbb', 'structure'=>$placeHolder)
);
$qsQry = "SELECT DISTINCT(actualJNo) FROM xs_S1Allocations_239 ORDER BY actualJNo ASC";
$qsResult = $igrtSqli->query($qsQry);
if ($igrtSqli->affected_rows > 0) {
  while ($qsRow = $qsResult->fetch_object()) {
    $actualJNo = $qsRow->actualJNo;
    $s1interactions = getS1Interactions($actualJNo);  // returns "bbb" "bgb" etc -
    $s2ogr = getS2OGR($actualJNo);            // returns list of g and g RespNo for each jType
    //echo print_r($s2genders, true);
    $s4ogr = getS4OGR($actualJNo);            
    $eS2array = array(
      's2g'=>$s2ogr['eS2g'],
      's2b'=>$s2ogr['eS2b']
    );
    $oS2array = array(
      's2g'=>$s2ogr['oS2g'],
      's2b'=>$s2ogr['oS2b']
    );
    $eS4array = array(
      's4all'=>$s4ogr['eS4all']
    );
    $oS4array = array(
      's4all'=>$s4ogr['oS4all']
    );
    array_push($stratificationStructure[$s1interactions['eLabel']]['structure']['e'], array('qs'=>$actualJNo, 's2'=>$eS2array, 's4'=>$eS4array));
    array_push($stratificationStructure[$s1interactions['oLabel']]['structure']['o'], array('qs'=>$actualJNo, 's2'=>$oS2array, 's4'=>$oS4array));    
  }
  //displayRawStructure($stratificationStructure);
  echo 'for columns B to E,1=poor knowledge,2=good knowledge<br/>';
  echo 'for column F,1=Odd,2=Even<br/>';
  
  echo 'Step4 Judge,Step1 Interrogator,Step1 NP,Step 1 P,Step2 P,Odd or Even,qs,s4jNo,shuffleHalf,respNo,correct,confidence,8 point scale,t-test scale,count right,count DK,count wrong<br/>';
  for ($v=0; $v<2; $v++) {
//    for ($w=0; $w<2; $w++) {
      for ($x=0; $x<2; $x++) {
        for ($y=0; $y<2; $y++) {
          for ($z=0; $z<2; $z++) {
            for ($k=0; $k<2; $k++) {
              doScores($v,-1,$x,$y,$z,$k);
            }
          }
        }
      }
//    }
  }
  
}