<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
$full_ws_path = realpath(dirname(__FILE__));
$root_path = substr($full_ws_path, 0, strlen($full_ws_path)-17); // /tests
include_once $root_path.'/domainSpecific/mySqlObject.php';       

  function getLivedAbroadIndex($text) {
    return $text == "Yes" ? 1 : 0;
  }
  
  function getAnotherUniversityIndex($text) {
    return $text == 2 ? 1 : 0;  
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
    if ($components[22] > '') { ++$noLanguages; } 
    if ($components[23] > '') { ++$noLanguages; } 
    for ($i=24; $i<=32; $i++) {
      if ($components[$i] > '') { 
        ++$noLanguages; 
        $hasOthers = 1;
      }       
    }
    return array('noLanguages'=>$noLanguages, 'otherLanguage'=>$hasOthers);
  }
  
  function getS4Details($text) {
    $s4Details = explode('_', $text);
    $jType = $s4Details[2];
    $s4jNo = intval($s4Details[3]);
    return array('s4jNo'=>$s4jNo, 'jType'=>$jType);
  }
  
  function getNationalityText($primary, $text) {
    return $primary == 1 ? "South African" : $text;
  }
  
  function getYearValue($text) {
    if (mb_strlen($text)>1) {
      return substr($text,0 ,1);
    }
    return 0;
  }
  
  function getPrevParticipationMatrix($primary, $stage) {
    $prevPpt = $primary == 1 ? 1: 0;
    $prevStage = $primary == 1 ? $stage : 0;
    return array('prevPpt'=>$prevPpt, 'prevStage'=>$prevStage);
  }
  
  function getGenderIndex($value) {
    return $value == 1 ? 1 : 0;
  }
  
$exptId = 239;
$fn1 = "S4b.csv";
$lines1 = file($fn1, FILE_IGNORE_NEW_LINES);
$line1Cnt = count($lines1);
$fn2 = "S4w.csv";
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
  $s4Details = getS4Details($components[10]);
  $prevParticipationMatrix = getPrevParticipationMatrix($components[11], $components[12]);
  $languageMatrix = getLanguageMatrix($components);
  $dataItem = array(
    'startDate' => $components[7],
    's4jNo' => $s4Details['s4jNo'],
    'jType' => $s4Details['jType'],
    'effort' => $components[13],
    'age' => $components[14],
    'gender' => getGenderIndex($components[15]),
    'nationality' => getNationalityText($components[16], $components[17]),
    'populationGroup' => $components[18],
    'dwellingType' => $components[19],
    'neighbourhoodRacialMix' => $components[20],
    'province' => $components[21],
    'noLanguages' => $languageMatrix['noLanguages'],
    'Afrikaans' => $components[22] > '' ? 1 : 0,
    'English' => $components[23] > '' ? 1 : 0,
    'otherLanguage' => $languageMatrix['otherLanguage'],
    'livedAbroad' => $components[33],
    'yearsAbroad' => $components[34],
    'highSchoolType' => $components[35],
    'highSchoolLocation' => $components[36],
    'highSchoolRacialMix' => $components[37],
    'currentStudyLocation' => $components[38],
    'studyYears' => $components[39],
    'level' => $components[40],
    'faculty' => $components[41],
    'anotherUniversity' => getAnotherUniversityIndex($components[42]),
    'yr1dwelling' => $components[44],
    'yr2dwelling' => $components[46],
    'yr3dwelling' => $components[48],
    'yr4dwelling' => $components[50],
    'musicChoice' => $components[53],
    'africanFriends' => $components[54],
    'colouredFriends' => $components[55],
    'indianFriends' => $components[56],
    'whiteFriends' => $components[57],
    'otherSAFriends' => $components[58],
    'othernonSAFriends' => $components[59]
  );
  array_push($dataItems, $dataItem);
}

$s4stratTblName = "xs_S4_".$exptId;
$tblExistsQry = sprintf("show tables like '%s'", $s4stratTblName);
$igrtSqli->query($tblExistsQry);
if ($igrtSqli->affected_rows == 0) {
  $createTblQry = sprintf("CREATE TABLE `%s` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` TEXT NULL,
    `s4jNo` int(4) NULL,
    `jType` int(4) NULL,
    `effort` int(4) NULL,
    `age` int(4) NULL,
    `gender` int(4) NULL,
    `nationality` TEXT NULL,
    `populationGroup` int(4) NULL,
    `dwellingType` int(4) NULL,
    `neighbourhoodRacialMix` int(4) NULL,
    `province` int(4) NULL,
    `noLanguages` int(4) NULL,
    `Afrikaans` int(4) NULL,
    `English` int(4) NULL,
    `otherLanguage` int(4) NULL,
    `livedAbroad` int(4) NULL,
    `yearsAbroad` TEXT NULL,
    `highSchoolType` int(4) NULL,
    `highSchoolLocation` int(4) NULL,
    `highSchoolRacialMix` int(4) NULL,
    `currentStudyLocation` int(4) NULL,
    `studyYears` int(4) NULL,
    `level` int(4) NULL,
    `faculty` int(4) NULL,
    `anotherUniversity` int(4) NULL,
    `yr1dwelling` TEXT NULL,
    `yr2dwelling` TEXT NULL,
    `yr3dwelling` TEXT NULL,
    `yr4dwelling` TEXT NULL,
    `musicChoice` TEXT NULL,
    `africanFriends` int(4) NULL,
    `colouredFriends` int(4) NULL,
    `indianFriends` int(4) NULL,
    `whiteFriends` int(4) NULL,
    `otherSAFriends` int(4) NULL,
    `othernonSAFriends` int(4) NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8", $s4stratTblName);
  $igrtSqli->query($createTblQry);
  echo $createTblQry.'<br />';
}


for($i=0; $i<count($dataItems); $i++) {
  $dataItem = $dataItems[$i];
  $insertQry = sprintf("INSERT INTO %s (date,s4jNo,jType,effort,age,gender,"
      . "nationality,populationGroup,dwellingType,neighbourhoodRacialMix,"
      . "province,noLanguages,Afrikaans,English,otherLanguage,"
      . "livedAbroad,yearsAbroad,highSchoolType,highSchoolLocation,highSchoolRacialMix,"
      . "currentStudyLocation,studyYears,level,faculty,anotherUniversity,"
      . "yr1dwelling,yr2dwelling,yr3dwelling,yr4dwelling,musicChoice,"
      . "africanFriends,colouredFriends,indianFriends,whiteFriends,otherSAFriends,othernonSAFriends) "
      . "VALUES ("
      . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
      . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
      . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
      . "'%s','%s','%s','%s','%s','%s')",
      $s4stratTblName,
      $dataItem['startDate'],
      $dataItem['s4jNo'],
      $dataItem['jType'],
      $dataItem['effort'],
      $dataItem['age'],
      $dataItem['gender'],
      $dataItem['nationality'],
      $dataItem['populationGroup'],
      $dataItem['dwellingType'],
      $dataItem['neighbourhoodRacialMix'],
      $dataItem['province'],
      $dataItem['noLanguages'],
      $dataItem['Afrikaans'],
      $dataItem['English'],
      $dataItem['otherLanguage'],
      $dataItem['livedAbroad'],
      $dataItem['yearsAbroad'],
      $dataItem['highSchoolType'],
      $dataItem['highSchoolLocation'],
      $dataItem['highSchoolRacialMix'],
      $dataItem['currentStudyLocation'],
      $dataItem['studyYears'],
      $dataItem['level'],
      $dataItem['faculty'],
      $dataItem['anotherUniversity'],
      $dataItem['yr1dwelling'],
      $dataItem['yr2dwelling'],
      $dataItem['yr3dwelling'],
      $dataItem['yr4dwelling'],
      $dataItem['musicChoice'],
      $dataItem['africanFriends'],
      $dataItem['colouredFriends'],
      $dataItem['indianFriends'],
      $dataItem['whiteFriends'],
      $dataItem['otherSAFriends'],
      $dataItem['othernonSAFriends']
  );
  //echo $insertQry.'<br/>';
  //$igrtSqli->query($insertQry);
}

echo 'not done - queries disabled';