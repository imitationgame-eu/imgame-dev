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

  function getS1attributes($iUid, $npUid, $pUid) {
    global $igrtSqli;
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $iUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $iAttributes = array(
        'jNo'=> $aRow->jNo,
        'step1Id'=> $aRow->step1Id,
        'effort'=> $aRow->effort,
        'understandingOtherGroup'=> $aRow->understandingOtherGroup,
        'understandingFromEducation'=> $aRow->understandingFromEducation,
        'understandingFromFamilyFriends'=> $aRow->understandingFromFamilyFriends,
        'understandingFromOther'=> $aRow->understandingFromOther,
        'age'=> $aRow->age,
        'gender'=> $aRow->gender,
        'dwellingType'=> $aRow->dwellingType,
        'neighbourhoodRacialMix'=> $aRow->neighbourhoodRacialMix,
        'noLanguages'=> $aRow->noLanguages,
        'Afrikaans'=> $aRow->Afrikaans,
        'English'=> $aRow->English,
        'otherLanguage'=> $aRow->otherLanguage,
        'livedAbroad'=> $aRow->livedAbroad,
        'yearsAbroad'=> $aRow->yearsAbroad,
        'highSchoolRacialMix'=> $aRow->highSchoolRacialMix,
        'studyYears'=> $aRow->studyYears,
        'anotherUniversity'=> $aRow->anotherUniversity,
        'friendType1'=> $aRow->friendType1,
        'friendType2'=> $aRow->friendType2,
        'friendType3'=> $aRow->friendType3,
        'friendType4'=> $aRow->friendType4,
        'friendType5'=> $aRow->friendType5,
        'friendType6'=> $aRow->friendType6,
        'nationality'=> $aRow->nationality,
        'populationGroup'=> $aRow->populationGroup,
        'province'=> $aRow->province,
        'highSchoolType'=> $aRow->highSchoolType,
        'highSchoolLocation'=> $aRow->highSchoolLocation,
        'currentStudyLocation'=> $aRow->currentStudyLocation,
        'level'=> $aRow->level,
        'faculty'=> $aRow->faculty,
        'yr1dwelling'=> $aRow->yr1dwelling,
        'yr2dwelling'=> $aRow->yr2dwelling,
        'yr3dwelling'=> $aRow->yr3dwelling,
        'yr4dwelling'=> $aRow->yr4dwelling,
        'musicChoice'=> $aRow->musicChoice
      );
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $npUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $npAttributes = array(
        'jNo'=> $aRow->jNo,
        'step1Id'=> $aRow->step1Id,
        'effort'=> $aRow->effort,
        'understandingOtherGroup'=> $aRow->understandingOtherGroup,
        'understandingFromEducation'=> $aRow->understandingFromEducation,
        'understandingFromFamilyFriends'=> $aRow->understandingFromFamilyFriends,
        'understandingFromOther'=> $aRow->understandingFromOther,
        'age'=> $aRow->age,
        'gender'=> $aRow->gender,
        'dwellingType'=> $aRow->dwellingType,
        'neighbourhoodRacialMix'=> $aRow->neighbourhoodRacialMix,
        'noLanguages'=> $aRow->noLanguages,
        'Afrikaans'=> $aRow->Afrikaans,
        'English'=> $aRow->English,
        'otherLanguage'=> $aRow->otherLanguage,
        'livedAbroad'=> $aRow->livedAbroad,
        'yearsAbroad'=> $aRow->yearsAbroad,
        'highSchoolRacialMix'=> $aRow->highSchoolRacialMix,
        'studyYears'=> $aRow->studyYears,
        'anotherUniversity'=> $aRow->anotherUniversity,
        'friendType1'=> $aRow->friendType1,
        'friendType2'=> $aRow->friendType2,
        'friendType3'=> $aRow->friendType3,
        'friendType4'=> $aRow->friendType4,
        'friendType5'=> $aRow->friendType5,
        'friendType6'=> $aRow->friendType6,
        'nationality'=> $aRow->nationality,
        'populationGroup'=> $aRow->populationGroup,
        'province'=> $aRow->province,
        'highSchoolType'=> $aRow->highSchoolType,
        'highSchoolLocation'=> $aRow->highSchoolLocation,
        'currentStudyLocation'=> $aRow->currentStudyLocation,
        'level'=> $aRow->level,
        'faculty'=> $aRow->faculty,
        'yr1dwelling'=> $aRow->yr1dwelling,
        'yr2dwelling'=> $aRow->yr2dwelling,
        'yr3dwelling'=> $aRow->yr3dwelling,
        'yr4dwelling'=> $aRow->yr4dwelling,
        'musicChoice'=> $aRow->musicChoice
      );
    }
    $aQry = sprintf("SELECT * FROM xs_S1_239 WHERE step1Id='igsn%s@imgame.cf.ac.uk'", $pUid);
    $aResult = $igrtSqli->query($aQry);
    if ($igrtSqli->affected_rows > 0) {
      $aRow = $aResult->fetch_object();
      $pAttributes = array(
        'jNo'=> $aRow->jNo,
        'step1Id'=> $aRow->step1Id,
        'effort'=> $aRow->effort,
        'understandingOtherGroup'=> $aRow->understandingOtherGroup,
        'understandingFromEducation'=> $aRow->understandingFromEducation,
        'understandingFromFamilyFriends'=> $aRow->understandingFromFamilyFriends,
        'understandingFromOther'=> $aRow->understandingFromOther,
        'age'=> $aRow->age,
        'gender'=> $aRow->gender,
        'dwellingType'=> $aRow->dwellingType,
        'neighbourhoodRacialMix'=> $aRow->neighbourhoodRacialMix,
        'noLanguages'=> $aRow->noLanguages,
        'Afrikaans'=> $aRow->Afrikaans,
        'English'=> $aRow->English,
        'otherLanguage'=> $aRow->otherLanguage,
        'livedAbroad'=> $aRow->livedAbroad,
        'yearsAbroad'=> $aRow->yearsAbroad,
        'highSchoolRacialMix'=> $aRow->highSchoolRacialMix,
        'studyYears'=> $aRow->studyYears,
        'anotherUniversity'=> $aRow->anotherUniversity,
        'friendType1'=> $aRow->friendType1,
        'friendType2'=> $aRow->friendType2,
        'friendType3'=> $aRow->friendType3,
        'friendType4'=> $aRow->friendType4,
        'friendType5'=> $aRow->friendType5,
        'friendType6'=> $aRow->friendType6,
        'nationality'=> $aRow->nationality,
        'populationGroup'=> $aRow->populationGroup,
        'province'=> $aRow->province,
        'highSchoolType'=> $aRow->highSchoolType,
        'highSchoolLocation'=> $aRow->highSchoolLocation,
        'currentStudyLocation'=> $aRow->currentStudyLocation,
        'level'=> $aRow->level,
        'faculty'=> $aRow->faculty,
        'yr1dwelling'=> $aRow->yr1dwelling,
        'yr2dwelling'=> $aRow->yr2dwelling,
        'yr3dwelling'=> $aRow->yr3dwelling,
        'yr4dwelling'=> $aRow->yr4dwelling,
        'musicChoice'=> $aRow->musicChoice
      );
    }
    return array(
      'i' => $iAttributes,
      'np' => $npAttributes,
      'p' => $pAttributes
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
      $s2 = array(
        'respNo' => $s2Row->respNo,
        'qs' => $s2Row->qs,
        'effort' => $s2Row->effort,
        'understandingOtherGroup' => $s2Row->understandingOtherGroup,
        'understandingFromEducation' => $s2Row->understandingFromEducation,
        'understandingFromFamilyFriends' => $s2Row->understandingFromFamilyFriends,
        'understandingFromOther' => $s2Row->respNo,
        'age' => $s2Row->age,
        'gender' => $s2Row->gender,
        'dwellingType' => $s2Row->dwellingType,
        'neighbourhoodRacialMix' => $s2Row->neighbourhoodRacialMix,
        'noLanguages' => $s2Row->noLanguages,
        'Afrikaans' => $s2Row->Afrikaans,
        'English' => $s2Row->English,
        'otherLanguage' => $s2Row->otherLanguage,
        'livedAbroad' => $s2Row->livedAbroad,
        'yearsAbroad' => $s2Row->yearsAbroad,
        'highSchoolRacialMix' => $s2Row->highSchoolRacialMix,
        'studyYears' => $s2Row->studyYears,
        'anotherUniversity' => $s2Row->anotherUniversity,
        'friendType1' => $s2Row->friendType1,
        'friendType2' => $s2Row->friendType2,
        'friendType3' => $s2Row->friendType3,
        'friendType4' => $s2Row->friendType4,
        'friendType5' => $s2Row->friendType5,
        'friendType6' => $s2Row->friendType6,
        'nationality' => $s2Row->nationality,
        'populationGroup' => $s2Row->populationGroup,
        'province' => $s2Row->province,
        'highSchoolType' => $s2Row->highSchoolType,
        'highSchoolLocation' => $s2Row->highSchoolLocation,
        'currentStudyLocation' => $s2Row->currentStudyLocation,
        'level' => $s2Row->level,
        'faculty' => $s2Row->faculty,
        'yr1dwelling' => $s2Row->yr1dwelling,
        'yr2dwelling' => $s2Row->yr2dwelling,
        'yr3dwelling' => $s2Row->yr3dwelling,
        'yr4dwelling' => $s2Row->yr4dwelling,
        'musicChoice' => $s2Row->musicChoice
      );
      return $s2;
    }
    return array();
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
              'gender' => $aRow->gender,
              'effort' => $aRow->effort,
              'nationality' => $aRow->nationality,
              'populationGroup' => $aRow->populationGroup,
              'dwellingType' => $aRow->dwellingType,
              'neighbourhoodRacialMix' => $aRow->neighbourhoodRacialMix,
              'province' => $aRow->province,
              'noLanguages' => $aRow->noLanguages,
              'Afrikaans' => $aRow->Afrikaans,
              'English' => $aRow->English,
              'otherLanguage' => $aRow->otherLanguage,
              'livedAbroad' => $aRow->livedAbroad,
              'yearsAbroad' => $aRow->yearsAbroad,
              'highSchoolType' => $aRow->highSchoolType,
              'highSchoolLocation' => $aRow->highSchoolLocation,
              'highSchoolRacialMix' => $aRow->highSchoolRacialMix,
              'currentStudyLocation' => $aRow->currentStudyLocation,
              'studyYears' => $aRow->studyYears,
              'level' => $aRow->level,
              'faculty' => $aRow->faculty,
              'anotherUniversity' => $aRow->anotherUniversity,
              'yr1dwelling' => $aRow->yr1dwelling,
              'yr2dwelling' => $aRow->yr2dwelling,
              'yr3dwelling' => $aRow->yr3dwelling,
              'yr4dwelling' => $aRow->yr4dwelling,
              'musicChoice' => $aRow->musicChoice,
              'africanFriends' => $aRow->africanFriends,
              'colouredFriends' => $aRow->colouredFriends,
              'indianFriends' => $aRow->indianFriends,
              'whiteFriends' => $aRow->whiteFriends,
              'otherSAFriends' => $aRow->otherSAFriends,
              'othernonSAFriends' => $aRow->othernonSAFriends,
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
  
  function writePptValues($player) {
    echo $player['jNo'].',';
    echo $player['step1Id'].',';
    echo $player['effort'].',';
    echo $player['understandingOtherGroup'].',';
    echo $player['understandingFromEducation'].',';
    echo $player['understandingFromFamilyFriends'].',';
    echo $player['understandingFromOther'].',';
    echo $player['age'].',';
    echo $player['gender'].',';
    echo $player['dwellingType'].',';
    echo $player['neighbourhoodRacialMix'].',';
    echo $player['noLanguages'].',';
    echo $player['Afrikaans'].',';
    echo $player['English'].',';
    echo $player['otherLanguage'].',';
    echo $player['livedAbroad'].',';
    echo $player['yearsAbroad'].',';
    echo $player['highSchoolRacialMix'].',';
    echo $player['studyYears'].',';
    echo $player['anotherUniversity'].',';
    echo $player['friendType1'].',';
    echo $player['friendType2'].',';
    echo $player['friendType3'].',';
    echo $player['friendType4'].',';
    echo $player['friendType5'].',';
    echo $player['friendType6'].',';
    echo $player['nationality'].',';
    echo $player['populationGroup'].',';
    echo $player['province'].',';
    echo $player['highSchoolType'].',';
    echo $player['highSchoolLocation'].',';
    echo $player['currentStudyLocation'].',';
    echo $player['level'].',';
    echo $player['faculty'].',';
    echo $player['yr1dwelling'].',';
    echo $player['yr2dwelling'].',';
    echo $player['yr3dwelling'].',';
    echo $player['yr4dwelling'].',';
    echo $player['musicChoice'].',';
  }
  
  function writeS1Values($s1) {
    writePptValues($s1['i']);
    writePptValues($s1['np']);
    writePptValues($s1['p']);
  }
  
  function writeS2Values($s2) {
    echo $s2['respNo'].',';
    echo $s2['qs'].',';
    echo $s2['effort'].',';
    echo $s2['understandingOtherGroup'].',';
    echo $s2['understandingFromEducation'].',';
    echo $s2['understandingFromFamilyFriends'].',';
    echo $s2['understandingFromOther'].',';
    echo $s2['age'].',';
    echo $s2['gender'].','; 
    echo $s2['dwellingType'].','; 
    echo $s2['neighbourhoodRacialMix'].','; 
    echo $s2['noLanguages'].',';
    echo $s2['Afrikaans'].',';
    echo $s2['English'].',';
    echo $s2['otherLanguage'].','; 
    echo $s2['livedAbroad'].','; 
    echo $s2['yearsAbroad'].','; 
    echo $s2['highSchoolRacialMix'].',';
    echo $s2['studyYears'].','; 
    echo $s2['anotherUniversity'].','; 
    echo $s2['friendType1'].','; 
    echo $s2['friendType2'].','; 
    echo $s2['friendType3'].',';
    echo $s2['friendType4'].',';
    echo $s2['friendType5'].','; 
    echo $s2['friendType6'].','; 
    echo $s2['nationality'].',';
    echo $s2['populationGroup'].','; 
    echo $s2['province'].',';
    echo $s2['highSchoolType'].','; 
    echo $s2['highSchoolLocation'].','; 
    echo $s2['currentStudyLocation'].','; 
    echo $s2['level'].','; 
    echo $s2['faculty'].','; 
    echo $s2['yr1dwelling'].','; 
    echo $s2['yr2dwelling'].','; 
    echo $s2['yr3dwelling'].','; 
    echo $s2['yr4dwelling'].','; 
    echo $s2['musicChoice'].','; 
  }
// 17,2,6,3,1,0,2,20,4,  
  function writeValues($jType) {
    global $dataset;
    $data = $jType == 0 ? $dataset['eS4'] : $dataset['oS4'];
    for ($i=0; $i<count($data); $i++) {
      echo $jType.',';
      echo $data[$i]['s4jNo'].',';
      echo $data[$i]['qsNo'].',';
      echo $data[$i]['respNo'].','; 
      echo $data[$i]['s3respNo'].','; 
      echo $data[$i]['shuffleHalf'].','; 
      echo $data[$i]['correct'].','; 
      echo $data[$i]['confidence'].',';
      echo $data[$i]['age'].',';
      echo $data[$i]['gender'].',';
      echo $data[$i]['effort'].',';
      echo $data[$i]['nationality'].',';
      echo $data[$i]['populationGroup'].',';
      echo $data[$i]['dwellingType'].',';
      echo $data[$i]['neighbourhoodRacialMix'].',';
      echo $data[$i]['province'].',';
      echo $data[$i]['noLanguages'].',';
      echo $data[$i]['Afrikaans'].',';
      echo $data[$i]['English'].','; 
      echo $data[$i]['otherLanguage'].',';
      echo $data[$i]['livedAbroad'].',';
      echo $data[$i]['yearsAbroad'].','; 
      echo $data[$i]['highSchoolType'].','; 
      echo $data[$i]['highSchoolLocation'].','; 
      echo $data[$i]['highSchoolRacialMix'].','; 
      echo $data[$i]['currentStudyLocation'].',';
      echo $data[$i]['studyYears'].','; 
      echo $data[$i]['level'].',';
      echo $data[$i]['faculty'].','; 
      echo $data[$i]['anotherUniversity'].','; 
      echo $data[$i]['yr1dwelling'].','; 
      echo $data[$i]['yr2dwelling'].','; 
      echo $data[$i]['yr3dwelling'].','; 
      echo $data[$i]['yr4dwelling'].','; 
      echo $data[$i]['musicChoice'].','; 
      echo $data[$i]['africanFriends'].','; 
      echo $data[$i]['colouredFriends'].','; 
      echo $data[$i]['indianFriends'].','; 
      echo $data[$i]['whiteFriends'].','; 
      echo $data[$i]['otherSAFriends'].','; 
      echo $data[$i]['othernonSAFriends'].',';
      writeS2Values($data[$i]['s2respondent']);
      writeS1Values($data[$i]['s1attributes']);
      echo '<br/>';      
    }
  }
  
  function writeHeaders() {
    echo "jType,s4jNo,s4qsNo,s4respNo,s4s3respNo,s4shuffleHalf,s4correct,s4confidence,s4age,s4gender,s4effort,
      s4nationality,s4populationGroup,s4dwellingType,s4neighbourhoodRacialMix,
      s4province,s4noLanguages,s4Afrikaans,s4English,s4otherLanguage,s4livedAbroad,s4yearsAbroad,
      s4highSchoolType,s4highSchoolLocation,s4highSchoolRacialMix,s4currentStudyLocation,
      s4studyYears,s4level,s4faculty,s4anotherUniversity,s4yr1dwelling,s4yr2dwelling,
      s4yr3dwelling,s4yr4dwelling,s4musicChoice,s4africanFriends,s4colouredFriends,
      s4indianFriends,s4whiteFriends,s4otherSAFriends,s4othernonSAFriends,";
    echo "s2respNo,s2qs,s2effort,s2understandingOtherGroup,s2understandingFromEducation,
      s2understandingFromFamilyFriends,s2understandingFromOther,s2age,s2gender,s2dwellingType,
      s2neighbourhoodRacialMix,s2noLanguages,s2Afrikaans,s2English,s2otherLanguage,
      s2livedAbroad,s2yearsAbroad,s2highSchoolRacialMix,s2studyYears,s2anotherUniversity,
      s2friendType1,s2friendType2,s2friendType3,s2friendType4,s2friendType5,
      s2friendType6,s2nationality,s2populationGroup,s2province,s2highSchoolType,
      s2highSchoolLocation,s2currentStudyLocation,s2level,s2faculty,s2yr1dwelling,
      s2yr2dwelling,s2yr3dwelling,s2yr4dwelling,s2musicChoice,";
    echo "ijNo,istep1Id,ieffort,iunderstandingOtherGroup,iunderstandingFromEducation,
      iunderstandingFromFamilyFriends,iunderstandingFromOther,iage,igender,idwellingType,
      ineighbourhoodRacialMix,inoLanguages,iAfrikaans,iEnglish,iotherLanguage,
      ilivedAbroad,iyearsAbroad,ihighSchoolRacialMix,istudyYears,ianotherUniversity,
      ifriendType1,ifriendType2,ifriendType3,ifriendType4,ifriendType5,ifriendType6,
      inationality,ipopulationGroup,iprovince,ihighSchoolType,ihighSchoolLocation,
      icurrentStudyLocation,ilevel,ifaculty,iyr1dwelling,iyr2dwelling,iyr3dwelling,iyr4dwelling,imusicChoice,";
    echo "npjNo,npstep1Id,npeffort,npunderstandingOtherGroup,npunderstandingFromEducation,
      npunderstandingFromFamilyFriends,npunderstandingFromOther,npage,npgender,npdwellingType,
      npneighbourhoodRacialMix,npnoLanguages,npAfrikaans,npEnglish,npotherLanguage,
      nplivedAbroad,npyearsAbroad,nphighSchoolRacialMix,npstudyYears,npanotherUniversity,
      npfriendType1,npfriendType2,npfriendType3,npfriendType4,npfriendType5,npfriendType6,
      npnationality,nppopulationGroup,npprovince,nphighSchoolType,nphighSchoolLocation,
      npcurrentStudyLocation,nplevel,npfaculty,npyr1dwelling,npyr2dwelling,npyr3dwelling,npyr4dwelling,npmusicChoice,";
    echo "pjNo,pstep1Id,peffort,punderstandingOtherGroup,punderstandingFromEducation,
      punderstandingFromFamilyFriends,punderstandingFromOther,page,pgender,pdwellingType,
      pneighbourhoodRacialMix,pnoLanguages,pAfrikaans,pEnglish,potherLanguage,
      plivedAbroad,pyearsAbroad,phighSchoolRacialMix,pstudyYears,panotherUniversity,
      pfriendType1,pfriendType2,pfriendType3,pfriendType4,pfriendType5,pfriendType6,
      pnationality,ppopulationGroup,pprovince,phighSchoolType,phighSchoolLocation,
      pcurrentStudyLocation,plevel,pfaculty,pyr1dwelling,pyr2dwelling,pyr3dwelling,pyr4dwelling,pmusicChoice";
    echo "<br/>";
  }

$dataset = getS4Attributes();
writeHeaders();
for ($i=0; $i<2; $i++) { writeValues($i); }  

  
