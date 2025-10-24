<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
//echo $root_path;
include_once $root_path.'/domainSpecific/mySqlObject.php';       
$exptId = -1;         



// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions

function getS2Lines($lines) {
  $s2lines = [];
  for ($i=0; $i<count($lines); $i++) {
    $line = $lines[$i]['data'];
    $data = explode("+0100]", $line);
    //echo $data[1].'<br />';
    //echo substr($data[1],0,91).'<br />';
    if (substr($data[1],0,91)== ' "GET /webServices/step2/step2RunController.php?permissions=255&messageType=storeStep2reply') {
      array_push($s2lines, $data[1]);
    }
  }
  return $s2lines;
}

function processFile($fn) {
  $lines = file($fn, FILE_IGNORE_NEW_LINES);
  $lineCnt = count($lines);
  $datalines = [];
  for ($i=0; $i<$lineCnt; $i++) {
    if (isset($temp)) { unset($temp); }
    $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
    array_push($datalines, $temp);
  }
  $s2store = getS2Lines($datalines);
  //echo print_r($s2store, true).'<br />';
  processData($s2store);
}

function getSessionDetails($jType, $respId) {
  global $igrtSqli;
  $sql = sprintf("SELECT * FROM wt_Step2pptStatus WHERE id='%s'", $respId);
  //echo $sql;
  $sdResult = $igrtSqli->query($sql);
  $sdRow = $sdResult->fetch_object();
  $actualJNo = $sdRow->actualJNo;
  $isdSql = sprintf("SELECT * FROM wt_Step2Balancer WHERE exptId='%s' AND jType='%s' AND actualJNo='%s'", 332, 0, $actualJNo);
  //echo $isdSql;
  $isdResult = $igrtSqli->query($isdSql);
  $isdRow = $isdResult->fetch_object();
  $retArray = array('igrNo'=>$actualJNo, 'jNo' => $isdRow->jNo, 'dayNo' => $isdRow->dayNo, 'sessionNo'=> $isdRow->sessionNo, 'label' => $isdRow->label);
  return $retArray;
}

function stripSpaces($reply) {
  return str_replace('%20',' ',$reply);
}

function getPptNo($jNo, $respId) {
  // check whether this respId has a pptNo, and if not create the mapping
  global $igrtSqli;
  $getQry = sprintf("SELECT * FROM wt_s2Mapping_Wroclaw WHERE respId='%s'", $respId);
  $getResult = $igrtSqli->query($getQry);
  if ($igrtSqli->affected_rows > 0) {
    $getRow = $getResult->fetch_object();
    return $getRow->pptNo;
  }
  else {
    $maxQuery = sprintf("SELECT MAX(pptNo) AS pptNo FROM wt_s2Mapping_Wroclaw WHERE igNo='%s'", $jNo);
    $maxResult = $igrtSqli->query($maxQuery);
    if ($igrtSqli->affected_rows > 0) {
      $maxRow = $maxResult->fetch_object();
      $pptNo = $maxRow->pptNo + 1;
      $insertPpt = sprintf("INSERT INTO wt_s2Mapping_Wroclaw (igNo, respId, pptNo) VALUES('%s','%s','%s')",
        $jNo, $respId, $pptNo);
      $igrtSqli->query($insertPpt);
      $statusPpt = sprintf("UPDATE wt_Step2pptStatus respNo='%s' "
        . "WHERE exptId=332 AND id='%s'",
        $pptNo, $respId);
      $igrtSqli->query($statusPpt);
      return $pptNo;
    }
    else {
      $pptNo = 1;
      $insertPpt = sprintf("INSERT INTO wt_s2Mapping_Wroclaw (igNo, respId, pptNo) VALUES('%s','%s','%s')",
        $jNo, $respId, $pptNo);
      $igrtSqli->query($insertPpt);
      $statusPpt = sprintf("UPDATE wt_Step2pptStatus respNo='%s' "
        . "WHERE exptId=332 AND id='%s'",
        $pptNo, $respId);
      $igrtSqli->query($statusPpt);
      return $pptNo;      
    }
  }
}

function processData($s2store) {
  global $igrtSqli;
  for ($i=0; $i<count($s2store); $i++) {
    //echo $s2store[$i].'<br/>';
    $details = explode("=", $s2store[$i]);
    //echo print_r($details, true);
    $arrayExplode = explode("&content", $details[5]);
    //echo print_r($arrayExplode, true);
    $qNo = $arrayExplode[0];
    //echo $qNo.'<br/>';
    $arrayExplode = explode("&content", $details[6]);
    $reply = urldecode(urldecode($arrayExplode[0]));
    $reply = stripSpaces($reply);
    //echo $reply.'<br/>';
//    $arrayExplode = explode("&content", $details[7]);
//    $pptNo = $arrayExplode[0];
    //echo $pptNo.'<br/>';
    
    $arrayExplode = explode("&content", $details[8]);
    $respId = $arrayExplode[0];
    $sessionDetails = getSessionDetails(0, $respId);
    $jNo = $sessionDetails['jNo'];
    $dayNo = $sessionDetails['dayNo'];
    $sessionNo = $sessionDetails['sessionNo'];
    //echo $respId.'<br/>';
    
    $pptNo = getPptNo($jNo, $respId);
    // check that this data does not already exist - some double-clicks occurred so double entries in logs
    $checkQry = sprintf("SELECT * FROM dataSTEP2 WHERE "
      . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND qNo='%s' AND pptNo='%s'",
      332, $dayNo, $sessionNo, $jNo, ($qNo+1), $pptNo);
    //echo $checkQry.'<br />';
    $igrtSqli->query($checkQry);
    if ($igrtSqli->affected_rows == 0) {
      $insert = sprintf("INSERT INTO dataSTEP2 (exptId, dayNo, sessionNo, jType, jNo, qNo, pptNo, reply) "
        . "VALUES ('332', '%s', '%s', '0', '%s', '%s', '%s', '%s')",
        $dayNo, $sessionNo, $jNo, ($qNo + 1), $pptNo, $igrtSqli->real_escape_string($reply));
      //echo $insert.'<br />';
      $igrtSqli->query($insert);
    }
  }
//      var contentArray = {};
//      contentArray[0] = qNo;
//      contentArray[1] = encodeURIComponent($('#answerTA').val());
//      contentArray[2] = pptNo;
//      contentArray[3] = respId;
//      contentArray[4] = 0;  // 0 = no alignment data expected
//      contentArray[5] = -1;  // 
//      contentArray[6] = '';
//      contentArray[7] = -1;  // 
//      contentArray[8] = uid for the step2 respondent. USE THIS!;
//      content = contentArray;
//Array ( 
//[0] => "GET /webServices/step2/step2RunController.php?permissions 
//[1] => 255&messageType 
//[2] => storeStep2reply&exptId 
//[3] => 332&jType 
//[4] => 0&content%5B0%5D 
//[5] => 0&content%5B1%5D 
//[6] => Albert%2520%2520%2520%2520%2520%2520&content%5B2%5D 
//[7] => 1&content%5B3%5D 
//[8] => 10453&content%5B4%5D 
//[9] => 0&content%5B5%5D 
//[10] => -1&content%5B6%5D 
//[11] => HTTP/1.1" 200 156 "http://imgame1.cf.ac.uk/s2_332_0" "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.112 Safari/537.36" )     
//  }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// clear data
$clearQry = "TRUNCATE wt_s2Mapping_Wroclaw";
$igrtSqli->query($clearQry);
$clearQry = "DELETE FROM dataSTEP2 WHERE exptId=332";
$igrtSqli->query($clearQry);
$exptId = 332; 
$fnList = ['access_log', 'access_log-20160424','access_log-20160425','access_log-20160426','access_log-20160427'];
for ($i=0; $i<count($fnList); $i++) {
  //echo $fnList[$i].'<br/>';
  processFile($fnList[$i]);  
}
echo 'done';

