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
  $playerAllocations;
  $Ratings;
  $finalRatings;
  $jQCnt = array();
  $NPCnt = array();
  $PCnt = array();
  $JRCnt = array();
  $JudgeArray = array();
  $sessionDetails = array();
  $minmaxLoginUids = array();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// functions

  function getSessionDetails($datalines) {
    //initSession<:>1<:>261<:>1<:>1<:>9    
    $sdArray = array();
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "initSession") {
          $clientId = $details[1];
          $exptId = $details[2];
          $dayNo = $details[3];
          $sessionNo = $details[4];
          $judgeCount = $details[5];
          $tempItem = array (
            'exptId' => $exptId,
            'dayNo' => $dayNo,
            'sessionNo' => $sessionNo,
            'judgeCount' => $judgeCount * 2,
          );
          array_push($sdArray, $tempItem);       
        }
      }
    }    
    return $sdArray;
  }
  
  function getMinMaxLogin() {
    global $igrtSqli;
    global $sessionDetails;
    $mmArray = array();
    $uidQry = sprintf("SELECT MIN(uid) AS uid FROM igActiveStep1Users WHERE exptId='%s' AND day='%s' AND session='%s'",
        $sessionDetails['exptId'], $sessionDetails['dayNo'], $sessionDetails['sessionNo'] );
    $uidResult = $igrtSqli->query($uidQry);
    $uidRow = $uidResult->fetch_object();
    $minUid = $uidRow->uid;
    $maxUid = $minUid + $sessionDetails['judgeCount'] - 1;
    $mmArray['minUid'] = $minUid;
    $mmArray['maxUid'] = $maxUid;
    return $mmArray;
  }

  function getLoginJoins($datalines) {
    //<loginJoin><:>2<:>21454<:>261<:>1<:>1<:>0<:>4<:>
    global $minmaxLoginUids;
    $minmaxLoginUids = getMinMaxLogin();
    $ljArray = array();
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<loginJoin>") {
          $clientId = $details[1];
          $msg = urldecode($details[5]);
          $uid = $details[2];
          $exptId = $details[3];
          $jType = $details[4];
          $jNo = $details[5];
          $dayNo = $details[6];
          $sessionNo = $details[7];
          if (($uid >= $minmaxLoginUids['minUid']) && ($uid <= $minmaxLoginUids['maxUid'])) {
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
    }
    return $ljArray;
  }
  
  function populateJQ($datalines) {
    //<JQ><:>7<:>0<:>Explain in as much detail the concept of the offside rule in football.
    global $jQCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<JQ>") {
          $playerNo = $details[1];
          $turnPtr = $details[2];
          $msg = urldecode($details[3]);
          //$JudgeArray[$playerNo][$turnPtr]['jcid'] = $clientId; 
          //$JudgeArray[$playerNo][$turnPtr]['npcid'] = -1; 
          //$JudgeArray[$playerNo][$turnPtr]['pcid'] = -1; 
          $JudgeArray[$playerNo][$turnPtr]['q'] = $msg; 
          $JudgeArray[$playerNo][$turnPtr]['npr'] = '';
          $JudgeArray[$playerNo][$turnPtr]['pr'] = '';
          $JudgeArray[$playerNo][$turnPtr]['choice'] = -1;
          $JudgeArray[$playerNo][$turnPtr]['confidence'] = '';
          $JudgeArray[$playerNo][$turnPtr]['reason'] = '';
          $JudgeArray[$playerNo][$turnPtr]['isFinal'] = -1;
        }
      }
    }
  }
  
  function populateNPA($datalines) {
    //<NPA><:>7<:>5<:>0<:>VO5 matt clay, because its cheap and easy and voted the best brand. If I'm going out I'll use hairspray.
    global $NPCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<NPA>") {
          $playerNo = $details[1];
          $npNo = $details[2];
          $turnPtr = $details[3];
          $msg = urldecode($details[4]);
          $JudgeArray[$npNo][$turnPtr]['npr'] = $msg;
          //$JudgeArray[$npNo][$turnPtr]['npcid'] = $clientId; 
        }
      }
    }
  }

  function populatePA($datalines) {
    //<PA><:>4<:>3<:>0<:>Whatever is cheapest, if it comes in a 2 in 1 thats good, if not then I tend to just use shower gel...I don't know what a hair type is.
    global $PCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<PA>") {
          $playerNo = $details[1];
          $pNo = $details[2];
          $turnPtr = $details[3];
          $msg = urldecode($details[4]);
          $JudgeArray[$pNo][$turnPtr]['pr'] = $msg;
          //$JudgeArray[$pNo][$turnPtr]['pcid'] = $clientId; 
        }
      }
    }
  }

  function populateJR($datalines) {
    //<JR><:>1<:>1<:>1<:>Cos that boy got knowledge <:>interval4
    global $JRCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<JR>") {
          if (!isset($details[5])) { echo $dl["data"]; }
          $playerNo = $details[1];
          $turnPtr = $details[2] - 1;   // as qNo has been incremented in step1 controller         
          $choice = $details[3];
          $reason = urldecode($details[4]);
          $confidence = $details[5];
          $JudgeArray[$playerNo][$turnPtr]['choice'] = $choice;
          $JudgeArray[$playerNo][$turnPtr]['confidence'] = $confidence;
          $JudgeArray[$playerNo][$turnPtr]['reason'] = $reason;
        }
      }
    }
  }
  
  function populateJfinalR($datalines) {
    //<JfinalR><:>6<:>5<:>1<:>Apart from the first response which I thought was convincing, the other responses seam a tad over dramatic and seamed  more fake and stereotypical in general. The magazine response used a wedding example, I would need a lot more time to pick an outfit for a wedding. The ideal date question seamed the most faked as it used words like romantic and heartfelt, which seams OTT for the first date.  <:>finalInterval4
    global $JRCnt;
    global $JudgeArray;
    foreach ($datalines as $dl) {
      if ($dl["count"] > 0) {
        $source = $dl["data"];  
        $details = explode('<:>', $source);
        $msgType = $details[0];
        if ($msgType == "<JfinalR>") {
          if (!isset($details[5])) { echo $dl["data"]; }
          $playerNo = $details[1];
          $turnPtr = $details[2];
          $choice = $details[3];
          $reason = urldecode($details[4]);
          $confidence = $details[5];
          $JudgeArray[$playerNo][$turnPtr]['q'] = 'FINAL'; 
          $JudgeArray[$playerNo][$turnPtr]['npr'] = 'FINAL';
          $JudgeArray[$playerNo][$turnPtr]['pr'] = 'FINAL';
          $JudgeArray[$playerNo][$turnPtr]['choice'] = $choice;
          $JudgeArray[$playerNo][$turnPtr]['confidence'] = $confidence;
          $JudgeArray[$playerNo][$turnPtr]['reason'] = $reason;
          $JudgeArray[$playerNo][$turnPtr]['isFinal'] = 1;
        }
      }
    }
  }

   function getNoTypeFromPlayerNo($playerNo) {
    $jType = ($playerNo % 2 == 0) ? 0 : 1;
    if ($jType == 0) {
      $jNo = round( ($playerNo  / 2) - 1, PHP_ROUND_HALF_DOWN);
    }
    else {
      $jNo = round( ($playerNo - 1) / 2, PHP_ROUND_HALF_DOWN);
    }
    return array('jType' => $jType, 'jNo' => $jNo);
  }

  function getUIDFromPlayerNo($pn) {
    global $minmaxLoginUids;
    $offset = $pn - 1;
    return $minmaxLoginUids['minUid'] + $offset;
  } 
  
  function putStep1Data() {
    global $igrtSqli;
    global $JudgeArray;
    global $sessionDetails;
    global $playerCount;
    $exptId = $sessionDetails['exptId'];
    $dayNo = $sessionDetails['dayNo'];
    $sessionNo = $sessionDetails['sessionNo'];
    for ($playerNo=1; $playerNo<=$playerCount; $playerNo++) {
      $jDetails = getNoTypeFromPlayerNo($playerNo);
      $jNo = $jDetails['jNo'];
      $jType = $jDetails['jType'];
      $uid = getUIDFromPlayerNo($playerNo);
      $npLeft = ($jType == 0) ? 1 : 0;
      $turnCnt = count($JudgeArray[$playerNo]);
      echo $playerNo.' : '.$turnCnt.'<br />';
      if ($turnCnt > 0) {
        for ($j=0; $j<$turnCnt; $j++) {
          if ( (!isset($JudgeArray[$playerNo][$j]['q'])) ||
              (!isset($JudgeArray[$playerNo][$j]['npr'])) ||
              (!isset($JudgeArray[$playerNo][$j]['q'])) ) {
            echo 'error on turn '.$j.'<br />';
          }
          $sql = sprintf("INSERT INTO dataSTEP1 (uid, exptId, jType, JNo, sessionNo, dayNo, npLeft, qNo, q, npr, pr, choice, rating, reason) "
              . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
              $uid, $exptId, $jType, $jNo, $sessionNo, $dayNo, $npLeft, $j, 
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['q']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['npr']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['pr']),
              $JudgeArray[$playerNo][$j]['choice'],
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['confidence']),
              $igrtSqli->real_escape_string($JudgeArray[$playerNo][$j]['reason'])
              );
          echo $sql."<br />";
          $igrtSqli->query($sql);
        }
      }
    }
    
  }
  
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
$playerCount = 0;     
$exptId = 0;          
$dayNo = 0;           
$sessionNo = 0;       
$fn = "cardiffgroup1.txt";
$lines = file($fn, FILE_IGNORE_NEW_LINES);
$lineCnt = count($lines);
$datalines = array();
for ($i=0; $i<$lineCnt; $i++) {
  if (isset($temp)) { unset($temp); }
  $temp = array("count" => strlen($lines[$i]), "data"=>$lines[$i]);
  array_push($datalines, $temp);
}
$sessionDetailsList = getSessionDetails($datalines);
$sessionDetails = $sessionDetailsList[count($sessionDetailsList) - 1];  // assume last always
$debug = print_r($sessionDetails, true);
echo $debug.'<br />';
$loginJoins = getLoginJoins($datalines);
//  $status = print_r($loginJoins, true)."<br />";
//  echo $status;
//$mapClientIdToPlayer = getMappings($loginJoins);
//  $status = print_r($mapClientIdToPlayer, true)."<br />";
//  echo $status;
//$playerCount = count($mapClientIdToPlayer); // could check this as query against edSessions
$playerCount = $sessionDetails['judgeCount'];
$judgeCount = $playerCount / 2;
echo $playerCount.' '.$judgeCount."<br />";
//$playerAllocations = new roleAllocations($judgeCount, 1);
//$playerAllocations->generateFixed();
//$status = print_r($playerAllocations->mappings, true)."<br />";
//echo $status;
$turnArray = array();
for ($i = 1; $i <= $playerCount; $i++) {
  $jQCnt[$i] = 0;
  $NPCnt[$i] = 0;
  $PCnt[$i] = 0;
  $JRCnt[$i] = 0;
  array_push($JudgeArray, $turnArray);
} 
populateJQ($datalines);
//  echo "post populate JQ <br /> ";
//  $status = print_r($JudgeArray[2], true)."<br />";
//  echo $status;
populateNPA($datalines);
populatePA($datalines);
populateJR($datalines);
//echo "post populate rating <br /> ";
//$status = print_r($JudgeArray[2], true)."<br />";
//echo $status;
populateJfinalR($datalines);
putStep1Data();
//unset($mapClientIdToPlayer);
//unset($playerAllocations);
//unset($datalines);
//unset($lines);
//unset($loginJoins);
//unset($JQ);

echo 'done';
//$debug = print_r($JudgeArray);
//echo $debug;


