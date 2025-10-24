<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
//echo $root_path;
include_once $root_path.'/domainSpecific/mySqlObject.php';       
$exptId = -1;         



// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions

function getSPLines($lines) {
  global $exptId;
  $spLines = [];
  for ($i=0; $i<count($lines); $i++) {
    $line = $lines[$i]['data'];
    $data = explode("+0100]", $line);
    if (strpos($data[1], '&messageType=getStep2Status&exptId=331') > 0) {
      array_push($spLines, $line);
    }    
  }
  return $spLines;
}

function getS2Lines($lines) {
  global $exptId;
  $s2lines = [];
  for ($i=0; $i<count($lines); $i++) {
    $line = $lines[$i]['data'];
    $data = explode("+0100]", $line);
    //echo $data[1].'<br />';
    //echo substr($data[1],0,91).'<br />';
    if (strpos($data[1], '&messageType=storeStep2reply&exptId=331') > 0) {
      array_push($s2lines, $line);
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
  // firstly process mapping data and igNos
  $spStore = getSPLines($datalines);
  processStartParameters($spStore);
  // then process actual data submissions  
  $s2store = getS2Lines($datalines);
  processData($s2store);
}

function getSessionDetails($jType, $respId) {
  global $igrtSqli, $exptId;
  $sql = sprintf("SELECT * FROM wt_s2Mapping_Madrid WHERE respId='%s'", $respId);
  //echo $sql;
  $sdResult = $igrtSqli->query($sql);
  $mapRow = $sdResult->fetch_object();
  $retArray = array('igNo' => $mapRow->igNo, 'jNo' => $mapRow->igNo - 1, 'dayNo' => 1, 'sessionNo'=> 1, 'pptNo'=> $mapRow->pptNo);
  return $retArray;
}

function stripSpaces($reply) {
  return str_replace('%20',' ',$reply);
}

function processStartParameters($spStore) {
  global $igrtSqli, $exptId;
  for ($i=0; $i<count($spStore); $i++) {
    $dateDetails = explode('+0100', $spStore[$i]);
    //echo print_r($dateDetails, true).'<br />';
    $details = explode('&content', $dateDetails[1]);
    //echo print_r($details, true).'<br />';
    $respIdDetails = explode('=', $details[2]);
    $respId = $respIdDetails[1];
    $igDetails = explode('=', $details[3]);
    $igNo = $igDetails[1];
    //echo "respId=$respId ig=$igNo<br />";
    $getPPTQry = sprintf("SELECT MAX(pptNo) AS pptNo FROM wt_s2Mapping_Madrid WHERE igNo='%s'", $igNo);
    $pptResult = $igrtSqli->query($getPPTQry);
    if ($pptResult) {
      $pptRow = $pptResult->fetch_object();
      $pptNo = $pptRow->pptNo + 1;
    }
    else {
      $pptNo = 1;
    }
    $dtDetails = explode('[', $dateDetails[0]);
    $dtDetails2 = explode(':', $dtDetails[1]);
    $dtStr = $igrtSqli->real_escape_string($dtDetails2[0]);
    echo $dtStr.'<br/>';
    $insertPpt = sprintf("INSERT INTO wt_s2Mapping_Madrid (igNo, respId, pptNo, dt) VALUES('%s','%s','%s','%s')",
      $igNo, $respId, $pptNo, $dtStr);
    $igrtSqli->query($insertPpt);
    echo $insertPpt.'<br />';
    date_default_timezone_set("Europe/London");    
    $dateItems = explode('/',$dtStr);
    $y = $dateItems[2];
    $d = $dateItems[0];
    switch ($dateItems[1]) {
      case 'Jan': { $m = '01'; break; }
      case 'Feb': { $m = '02'; break; }
      case 'Mar': { $m = '03'; break; }
      case 'Apr': { $m = '04'; break; }
      case 'May': { $m = '05'; break; }
      case 'Jun': { $m = '06'; break; }
      case 'Jul': { $m = '07'; break; }
      case 'Aug': { $m = '08'; break; }
      case 'Sep': { $m = '09'; break; }
      case 'Oct': { $m = '10'; break; }
      case 'Nov': { $m = '11'; break; }
      case 'Dec': { $m = '12'; break; }
    }
    $dateStr = $y.'-'.$m.'-'.$d;
    //$dt = new DateTime($d.'-'.$m.'-'.$y);
    $statusPpt = sprintf("INSERT INTO wt_Step2pptStatus (exptId, jType, actualJNo, respNo, chrono) "
      . "VALUES('%s', '%s', '%s', '%s', '%s')", 331, 0, $igNo, $pptNo, $dateStr); 
    $igrtSqli->query($statusPpt);
    echo $statusPpt.'<br />';
  }
}

function processData($s2store) {
  global $igrtSqli, $exptId;
  for ($i=0; $i<count($s2store); $i++) {
    $dateDetails = explode('+0100', $s2store[$i]);
    //echo print_r($dateDetails, true).'<br />';
    //echo $s2store[$i].'<br/>';
    $details = explode("=", $dateDetails[1]);
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
    
    $arrayExplode = explode("&content", $details[8]);
    $respId = $arrayExplode[0];
    $sessionDetails = getSessionDetails(0, $respId);
    $jNo = $sessionDetails['jNo'];
    $pptNo = $sessionDetails['pptNo'];
    $dayNo = $sessionDetails['dayNo'];
    $sessionNo = $sessionDetails['sessionNo'];
    //echo $pptNo.'<br/>';
    // check that this data does not already exist - some double-clicks occurred so double entries in logs
    $checkQry = sprintf("SELECT * FROM dataSTEP2 WHERE "
      . "exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jNo='%s' AND qNo='%s' AND pptNo='%s'",
      $exptId, $dayNo, $sessionNo, $jNo, ($qNo+1), $pptNo);
    //echo $checkQry.'<br />';
    $igrtSqli->query($checkQry);
    if ($igrtSqli->affected_rows == 0) {
      $insert = sprintf("INSERT INTO dataSTEP2 (exptId, dayNo, sessionNo, jType, jNo, qNo, pptNo, reply) "
        . "VALUES ('%s', '%s', '%s', '0', '%s', '%s', '%s', '%s')",
        $exptId, $dayNo, $sessionNo, $jNo, ($qNo + 1), $pptNo, $igrtSqli->real_escape_string($reply));
      echo $insert.'<br />';
      $igrtSqli->query($insert);
    }
    else {
      // update - may be correct data after a restart
      $updateQry = sprintf("UPDATE dataSTEP2 SET reply='%s' WHERE exptId='%s' AND dayNo='%s' AND sessionNo='%s' AND jType='%s' AND jNo='%s' AND qNo='%s' AND pptNo='%s'",
        $igrtSqli->real_escape_string($reply), $exptId, $dayNo, $sessionNo, 0, $jNo, ($qNo + 1), $pptNo );
      $igrtSqli->query($updateQry);
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
$exptId = 331; 
$clearQry = "TRUNCATE wt_s2Mapping_Madrid";
$igrtSqli->query($clearQry);
$clearQry = "DELETE FROM dataSTEP2 WHERE exptId=".$exptId;
$igrtSqli->query($clearQry);
$clearQry = "DELETE FROM wt_Step2pptStatus WHERE exptId=".$exptId;
$igrtSqli->query($clearQry);
$fnList = ['access_log-20160701', 'access_log-20160702', 'access_log-20160703', 'access_log-20160704', 'access_log-20160705',
  'access_log-20160706','access_log-20160707','access_log-20160708','access_log-20160709',
  'access_log-20160710','access_log-20160711','access_log-20160712','access_log-20160713','access_log-20160714',
  'access_log-20160715','access_log-20160716','access_log-20160717','access_log-20160718','access_log-20160719','access_log-20160720',
  'access_log-20160721','access_log-20160722','access_log-20160723','access_log-20160724','access_log-20160725','access_log-20160726',
  'access_log-20160730','access_log-20160731','access_log-20160801','access_log-20160802','access_log-20160803','access_log-20160804',
  'access_log-20160805','access_log-20160806','access_log-20160807','access_log-20160808','access_log-20160809',
  'access_log-20160811','access_log-20160812','access_log-20160813','access_log'
];
for ($i=0; $i<count($fnList); $i++) {
  //echo $fnList[$i].
  processFile($fnList[$i]);  
}
//// now make wt_Step2pptStatus consistent with wt_s2Mapping_Madrid 
//$clearQry = "DELETE FROM wt_Step2pptStatus WHERE exptId=".$exptId;
//$igrtSqli->query($clearQry);
//$selectAll = "SELECT * FROM wt_s2Mapping_Madrid ORDER BY igNo ASC, pptNo ASC";
//$mappingResult = $igrtSqli->query($selectAll);
//while ($mappingRow = $mappingResult->fetch_object()) {
//  $insertStatus = sprintf("INSERT INTO wt_Step2pptStatus (exptId, jType, finished, actualJNo, respNo, userCode) "
//    . "VALUES(331, 0, 1, '%s', '%s', '%s')",
//    ($mappingRow->igNo + 1),
//    $mappingRow->pptNo,
//    $mappingRow->respId);
//  $igrtSqli->query($insertStatus);
//}
//

echo 'done';

