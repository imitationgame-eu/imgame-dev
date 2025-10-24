<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-17); // /tests
include_once $root_path.'/domainSpecific/mySqlObject.php';       

  function getEffortIndex($text) {
    switch ($text) {
      case 'I did try hard all of the time': {
        return 4;
      }
      case 'I did try hard some of the time': {
        return 3;
      }
      case 'I did not try hard some of the time' : {
        return 2;
      }
      case 'I did not try hard all the time': {
        return 1;
      }
      default: {
        return -1;
      }
    }
  }
  
  function getGenderIndex($text) {
    return $text == 'Male' ? 1 : 0;
  }
  
  function getRacialMixIndex($text) {
    switch (strtolower($text)) {
      case 'not mixed': {
        return 0;
      }
      case 'a little mixed': {
        return 1;
      }
      case 'very mixed' : {
        return 2;
      }
      default : {
        return -1;
      }     
    }
  }

  function getLivedAbroadIndex($text) {
    return $text == "Yes" ? 1 : 0;
  }
  
  function getAnotherUniversityIndex($text) {
    return $text == "No" ? 0 : 1;  // yes has extra text!
  }

  function getDwellingIndex($text) {
    $comparand = substr($text,0,4);
    switch ($comparand) {
      case 'Farm': {
        return 1;
      }
      case 'Town': {
        return 2;
      }
      case 'Smal': {
        return 3;
      }
      case 'Larg': {
        return 4;
      }
      default: {
        return -1;
      }
    }
  }
  
  function getLanguageMatrix($components) {
    $noLanguages = 0;
    $hasOthers = 0;
    if ($components[31] > '') { ++$noLanguages; } 
    if ($components[32] > '') { ++$noLanguages; } 
    for ($i=33; $i<41; $i++) {
      if ($components[$i] > '') { 
        ++$noLanguages; 
        $hasOthers = 1;
      }       
    }
    return array('noLanguages'=>$noLanguages, 'otherLanguage'=>$hasOthers);
  }

  function getUnderstandingMatrix($components) {
    $education = ($components[13] > '' || $components[14]>'') ? 1 : 0;
    $homeSocialLife = ($components[15] > '' || $components[16]>'') ? 1 : 0;
    $media = ($components[17] > '' || $components[18]>'' || $components[19]>'') ? 1 : 0;
    $other = $components[20]>'' ? 1 : 0;
    return array(
      'education'=>$education, 
      'homeSocialLife'=>$homeSocialLife,
      'media'=>$media,
      'other'=>$other
    );
  }
  
  function getS2Details($text) {
    $emailDetails = explode('@',$text);
    $s2Details = explode('_', $text);
    $jType = -1;
    $respNo = -1;
    $qs = -1;
    if (($s2Details[0]=='s2') && ($s2Details[1]=='239')) {
      $jType = $s2Details[2];
      $qs = substr($s2Details[3], 2);
      $respNo = $s2Details[4];      
    }
    return array('qs'=>$qs, 'jType'=>$jType, 'respNo'=>$respNo);
  }
  
  function getNationalityText($primary, $text) {
    return $primary == 'Other - Please specify' ? $text : $primary;
  }
  
  function getYearValue($text) {
    if (mb_strlen($text)>1) {
      return substr($text,0 ,1);
    }
    return 0;
  }
  
  function showComponents($components) {
    for ($i=0; $i<count($components); $i++) {
      echo $i.' - '.$components[$i].'<br/>';
    }
  }
  
  

$exptId = 239;
$fn1 = "S2B.csv";
$lines1 = file($fn1, FILE_IGNORE_NEW_LINES);
$line1Cnt = count($lines1);
$fn2 = "S2W.csv";
$lines2 = file($fn2, FILE_IGNORE_NEW_LINES);
$line2Cnt = count($lines2);
$datalines = array();
$dataItems = array();
for ($i=0; $i<$line1Cnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines1[$i]), "data"=>$lines1[$i]);
  array_push($datalines, $temp);
}
for ($i=0; $i<$line2Cnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines2[$i]), "data"=>$lines2[$i]);
  array_push($datalines, $temp);
}
for ($i=0; $i<count($datalines); $i++) {
  $components = explode(',', $datalines[$i]['data']);
  if ($i == 0) { showComponents($components); }
  $s2Details = getS2Details($components[10]);
  $languageMatrix = getLanguageMatrix($components);
  $understandingMatrix = getUnderstandingMatrix($components);
  //echo $components[10].' '.print_r($s2Details, true).'<br />';
  $dataItem = array(
    'startDate' => $components[7],
    'step2Id' => $components[10],
    'respNo' => $s2Details['respNo'],
    'qs' => $s2Details['qs'],
    'jType' => $s2Details['jType'],
    'effort' => getEffortIndex($components[11]),
    'understandingOtherGroup' => $components[12],
    'understandingFromEducation' => $understandingMatrix['education'],
    'understandingFromFamilyFriends' => $understandingMatrix['homeSocialLife'],
    'understandingFromMedia' => $understandingMatrix['media'],
    'understandingFromOther' => $understandingMatrix['other'],
    'age' => $components[22],
    'gender' => getGenderIndex($components[23]),
    'nationality' => getNationalityText($components[24], $components[25]),
    'populationGroup' => $components[26],
    'dwellingType' => getDwellingIndex($components[27]),
    'neighbourhoodRacialMix' => getRacialMixIndex($components[28]),
    'province' => $components[29],
    'noLanguages' => $languageMatrix['noLanguages'],
    'Afrikaans' => $components[30] > '' ? 1 : 0,
    'English' => $components[31] > '' ? 1 : 0,
    'otherLanguage' => $languageMatrix['otherLanguage'],
    'livedAbroad' => getLivedAbroadIndex($components[41]),
    'yearsAbroad' => $components[42],
    'highSchoolType' => $components[43],
    'highSchoolLocation' => $components[44],
    'highSchoolRacialMix' => getRacialMixIndex($components[45]),
    'currentStudyLocation' => $components[46],
    'studyYears' => getYearValue($components[47]),
    'level' => $components[48],
    'faculty' => $components[49],
    'anotherUniversity' => getAnotherUniversityIndex($components[50]),
    'yr1dwelling' => $components[52],
    'yr2dwelling' => $components[54],
    'yr3dwelling' => $components[56],
    'yr4dwelling' => $components[58],
    'musicChoice' => $components[60],
    'friendType1' => $components[62],
    'friendType2' => $components[63],
    'friendType3' => $components[64],
    'friendType4' => $components[65],
    'friendType5' => $components[65],
    'friendType6' => $components[67]
  );
  array_push($dataItems, $dataItem);
}

$s2stratTblName = "xs_S2_".$exptId;
$tblExistsQry = sprintf("show tables like '%s'", $s2stratTblName);
$igrtSqli->query($tblExistsQry);
if ($igrtSqli->affected_rows == 0) {
  $createTblQry = sprintf("CREATE TABLE `%s` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` TEXT NULL,
    `jType` int(4) NULL,
    `respNo` int(4) NULL,
    `qs` int(4) NULL,
    `step2Id` TEXT NULL,
    `effort` int(4) NULL,
    `understandingOtherGroup` int(4) NULL,
    `understandingFromEducation` int(4) NULL,
    `understandingFromFamilyFriends` int(4) NULL,
    `understandingFromOther` int(4) NULL,
    `age` int(4) NULL,
    `gender` int(4) NULL,
    `dwellingType` int(4) NULL,
    `neighbourhoodRacialMix` int(4) NULL,
    `noLanguages` int(4) NULL,
    `Afrikaans` int(4) NULL,
    `English` int(4) NULL,
    `otherLanguage` int(4) NULL,
    `livedAbroad` int(4) NULL,
    `yearsAbroad` TEXT NULL,
    `highSchoolRacialMix` int(4) NULL,
    `studyYears` int(4) NULL,
    `anotherUniversity` int(4) NULL,
    `friendType1` TEXT NULL,
    `friendType2` TEXT NULL,
    `friendType3` TEXT NULL,
    `friendType4` TEXT NULL,
    `friendType5` TEXT NULL,
    `friendType6` TEXT NULL,
    `nationality` TEXT NULL,
    `populationGroup` TEXT NULL,
    `province` TEXT NULL,
    `highSchoolType` TEXT NULL,
    `highSchoolLocation` TEXT NULL,
    `currentStudyLocation` TEXT NULL,
    `level` TEXT NULL,
    `faculty` TEXT NULL,
    `yr1dwelling` TEXT NULL,
    `yr2dwelling` TEXT NULL,
    `yr3dwelling` TEXT NULL,
    `yr4dwelling` TEXT NULL,
    `musicChoice` TEXT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8", $s2stratTblName);
  $igrtSqli->query($createTblQry);
  echo $createTblQry.'<br />';
}


for($i=0; $i<count($dataItems); $i++) {
  $dataItem = $dataItems[$i];
  $insertQry = sprintf("INSERT INTO %s (date,jType,respNo,qs,step2Id,effort,"
      . "understandingOtherGroup,understandingFromEducation,understandingFromFamilyFriends,"
      . "understandingFromOther,age,gender, dwellingType,neighbourhoodRacialMix, "
      . "noLanguages,Afrikaans, English,otherLanguage,livedAbroad,"
      . "yearsAbroad,highSchoolRacialMix,studyYears,anotherUniversity,"
      . "friendType1,friendType2,friendType3,friendType4,friendType5,friendType6,"
      . "nationality,populationGroup,province,highSchoolType,highSchoolLocation,"
      . "currentStudyLocation,level,faculty,"
      . "yr1dwelling,yr2dwelling,yr3dwelling,yr4dwelling,musicChoice) "
      . "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
      . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
      . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
      $s2stratTblName,
      $dataItem['startDate'],
      $dataItem['jType'],
      $dataItem['respNo'],
      $dataItem['qs'],
      $dataItem['step2Id'],
      $dataItem['effort'],
      $dataItem['understandingOtherGroup'],
      $dataItem['understandingFromEducation'],
      $dataItem['understandingFromFamilyFriends'],
      $dataItem['understandingFromOther'],
      $dataItem['age'],
      $dataItem['gender'],
      $dataItem['dwellingType'],
      $dataItem['neighbourhoodRacialMix'],
      $dataItem['noLanguages'],
      $dataItem['Afrikaans'],
      $dataItem['English'],
      $dataItem['otherLanguage'],
      $dataItem['livedAbroad'],
      $dataItem['yearsAbroad'],
      $dataItem['highSchoolRacialMix'],
      $dataItem['studyYears'],
      $dataItem['anotherUniversity'],
      $dataItem['friendType1'],
      $dataItem['friendType2'],
      $dataItem['friendType3'],
      $dataItem['friendType4'],
      $dataItem['friendType5'],
      $dataItem['friendType6'],
      $dataItem['nationality'],
      $dataItem['populationGroup'],
      $dataItem['province'],
      $dataItem['highSchoolType'],
      $dataItem['highSchoolLocation'],
      $dataItem['currentStudyLocation'],
      $dataItem['level'],
      $dataItem['faculty'],
      $dataItem['yr1dwelling'],
      $dataItem['yr2dwelling'],
      $dataItem['yr3dwelling'],
      $dataItem['yr4dwelling'],
      $dataItem['musicChoice']
  );
  //echo $insertQry.'<br/>';
  $igrtSqli->query($insertQry);
}

echo 'not done - queries disabled';