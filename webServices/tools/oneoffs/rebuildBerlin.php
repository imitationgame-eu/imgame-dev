<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';       
include_once $root_path.'/ws/models/class.step1AllocationModel.php';

$playerCount = 0;     // will be derived from log
$exptId = 0;          // will be derived from log
$dayNo = 0;           // will be derived from log
$sessionNo = 0;       // will be derived from log

// arrays of Step1 components
$mapClientIdToPlayer;
$PlayerAllocations;
$Ratings;
$finalRatings;
$jQCnt = array();
$NPCnt = array();
$PCnt =array();
$JudgeArray = array();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions
  function getLoginJoins($datalines) {
    $ljArray = array();
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "loginJoin") {
          $clientId = $details[3];
          $msg = urldecode($details[5]);
          $loginDetails = explode('--', $msg);
          $uid = substr($loginDetails[1], 1);
          $exptId = substr($loginDetails[2], 1);
          $jType = substr($loginDetails[3], 1);
          $jNo = substr($loginDetails[4], 1);
          $dayNo = substr($loginDetails[5], 1);
          $sessionNo = substr($loginDetails[6], 1);
          $tempItem = array (
            'clientId' => $clientId,
            'msg' => $msg,
            'uid' => $uid,
            'exptId' => $exptId,
            'jType' => $jType,
            'jNo' => $jNo,
            'dayNo' => $dayNo,
            'sessionNo' => $sessionNo,
          );
          array_push($ljArray, $tempItem);       
        }
      }
    }
    return $ljArray;
  }
  
  function populateJQ($datalines) {
    global $mapClientIdToPlayer;
    global $jQCnt;
    global $NPCnt;
    global $PCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl[$i]["count"] > 0) {
        $source = $dl[$i]["data"];  
        $details = explode(':', $source);
        $msgType = $details[0];
        if ($msgType == "JQ") {
          $msg = urldecode($details[5]);
          $content = substr($msg, 6);
          $turnPtr = ++$jQCnt[$jNo];
          $JudgeArray[$jNo][$turnPtr]['q'] = $content; 
          $JudgeArray[$jNo][$turnPtr]['npr'] = '';
          $JudgeArray[$jNo][$turnPtr]['pr'] = '';
          $JudgeArray[$jNo][$turnPtr]['choice'] = -1;
          $JudgeArray[$jNo][$turnPtr]['confidence'] = '';
          $JudgeArray[$jNo][$turnPtr]['reason'] = '';
          $JudgeArray[$jNo][$turnPtr]['isFinal'] = false;
        }
      }
    }
  }

  function isUniqueMapping($mappings, $lj) {
    foreach ($mappings as $map) {
      if ($map['uid'] == $lj['uid']) { return false; }
    }
    return true;
  }
  
  function makeMapping($lj) {
    global $playerCount;
    global $exptId;
    global $dayNo;
    global $sessionNo;
    global $igrtSqli;
    if ($exptId == 0) {
      // get session parameters
      $activeUserQry = sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $lj['uid']);
      $activeUserResult = $igrtSqli->query($activeUserQry);
      $activeUserRow = $activeUserResult->fetch_object();
      $exptId = $activeUserRow->exptId;
      $dayNo = $activeUserRow->day;
      $sessionNo = $activeUserRow->session;
    }
    // get this player's parameters
    $playerDetailsQry = sprintf("SELECT * FROM igActiveStep1Users WHERE uid='%s'", $lj['uid']);
    $playerDetailsResult = $igrtSqli->query($playerDetailsQry);
    $playerDetailsRow = $playerDetailsResult->fetch_object();
    $jType = $playerDetailsRow->jType;
    $jNo = $playerDetailsRow->jNo;
    $playerNo = ($jType == 1) ? $jNo*2+1 : ($jNo+1)*2;
    ++$playerCount;
    return array('uid'=> $lj['uid'], 'playerNo'=>$playerNo, 'jType'=> $jType, 'jNo' => $jNo);    
  }
  
  function getMappings($loginJoins) {
    $mappings = array();
    foreach ($loginJoins as $lj) {
      if (isUniqueMapping($mappings, $lj)) {
        array_push($mappings, makeMapping($lj));
      }
    }
    return $mappings;
  }
  
  function getPlayerNoFromID($uid) {
    global $mapClientIdToPlayer;  
    foreach ($mapClientIdToPlayer as $map) {
      if ($map['uid'] == $uid) { return $map['playerNo']; }
    }
    return -1;
  } 
  
  
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

for ($k=1; $k<=1; $k++) {
  echo $k. "<br />";
  $playerCount = 0;     
  $exptId = 0;          
  $dayNo = 0;           
  $sessionNo = 0;       
  $fn = "berlin".$k.".txt";
  $lines = file($fn, FILE_IGNORE_NEW_LINES);
  $lineCnt = count($lines);
  $datalines = array();
  for ($i=0; $i<$lineCnt; $i++) {
    if (isset($temp)) { unset($temp); }
    $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
    array_push($datalines, $temp);
  }
  $loginJoins = getLoginJoins($datalines);
  $status = print_r($loginJoins, true)."<br />";
  echo $status;
  $mapClientIdToPlayer = getMappings($loginJoins);
  $status = print_r($mapClientIdToPlayer, true)."<br />";
  echo $status;
  $playerCount = count($mapClientIdToPlayer); // could check this as query against edSessions
  $judgeCount = $playerCount / 2;
  echo $judgeCount."<br />";
//  $PlayerAllocations = new roleAllocations($judgeCount, 1);
//  $PlayerAllocations->generateFixed();
//  $status.= print_r($PlayerAllocations->mappings, true)."<br />";
//  $turnArray = array();
//  for ($i=1; $i<=$playerCount; $i++) {
//    $jQCnt[$i]=0;
//    $NPCnt[$i]=0;
//    $PCnt[$i]=0;
//    array_push($JudgeArray, $turnArray);
//  } 
//  populateJQ($datalines);
//  $status.= print_r($JudgeArray, true)."<br />";
  echo $status;
  unset($mapClientIdToPlayer);
  unset($PlayerAllocations);
  unset($datalines);
  unset($lines);
  unset($loginJoins);
  unset($JQ);
}

//$dlCnt = count($datalines);
//for ($i=0; $i<$dlCnt; $i++) {
//  // process each line
//  if ($datalines[$i]["count"] > 0) {
//    $source = $datalines[$i]["data"];  
//    $details = explode(':', $source);
//    $msgType = $details[0];
//    $jNo = $details[3];
//    $msg = urldecode($details[5]);
//    switch ($msgType) {
//      case 'JQ' : {
//        $content = substr($msg, 6);
//        $turnPtr = ++$jQCnt[$jNo];
//        $JudgeArray[$jNo][$turnPtr]['q'] = $content;
//        break;
//      }
//      case 'NPA' : {
//        $content = substr($msg, 7);
//        $turnPtr = ++$NPCnt[$jNo];
//        $JudgeArray[$jNo][$turnPtr]['np'] = $content;
//        break;
//      }
//      case 'PA' : {
//        $content = substr($msg, 6);
//        $turnPtr = ++$PCnt[$jNo];
//        $JudgeArray[$jNo][$turnPtr]['p'] = $content;
//        break;
//      }
//    }
//  }
//}
//
//for ($i=1; $i<=$playerCount; $i++) {
//  $jNo = round($i / 2,0,PHP_ROUND_HALF_DOWN);
//  $jType = ($i%2 == 0) ? 0 : 1;
//  $uid = $firstID + $i - 1;
//  $npLeft = ($jType == 0) ? 1 : 0;
//  if ($jQCnt[$i] > 0) {
//    for ($j=1; $j<=$jQCnt[$i]; $j++) {
//      $sql = sprintf("INSERT INTO dataSTEP1 (uid, exptId, jType, JNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr) "
//          . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
//          $uid, $exptId, $jType, $jNo, $sessionNo, $dayNo, $npLeft, $j, 
//          $igrtSqli->real_escape_string($JudgeArray[$i][$j]['q']),
//          $igrtSqli->real_escape_string($JudgeArray[$i][$j]['np']),
//          $igrtSqli->real_escape_string($JudgeArray[$i][$j]['p'])       
//          );
//      //echo $sql."<br />";
//      $igrtSqli->query($sql);
//    }
//    $sql = sprintf("INSERT INTO dataSTEP1 (exptId, jType, JNo, sessionNo, dayNo, qNo, q, npr, pr) "
//          . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s')",
//          $exptId, $jType, $jNo, $sessionNo, $dayNo, $j, 
//          'FINAL',
//          'FINAL',
//          'FINAL'       
//          );
//    $igrtSqli->query($sql);   
//  }
//}
echo 'done';
//$debug = print_r($JudgeArray);
//echo $debug;


